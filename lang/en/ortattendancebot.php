<?php
/**
 * English language strings
 *
 * @package     mod_ortattendancebot
 * @copyright   2025
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ORT Attendance Bot';
$string['modulename'] = 'ORT Attendance Bot';
$string['modulenameplural'] = 'ORT Attendance Bots';
$string['modulename_help'] = 'Automatically syncs attendance from Zoom meetings with the Moodle Attendance module and backs up recordings.';
$string['pluginadministration'] = 'ORT Attendance Bot administration';

$string['ortattendancebot:addinstance'] = 'Add a new ORT Attendance Bot';
$string['ortattendancebot:view'] = 'View ORT Attendance Bot';

$string['configuration'] = 'General configuration';
$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'Enable or disable automatic attendance processing.';
$string['disabled'] = 'Disabled';
$string['status'] = 'Status';
$string['status_enabled'] = 'Enabled';
$string['status_disabled'] = 'Disabled';

$string['api_settings'] = 'API settings';
$string['api_settings_desc'] = 'Configure access to the APIs used to fetch meeting and participant information.';
$string['zoom_api_base_url'] = 'Zoom API base URL';
$string['zoom_api_base_url_desc'] = 'Base URL used for requests to the Zoom API (default: https://api.zoom.us/v2).';
$string['mock_api_url'] = 'Mock API URL';
$string['mock_api_url_desc'] = 'URL of a simulated API used for testing and development.';

$string['provider_settings'] = 'Video provider selection';
$string['provider_settings_desc'] = 'Choose the platform used for online meetings.';
$string['video_provider'] = 'Video provider';
$string['video_provider_desc'] = 'Select the platform used for video meetings (Zoom or Google Meet).';

$string['zoom_configuration'] = 'Zoom configuration';
$string['zoom_credentials_detected'] = 'Detected credentials from {$a}';
$string['zoom_using_mod_zoom'] = 'Using Server-to-Server OAuth credentials from the mod_zoom plugin.';
$string['zoom_accountid_label'] = 'Account ID';
$string['zoom_configured_success'] = 'mod_zoom is configured and will be used for API calls.';
$string['zoom_not_detected'] = 'No Zoom credentials detected.';
$string['zoom_configure_or_install'] = 'Configure your own Zoom OAuth credentials below or install the {$a} plugin.';

$string['zoom_account_id'] = 'Zoom account ID';
$string['zoom_account_id_desc'] = 'Account ID from your Server-to-Server OAuth app in the Zoom Marketplace.';
$string['zoom_client_id'] = 'Zoom client ID';
$string['zoom_client_id_desc'] = 'Client ID from your Server-to-Server OAuth app in Zoom.';
$string['zoom_client_secret'] = 'Zoom client secret';
$string['zoom_client_secret_desc'] = 'Client secret from your Server-to-Server OAuth app in Zoom.';

$string['google_oauth_token'] = 'Google OAuth token';
$string['google_oauth_token_desc'] = 'OAuth 2.0 token used to access Google APIs.';
$string['google_calendar_id'] = 'Google Calendar ID';
$string['google_calendar_id_desc'] = 'ID of the calendar from which meetings will be retrieved (default: primary).';

$string['mock_configuration'] = 'Mock API configuration';
$string['mock_configuration_desc'] = 'Used for development and testing only. Allows simulated API responses.';

$string['camera_required'] = 'Camera required';
$string['camera_required_help'] = 'If enabled, attendance will only be marked when the camera was on.';
$string['camera_threshold'] = 'Camera threshold (%)';
$string['camera_threshold_help'] = 'Minimum percentage of time the camera must be on to mark attendance.';
$string['min_percentage'] = 'Minimum attendance (%)';
$string['min_percentage_help'] = 'Minimum percentage of meeting duration required to be marked as present.';
$string['late_tolerance'] = 'Late tolerance (minutes)';
$string['late_tolerance_help'] = 'Number of minutes after the meeting start time before being marked as late.';

$string['datetime_range'] = 'Date/time range';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['start_time'] = 'Daily start time';
$string['end_time'] = 'Daily end time';

$string['backup_settings'] = 'Recordings backup';
$string['backup_settings_desc'] = 'Configure automatic backup of Zoom recordings.';
$string['backup_recordings'] = 'Enable recordings backup';
$string['backup_recordings_help'] = 'Automatically downloads and saves Zoom cloud recordings into Moodle.';
$string['recordings_backup'] = 'Recordings backup';
$string['recordings_path'] = 'Recordings path';
$string['recordings_path_desc'] = 'Filesystem path where recordings will be temporarily stored.';
$string['recordings_path_help'] = 'This must be a folder writable by the web server.';
$string['delete_source'] = 'Delete from Zoom after backup';
$string['delete_source_help'] = 'Automatically deletes recordings from Zoom once backed up in Moodle.';
$string['error_path_empty'] = 'Recordings path cannot be empty if backup is enabled.';
$string['error_path_not_writable'] = 'Cannot write to the recordings directory. Check folder permissions.';

$string['scheduler_task'] = 'ORT Attendance Bot scheduler';
$string['meeting_processor_task'] = 'Meeting and recordings processor';

$string['last_meeting'] = 'Last meeting';
$string['processed'] = 'Processed';
$string['no_instances'] = 'No ORT Attendance Bot instances found in this course.';
$string['view_configuration'] = 'Configuration';
$string['view_date_range'] = 'Date range';
$string['view_time_window'] = 'Time window';
$string['view_status'] = 'Status';
$string['view_actions'] = 'Actions';
$string['view_attendance_queue'] = 'Attendance queue';
$string['view_testing_controls'] = 'Testing controls';
$string['view_testing_warning'] = 'These actions delete data! Use for testing only.';
$string['view_no_queue'] = 'No meetings in queue.';
$string['view_found_meetings'] = '{$a} meetings found.';

$string['action_fetch_all'] = 'Fetch all meetings';
$string['action_queue_yesterday'] = 'Queue yesterday\'s meetings';
$string['action_process_attendance'] = 'Process attendance';
$string['action_process_backup'] = 'Process backup';
$string['action_clear_queue'] = 'Clear queue';
$string['action_clear_attendance'] = 'Clear attendance';
$string['action_back'] = 'Back';

$string['confirm_clear_queue'] = 'Are you sure you want to delete all items in the queue?';
$string['confirm_clear_attendance'] = 'Are you sure you want to delete all AttendanceBot sessions?';

$string['result_total_meetings'] = 'Total meetings found';
$string['result_queued'] = 'Queued';
$string['result_already_queued'] = 'Already queued';
$string['result_filtered_out'] = 'Filtered out';
$string['result_deleted'] = 'Deleted';
$string['result_sessions_deleted'] = 'Sessions deleted';
$string['result_logs_deleted'] = 'Logs deleted';

$string['table_meeting_id'] = 'Meeting ID';
$string['table_topic'] = 'Topic';
$string['table_start_time'] = 'Start time';
$string['table_date'] = 'Date';
$string['table_status'] = 'Status';

$string['status_processed'] = 'Processed';
$string['status_pending'] = 'Pending';
$string['error_general'] = 'General error';
