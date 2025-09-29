<?php

require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');

class StudentMap{

    private $map = [];

    public function __construct($students)
    {
        foreach ($students as $student) {
            if (!isset($this->map[$student->getGroupId()])) {
                $this->map[$student->getGroupId()] = [];
            }
            array_push($this->map[$student->getGroupId()],$student);
        }

    }

    public function getMap() {
        return $this->map;
    }


}