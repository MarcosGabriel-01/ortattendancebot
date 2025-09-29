<?php
namespace mod_attendancebot\task;
require_once($CFG->dirroot . '/mod/attendancebot/classes/task/meeting_processer_task.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');


class scheduler_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('scheduler_task_name', 'mod_attendancebot');
    }

    public function execute() {
        global $DB;
        mtrace('Empezando ejecución de programador de tareas de asistencia automatica');

        $installations = $this->get_installations();
        foreach ($installations as $installation) {
            $courseid = $installation->course;
            $installationid = $installation->id;
            mtrace('Curso obtenido : ' . $courseid);
            mtrace('Installation id obtenido : ' . $installationid);
            mtrace('Creando tarea AdHoc para el curso: ' . $courseid);

            $this->schedule_adhoc_task($courseid, $installationid);
        }

        mtrace('La tarea programador de tareas de asistencia automatica ha sido completada');
    }

    private function schedule_adhoc_task($courseid, $installationid) {
        $task = new meeting_processer_task();
        $task->set_custom_data(['courseid' => $courseid,'installationid' => $installationid]);
        \core\task\manager::queue_adhoc_task($task);
    }

    private function get_installations() {
        global $DB;

        $module_id = obtener_module_id('attendancebot');
        mtrace('Module de attencandebot: '. $module_id);
        
        $sql = "
        SELECT * FROM mdl_attendancebot 
        WHERE clases_start_date <= :current_time1 
        AND clases_finish_date >= :current_time2
        AND enabled = :enabled
        AND id in (SELECT instance FROM mdl_course_modules
                   WHERE module = :module_id 
                   AND deletioninprogress = :deletioninprogress)";
                   
        $params = ['current_time1' => time(), 'current_time2' => time(),'enabled' => '1','module_id' => $module_id,'deletioninprogress' => '0'];
        
        // Las instalaciones que se encuentran en el rango de fechas de inicio y fin, ademas de que estén habilitadas
        $plugin_instalation = array_values($DB->get_records_sql($sql, $params));
        mtrace('Numero de instalaciones activas encontradas'. count($plugin_instalation));
        mtrace('Time actual: '. time());
        return $plugin_instalation;
    }
}