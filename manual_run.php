<?php
// /mod/attendancebot/manual_run.php

require_once(__DIR__ . '/../../config.php'); // Accede a config.php de Moodle
require_login();
require_capability('moodle/site:config', context_system::instance()); // Solo administradores

// Incluir clase de la tarea
require_once($CFG->dirroot . '/mod/attendancebot/classes/task/scheduler_task.php');

$task = new \mod_attendancebot\task\scheduler_task();
$task->execute();

// Mostrar resultado con diseño de Moodle
echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('taskSuccess', 'attendancebot'), 'notifysuccess');
echo $OUTPUT->single_button(
    new moodle_url('/my/courses.php'),
    get_string('gotocourses', 'attendancebot'),
    'get'
);
echo $OUTPUT->footer();
