<?php
/**
 * Plugin administration settings - Multi-provider support (Moodle-native styled version)
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_ortattendancebot_settings', get_string('pluginname', 'mod_ortattendancebot'));

    if ($ADMIN->fulltree) {

        // ==========================================
        // PROVIDER SELECTION
        // ==========================================
        $settings->add(new admin_setting_heading(
            'ortattendancebot_provider_heading',
            html_writer::tag('div',
                html_writer::tag('h3', get_string('provider_settings', 'mod_ortattendancebot'), ['class' => 'fitemtitle']) .
                html_writer::empty_tag('hr', ['class' => 'my-2']),
                ['class' => 'generalbox']
            ),
            get_string('provider_settings_desc', 'mod_ortattendancebot')
        ));

        $provider_options = [
            'zoom' => 'Zoom',
            'mock' => 'Mock API (Testing)',
        ];

        $settings->add(new admin_setting_configselect(
            'mod_ortattendancebot/video_provider',
            get_string('video_provider', 'mod_ortattendancebot'),
            get_string('video_provider_desc', 'mod_ortattendancebot'),
            'zoom',
            $provider_options
        ));

        // ==========================================
        // ZOOM CONFIGURATION
        // ==========================================
        $zoom_accountid = get_config('zoom', 'accountid');
        $zoom_clientid = get_config('zoom', 'clientid');
        $zoom_clientsecret = get_config('zoom', 'clientsecret');

        $zoom_status = html_writer::empty_tag('hr', ['class' => 'my-3']);

        if ($zoom_accountid && $zoom_clientid && $zoom_clientsecret) {
            $zoom_status .= html_writer::div(
                html_writer::tag('strong', '✓ ' . get_string('zoom_credentials_detected', 'mod_ortattendancebot', 'Zoom')) . '<br>' .
                get_string('zoom_using_mod_zoom', 'mod_ortattendancebot') . '<br>' .
                get_string('zoom_accountid_label', 'mod_ortattendancebot') . ': <code>' . htmlspecialchars($zoom_accountid) . '</code><br>' .
                html_writer::tag('small', get_string('zoom_configured_success', 'mod_ortattendancebot'), ['class' => 'text-success']),
                'alert alert-success p-3'
            );
        } else {
            $zoom_status .= html_writer::div(
                html_writer::tag('strong', '⚠ ' . get_string('zoom_not_detected', 'mod_ortattendancebot')) . '<br>' .
                get_string('zoom_configure_or_install', 'mod_ortattendancebot', 
                    '<a href="https://moodle.org/plugins/mod_zoom" target="_blank">mod_zoom</a>'
                ),
                'alert alert-warning p-3'
            );
        }

        $settings->add(new admin_setting_heading(
            'ortattendancebot_zoom_heading',
            html_writer::tag('div',
                html_writer::tag('h3', get_string('zoom_configuration', 'mod_ortattendancebot'), ['class' => 'fitemtitle']) .
                html_writer::empty_tag('hr', ['class' => 'my-2']),
                ['class' => 'generalbox']
            ),
            $zoom_status
        ));

        // Manual credentials fallback if mod_zoom not configured.
        $settings->add(new admin_setting_configtext(
            'mod_ortattendancebot/zoom_account_id',
            get_string('zoom_account_id', 'mod_ortattendancebot'),
            get_string('zoom_account_id_desc', 'mod_ortattendancebot'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_ortattendancebot/zoom_client_id',
            get_string('zoom_client_id', 'mod_ortattendancebot'),
            get_string('zoom_client_id_desc', 'mod_ortattendancebot'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'mod_ortattendancebot/zoom_client_secret',
            get_string('zoom_client_secret', 'mod_ortattendancebot'),
            get_string('zoom_client_secret_desc', 'mod_ortattendancebot'),
            ''
        ));


        // ==========================================
        // MOCK API CONFIGURATION
        // ==========================================
        $settings->add(new admin_setting_heading(
            'ortattendancebot_mock_heading',
            html_writer::tag('div',
                html_writer::tag('h3', get_string('mock_configuration', 'mod_ortattendancebot'), ['class' => 'fitemtitle']) .
                html_writer::empty_tag('hr', ['class' => 'my-2']),
                ['class' => 'generalbox']
            ),
            get_string('mock_configuration_desc', 'mod_ortattendancebot')
        ));

        $settings->add(new admin_setting_configtext(
            'mod_ortattendancebot/mock_api_url',
            get_string('mock_api_url', 'mod_ortattendancebot'),
            get_string('mock_api_url_desc', 'mod_ortattendancebot'),
            'http://localhost:5000',
            PARAM_URL
        ));

        // ==========================================
        // RECORDING BACKUP SETTINGS
        // ==========================================
        $settings->add(new admin_setting_heading(
            'ortattendancebot_backup_heading',
            html_writer::tag('div',
                html_writer::tag('h3', get_string('backup_settings', 'mod_ortattendancebot'), ['class' => 'fitemtitle']) .
                html_writer::empty_tag('hr', ['class' => 'my-2']),
                ['class' => 'generalbox']
            ),
            get_string('backup_settings_desc', 'mod_ortattendancebot')
        ));

        $settings->add(new admin_setting_configtext(
            'mod_ortattendancebot/recordings_path',
            get_string('recordings_path', 'mod_ortattendancebot'),
            get_string('recordings_path_desc', 'mod_ortattendancebot'),
            '/var/moodledata/ortattendancebot/recordings',
            PARAM_TEXT
        ));
    }
}

