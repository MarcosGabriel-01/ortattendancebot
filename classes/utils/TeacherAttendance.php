<?php

class TeacherAttendance  {
    private $userId;
    private $attendancePercentage;
    private $isLate;
    private $startTime;
    private $endTime;
    private $groupId; 
    private $hasVideo;
    private $duration;

    public function __construct($rawTeacher, $hasVideo = false)  {

        $this->userId = $rawTeacher->userid;
        $this->attendancePercentage = $rawTeacher->attendance_percentage;
        $this->isLate = $rawTeacher->is_late;
        $this->startTime = $rawTeacher->start_time;
        $this->endTime = $rawTeacher->end_time;
        $this->groupId = $rawTeacher->groupid; 
        $this->hasVideo = $hasVideo;
        $this->duration = $rawTeacher->duration ?? 0;
    }

    public function getUserId()  {
        return $this->userId;
    }

    public function getAttendancePercentage()  {
        return $this->attendancePercentage;
    }

    public function getIsLate()  {
        return $this->isLate;
    }

    public function getStartTime()  {
        return $this->startTime;
    }

    public function getEndTime()  {
        return $this->endTime;
    }

    public function getGroupId()  {
        return $this->groupId;
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
