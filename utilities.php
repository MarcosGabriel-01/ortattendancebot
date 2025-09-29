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
 *
 * @package     mod_attendancebot
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Devuelve la cantidad de instancias de un modulo de un plugin en un curso
 *
 * @param string $coruse_id El id del curso donde se esta instanciando
 * @param string $module_id El id del modulo que se quiere buscar
 * @return int Cantidad de instancias
 */
function obtener_cantidad_instancias_plugin($course_id,$module_id){
  global $DB;
  
  $SQL = "
        SELECT
        COUNT(mcm.id) as cantidad FROM
        mdl_course_modules mcm
        WHERE mcm.course = :course_id
        AND mcm.module = :module_id
        AND mcm.deletioninprogress = :estado_deletion;";
        
  $params = ['course_id' => $course_id, 'module_id' => $module_id,'estado_deletion' => "0"];
  
  $array_db = array_values($DB->get_records_sql($SQL, $params));
  $cantidad_instancias = $array_db[0]->cantidad;
  
  return $cantidad_instancias;
}

/**
 * Devuelve el id de un modulo dado su nombre
 *
 * @param string $module_name El nombre del modulo
 * @return int module_id
 */
function obtener_module_id($module_name){
  global $DB;
  
  $SQL = "
        SELECT
        modules.id FROM 
        mdl_modules modules
        WHERE modules.name = :module_name";
        
  $params = ['module_name' => $module_name];
  
  $array_db = array_values($DB->get_records_sql($SQL, $params));
  $module_id = $array_db[0]->id;
  
  return $module_id;
}

/**
 * Crea un timestamp del tiempo (horas y minutos desde el comienzo del dia)
 *
 * @param int $hours hours of the mform
 * @param int $minutes minutes of the mform
 * @return int timestamp del tiempo en horas minutos
 */
function hour_minutes_to_timestamp($hours,$minutes) {
  return ($hours*3600)+($minutes*60);
}

/**
 * Convierte del timestamp a horas
 *
 * @param int $timestamp_hour_minutes del tiempo en horas minutos
 * @return int hours
 */
function timestamp_to_hour($timestamp_hour_minutes) {
  return floor($timestamp_hour_minutes/3600);
}

/**
 * Convierte del timestamp a minutos
 *
 * @param int $timestamp_hour_minutes del tiempo en horas minutos
 * @return int minutes
 */
function timestamp_to_minutes($timestamp_hour_minutes) {
  return ($timestamp_hour_minutes % 3600) / 60;
}

function getInstanceByModuleName($modulename,$course_id)
{
    global $DB;
    $moduleId = obtener_module_id($modulename);
    $sql = "SELECT * FROM mdl_course_modules WHERE course = :course AND module = :moduleid AND  deletioninprogress = 0 ORDER BY id DESC LIMIT 1";
    $pluginModule = $DB->get_record_sql($sql, array('course' => $course_id, 'moduleid' => $moduleId), MUST_EXIST);
    $instanceId = $pluginModule->instance != null ? $pluginModule->instance : throw new Exception("No se encontro la instancia del modulo");
    return $instanceId;
}
function getAllInstanceByModuleName($modulename,$course_id)
{
    global $DB;
    mtrace('Curso en el que fue llamado: ' . $course_id);
    $moduleId = obtener_module_id($modulename);
    $sql = "SELECT * FROM mdl_course_modules WHERE course = :course AND module = :moduleid AND  deletioninprogress = 0";
    $pluginModule = $DB->get_records_sql($sql, array('course' => $course_id, 'moduleid' => $moduleId));
    $instancesId = [];
    foreach ($pluginModule as $module) {
        $instancesId[] = $module->instance;
    }
    return $instancesId;
}

function getTime($time, $addedTime){
    $localDate = date('Y-m-d', $time);
    $base = strtotime($localDate . ' 00:00:00');

    $final = $base + $addedTime;

    //debugging
    //mtrace("DEBUG - [getTime] base: $base (" . date('Y-m-d H:i:s', $base) . ")");
    //mtrace("DEBUG - [getTime] final: $final (" . date('Y-m-d H:i:s', $final) . ")");
    return $final;

    //return $time - $time % $day + $addedTime + $threeHours;
    //por qué siempre suma 3 horas?
    
}