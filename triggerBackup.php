<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$PAGE->set_url('/mod/attendancebot/triggerBackup.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title('Run Recording Backup');
$PAGE->set_heading('Run Recording Backup');

require_once(__DIR__ . '/recordingBackup.php');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Manual Recording Backup');
try {
    ob_start();
    $courseid = required_param('courseid', PARAM_INT);
    recording_backup_run($courseid);
    $output = ob_get_clean();
    echo html_writer::tag('pre', s($output), ['style'=>'background:#111;color:#0f0;padding:10px;']);
} catch (Exception $e) {
    echo html_writer::tag('pre', '❌ Error: '.$e->getMessage(), ['style'=>'background:#300;color:#f88;padding:10px;']);
}
echo $OUTPUT->footer();
