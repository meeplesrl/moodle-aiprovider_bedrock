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

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;
use core\http_client;
use core_ai\aiactions\responses\response_base;
use core_ai\process_base;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract class for Bedrock processors.
 *
 * @package    aiprovider_bedrock
 * @copyright  2025 Davide Ferro <dferro@meeplesrl.it>, Angelo Calò <acalo@meeplesrl.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_processor extends process_base {
    /**
     * Get the Bedrock client.
     *
     * @return BedrockRuntimeClient
     */
    protected function get_bedrock_client(): BedrockRuntimeClient {
        $region = get_config('aiprovider_bedrock', 'region');
        $usedefaultcredentialchain = get_config('aiprovider_bedrock', 'usedefaultcredentialchain');
        $accesskey = get_config('aiprovider_bedrock', 'accesskeyid');
        $secretkey = get_config('aiprovider_bedrock', 'secretaccesskey');

        $config = [
            'version' => 'latest',
            'region' => $region,
        ];

        if (!$usedefaultcredentialchain) {
            $config['credentials'] = [
                'key' => $accesskey,
                'secret' => $secretkey,
            ];
        }

        return new BedrockRuntimeClient($config);
    }

    /**
     * Get the name of the model to use.
     *
     * @return string
     */
    abstract protected function get_model(): string;

    /**
     * Get the system instructions.
     *
     * @return string
     */
    protected function get_system_instruction(): string {
        return $this->action::get_system_instruction();
    }

    /**
     * Create the request parameters to send to the Bedrock API.
     *
     * @param string $userid The user id.
     * @return array The request parameters to send to the Bedrock API.
     */
    abstract protected function create_request_params(
        string $userid,
    ): array;

    /**
     * Handle a successful response from the external AI api.
     *
     * @param array $response The response from the Bedrock API.
     * @return array The processed response.
     */
    abstract protected function handle_api_success(array $response): array;

    /**
     * Query the AI API using the Bedrock client.
     *
     * @return array The response from the API.
     */
    protected function query_ai_api(): array {
        try {
            $userid = $this->provider->generate_userid($this->action->get_configuration('userid'));
            $client = $this->get_bedrock_client();
            $model = $this->get_model();
            $params = $this->create_request_params($userid);

            try {
                $result = $client->invokeModel([
                    'modelId' => $model,
                    'body' => json_encode($params),
                    'contentType' => 'application/json',
                    'accept' => 'application/json',
                ]);
            } catch (AwsException $e) {
                // Some models deprecate parameters (e.g. 'temperature') that older models accept.
                // If that is the cause, strip the offending parameter and retry once.
                $unsupportedparam = $this->get_unsupported_param_from_error($e, $params);
                if ($unsupportedparam === null) {
                    throw $e;
                }

                unset($params[$unsupportedparam]);
                $result = $client->invokeModel([
                    'modelId' => $model,
                    'body' => json_encode($params),
                    'contentType' => 'application/json',
                    'accept' => 'application/json',
                ]);
            }

            $response = json_decode($result->get('body')->getContents(), true);
            return $this->handle_api_success($response);
        } catch (\Throwable $e) {
            $this->log_exception($e, 'Model invocation');
            return [
                'success' => false,
                'errorcode' => $e->getCode() ?: 500,
                'errormessage' => get_string('error:unknownerror', 'aiprovider_bedrock'),
            ];
        }
    }

    /**
     * Write safe exception diagnostics to the PHP error log.
     *
     * Exception messages and response bodies are deliberately excluded because they may contain
     * prompts, generated content, or other sensitive data.
     *
     * @param \Throwable $exception The exception to log.
     * @param string $operation The operation that failed.
     */
    protected function log_exception(\Throwable $exception, string $operation): void {
        $diagnostics = [
            'exception' => get_class($exception),
            'code' => (string) $exception->getCode(),
        ];

        if ($exception instanceof AwsException) {
            $awsdiagnostics = [
                'aws_error_code' => $exception->getAwsErrorCode(),
                'aws_request_id' => $exception->getAwsRequestId(),
                'http_status' => $exception->getStatusCode(),
            ];

            foreach ($awsdiagnostics as $key => $value) {
                if ($value !== null && $value !== '') {
                    $diagnostics[$key] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value);
                }
            }
        }

        // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
        error_log('aiprovider_bedrock: ' . $operation . ' failed: ' . json_encode($diagnostics));
    }

    /**
     * Check whether an AWS exception is reporting a deprecated/unsupported request parameter.
     *
     * @param AwsException $e The exception thrown by the Bedrock client.
     * @param array $params The request parameters that were sent.
     * @return string|null The name of the offending parameter if found and present in $params, else null.
     */
    private function get_unsupported_param_from_error(AwsException $e, array $params): ?string {
        $message = $e->getAwsErrorMessage() ?? $e->getMessage();
        $matched = preg_match('/\x60?([a-zA-Z_]+)\x60? is deprecated for this model/', $message, $matches);
        if ($matched && array_key_exists($matches[1], $params)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Handle an error from the external AI api.
     *
     * @param array $errorinfo The error information.
     * @return array The error response.
     */
    protected function handle_api_error(array $errorinfo): array {
        $errorcode = $errorinfo['code'] ?? 500;
        $logcode = is_scalar($errorcode) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $errorcode) : 'unknown';
        // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
        error_log('aiprovider_bedrock: API returned an error response (code ' . $logcode . ').');

        return [
            'success' => false,
            'errorcode' => $errorcode,
            'errormessage' => get_string('error:unknownerror', 'aiprovider_bedrock'),
        ];
    }
}
