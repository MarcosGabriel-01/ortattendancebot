<?php
/**
 * English language strings
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ORT Attendance Bot';
$string['modulename'] = 'ORT Attendance Bot';
$string['modulenameplural'] = 'ORT Attendance Bots';
$string['modulename_help'] = 'Automatically sync Zoom meeting attendance to Moodle Attendance module and backup recordings';
$string['pluginadministration'] = 'ORT Attendance Bot administration';
$string['ortattendancebot:addinstance'] = 'Add a new ORT Attendance Bot';
$string['ortattendancebot:view'] = 'View ORT Attendance Bot';
$string['api_settings'] = 'API Configuration';
$string['api_settings_desc'] = 'Configure Zoom API access';
$string['zoom_api_base_url'] = 'Zoom API Base URL';
$string['zoom_api_base_url_desc'] = 'Base URL for Zoom API (default: https://api.zoom.us/v2)';
$string['zoom_oauth_token'] = 'Zoom OAuth Token';
$string['zoom_oauth_token_desc'] = 'Server-to-Server OAuth token for Zoom API';
$string['mock_api_url'] = 'Mock API URL';
$string['mock_api_url_desc'] = 'URL for mock API (testing only)';
$string['use_mock_api'] = 'Use Mock API';
$string['use_mock_api_desc'] = 'Enable to use mock API instead of real Zoom API';
$string['configuration'] = 'Configuration';
$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'Enable automatic attendance processing';
$string['disabled'] = 'Disabled';
$string['camera_required'] = 'Camera Required';
$string['camera_required_help'] = 'Require camera to be on for attendance';
$string['camera_threshold'] = 'Camera Threshold (%)';
$string['camera_threshold_help'] = 'Minimum percentage of time camera must be on';
$string['min_percentage'] = 'Minimum Attendance (%)';
$string['min_percentage_help'] = 'Minimum attendance percentage to be marked present';
$string['late_tolerance'] = 'Late Tolerance (minutes)';
$string['late_tolerance_help'] = 'Minutes after start time before marked as late';
$string['datetime_range'] = 'Date/Time Range';
$string['start_date'] = 'Start Date';
$string['end_date'] = 'End Date';
$string['start_time'] = 'Daily Start Time';
$string['end_time'] = 'Daily End Time';
$string['recordings_backup'] = 'Recording Backup';
$string['backup_recordings'] = 'Enable Recording Backup';
$string['backup_recordings_help'] = 'Automatically download and backup Zoom cloud recordings to Moodle';
$string['recordings_path'] = 'Local Recordings Path';
$string['recordings_path_help'] = 'Local filesystem path where recordings will be stored before upload to Moodle. Must be writable by web server.';
$string['delete_source'] = 'Delete from Zoom After Backup';
$string['delete_source_help'] = 'Automatically delete recordings from Zoom cloud after successful backup to Moodle';
$string['error_path_empty'] = 'Recordings path cannot be empty when backup is enabled';
$string['error_path_not_writable'] = 'Recordings path is not writable. Please check directory permissions.';
$string['scheduler_task'] = 'ORT Attendance Bot Scheduler';
$string['meeting_processor_task'] = 'Meeting and Recording Processor';
$string['status'] = 'Status';
$string['last_meeting'] = 'Last Meeting';
$string['processed'] = 'Processed';
$string['no_instances'] = 'No ORT Attendance Bot instances in this course';
$string['zoom_host_email'] = 'Zoom Host Email';
$string['zoom_host_email_desc'] = 'Email address of the Zoom user whose meetings you want to track. Required for real Zoom API. Example: teacher@university.edu';
