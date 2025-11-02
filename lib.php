<?php
/**
 * Library of interface functions and constants
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Supported features
 */
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

/**
 * Saves a new instance of ortattendancebot
 */
function ortattendancebot_add_instance($data, $mform = null) {
    global $DB;
    
    $data->timecreated = time();
    $data->timemodified = time();
    
    // Set default values for new backup fields
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
    
    return $DB->insert_record('ortattendancebot', $data);
}

/**
 * Updates an instance of ortattendancebot
 */
function ortattendancebot_update_instance($data, $mform = null) {
    global $DB;
    
    $data->timemodified = time();
    $data->id = $data->instance;
    
    return $DB->update_record('ortattendancebot', $data);
}

/**
 * Deletes an instance of ortattendancebot
 */
function ortattendancebot_delete_instance($id) {
    global $DB;
    
    if (!$ortattendancebot = $DB->get_record('ortattendancebot', ['id' => $id])) {
        return false;
    }
    
    // Delete related queue entries
    $DB->delete_records('ortattendancebot_queue', ['attendancebotid' => $id]);
    $DB->delete_records('ortattendancebot_backup_queue', ['attendancebotid' => $id]);
    $DB->delete_records('ortattendancebot_cleanup_queue', ['attendancebotid' => $id]);
    
    // Delete the instance
    $DB->delete_records('ortattendancebot', ['id' => $id]);
    
    return true;
}

/**
 * Get module instance by module name and course
 */
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

/**
 * Convert hours and minutes to seconds
 */
function ortattendancebot_time_to_seconds($hours, $minutes) {
    return ($hours * 3600) + ($minutes * 60);
}
