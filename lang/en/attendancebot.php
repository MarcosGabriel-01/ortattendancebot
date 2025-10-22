<?php
$string['camera'] = 'Camera Detection';
$string['form_cameradescription_settings'] = 'Check the box to enable camera detection';
$string['form_backuprecordings_desc'] = 'Enables automatic backup of Zoom recordings in Moodle and on the local machine.';
$string['attendancebot_settings'] = 'Automatic Attendance Settings';
$string['attendancebotfieldset'] = 'Other settings';
$string['attendancebotname'] = 'Name';
$string['attendancebotsettings'] = 'Settings';
$string['camera'] = 'camera detection enabled';
$string['camera_help'] = 'If the box is checked, the student must have turned on the camera during the meeting to be considered present; if unchecked, the camera will not be taken into account.';
$string['clases_finish'] = 'class end time';
$string['clases_finish_date'] = 'class end date';
$string['clases_finish_date_help'] = 'Date when the plugin will stop marking attendance';
$string['clases_finish_help'] = 'Hour and minutes when the class ends';
$string['clases_start'] = 'class start time';
$string['clases_start_date'] = 'class start date';
$string['clases_start_date_help'] = 'Date when the plugin will start marking attendance';
$string['clases_start_help'] = 'Hour and minutes when the class starts';
$string['enabled'] = 'plugin enabled';
$string['enabled_help'] = 'If the box is checked, the plugin is enabled; if unchecked, it is disabled';
$string['end_date'] = 'End date';
$string['error_backuprecordings'] = 'Error validating backup field.';
$string['error_camera'] = 'ERROR: Value cannot be null';
$string['error_clases_finish'] = 'ERROR: End time of the meeting cannot be null';
$string['error_clases_finish_date'] = 'ERROR: End date of classes cannot be null';
$string['error_clases_start'] = 'ERROR: Start time of the meeting cannot be null';
$string['error_clases_start_date'] = 'ERROR: Start date of classes cannot be null';
$string['error_enabled'] = 'ERROR: Value cannot be null';
$string['error_fechafinalizacion'] = 'ERROR: End date cannot be earlier than the start date';
$string['error_fechafinalizacion_igual'] = 'ERROR: End date cannot be equal to start date';
$string['error_fechainicio'] = 'ERROR: Start date cannot be later than end date';
$string['error_fechainicio_igual'] = 'ERROR: Start date cannot be equal to end date';
$string['error_horaminutos_comienzo_igual'] = 'ERROR: Start time cannot be equal to end time';
$string['error_horaminutos_final_igual'] = 'ERROR: End time cannot be equal to start time';
$string['error_horaminutos_mayor'] = 'ERROR: Time cannot be later than end time';
$string['error_horaminutos_menor'] = 'ERROR: Time cannot be earlier than start time';
$string['error_late_tolerance'] = 'ERROR: Late tolerance cannot be null';
$string['error_min_percentage'] = 'ERROR: Minimum presence percentage cannot be null';
$string['error_min_required_minutes'] = 'You must select the minimum attendance time';
$string['error_recolection_platform'] = 'ERROR: The platform where data is collected from cannot be null';
$string['error_required_type'] = 'You must select a method of minimum attendance: percentage or minutes';
$string['error_saving_platform'] = 'ERROR: The platform where data is saved cannot be null';
$string['errornotifacationattadance'] = 'WARNING, the attendance plugin is not installed, and without it, AttendanceBot will not work properly';
$string['fix_historical_sessions'] = 'Fix historical sessions';
$string['fix_sessions'] = 'Fix sessions';
$string['form_backuprecordings'] = 'Backup Recordings';
$string['form_backuprecordings_desc'] = 'Enable automatic backup of Zoom recordings in Moodle and on the local machine.';
$string['form_backuprecordings_help'] = 'If enabled, Zoom recordings will automatically be backed up in Moodle and on the local machine.';
$string['form_by_minutes'] = 'By time';
$string['form_by_percentage'] = 'By percentage';
$string['form_camera_settings'] = 'Camera Detection';
$string['form_cameradescription_settings'] = 'Check the box to enable camera detection';
$string['form_clases_finish'] = 'Meeting end time (hour and minute)';
$string['form_clases_finish_date'] = 'Class end date';
$string['form_clases_start'] = 'Meeting start time (hour and minute)';
$string['form_clases_start_date'] = 'Class start date';
$string['form_enable_settings'] = 'Plugin Enabled';
$string['form_enabledescription_settings'] = 'Check the box to enable it';
$string['form_late_tolerance'] = 'Select late arrival tolerance';
$string['form_late_tolerance_text'] = ' minutes (selecting 0 disables late tolerance)';
$string['form_min_percentage_text'] = ' %';
$string['form_min_required_minutes_settings'] = 'Minimum time (in minutes)';
$string['form_min_required_minutes_text'] = 'minutes of attendance';
$string['form_percentage_settings'] = 'Select the minimum percentage to be considered present';
$string['form_recolection_platform'] = 'Select the platform from which data will be collected';
$string['form_required_type'] = 'Minimum attendance time';
$string['form_saving_platform'] = 'Select the platform where data will be saved';
$string['invalid_date_range'] = 'Start date must be earlier than end date.';
$string['late_tolerance'] = 'late arrival tolerance';
$string['late_tolerance_help'] = 'Choose the number of minutes a student can arrive late and still be considered present';
$string['meeting_processer_task'] = 'Meeting Processing Task';
$string['min_percentage'] = 'minimum percentage to be considered present';
$string['min_percentage_help'] = 'Choose the minimum percentage a student must attend to be considered present: value from 0 to 100';
$string['min_required_minutes_help'] = 'Choose the minimum presence time for a student to be considered present';
$string['missing_dates'] = 'Start and end dates are required.';
$string['modulename'] = 'ORT Attendance Bot Module';
$string['modulenameplural'] = 'ORT Attendance Bots';
$string['no_sessions_to_fix'] = 'No sessions need fixing.';
$string['pluginalredyoncourse'] = 'The plugin "{$a}" is already installed in this course.';
$string['pluginmissingfromcourse'] = 'The plugin "{$a}" is not installed in this course.';
$string['pluginname'] = 'ORT Attendance Bot';
$string['recolection_platform'] = 'data collection platform';
$string['recolection_platform_help'] = 'Platform from which the attendance data will be collected. Default: Zoom';
$string['required_type'] = 'Minimum attendance type';
$string['required_type_help'] = 'Check Zoom attendance by percentage or minutes';
$string['saving_platform'] = 'data saving platform';
$string['saving_platform_help'] = 'Platform where attendance data will be saved. Default: Attendance';
$string['scheduler_task_name'] = 'Scheduler for the automatic class attendance processing tasks';
$string['sessions_fixed_success'] = 'Historical sessions have been successfully fixed.';
$string['start_date'] = 'Start date';
$string['text_descripcion_1'] = 'AttendanceBot is a plugin installed in a course and works automatically in the background via a cron job. The cron job runs at 1am and triggers a scheduler.';
$string['text_descripcion_2'] = 'The scheduler runs every 24 hours, and for each course where the plugin is installed, it triggers an ad-hoc task which calculates attendance for all groups in the course.';
$string['text_instrucciones'] = '
    <p>To use this plugin correctly, you must configure the necessary settings through a form. To do this, go to the "Settings" tab in the "Automatic Attendance Settings" section:</p>
    <ul>
        <li><strong>Plugin enabled:</strong> If this option is checked, the plugin will work for the course where it is installed.</li>
        <li><strong>Minimum presence percentage:</strong> This value, from 0 to 100%, defines the percentage of meeting duration required for a person to be considered present.</li>
        <li><strong>Late arrival tolerance:</strong> This value, from 0 to 60 minutes, defines how many minutes a person can be late and still be considered on time. If set to 0, late tolerance is disabled.</li>
        <li><strong>Data collection platform:</strong> Plugin/platform used to obtain attendance data. Currently Zoom; could be extended to Meet and Teams in the future.</li>
        <li><strong>Data saving platform:</strong> Plugin/platform used to save attendance data. Currently Attendance.</li>
        <li><strong>Class start date:</strong> The plugin will not take attendance before this date.</li>
        <li><strong>Class end date:</strong> The plugin will stop taking attendance after this date.</li>
        <li><strong>Meeting start time:</strong> Hour and minute when the meeting starts (used to create an attendance session).</li>
        <li><strong>Meeting end time:</strong> Hour and minute when the meeting ends (used to create an attendance session).</li>
    </ul>
