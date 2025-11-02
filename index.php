<?php
/**
 * List of all attendancebot instances in a course
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/ortattendancebot/index.php', ['id' => $id]);
$PAGE->set_title($course->shortname.': '.get_string('modulenameplural', 'mod_ortattendancebot'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_ortattendancebot'));

$attendancebots = get_all_instances_in_course('ortattendancebot', $course);

if (empty($attendancebots)) {
    notice(get_string('no_instances', 'mod_ortattendancebot'), new moodle_url('/course/view.php', ['id' => $course->id]));
    exit;
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('status', 'mod_ortattendancebot'),
];

foreach ($attendancebots as $bot) {
    $status = $bot->enabled ? get_string('enabled', 'mod_ortattendancebot') : get_string('disabled', 'mod_ortattendancebot');
    $url = new moodle_url('/mod/ortattendancebot/view.php', ['id' => $bot->coursemodule]);
    $table->data[] = [
        html_writer::link($url, $bot->name),
        $status,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
