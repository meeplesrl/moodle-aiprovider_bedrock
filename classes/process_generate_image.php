<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiprovider_bedrock;

/**
 * Class process image generation.
 *
 * @package    aiprovider_bedrock
 * @copyright  2025 Davide Ferro <dferro@meeplesrl.it>, Angelo Calò <acalo@meeplesrl.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_image extends abstract_processor {
    /**
     * Get the model ID from settings.
     *
     * @return string The model ID (amazon.nova-canvas-v1:0, stability.stable-diffusion-xl-v1)
     */
    protected function get_model(): string {
        return get_config('aiprovider_bedrock', 'action_generate_image_model');
    }

    /**
     * Determine the model provider from the model ID.
     *
     * @return string The provider name (stability, amazon, etc.)
     */
    private function get_model_provider(): string {
        $model = $this->get_model();
        if (strpos($model, 'stability') === 0) {
            return 'stability';
        } else if (strpos($model, 'amazon') === 0 || strpos($model, 'nova') !== false) {
            return 'amazon';  // Nova Canvas is an Amazon model.
        }
        return 'generic';
    }

    /**
     * Create request parameters for Stability AI models.
     *
     * @param string $userid The user id.
     * @return array The request parameters.
     */
    private function create_stability_request(string $userid): array {
        $prompttext = $this->action->get_configuration('prompttext');
        $width = (int) $this->action->get_configuration('width');
        $height = (int) $this->action->get_configuration('height');

        return [
            'text_prompts' => [
                [
                    'text' => $prompttext,
                    'weight' => 1.0,
                ],
            ],
            'cfg_scale' => 7.0,
            'steps' => 30,
            'width' => $width > 0 ? $width : 1024,
            'height' => $height > 0 ? $height : 1024,
        ];
    }

    /**
     * Create request parameters for Amazon Titan models.
     *
     * @param string $userid The user id.
     * @return array The request parameters.
     */
    private function create_amazon_request(string $userid): array {
        $prompttext = $this->action->get_configuration('prompttext');
        $width = (int) $this->action->get_configuration('width');
        $height = (int) $this->action->get_configuration('height');

        // Check if this is the Nova Canvas model which requires negativeText.
        $model = $this->get_model();
        $isnova = strpos($model, 'nova') !== false;

        return [
            'taskType' => 'TEXT_IMAGE',
            'textToImageParams' => [
                'text' => $prompttext,
                'negativeText' => $isnova ? 'blurry, bad quality, distorted' : '', // Default negative prompt for Nova models.
            ],
            'imageGenerationConfig' => [
                'numberOfImages' => 1,
                'height' => $height > 0 ? $height : 1024,
                'width' => $width > 0 ? $width : 1024,
                'cfgScale' => 8.0,
            ],
        ];
    }

    /**
     * Create request parameters.
     *
     * @param string $userid The user ID.
     * @return array The request parameters.
     */
    protected function create_request_params(string $userid): array {
        $provider = $this->get_model_provider();

        if ($provider === 'stability') {
            return $this->create_stability_request($userid);
        } else if ($provider === 'amazon') {
            return $this->create_amazon_request($userid);
        } else {
            // Generic fallback.
            $prompttext = $this->action->get_configuration('prompttext');
            $width = (int) $this->action->get_configuration('width');
            $height = (int) $this->action->get_configuration('height');

            return [
                'prompt' => $prompttext,
                'width' => $width > 0 ? $width : 1024,
                'height' => $height > 0 ? $height : 1024,
            ];
        }
    }

    /**
     * Extract image data from the Stability AI response.
     *
     * @param array $response The API response.
     * @return string The base64 encoded image data.
     */
    private function extract_stability_image_data(array $response): string {
        if (!empty($response['artifacts'][0]['base64'])) {
            return $response['artifacts'][0]['base64'];
        }

        throw new \RuntimeException(get_string('error:noimagedata', 'aiprovider_bedrock'));
    }

    /**
     * Extract image data from the Amazon Titan response.
     *
     * @param array $response The API response.
     * @return string The base64 encoded image data.
     */
    private function extract_amazon_image_data(array $response): string {
        // Standard Titan format.
        if (!empty($response['images'][0])) {
            return $response['images'][0];
        }

        // Nova Canvas format may be different.
        if (!empty($response['image'])) {
            return $response['image'];
        }

        // For Nova Canvas response.
        if (!empty($response['output']) && !empty($response['output']['images'][0])) {
            return $response['output']['images'][0];
        }

        throw new \RuntimeException(get_string('error:noimagedata', 'aiprovider_bedrock'));
    }

    /**
     * Extract image data from a generic model response.
     * Try different possible response formats.
     *
     * @param array $response The API response.
     * @return string The base64 encoded image data.
     */
    private function extract_generic_image_data(array $response): string {
        if (!empty($response['image'])) {
            return $response['image'];
        } else if (!empty($response['images'][0])) {
            return $response['images'][0];
        } else if (!empty($response['data'][0]['b64_json'])) {
            return $response['data'][0]['b64_json'];
        } else if (!empty($response['result'])) {
            return $response['result'];
        }

        throw new \RuntimeException(get_string('error:noimagedata', 'aiprovider_bedrock'));
    }

    /**
     * Convert base64 encoded image data to a temporary file
     *
     * @param string $base64data Base64 encoded image data
     * @return string Path to the temporary file
     */
    private function base64_to_temp_file(string $base64data): string {
        global $CFG;

        // Create the temporary file.
        $tempfolder = make_temp_directory('ai/bedrock/images');
        $tempfilepath = $tempfolder . '/' . uniqid('bedrock_', true) . '.png';

        // Determine if the base64 string includes the data:image prefix
        // and remove it if present.
        if (strpos($base64data, 'data:image') === 0) {
            $base64data = substr($base64data, strpos($base64data, ',') + 1);
        }

        // Decode the base64 data and write to the temp file.
        $imagedata = base64_decode($base64data);
        file_put_contents($tempfilepath, $imagedata);

        return $tempfilepath;
    }

    /**
     * Handle API success and process the image
     *
     * @param array $response API Response
     * @return array Image generation response
     */
    protected function handle_api_success(array $response): array {
        global $USER;

        try {
            $provider = $this->get_model_provider();

            // Extract the base64 image data from the response.
            if ($provider === 'stability') {
                $base64data = $this->extract_stability_image_data($response);
            } else if ($provider === 'amazon') {
                $base64data = $this->extract_amazon_image_data($response);
            } else {
                $base64data = $this->extract_generic_image_data($response);
            }

            // Convert base64 data to a temporary file.
            $tempfilepath = $this->base64_to_temp_file($base64data);

            // Process the image with ai_image (add watermark).
            $aiimage = new \core_ai\ai_image($tempfilepath);
            $aiimage->add_watermark();
            $aiimage->save();

            // Create a stored file in the user's draft area.
            $context = \context_user::instance($USER->id);
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => file_get_unused_draft_itemid(),
                'filepath'  => '/',
                'filename'  => 'bedrock_generated_image_' . time() . '.png',
                'mimetype'  => 'image/png',
            ];

            // Create the stored file from the temporary file.
            $storedfile = $fs->create_file_from_pathname($filerecord, $tempfilepath);

            // Clean up the temporary file.
            @unlink($tempfilepath);

            return [
                'success' => true,
                'draftfile' => $storedfile, // The key must be 'draftfile', not 'image'.
                'id' => $response['id'] ?? uniqid('bedrock_img_'),
                'prompttokens' => 0, // Not typically provided by image models.
            ];
        } catch (\Throwable $e) {
            $this->log_exception($e, 'Image processing');
            return [
                'success' => false,
                'errorcode' => 500,
                'errormessage' => get_string('error:failedprocessimage', 'aiprovider_bedrock'),
            ];
        }
    }
}
