<?php
/**
 * Plugin administration settings - Reuses mod_zoom credentials
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_ortattendancebot_settings', get_string('pluginname', 'mod_ortattendancebot'));

    if ($ADMIN->fulltree) {
        // Check if mod_zoom credentials exist
        $zoom_token = get_config('mod_zoom', 'apikey') ?: get_config('zoom', 'apikey');
        $zoom_email = get_config('mod_zoom', 'email') ?: get_config('zoom', 'email');
        
        $zoom_status = '';
        if ($zoom_token && $zoom_email) {
            $zoom_status = '<div style="padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;margin:10px 0;">
                <strong>✓ Zoom Credentials Found</strong><br>
                Using credentials from <strong>mod_zoom</strong><br>
                Email: ' . htmlspecialchars($zoom_email) . '<br>
                <small>No additional Zoom configuration needed below unless you want to override.</small>
            </div>';
        } else {
            $zoom_status = '<div style="padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;margin:10px 0;">
                <strong>⚠ No Zoom Plugin Detected</strong><br>
                Please configure Zoom credentials below, or install <a href="https://moodle.org/plugins/mod_zoom" target="_blank">mod_zoom</a>.
            </div>';
        }
        
        // API Configuration
        $settings->add(new admin_setting_heading(
            'ortattendancebot_api_heading',
            get_string('api_settings', 'mod_ortattendancebot'),
            $zoom_status . get_string('api_settings_desc', 'mod_ortattendancebot')
        ));

        // Only show these if mod_zoom is NOT configured
        if (!$zoom_token || !$zoom_email) {
            $settings->add(new admin_setting_configtext(
                'mod_ortattendancebot/zoom_oauth_token',
                get_string('zoom_oauth_token', 'mod_ortattendancebot'),
                get_string('zoom_oauth_token_desc', 'mod_ortattendancebot'),
                '',
                PARAM_TEXT
            ));

            $settings->add(new admin_setting_configtext(
                'mod_ortattendancebot/zoom_host_email',
                get_string('zoom_host_email', 'mod_ortattendancebot'),
                get_string('zoom_host_email_desc', 'mod_ortattendancebot'),
                '',
                PARAM_EMAIL
            ));
        } else {
            // Show read-only info
            $settings->add(new admin_setting_description(
                'ortattendancebot_zoom_info',
                'Zoom Configuration',
                'Currently using credentials from <strong>mod_zoom</strong>. To use different credentials, configure them in mod_zoom settings or disable mod_zoom and enter credentials below.'
            ));
        }

        // Mock API for testing
        $settings->add(new admin_setting_heading(
            'ortattendancebot_testing_heading',
            'Testing Configuration',
            'For development and testing only'
        ));
        
        $settings->add(new admin_setting_configcheckbox(
            'mod_ortattendancebot/use_mock_api',
            get_string('use_mock_api', 'mod_ortattendancebot'),
            get_string('use_mock_api_desc', 'mod_ortattendancebot'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'mod_ortattendancebot/mock_api_url',
            get_string('mock_api_url', 'mod_ortattendancebot'),
            get_string('mock_api_url_desc', 'mod_ortattendancebot'),
            'http://localhost:5000',
            PARAM_URL
        ));
    }
}
