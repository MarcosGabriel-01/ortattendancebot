<?php


require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/TeacherAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/RoleUtils.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');

class zoomRecollector extends BaseRecollector  {

    private $courseId;

    private $checkCamera;

    public function __construct($courseId, $checkCamera)  {

        $this->courseId = $courseId;
        $this->checkCamera = $checkCamera;
    }


    public function getStudentsByCourseid()
    {
        $zoomIds = getAllInstanceByModuleName('zoom',$this->courseId);
        mtrace('Zoom id encontrados ' . count($zoomIds));
        mtrace('Zoom id en pos 0: ' . $zoomIds[0]);
        $students = [];
        $teachers = [];
        foreach ($zoomIds as $zoomId){

            $meetings = $this->getMeetingsByZoomId($zoomId);
            mtrace('Cantidad de Meetings ' . count($meetings));
            if (empty($meetings)) {
                continue;
            }

            $detailsId = array_map(function($record){return $record->id;},$meetings);
            mtrace('Cantidad details id ' . count($detailsId));
            mtrace('Details id en pos 0 ' . $detailsId[0]);
            $participants = $this->getStudentsByMeetingId($detailsId, $this->checkCamera);

            $students = array_merge($students, $participants['students']);
            $teachers = array_merge($teachers, $participants['teachers']);
            //if ($studentsInMeeting!= null){
            //    $students = array_merge($students,$studentsInMeeting);
            //}
        }
        return ['students' => $students, 'teachers' => $teachers];
    }
    
    //dado una meetingId devuelve un array de studentattendance
    private function getStudentsByMeetingId($meetingIds, bool $checkCamera){
        if (count($meetingIds)>1){
            $attendanceData = $this->getAttendanceDataByMultipleDetails($meetingIds);
        }
        else{
            $attendanceData = $this->getAttendanceData($meetingIds[0]);
        }

        //$cameraOnUserIds = $this->getUsersWithCameraOn($meetingIds); 

        if ($checkCamera) {
            $cameraOnUserIds = $this->getUsersWithCameraOn($meetingIds);
        } else {
            $cameraOnUserIds = [];
        }

        $students = [];
        $teachers = [];
        foreach ($attendanceData as $participant){
            $userId = $participant->userid;
            $hasVideo = !$checkCamera || in_array($userId, $cameraOnUserIds);
            //$hasVideo = in_array($participant->userid, $cameraOnUserIds);
            if (RoleUtils::isTeacher($userId, $this->courseId))  {

                $teachers[] = new TeacherAttendance($participant, $hasVideo);

            } else  {
                $students[] = new StudentAttendance($participant, $hasVideo);
            }

        }
        return ['students' => $students, 'teachers' => $teachers];
    }

       private function getTeacherUserIds($userid)  {

        global $DB;

        $roles = RoleUtils::getAllowedTeacherRoles(); //  [3, 4, 15, 9, 12]

        $placeholders = [];
        $params = ['userid' => $userid];

        foreach ($roles as $index => $roleid)  {

            $key = "role$index";
            $placeholders[] = ":$key";
            $params[$key] = $roleid;

        }

        $inClause = implode(',', $placeholders);

        $sql = "
            SELECT ra.userid
            FROM {role_assignments} ra
            JOIN {context} ctx ON ctx.id = ra.contextid
            WHERE ra.userid = :userid
            AND ctx.contextlevel = 50
            AND ra.roleid IN ($inClause)
        ";

        $records = $DB->get_records_sql($sql, $params);
        return array_keys($records);

    }


    private function getAttendanceData($detailsId) {
        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $minsOfTolerance = (int) $DB->get_record('attendancebot', array('id' => $idBot ), 'late_tolerance,min_percentage', MUST_EXIST)->late_tolerance;

        if ($minsOfTolerance == 0)  {
            $minsOfTolerance = 1000000000;
        }
        $SQL = "
            SELECT
                mzmp.userid,	
                mzmp.name,
                mgm.groupid,
                mzmd.start_time,
                mzmd.end_time,
            SUM(mzmp.duration) AS duration,
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
                AND (mgm.userid IS NOT NULL OR EXISTS (
                    SELECT 1 FROM mdl_role_assignments ra 
                    WHERE ra.userid = mzmp.userid 
                    AND ra.roleid IN (" . implode(",", RoleUtils::getAllowedTeacherRoles()) . ")
                    AND ra.contextid IN (SELECT ctx.id FROM mdl_context ctx WHERE ctx.instanceid = mz.course AND ctx.contextlevel = 50)
                ))
            GROUP BY
                mzmp.userid, mzmp.name, mzmd.start_time, mzmd.end_time, is_late,mgm.groupid;";
        $params = ['minutes_of_tolerance' => $minsOfTolerance, 'details_id' => $detailsId];

        return array_values($DB->get_records_sql($SQL, $params));

    }



