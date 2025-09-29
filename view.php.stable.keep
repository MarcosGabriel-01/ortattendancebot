<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_attendancebot.
 *
 * @package     mod_attendancebot
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

//Para importar el form de input
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/AttendancePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
//require_once($CFG->dirroot . '/mod/attendance/externallib.php');


// Course module id.
$id = optional_param('id', 0, PARAM_INT);
$t = optional_param('t', 0, PARAM_INT);

global $DB;

if ($id) {
    $cm = get_coursemodule_from_id('attendancebot', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('attendancebot', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('attendancebot',array('id' => $t), '*', MUST_EXIST);
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

echo $OUTPUT->heading(get_string('text_title', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_1', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_descripcion_2', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_instrucciones', 'mod_attendancebot'));
echo $OUTPUT->box(get_string('text_mensaje_warning', 'mod_attendancebot'));

$attendance_id = obtener_module_id("attendance");
$cantidad_attendance = obtener_cantidad_instancias_plugin($cm->course,$attendance_id);

if($cantidad_attendance == 0){
  \core\notification::error(get_string('errornotifacationattadance', 'mod_attendancebot'));
}

echo $OUTPUT->footer();
