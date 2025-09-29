<?php
// Este código se encuentra en el archivo view.php de tu plugin attendancebot.

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Cargar clases necesarias
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/AttendancePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
require_once($CFG->dirroot . '/mod/attendance/externallib.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/task/scheduler_task.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);
$t = optional_param('t', 0, PARAM_INT);

global $DB;

if ($id) {
    $cm = get_coursemodule_from_id('attendancebot', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('attendancebot', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('attendancebot', array('id' => $t), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('attendancebot', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);


$event = \mod_attendancebot\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('attendancebot', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/attendancebot/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

// Mostrar los mensajes de introducción
echo $OUTPUT->heading(get_string('text_title', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_1', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_2', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_instrucciones', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_mensaje_warning', 'mod_attendancebot'));

echo $OUTPUT->single_button(
    new moodle_url('/mod/attendancebot/manual_run.php'),
    get_string('runManualTask', 'attendancebot'),
    'post'
);

echo $OUTPUT->footer();
?>
