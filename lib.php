<?php
/**
 * Library of interface functions and constants
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function ortattendancebot_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

function ortattendancebot_add_instance($data, $mform = null) {
    global $DB;
    
    $data->timecreated = time();
    $data->timemodified = time();
    
    
    if (!isset($data->backup_recordings)) {
        $data->backup_recordings = 0;
    }
    if (!isset($data->delete_source)) {
        $data->delete_source = 0;
    }
    if (!isset($data->recordings_path)) {
        global $CFG;
        $data->recordings_path = $CFG->dataroot . '/ortattendancebot_recordings';
    }
    
    $id = $DB->insert_record('ortattendancebot', $data);
    
    
    ortattendancebot_queue_retroactive_task($id, $data->course);
    
    return $id;
}

function ortattendancebot_update_instance($data, $mform = null) {
    global $DB;
    
    $data->timemodified = time();
    $data->id = $data->instance;
    
    $result = $DB->update_record('ortattendancebot', $data);
    
    
    ortattendancebot_queue_retroactive_task($data->id, $data->course);
    
    return $result;
}

function ortattendancebot_queue_retroactive_task($attendancebotid, $courseid) {
    global $DB;
    
    
    $attendancebot = $DB->get_record('ortattendancebot', ['id' => $attendancebotid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
    
    $task = new \mod_ortattendancebot\task\fetch_retroactive_task();
    
    
    $taskdata = new \stdClass();
    $taskdata->attendancebotid = $attendancebot->id;
    $taskdata->courseid = $course->id;
    
    $task->set_custom_data($taskdata);
    
    
    \core\task\manager::queue_adhoc_task($task);
}

function ortattendancebot_delete_instance($id) {
    global $DB;
    
    if (!$ortattendancebot = $DB->get_record('ortattendancebot', ['id' => $id])) {
        return false;
    }
    
    
    $DB->delete_records('ortattendancebot_queue', ['attendancebotid' => $id]);
    $DB->delete_records('ortattendancebot_backup_queue', ['attendancebotid' => $id]);
    $DB->delete_records('ortattendancebot_cleanup_queue', ['attendancebotid' => $id]);
    
    
    $DB->delete_records('ortattendancebot', ['id' => $id]);
    
    return true;
}

function ortattendancebot_get_module_instance($modulename, $courseid) {
    global $DB;
    
    $module = $DB->get_record('modules', ['name' => $modulename], 'id', MUST_EXIST);
    
    $sql = "SELECT cm.instance 
            FROM {course_modules} cm
            WHERE cm.course = :courseid 
            AND cm.module = :moduleid 
            AND cm.deletioninprogress = 0
            ORDER BY cm.id DESC
            LIMIT 1";
    
    $result = $DB->get_record_sql($sql, ['courseid' => $courseid, 'moduleid' => $module->id]);
    
    return $result ? $result->instance : null;
}

function ortattendancebot_time_to_seconds($hours, $minutes) {
    return ($hours * 3600) + ($minutes * 60);
}