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
 * Library of interface functions and constants.
 *
 * @package     mod_attendancebot
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require_once(__DIR__ . '/utilities.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function attendancebot_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_attendancebot into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_attendancebot_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function attendancebot_add_instance($moduleinstance, $mform = null) {

// m5desa: normalize checkboxes camera/backuprecordings
if (isset($moduleinstance->camera)) {
    $moduleinstance->camera = !empty($moduleinstance->camera) ? 1 : 0;
}
if (isset($moduleinstance->backuprecordings)) {
    $moduleinstance->backuprecordings = !empty($moduleinstance->backuprecordings) ? 1 : 0;
}

    global $DB;

    $course_id = $moduleinstance->course;
    $attendance_id = obtener_module_id("attendance");
    $attendancebot_id = obtener_module_id("attendancebot");
    
    $cantidad_attendance = obtener_cantidad_instancias_plugin($course_id,$attendance_id);
    $cantidad_attendacebot = obtener_cantidad_instancias_plugin($course_id,$attendancebot_id);
    
  	//Validamos que si no existe una instancia de Attendance -> Falle o si ya existe el AttendanceBot Instalado
    if ($cantidad_attendance == 0){
      throw new moodle_exception('pluginmissingfromcourse','mod_attendancebot', '', 'attendance',null);
    }elseif($cantidad_attendacebot > 1){
      throw new moodle_exception('pluginalredyoncourse','mod_attendancebot','','attendacebot',null);
    }
  
    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('attendancebot', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_attendancebot in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_attendancebot_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function attendancebot_update_instance($moduleinstance, $mform = null) {
    global $DB;
    
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('attendancebot', $moduleinstance);
}

/**
 * Removes an instance of the mod_attendancebot from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function attendancebot_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('attendancebot', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('attendancebot', array('id' => $id));

    return true;
}
