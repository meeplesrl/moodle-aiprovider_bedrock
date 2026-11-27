<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     aiprovider_bedrock
 * @copyright   2025 Davide Ferro <dferro@meeplesrl.it>, Angelo Calò <acalo@meeplesrl.it>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_ai\admin\admin_settingspage_provider;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Provider specific settings heading.
    $settings = new admin_settingspage_provider(
        'aiprovider_bedrock',
        new lang_string('pluginname', 'aiprovider_bedrock'),
        'moodle/site:config',
        true,
    );

    $settings->add(new admin_setting_heading(
        'aiprovider_bedrock/general',
        new lang_string('settings', 'core'),
        '',
    ));

    // Setting to enable use of the AWS SDK default credential provider chain.
    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_bedrock/usedefaultcredentialchain',
        new lang_string('usedefaultcredentialchain', 'aiprovider_bedrock'),
        new lang_string('usedefaultcredentialchain_desc', 'aiprovider_bedrock'),
        0,
    ));

    // Setting to store AWS access key ID.
    $settings->add(new admin_setting_configpasswordunmask(
        'aiprovider_bedrock/accesskeyid',
        new lang_string('accesskeyid', 'aiprovider_bedrock'),
        new lang_string('accesskeyid_desc', 'aiprovider_bedrock'),
        '',
    ));
    $settings->hide_if('aiprovider_bedrock/accesskeyid', 'aiprovider_bedrock/usedefaultcredentialchain', 'eq', 1);

    // Setting to store AWS secret access key.
    $settings->add(new admin_setting_configpasswordunmask(
        'aiprovider_bedrock/secretaccesskey',
        new lang_string('secretaccesskey', 'aiprovider_bedrock'),
        new lang_string('secretaccesskey_desc', 'aiprovider_bedrock'),
        '',
    ));
    $settings->hide_if('aiprovider_bedrock/secretaccesskey', 'aiprovider_bedrock/usedefaultcredentialchain', 'eq', 1);

    // Setting to store AWS region.
    $settings->add(new admin_setting_configtext(
        'aiprovider_bedrock/region',
        new lang_string('region', 'aiprovider_bedrock'),
        new lang_string('region_desc', 'aiprovider_bedrock'),
        'eu-west-1',
        PARAM_TEXT,
    ));

    // Setting to enable/disable global rate limiting.
    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_bedrock/enableglobalratelimit',
        new lang_string('enableglobalratelimit', 'aiprovider_bedrock'),
        new lang_string('enableglobalratelimit_desc', 'aiprovider_bedrock'),
        0,
    ));

    // Setting to set how many requests per hour are allowed for the global rate limit.
    // Should only be enabled when global rate limiting is enabled.
    $settings->add(new admin_setting_configtext(
        'aiprovider_bedrock/globalratelimit',
        new lang_string('globalratelimit', 'aiprovider_bedrock'),
        new lang_string('globalratelimit_desc', 'aiprovider_bedrock'),
        100,
        PARAM_INT,
    ));
    $settings->hide_if('aiprovider_bedrock/globalratelimit', 'aiprovider_bedrock/enableglobalratelimit', 'eq', 0);

    // Setting to enable/disable user rate limiting.
    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_bedrock/enableuserratelimit',
        new lang_string('enableuserratelimit', 'aiprovider_bedrock'),
        new lang_string('enableuserratelimit_desc', 'aiprovider_bedrock'),
        0,
    ));

    // Setting to set how many requests per hour are allowed for the user rate limit.
    // Should only be enabled when user rate limiting is enabled.
    $settings->add(new admin_setting_configtext(
        'aiprovider_bedrock/userratelimit',
        new lang_string('userratelimit', 'aiprovider_bedrock'),
        new lang_string('userratelimit_desc', 'aiprovider_bedrock'),
        10,
        PARAM_INT,
    ));
    $settings->hide_if('aiprovider_bedrock/userratelimit', 'aiprovider_bedrock/enableuserratelimit', 'eq', 0);
}
