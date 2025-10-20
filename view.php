<?php
require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Dependencies
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/AttendancePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
require_once($CFG->dirroot . '/mod/attendance/externallib.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/task/scheduler_task.php');

// Parameters
$id = optional_param('id', 0, PARAM_INT);
$t  = optional_param('t', 0, PARAM_INT);

global $DB;

if ($id) {
    $cm = get_coursemodule_from_id('attendancebot', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('attendancebot', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('attendancebot', ['id' => $t], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('attendancebot', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

// Security
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

// Event
$event = \mod_attendancebot\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('attendancebot', $moduleinstance);
$event->trigger();

// Page setup
$PAGE->set_url('/mod/attendancebot/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Header
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('text_title', 'attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_1', 'attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_2', 'attendancebot'));
echo $OUTPUT->box(get_string('text_instrucciones', 'attendancebot'));
echo $OUTPUT->box(get_string('text_mensaje_warning', 'attendancebot'));
// -----------------------------------------------------------------------------
// 1️⃣ Create Folders Form (submit to create_folders.php)
// -----------------------------------------------------------------------------
$createfoldersurl = new moodle_url('/mod/attendancebot/create_folders.php'); // Changed here
$sites = ['YA' => 'YA', 'BE' => 'BE'];
$classes = ['FPR' => 'FPR', 'PNT' => 'PNT'];
$divisions = ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'];

echo html_writer::start_tag('form', [
    'action' => $createfoldersurl,
    'method' => 'post',
    'style' => 'margin-top: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 8px;'
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Selects
echo html_writer::tag('label', 'Site:', ['for' => 'site']);
echo html_writer::select($sites, 'site', '', ['' => 'Seleccionar sitio'], ['id' => 'site', 'required' => 'required']);
echo html_writer::empty_tag('br');

echo html_writer::tag('label', 'Class:', ['for' => 'class']);
echo html_writer::select($classes, 'class', '', ['' => 'Seleccionar clase'], ['id' => 'class', 'required' => 'required']);
echo html_writer::empty_tag('br');

echo html_writer::tag('label', 'Division:', ['for' => 'division']);
echo html_writer::select($divisions, 'division', '', ['' => 'Seleccionar división'], ['id' => 'division', 'required' => 'required']);
echo html_writer::empty_tag('br');

// Date inputs
echo html_writer::tag('label', 'Year:', ['for' => 'year']);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'year', 'id' => 'year', 'min' => 2000, 'max' => 2099, 'required' => 'required']);
echo html_writer::empty_tag('br');

echo html_writer::tag('label', 'Month:', ['for' => 'month']);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'month', 'id' => 'month', 'min' => 1, 'max' => 12, 'required' => 'required']);
echo html_writer::empty_tag('br');

echo html_writer::tag('label', 'Day:', ['for' => 'day']);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'day', 'id' => 'day', 'min' => 1, 'max' => 31, 'required' => 'required']);
echo html_writer::empty_tag('br');

// Hidden date field via JS
$js = <<<JS
document.querySelector('form[action*="create_folders.php"]').addEventListener('submit', function(e) {
    function pad(num, size) {
        let s = num.toString();
        while (s.length < size) s = "0" + s;
        return s;
    }
    const year = pad(document.getElementById('year').value, 4);
    const month = pad(document.getElementById('month').value, 2);
    const day = pad(document.getElementById('day').value, 2);
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'date';
    hidden.value = year + month + day;
    this.appendChild(hidden);
});
JS;
$PAGE->requires->js_amd_inline($js);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('create_folder_btn', 'attendancebot'),
    'style' => 'margin-top: 10px; padding: 6px 12px; cursor: pointer;'
]);

echo html_writer::end_tag('form');

// -----------------------------------------------------------------------------
// 2️⃣ Section & Folder Mirroring Button (unchanged)
// -----------------------------------------------------------------------------
$createsectionurl = new moodle_url('/mod/attendancebot/create_section.php', ['id' => $cm->id]);

echo html_writer::start_tag('form', [
    'action' => $createsectionurl,
    'method' => 'post',
    'style' => 'margin-top: 20px;'
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Create Section & Mirror Folders',
    'style' => 'padding: 6px 12px; cursor: pointer;'
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
