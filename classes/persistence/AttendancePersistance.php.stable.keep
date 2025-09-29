<?php
require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/BasePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/models/AttendanceLog.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentMap.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/Statuses.php');
// require_once($CFG->dirroot . '/mod/attendance/externallib.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');


class AttendancePersistance extends BasePersistance  {


    private $courseId;
    private $descriptionBot = "registered by ORT AttendanceBot";

    public function __construct($courseId) {
      $this->courseId = $courseId;
    }

    
    public function persistStudents($students, $teachers = [])
    {
        $map = new StudentMap($students);
        $sortedStudentMap = $map->getMap();
        $attendanceId = getInstanceByModuleName('attendance', $this->courseId);

        $existingSessionIds = $this->getExistingSessionIds($attendanceId, $sortedStudentMap);
        $sessionsNgroups= $this->insertStudents($sortedStudentMap,$attendanceId,$existingSessionIds);

        $combinedSessionsMap = $this->mergeSessionsMaps($existingSessionIds, $sessionsNgroups);

        $this->insertTeachers($teachers, $attendanceId, $combinedSessionsMap);

        $absentStudents = $this->markAbsentStudents($sessionsNgroups, $sortedStudentMap);
        $this->markSessions($sessionsNgroups["sessionid"]);

        return $absentStudents;
}

private function mergeSessionsMaps(array $existingMap, array $sessionsNgroups): array  {

        $combined = $existingMap;
        for ($i = 0; $i < count($sessionsNgroups['sessionid']); $i++)  {

            $groupId = $sessionsNgroups['groupid'][$i];
            $sessionId = $sessionsNgroups['sessionid'][$i];
            $combined[$groupId] = $sessionId;
        }

        return $combined;
}
    
    private function getExistingSessionIds($attendanceId, $studentMap): array
    {
        global $DB;
                $allStartTimes = [];

        foreach ($studentMap as $group) {
            foreach ($group as $student) {
                $allStartTimes[] = $student->getStartTime();
            }
        }

        if (empty($allStartTimes)) {
            return [];
        }

        $medianStartTime = (int) (array_sum($allStartTimes) / count($allStartTimes));
        $tolerance = 1800;
        $start = $medianStartTime - $tolerance;
        $end = $medianStartTime + $tolerance;
        $allStartTimes = [];

        foreach ($studentMap as $group) {
            foreach ($group as $student) {
                $allStartTimes[] = $student->getStartTime();
            }
        }

        if (empty($allStartTimes)) {
            return [];
        }

        $medianStartTime = (int) (array_sum($allStartTimes) / count($allStartTimes));
        $tolerance = 1800;
        $start = $medianStartTime - $tolerance;
        $end = $medianStartTime + $tolerance;

         $sql = "SELECT id, groupid FROM {attendance_sessions} 
            WHERE attendanceid = :attendanceid 
            AND sessdate >= :start AND sessdate <= :end";
        $params = [
            'attendanceid' => $attendanceId,
            'start' => $start,
            'end' => $end
        ];

        $records = $DB->get_records_sql($sql, $params);

        $map = [];
        foreach ($records as $record) {
            $groupid= $record->groupid;
                        
            if (!isset($map[$record->groupid])) {
                $map[$groupid] = $record->id;

            }else {
                $DB->delete_records('attendance_sessions', ['id' => $record->id]);
                mtrace("Eliminando sesión duplicada con id: " . $record->id . " para el grupo: " . $record->groupid);
            }
        }

        return $map;

    }

