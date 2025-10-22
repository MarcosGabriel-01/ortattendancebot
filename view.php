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

$courseid = $COURSE->id;

$triggerurl = new moodle_url('/mod/attendancebot/trigger_backup.php', ['courseid' => $courseid]);

echo html_writer::start_div('attendancebot-trigger-backup');
echo html_writer::link(
    $triggerurl,
    'Run Recording Backup',
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
