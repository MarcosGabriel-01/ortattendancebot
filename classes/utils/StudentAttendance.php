<?php
class StudentAttendance{
    private $userId;
    private $attendancePercentage;
    private $isLate;
    private $groupId;
    private $startTime;
    private $endTime;
    private $hasVideo;
    private $duration;

    public function __construct($rawStudent, $hasVideo = false) {
        $this->userId = $rawStudent->userid;
        $this->attendancePercentage = $rawStudent->attendance_percentage;
        $this->isLate = $rawStudent->is_late;
        $this->groupId = $rawStudent->groupid;
        $this->startTime = $rawStudent->start_time;
        $this->endTime = $rawStudent->end_time;
        $this->hasVideo = $hasVideo;
        $this->duration = $rawStudent->duration ?? 0;
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
    public function hasVideo() 
    {
        return $this->hasVideo;
    }

    public function getDuration()
    {
        return $this->duration;
    }

} 