    /**
     * crea una session nueva y inserta a todos los alumnos dentro de ella con sus respectivos estados
     * @param $studentMap  StudentMap
     * @param $attendanceId string
     * @return array de sessionesId
     */
       private function insertStudents($studentMap, $attendanceId, $existingSessionMap)
    {
        global $DB;
        $inserts = [];
        $sessionsNgroups = array(
            "sessionid" => array(),
            "groupid" => array()
        );
        $statuses = $this->getStatuses($attendanceId);


        foreach ($studentMap as $group) {
            $groupId = $group[0]->getGroupId();

            if (isset($existingSessionMap[$groupId])) {
                mtrace("Reutilizando sesión existente para grupo $groupId: " . $existingSessionMap[$groupId]);
                $sessionId = $existingSessionMap[$groupId];
                $this->markSessions([$sessionId]);
                $course = $DB->get_record('course', ['id' => $this->courseId], 'fullname', MUST_EXIST);

                $startTime = $group[0]->getStartTime();
                $endTime = $group[0]->getEndTime();
                $duration = $endTime - $startTime;  

                $DB->update_record('attendance_sessions', ['id' => $sessionId, 'description' => $this->descriptionBot, 'sessdate' => $startTime, 'duration' => $duration]);

            } else {
                mtrace("No se encontró sesión existente para grupo $groupId, creando nueva.");
                $sessionId = $this->getNewSession($group[0], $attendanceId);
            }

            if ($sessionId == null) {
                continue;
            }

            $sessionsNgroups ["sessionid"][] = $sessionId;
            $sessionsNgroups ["groupid"][] = $group[0]->getGroupId();

            
            $idBot = getInstanceByModuleName('attendancebot',$this->courseId);         
            $botConfig = $DB->get_record('attendancebot', ['id' => $idBot],
                'late_tolerance, min_percentage, min_required_minutes, required_type', MUST_EXIST);   

            $requiredType = $botConfig->required_type; 
            $minPercentage = (int) $botConfig->min_percentage;
            $minDuration = (int) $botConfig->min_required_minutes;
            $checkCamera = (bool) $botConfig->camera; 

            foreach ($group as $student) {
                $statusId = $statuses->getAbscent();
                if ($requiredType === 'percentage') {
                    $requisitos = $student->getAttendancePercentage() >= $minPercentage;
                 } elseif ($requiredType === 'time') {
                    $requisitos = $student->getDuration() >= $minDuration;
                }

                if ($requisitos) {
                    if ($checkCamera) {
                        if ($student->hasVideo()) {
                        $statusId = $student->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                        } else {
                            mtrace("Marcado como ausente: usuario {$student->getUserId()} por no encender cámara");
                        }
                    } else {
                        $statusId = $student->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                    }
                }else {
                    mtrace("ERROR: Tipo de requisito desconocido: $requiredType");
                }

                
                $attendanceLog = new AttendanceLog($student->getUserId(), $sessionId, $statuses->getAll(), $statusId); 
                $inserts[] = $attendanceLog;

                mtrace("Datos bot tipo: $requiredType - Porcentaje: $minPercentage %, Duración: $minDuration");
                mtrace("Agregando estudiante");
                mtrace("Estudiante {$student->getUserId()} - Porcentaje: {$student->getAttendancePercentage()}%, Duración: {$student->getDuration()}s, IsLate: {$student->getIsLate()}");
                mtrace("Status asignado: $statusId");
            }

        }

        $DB->insert_records('attendance_log', $inserts);
        return $sessionsNgroups;
    }


   private function insertTeachers($teachers, $attendanceId, $existingSessionMap)  {

        global $DB;
        $statuses = $this->getStatuses($attendanceId);
        $inserts = [];

        $idBot = getInstanceByModuleName('attendancebot', $this->courseId);
        $config = $DB->get_record('attendancebot', ['id' => $idBot], 'late_tolerance, min_percentage, min_required_minutes, required_type, camera', MUST_EXIST);
        $attendancePercentage = (int) $config->min_percentage;
        $tolerance = (int) $config->late_tolerance;

        $requiredType = $config->required_type; 
        $minDuration = (int) $config->min_required_minutes;
        $checkCamera = (bool) $config->camera; 

        foreach ($teachers as $teacher)  {
            $userId = $teacher->getUserId();
            $groupId = $teacher->getGroupId();
            $joinTime = (int) $teacher->getStartTime();

            if (!isset($existingSessionMap[$groupId]))  {
                mtrace("No se encontró sesión para docente ID $userId (grupo $groupId), se omite.");
                continue;
            }

            $sessionId = $existingSessionMap[$groupId];

            $statusId = $statuses->getAbscent();
            if($requiredType === 'percentage'){
                $requisitos = $teacher->getAttendancePercentage() >= $attendancePercentage;
            }
            elseif ($requiredType === 'time') {
                mtrace($teacher->getDuration());
                    $requisitos = $teacher->getDuration() >= $minDuration;
            }

            if ($requisitos) {
                    if ($checkCamera) {
                        if ($teacher->hasVideo()) {
                        $statusId = $teacher->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                        } else {
                            mtrace("Marcado como ausente: usuario {$teacher->getUserId()} por no encender cámara");
                        }
                    } else {
                        $statusId = $teacher->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                    }
            }else {
                mtrace("ERROR: Tipo de requisito desconocido: $requiredType");
            }

            //$expectedStart = (int) $DB->get_field('attendance_sessions', 'sessdate', ['id' => $sessionId]);
            //$isLate = $joinTime > ($expectedStart + $tolerance * 60);

            $attendanceLog = new AttendanceLog($userId, $sessionId, $statuses->getAll(), $statusId);
            $inserts[] = $attendanceLog;

            mtrace("Agregando docente");
            mtrace("docente {$teacher->getUserId()} - Porcentaje: {$teacher->getAttendancePercentage()}%, Duración: {$teacher->getDuration()}s, IsLate: {$teacher->getIsLate()}");
            mtrace("Status asignado: $statusId");
        }

        if (!empty($inserts))  {
            $DB->insert_records('attendance_log', $inserts);
        }
    }

