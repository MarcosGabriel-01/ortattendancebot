<?php
namespace mod_attendancebot\task;

require_once($CFG->dirroot . '/mod/attendancebot/classes/orchestrator/Orchestrator.php');

class meeting_processer_task extends \core\task\adhoc_task {
    public function get_name() {
        return get_string('meeting_processer_task', 'mod_attendancebot');
    }

    public function execute() {
        global $DB;
        
        $data = $this->get_custom_data();
        $courseid = $data->courseid;
        $installationid = $data->installationid;
        mtrace('Empezando ejecución de tarea AdHoc de asistencia automatica de curso: ' . $courseid);

        mtrace('Procesando curso: ' . $courseid);
        $this->process_course($courseid, $installationid);

        mtrace('La tarea AdHoc de asistencia automatica ha sido completada de curso: ' . $courseid);
    }

    private function process_course($courseid, $installationid) {
        global $DB;

        $orchestrator = new \Orchestrator($courseid, $installationid);
        $orchestrator->process();
        
        $attendancebot = $DB->get_record('attendancebot', ['course' => $courseid], '*', IGNORE_MISSING);
        if ($attendancebot && empty($attendancebot->backuprecordings)) {
            mtrace("Respaldo de recordings desactivado para esta instancia (course ID: $courseid).");
            return;
        }

        $orchestrator->processRecordings();
    }

}
