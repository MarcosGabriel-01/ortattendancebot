<?php
// File: create_folders.php

require(__DIR__ . '/../../config.php');
require_login();
require_sesskey(); // Ensure user session is valid

define('ATTBOT_BASEFOLDER', $CFG->dataroot . '/attendancebot');

// Required params
$site     = required_param('site', PARAM_ALPHA);       // e.g. 'YA'
$class    = required_param('class', PARAM_ALPHA);      // e.g. 'FPR'
$division = required_param('division', PARAM_ALPHA);   // e.g. 'A'
$year     = required_param('year', PARAM_INT);         // e.g. 2025
$month    = required_param('month', PARAM_INT);        // e.g. 10
$day      = required_param('day', PARAM_INT);          // e.g. 11

// Validate and build the path
$date = sprintf('%04d%02d%02d', $year, $month, $day);  // YYYYMMDD
$path = ATTBOT_BASEFOLDER . "/$site/$class/$division/$date";

// Try creating the directory
$created = 0;
if (!file_exists($path)) {
    if (mkdir($path, 0777, true)) {
        $created = 1;
    } else {
        print_error("No se pudo crear el directorio: $path");
    }
}

// Output results
echo $OUTPUT->header();
if ($created) {
    echo $OUTPUT->notification("Directorio creado: $path", 'notifysuccess');
} else {
    echo $OUTPUT->notification("El directorio ya existía: $path", 'notifymessage');
}

$returnurl = new moodle_url('/mod/attendancebot/view.php', ['id' => required_param('id', PARAM_INT)]);
echo $OUTPUT->single_button($returnurl, get_string('back', 'attendancebot'));
echo $OUTPUT->footer();