';
$string['text_mensaje_warning'] = 'AttendanceBot depends on the Attendance plugin to save data, as it creates sessions to store attendance. If the Attendance plugin is uninstalled from the course, a warning message will appear because the plugin will not work properly.';
$string['text_title'] = 'AttendanceBot Usage Instructions';
$string['warning_enabled'] = 'WARNING: The automatic attendance plugin is disabled';
$string['warning_late_tolerance'] = 'WARNING: Late arrival tolerance is disabled';
$string['pluginadministration'] = 'plugin administration';
$string['gotocourses'] = 'Return to My Courses';
$string['taskSuccess'] = 'The scheduled task was successfully executed manually';
$string['runManualTask'] = 'Run task manually';
$string['createfolder_title'] = 'Create Attendance Folder';
$string['folder_created'] = '✅ Folder created or already exists: {$a}';
$string['folder_notcreated'] = '❌ Could not create folder.';
$string['folder_mirrored'] = 'Folder mirrored successfully inside the course.';
$string['folder_mirror_failed'] = 'Folder mirror failed: {$a}';
$string['back'] = 'Back';
$string['invalidsite'] = 'Invalid site parameter.';
$string['invalidclass'] = 'Invalid class parameter.';
$string['invaliddivision'] = 'Invalid division parameter.';
$string['invaliddate'] = 'Date must be in the format YYYYMMDD.';
$string['text_title'] = 'Attendance Folder Creation';
$string['text_descripcion_1'] = 'This tool allows you to create attendance folders.';
$string['text_descripcion_2'] = 'Each folder is mirrored inside the Moodle course automatically.';
$string['text_instrucciones'] = 'Select the site, class, division, and date to create the folder.';
$string['text_mensaje_warning'] = 'Make sure the date format is correct (YYYY-MM-DD).';
$string['create_folder_btn'] = 'Create Folder';
$string['folder_exists'] = 'folder alredy exists';
$string['filesystemempty'] = 'Filesystem is empty. Nothing to mirror.';
$string['foldersmirrored'] = '{$a} folders mirrored successfully.';
$string['nofolderscreated'] = 'No new folders were created.';
$string['back'] = 'Back';
$string['attendancebot:triggerbackup'] = 'Trigger attendance bot recording backup';
