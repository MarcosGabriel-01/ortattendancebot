<?php

class AttendanceLog
{
    public $sessionid;
    public $studentid;
    public $statusid;
    public $statusset;
    public $timetaken;
    public  $remarks = "registered by ORT AttendanceBot";
    public function __construct($studentid, $sessionid, $statusset, $statusid)
    {
        $this->studentid = $studentid;
        $this->sessionid = $sessionid;
        $this->statusset = $statusset;
        $this->statusid = $statusid;
        $this->timetaken = time();
    }

}