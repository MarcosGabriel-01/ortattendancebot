<?php
global $CFG;

require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/BasePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/AttendancePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/ZoomRecordingBackup.php');

class Orchestrator  {

    private $recollector;
    private $persistance;
    private $courseId;
    private $installationid;

    public function __construct($courseId, $installationid)  {

        $this->courseId = $courseId;
        $this->installationid = $installationid;
        $this->loadValues();

    }

    public function process()  {

        $data = $this->recollector->getStudentsByCourseid();
        $students = $data['students'];
        $teachers = $data['teachers'];

        mtrace('Cantidad de estudiantes encontrados: ' . count($students));
        mtrace('Cantidad de docentes encontrados: ' . count($teachers));

        $absentStudents = [];

        if (count($students) > 0 || count($teachers) > 0)  {
            $absentStudents = $this->persistance->persistStudents($students, $teachers);
        }

        return $absentStudents;
    }

    private function loadValues(): void  {

        global $DB;
        $installation = $DB->get_record("attendancebot", array("id" => $this->installationid), '*', MUST_EXIST);

        $this->checkCamera = (bool) $installation->camera;

        $this->recollector = $this->recollectorFactory($installation->recolection_platform);
        $this->persistance = $this->persistanceFactory($installation->saving_platform);

    }

    private function recollectorFactory($recolectorType): BaseRecollector
    {
        switch ($recolectorType) {
            case "zoom":
                return new zoomRecollector($this->courseId, $this->checkCamera);
            default:
                return new zoomRecollector($this->courseId, $this->checkCamera);
        }
    }

    private function persistanceFactory($persistanceType): BasePersistance
    {
        switch ($persistanceType) {
            case "attendance":
                return new AttendancePersistance($this->courseId);
            default:
                return new AttendancePersistance($this->courseId);
        }
    }

    public function processRecordings(): void  {

        $zoomToken = getZoomToken(); 
        $backup = new ZoomRecordingBackup($this->courseId, $zoomToken);
        $zoomIds = getAllInstanceByModuleName('zoom', $this->courseId);

        foreach ($zoomIds as $zoomId)  {

            $meetings = $this->recollector->getMeetingsByZoomId($zoomId); 
            $meetingIds = array_map(fn($r) => $r->meeting_id, $meetings); 
            $backup->processRecordings($meetingIds);

        }
    }

}