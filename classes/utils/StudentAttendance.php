<?php


class StudentAttendance{
    private $userId;
    private $attendancePercentage;
    private $isLate;
    private $groupId;
    private $startTime;
    private $endTime;

    public function __construct($rawStudent) {
        $this->userId = $rawStudent->userid;
        $this->attendancePercentage = $rawStudent->attendance_percentage;
        $this->isLate = $rawStudent->is_late;
        $this->groupId = $rawStudent->groupid;
        $this->startTime = $rawStudent->start_time;
        $this->endTime = $rawStudent->end_time;
    }
    
    public function getUserId()
    {
        return $this->userId;
    }


    public function getAttendancePercentage()
    {
        return $this->attendancePercentage;
    }


    public function getIsLate()
    {
        return $this->isLate;
    }


    public function getGroupId()
    {
        return $this->groupId;
    }


    public function getStartTime()
    {
        return $this->startTime;
    }


    public function getEndTime()
    {
        return $this->endTime;
    }

} 


