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

if (optional_param('fix', null, PARAM_BOOL)) {
    $startdate = optional_param('startdate', null, PARAM_RAW);
    $enddate   = optional_param('enddate', null, PARAM_RAW);

    $fixer = new \AttendancePersistance($course->id);
    $fixer->validate_and_fix_sessions($startdate, $enddate);
}

echo html_writer::tag('div',$OUTPUT->heading(get_string('fix_historical_sessions', 'mod_attendancebot')),['style' => 'text-align: center;']);

// Iniciar formulario
$form = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url,
    'style'  => 'max-width: 500px; margin: 20px auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border: 1px solid #ccc;'
]);
// Campo oculto con ID
$form .= html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'id',
    'value' => $cm->id
]);
// Fecha de inicio
$form .= html_writer::start_tag('div', ['style' => 'margin-bottom: 15px;']);
$form .= html_writer::tag('label',
    get_string('start_date', 'mod_attendancebot'),
    ['for' => 'startdate', 'style' => 'display: block; margin-bottom: 5px; font-weight: bold;']
);
$form .= html_writer::empty_tag('input', [
    'type'     => 'date',
    'id'       => 'startdate',
    'name'     => 'startdate',
    'required' => 'required',
    'style'    => 'width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;'
]);
$form .= html_writer::end_tag('div');
// Fecha de fin
$form .= html_writer::start_tag('div', ['style' => 'margin-bottom: 15px;']);
$form .= html_writer::tag('label',
    get_string('end_date', 'mod_attendancebot'),
    ['for' => 'enddate', 'style' => 'display: block; margin-bottom: 5px; font-weight: bold;']
);
$form .= html_writer::empty_tag('input', [
    'type'     => 'date',
    'id'       => 'enddate',
    'name'     => 'enddate',
    'required' => 'required',
    'style'    => 'width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;'
]);
$form .= html_writer::end_tag('div');
// Botón de envío
$form .= html_writer::start_tag('div', ['style' => 'text-align: right;']);
$form .= html_writer::empty_tag('input', [
    'type'  => 'submit',
    'name'  => 'fix',
    'value' => get_string('fix_sessions', 'mod_attendancebot'),
    'style' => 'background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'
]);
$form .= html_writer::end_tag('div');
$form .= html_writer::end_tag('form');
echo $form;




$attendance_id = obtener_module_id("attendance");
$cantidad_attendance = obtener_cantidad_instancias_plugin($cm->course, $attendance_id);

if ($cantidad_attendance == 0) {
    \core\notification::error(get_string('errornotifacationattadance', 'mod_attendancebot'));
}

echo $OUTPUT->footer();
?>
