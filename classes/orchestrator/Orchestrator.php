<?php

require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/BasePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/AttendancePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');


class Orchestrator
{
    /** @var bool */
    private $checkCamera = false;


    private $recollector;
    private $persistance;
    private $courseId;
    private $installationid;


    public function __construct($courseId, $installationid)
    {
        $this->courseId = $courseId;
        $this->installationid = $installationid;
        $this->loadValues();
    }

    public function process() {
        $students = $this->recollector->getStudentsByCourseid();
        mtrace('Cantidad de estudiantes encontrados'. count($students));
        $absentStudents = [];
        if (count($students) > 1) {
          $absentStudents = $this->persistance->persistStudents($students);
        }
        return $absentStudents;
    }

    private function loadValues(): void
    {
        global $DB;
        $installation = $DB->get_record("attendancebot", array("id" => $this->installationid));
        
        if (isset($installation->camera)) { $this->checkCamera = (bool)$installation->camera; }
$this->recollector = $this->recollectorFactory($installation->recolection_platform);
        $this->persistance = $this->persistanceFactory($installation->saving_platform);
    }

    private function recollectorFactory($recolectorType): BaseRecollector
    {
        switch ($recolectorType) {
            case "zoom":
                return new zoomRecollector($this->courseId);
            default:
                return new zoomRecollector($this->courseId);
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
}