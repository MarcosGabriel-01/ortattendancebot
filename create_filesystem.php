<?php
require(__DIR__ . '/../../config.php');
require_sesskey();

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
define('ATTBOT_BASEFOLDER', $CFG->dataroot . '/attendancebot');

// -----------------------------------------------------------------------------
// Parameters
// -----------------------------------------------------------------------------
$cmid = required_param('id', PARAM_INT);

// -----------------------------------------------------------------------------
// Get course and module
// -----------------------------------------------------------------------------
$cm = get_coursemodule_from_id('attendancebot', $cmid, 0, false, MUST_EXIST);

// -----------------------------------------------------------------------------
// Create filesystem
// -----------------------------------------------------------------------------
$sites = ['YA','BE'];
$classes = ['FPR','PNT'];
$divisions = ['A','B','C','D'];
$today = date('Ymd'); // example: current date

$created = 0;

// Get POST parameters
$site = required_param('site', PARAM_ALPHA);
$class = required_param('class', PARAM_ALPHA);
$division = required_param('division', PARAM_ALPHA);
$year = required_param('year', PARAM_INT);
$month = required_param('month', PARAM_INT);
$day = required_param('day', PARAM_INT);

// Build date string YYYYMMDD
$date = sprintf('%04d%02d%02d', $year, $month, $day);

// Build folder path only for selected combination
$path = ATTBOT_BASEFOLDER . "/$site/$class/$division/$date";
$created = 0;

if (!file_exists($path)) {
    if (mkdir($path, 0777, true)) {
        $created++;
    }
}


echo $OUTPUT->header();
echo $OUTPUT->notification("Filesystem folders created or already exist: $created", 'notifysuccess');

$returnurl = new moodle_url('/mod/attendancebot/view.php', ['id' => $cmid]);
echo $OUTPUT->single_button($returnurl, get_string('back', 'attendancebot'));
echo $OUTPUT->footer();
