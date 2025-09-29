<?php
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');

class zoomRecollector extends BaseRecollector{

    private $courseId;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }


    public function getStudentsByCourseid()
    {
        $zoomIds = getAllInstanceByModuleName('zoom',$this->courseId);
        mtrace('Zoom id encontrados ' . count($zoomIds));
        mtrace('Zoom id en pos 0: ' . $zoomIds[0]);
        $students = [];
        foreach ($zoomIds as $zoomId){

            $meetings = $this->getMeetingsByZoomId($zoomId);
            mtrace('Cantidad de Meetings ' . count($meetings));
            if ($meetings == null || count($meetings) == 0){
                continue;
            }

            //transformo el array de meetings en array de ids
            $detailsId = array_map(function($record){return $record->id;},$meetings);
            mtrace('Cantidad details id ' . count($detailsId));
            mtrace('Details id en pos 0 ' . $detailsId[0]);
            $studentsInMeeting = $this->getStudentsByMeetingId($detailsId);
            if ($studentsInMeeting!= null){
                $students = array_merge($students,$studentsInMeeting);
            }
        }
        return $students;
    }
    
    //dado una meetingId devuelve un array de studentattendance
    private function getStudentsByMeetingId($meetingId){
        if (count($meetingId)>1){
            $attendanceData = $this->getAttendanceDataByMultipleDetails($meetingId);
        }
        else{
            $attendanceData = $this->getAttendanceData($meetingId[0]);
        }

        $students = [];
        foreach ($attendanceData as $student){
            array_push($students,new StudentAttendance($student));
        }
        return $students;
    }



// dado un detailsid y una tolerancia devuelve un array de studentattendance
    private function getAttendanceData($detailsId) {
        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $minsOfTolerance = (int) $DB->get_record('attendancebot', array('id' => $idBot ), 'late_tolerance,min_percentage', MUST_EXIST)->late_tolerance;
        if ($minsOfTolerance==0){
            $minsOfTolerance = 1000000000;
        }
        $SQL = "
            SELECT
                mzmp.userid,	
                mzmp.name,
                mgm.groupid,
                mzmd.start_time,
                mzmd.end_time,
             (SUM(mzmp.duration) * 100.0 / (mzmd.end_time - mzmd.start_time)) AS attendance_percentage,
            	CASE WHEN mzmp.join_time > mzmd.start_time + (:minutes_of_tolerance * 60) THEN 1 ELSE 0 END AS is_late
            FROM
                 mdl_zoom_meeting_participants mzmp
            JOIN
                mdl_zoom_meeting_details mzmd ON mzmp.detailsid = mzmd.id
            JOIN
	            mdl_zoom mz ON mzmd.zoomid = mz.id
            JOIN 
                mdl_groups_members mgm ON mzmp.userid = mgm.userid
             JOIN 
                mdl_groups mg ON mgm.groupid = mg.id AND mz.course = mg.courseid
            WHERE
                mzmp.detailsid = :details_id
            GROUP BY
                mzmp.userid, mzmp.name, mzmd.start_time, mzmd.end_time, is_late,mgm.groupid;";
        $params = ['minutes_of_tolerance' => $minsOfTolerance, 'details_id' => $detailsId];

        return array_values($DB->get_records_sql($SQL, $params));
    }
    public function getAttendanceDataByMultipleDetails($detailsId)
    {
        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $minsOfTolerance = (int) $DB->get_record('attendancebot', array('id' => $idBot ), 'late_tolerance,min_percentage', MUST_EXIST)->late_tolerance;
        if ($minsOfTolerance==0){
            $minsOfTolerance = 1000000000;
        }
        $sql = "
        SELECT mzmp.userid, 
        mzmp.name,
        mgm.groupid,
        min(mzmd.start_time) as start_time,
        max(mzmd.end_time)as end_time,
        min(mzmp.join_time) as min_join,
        max(mzmp.leave_time) as max_leave,
        sum(mzmp.duration) as total_stay,
        (select sum(mzmd2.duration) * 60 from mdl_zoom_meeting_details mzmd2 where mzmd2.id in (:detailsid1)) as meeting_duration,
        sum(mzmp.duration) * 100 / (select sum(mzmd2.duration) * 60 	from mdl_zoom_meeting_details mzmd2 where mzmd2.id in (:detailsid2)) as attendance_percentage,
        CASE WHEN min(mzmp.join_time) > min(mzmd.start_time) + (15 * 60) THEN 1 ELSE 0 END AS is_late
        FROM mdl_zoom_meeting_participants mzmp
        JOIN mdl_zoom_meeting_details mzmd ON mzmp.detailsid = mzmd.id
        JOIN mdl_zoom mz ON mzmd.zoomid = mz.id
        JOIN mdl_groups_members mgm ON mzmp.userid = mgm.userid
        JOIN mdl_groups mg ON mgm.groupid = mg.id AND mz.course = mg.courseid
        WHERE mzmp.detailsid in (:detailsid3)
        group by mzmp.name , mzmp.userid,mgm.groupid ;";

        $detailsidString = implode(',', $detailsId);
        $result = array_values($DB->get_records_sql($sql, array('minutes_of_tolerance' => $minsOfTolerance,'detailsid1' => $detailsidString,'detailsid2' => $detailsidString,'detailsid3' => $detailsidString)));
        return $result;
    }

    private function getMeetingsByZoomId($zoomId){
        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $claseInfo = $DB->get_record('attendancebot', array('id' => $idBot ), 'clases_start_time,clases_finish_time', MUST_EXIST);
        $classStartTime = $claseInfo->clases_start_time;
        $classFinishTime = $claseInfo->clases_finish_time;


        $day = 86400;
        $minsTolerance =1800;

        $start = getTime(time()-$day, $classStartTime - $minsTolerance);
        $end = getTime(time()-$day, $classFinishTime + $minsTolerance);

        //debugging
        //mtrace("DEBUG - time()-day: " . (time() - 86400));
        //mtrace("DEBUG - getTime(time()-day, ...) = " . getTime(time()-86400, $classStartTime - $minsTolerance));
        //mtrace("DEBUG - fecha legible = " . date('Y-m-d H:i:s', getTime(time()-86400, $classStartTime - $minsTolerance)));

        // fix horas desfazadas      
        if ($classFinishTime < $classStartTime) {
            $end   = getTime(time(), $classFinishTime + $minsTolerance); 
        }

        mtrace('Cuando empezo la meeting: ' . $start);
        mtrace('Cuando termino la meeting: ' . $end );

        //salidas por consola para debug
        /*
        $sqlDebug = "SELECT * FROM mdl_zoom_meeting_details WHERE zoomid = :zoomid";
        $reuniones = $DB->get_records_sql($sqlDebug, ['zoomid' => $zoomId]);
        mtrace("Total reuniones con zoomid=$zoomId: " . count($reuniones));
        foreach ($reuniones as $r) {
            mtrace("start_time: {$r->start_time} | duration: {$r->duration} | participants_count: {$r->participants_count}");
        }
        mtrace("Hora de inicio de clase original: $classStartTime");
        mtrace("Hora de fin de clase original: $classFinishTime");
        mtrace("Start timestamp usado en query: $start");
        mtrace("End timestamp usado en query: $end");
        */

        $sql = "SELECT * FROM mdl_zoom_meeting_details WHERE zoomid = :zoomid AND start_time > :start AND start_time < :end AND duration >1 AND participants_count>1";
        $meetings = array_values($DB->get_records_sql($sql, array('zoomid' => $zoomId, 'start' => $start, 'end' => $end)));
        return $meetings;
    }

}