    private function markSessions( $sessions)
    {
        global $DB;
        foreach ($sessions as $session){
            $result = $DB->get_record('attendance_sessions', array('id' => $session));
            if ($result == null){
                continue;
            }

            $result->lasttaken = time();
            $DB->update_record('attendance_sessions', $result);
        }

    }

    private function getStatuses($attendanceId) {
        global $DB;
        $statusesRaw = array_values($DB->get_records('attendance_statuses', array('attendanceid' => $attendanceId), 'id', 'id'));
        $statuses = new Statuses($statusesRaw);
        return $statuses;

    }


    private function getNewSession($student , $attendanceId){
        
        global $DB;
        $attendanceExternal = new mod_attendance_external();

        $startTime = $student->getStartTime();
        $endTime = $student->getEndTime();
        $duration = $endTime - $startTime;

        $groupid = $student->getGroupId();


        $description = $this->descriptionBot;
        return array_values($attendanceExternal->add_session($attendanceId, $description, $startTime, $duration, $groupid, false))[0];
    
    }

    
    private function markAbsentStudents($sessionsNgroups, $studentMap)
    {
               global $DB;

        $absentStudents = array();
        $statuses = $this->getStatuses(getInstanceByModuleName('attendance', $this->courseId));
        $absentStatusId = $statuses->getAbscent();

        for ($i = 0; $i < count($sessionsNgroups["sessionid"]); $i++) {
            $sessionId = $sessionsNgroups["sessionid"][$i];
            $groupId = $sessionsNgroups["groupid"][$i];

            $groupMembers = $DB->get_records('groups_members', ['groupid' => $groupId], '', 'userid');
            $presentStudents = isset($studentMap[$groupId]) ? $studentMap[$groupId] : [];
            $presentIds = array_map(fn($s) => $s->getUserId(), $presentStudents);

            foreach ($groupMembers as $member) {
                $userId = $member->userid;

                if (in_array($userId, $presentIds)) {
                    continue;
                }

                $log = $DB->get_record('attendance_log', [
                    'studentid' => $userId,
                    'sessionid' => $sessionId
                ]);

                if ($log) {
                    if ($log->statusid != $absentStatusId) {
                        $log->statusid = $absentStatusId;
                        $DB->update_record('attendance_log', $log);
                        mtrace("Actualizado a ausente user $userId en session $sessionId");
                        $absentStudents[] = [$sessionId, $userId];
                    } else {
                        mtrace("Ya estaba marcado como ausente user $userId en session $sessionId");
                    }
                } else {
                    $attendanceLog = new AttendanceLog($userId, $sessionId, $statuses->getAll(), $absentStatusId);
                    $DB->insert_record('attendance_log', $attendanceLog);
                    mtrace("Insertado ausente user $userId en session $sessionId");
                    $absentStudents[] = [$sessionId, $userId];
                }
            }
        }

        return $absentStudents;
    }



    private function insertAbsentStudents($records, $sessionid)  {

        global $DB;
        $statuses = $this->getStatuses(getInstanceByModuleName('attendance', $this->courseId));
        $inserts = [];
        foreach ($records as $record) {
            $attendanceLog = new AttendanceLog($record->userid, $sessionid, $statuses->getAll(), $statuses->getAbscent());
            $inserts[] = $attendanceLog;
            mtrace('Agregando estudiante ausente'. $attendanceLog->studentid);
        }
        $DB->insert_records('attendance_log', $inserts);
    }



}