    public function getAttendanceDataByMultipleDetails($detailsId)  {

        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $minsOfTolerance = (int) $DB->get_record('attendancebot', array('id' => $idBot ), 'late_tolerance,min_percentage', MUST_EXIST)->late_tolerance;

        if ($minsOfTolerance == 0)  {
            $minsOfTolerance = 1000000000;
        }
        //TODO
        //cambiar sum(mzmp.duration) as total_stay, por: sum(mzmp.duration) as duration,
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
            -- WHERE mzmp.detailsid in (:detailsid3)
        WHERE
            mzmp.detailsid = :details_id
        AND (mgm.userid IS NOT NULL OR EXISTS (
            SELECT 1 FROM mdl_role_assignments ra 
            WHERE ra.userid = mzmp.userid 
            AND ra.roleid IN (" . implode(",", RoleUtils::getAllowedTeacherRoles()) . ")
            AND ra.contextid IN (SELECT ctx.id FROM mdl_context ctx WHERE ctx.instanceid = mz.course AND ctx.contextlevel = 50)
            ))
        group by mzmp.name , mzmp.userid,mgm.groupid ;";

        $detailsidString = implode(',', $detailsId);
        $result = array_values($DB->get_records_sql($sql, array('minutes_of_tolerance' => $minsOfTolerance,'detailsid1' => $detailsidString,'detailsid2' => $detailsidString,'detailsid3' => $detailsidString)));
        return $result;
    }




    public function getMeetingsByZoomId($zoomId)  {

        global $DB;

        $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
        $claseInfo = $DB->get_record('attendancebot', array('id' => $idBot ), 'clases_start_time,clases_finish_time', MUST_EXIST);
        $classStartTime = $claseInfo->clases_start_time;
        $classFinishTime = $claseInfo->clases_finish_time;

        $day = 86400;
        $minsTolerance =1800;

        $start = getTime(time()-$day, $classStartTime - $minsTolerance);
        $end = getTime(time()-$day, $classFinishTime + $minsTolerance);
    
        if ($classFinishTime < $classStartTime) {
            $end = getTime(time(), $classFinishTime + $minsTolerance); 
        }

        mtrace('Cuando empezo la meeting: ' . $start);
        mtrace('Cuando termino la meeting: ' . $end );

        $sql = "SELECT * FROM mdl_zoom_meeting_details WHERE zoomid = :zoomid AND start_time > :start AND start_time < :end AND duration >1 AND participants_count>1";
        $meetings = array_values($DB->get_records_sql($sql, array('zoomid' => $zoomId, 'start' => $start, 'end' => $end)));
        return $meetings;
    }


    private function getUsersWithCameraOn(array $meetingIds): array {
        $cameraOnUsers = [];
        $accessToken = getZoomToken();
        if (!$accessToken) {
            mtrace("No se pudo obtener access token de Zoom");
            return [];
        }  

        foreach ($meetingIds as $id) {
            //$url = "https://api.zoom.us/v2/metrics/meetings/{$id}/participants?page_size=300&type=past";
            $url = "https://api.zoom.us/v2/report/meetings/{$id}/participants";
            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json",
                "User-Agent: MoodleAttendanceBot/1.0"
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            ]);
            $response = curl_exec($ch);
        
            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                throw new Exception("cURL error ($errno): $error");
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            mtrace("Zoom API raw response (HTTP $httpCode):");
            mtrace($response ?: '[empty]');
            curl_close($ch);

            if ($httpCode === 204) {
                return []; 
            }

            if ($httpCode >= 400) {
                throw new Exception("Zoom API Error [$httpCode]: $response");
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                throw new Exception("Zoom API response not valid JSON: $response");
            }

            foreach ($data['participants'] ?? [] as $participant) {
                if (!empty($participant['has_video']) && $participant['has_video'] === true) {
                    $cameraOnUsers[] = $participant['user_id'] ?? $participant['id'];
                }
            }
        }

        return array_unique($cameraOnUsers);
    }

}