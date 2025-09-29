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
echo $OUTPUT->notification('✅ La tarea programada se ejecutó manualmente con éxito.', 'notifysuccess');
// llevar a view
// echo $OUTPUT->continue_button($PAGE->set_url('/mod/attendancebot/view.php'));
echo $OUTPUT->footer();
