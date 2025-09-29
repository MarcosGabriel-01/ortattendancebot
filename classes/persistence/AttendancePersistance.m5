<?php

require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/BasePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/models/AttendanceLog.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentMap.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/Statuses.php');
require_once($CFG->dirroot . '/mod/attendance/externallib.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
//Probando
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');

class AttendancePersistance extends BasePersistance
{
    /** @var bool */
    private $checkCamera = false;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
        // m5desa: $checkCamera remains false by default; will be set when config is available.
    }

    
    public function persistStudents($students)
    {
        $map = new StudentMap($students);
        $sortedStudentMap = $map->getMap();
        $attendanceId = getInstanceByModuleName('attendance',$this->courseId);

        $existingSessionIds = $this->getExistingSessionIds($attendanceId, $sortedStudentMap);

        $sessionsNgroups= $this->insertStudents($sortedStudentMap,$attendanceId,$existingSessionIds);

        $absentStudents = $this->markAbsentStudents($sessionsNgroups, $sortedStudentMap);
        $this->markSessions($sessionsNgroups["sessionid"]);

        return $absentStudents;
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
        //debug
        //mtrace("Buscando sesiones entre " . date('Y-m-d H:i:s', $params['start']) . " y " . date('Y-m-d H:i:s', $params['end']));
        //mtrace("Cantidad de sesiones encontradas: " . count($records));
        return $map;
    }


    /**
     * crea una session nueva y inserta a todos los alumnos dentro de ella con sus respectivos estados
     * @param $studentMap  StudentMap
     * @param $attendanceId string
     * @return array de sessionesId
     */
    //Probar de usar para poner los presentes y ausentes
    private function insertStudents($studentMap, $attendanceId, $existingSessionMap){
        global $DB;
        $inserts = [];
        $sessionsNgroups = array( "sessionid" => array(),
                            "groupid" => array());
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

                $DB->update_record('attendance_sessions', ['id' => $sessionId, 'description' => $course->fullname . " " . $this->descriptionBot, 'sessdate' => $startTime, 'duration' => $duration]);

            } else {
                mtrace("No se encontró sesión existente para grupo $groupId, creando nueva.");
                $sessionId = $this->getNewSession($group[0], $attendanceId);
            }

            if ($sessionId == null){
                continue;
            }

            $sessionsNgroups ["sessionid"][] = $sessionId;
            $sessionsNgroups ["groupid"][] = $group[0]->getGroupId();

            $idBot = getInstanceByModuleName('attendancebot',$this->courseId);
            $attendancePercentage = (int) $DB->get_record('attendancebot', array('id' => $idBot ),
                'late_tolerance,min_percentage', MUST_EXIST)->min_percentage;


            foreach ($group as $student) {
                $statusId = $statuses->getAbscent();
                if ($student->getAttendancePercentage()>= $attendancePercentage and $student->getIsLate() == 0){
                    $statusId = $statuses->getPresent();
                }elseif ($student->getAttendancePercentage()>= $attendancePercentage and $student->getIsLate() == 1){
                    $statusId = $statuses->getLate();
                }

                $attendanceLog = new AttendanceLog($student->getUserId(),$sessionId,$statuses->getAll(),$statusId); 
                $inserts[] = $attendanceLog;
                mtrace('Agregando estudiante');                             
                
            } 

        }

        $DB->insert_records('attendance_log', $inserts);
        return $sessionsNgroups;
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

    private function getStatuses($attendanceId){
        global $DB;
        $statusesRaw = array_values($DB->get_records('attendance_statuses', array('attendanceid' => $attendanceId),'id','id'));
        $statuses = new Statuses($statusesRaw);
        return $statuses;
    }


    private function getNewSession($student , $attendanceId){
        global $DB;
        $attendanceExternal = new mod_attendance_external();

        //new
        $startTime = $student->getStartTime();
        $endTime = $student->getEndTime();
        $duration = $endTime - $startTime;

        $groupid = $student->getGroupId();

        $description = ($DB->get_record('course', array('id' => $this->courseId))->fullname) . " " . $this->descriptionBot;
        return array_values($attendanceExternal->add_session($attendanceId, $description, $startTime, $duration, $groupid, false))[0];
    }


    private function markAbsentStudents($sessionsNgroups, $studentMap)
    {
        global $DB;

        $absentStudents =  array();
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


    private function insertAbsentStudents($records, $sessionid)
    {
        global $DB;
        $statuses = $this->getStatuses(getInstanceByModuleName('attendance',$this->courseId));
        $inserts = [];
        foreach ($records as $record){
            $attendanceLog = new AttendanceLog($record->userid,$sessionid,$statuses->getAll(),$statuses->getAbscent());
            $inserts[] = $attendanceLog;
            mtrace('Agregando estudiante ausente'. $attendanceLog->studentid);
        }
        $DB->insert_records('attendance_log', $inserts);
    }


    public function validate_and_fix_sessions($startdate, $enddate): bool {
    global $OUTPUT;
    if (empty($startdate) || empty($enddate)) {
        \core\notification::error(get_string('missing_dates', 'mod_attendancebot'));
        return false;
    }
    if ($startdate > $enddate) {
        \core\notification::error(get_string('invalid_date_range', 'mod_attendancebot'));
        return false;
    }
    $this->correctHistoricalSessions($startdate, $enddate);
    \core\notification::success(get_string('sessions_fixed_success', 'mod_attendancebot'));
    return true;
}



    private function correctHistoricalSessions($firstDay, $endDay) {
    global $DB;
    $newDescription = "historical sessions corrected by ORT AttendanceBot";
    if ($firstDay > $endDay) {
        throw new moodle_exception('La fecha de inicio no puede ser posterior a la de fin.');
    }
    $attendanceId = getInstanceByModuleName('attendance', $this->courseId);
    if (!$attendanceId) {
       error_log("No se encontró una instancia de attendance para el curso ID {$this->courseId}");
    } else {
        $startdate = strtotime($firstDay);
        $enddate = strtotime($endDay);
        $sessions = $this->getSessionsToCorrect($attendanceId, $newDescription, $startdate, $enddate);
        if (empty($sessions)) {
            error_log("No hay sesiones históricas para corregir.");
        } else {
            $description = $DB->get_field('course', 'fullname', ['id' => $this->courseId]) . " " . $newDescription;
            error_log("Corrigiendo " . count($sessions) . " sesiones históricas.");
            foreach ($sessions as $session) {
                if (empty($session->groupid)) continue;
                if (!empty($session->sessdate) && !empty($session->duration)) {
                    $session->endtime = $session->sessdate + $session->duration;
                }
                $this->updateSessionDuration($session);
                $this->updateSessionDescription($session, $description);
                $this->processSessionMembers($session, $attendanceId, $description); 
            }
        }
    }
}

//OK
private function getSessionsToCorrect($attendanceId, $excludedDescription, $firstDay, $endDay) {
    global $DB;
    $sql = "SELECT * FROM {attendance_sessions} 
            WHERE attendanceid = :attendanceid 
            AND description != :description
            AND sessdate >= :starttime
            AND sessdate <= :endtime";
    $params = [
        'attendanceid' => $attendanceId,
        'description'  => $excludedDescription,
        'starttime'    => $firstDay,
        'endtime'      => $endDay
    ];
    return $DB->get_records_sql($sql, $params);
}

//Revisar, no actualiza la duración de las sesiones historicas
private function updateSessionDuration($session) {
    global $DB;

    if (empty($session->id)) {
        throw new moodle_exception('Session ID faltante.');
    }

    $tolerance = 1800; // 30 minutos de margen
    $start = $session->sessdate - $tolerance;
    $end = $session->sessdate + $tolerance;

  $sql = "SELECT zmd.duration 
            FROM {zoom} z
            JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
            WHERE z.course = :courseid
            AND z.start_time BETWEEN :start AND :end
            ORDER BY ABS(z.start_time - :center) ASC
            LIMIT 1";
    
    $params = [
        'courseid' => $this->courseId,
        'start' => $start,
        'end' => $end,
        'center' => $session->sessdate
    ];

    $zoomMeeting = $DB->get_record_sql($sql, $params);
    
    if ($zoomMeeting && !empty($zoomMeeting->duration)) {
        $session->duration = $zoomMeeting->duration;
        error_log("Sesión ID {$session->id} actualizada con duración de Zoom: {$session->duration} segundos.");
        $DB->update_record('attendance_sessions', $session);
    } else {
        error_log("No se encontró reunión Zoom válida para sesión ID {$session->id}");
    }
}

//Revisar, no actualiza la descripción de sesiones historicas
private function updateSessionDescription($session, $description) {
  global $DB;
    $session->description = $description;
    $session->lasttaken = time();

    if (empty($session->id)) {
        error_log("Error: El ID de la sesión no es válido o está vacío.");
        return;
    }
    $result = $DB->update_record('attendance_sessions', $session);
    if ($result) {
       error_log("Sesión actualizada correctamente.");
    } else {
        error_log("Error al actualizar la sesión.");
    }
    $verif = $DB->get_record('attendance_sessions', ['id' => $session->id]);
    if ($verif) {
        error_log("DESPUÉS de update: descripción en DB = {$verif->description}, lasttaken = {$verif->lasttaken}");
    } else {
        error_log("No se encontró el registro con ID: {$session->id}");
    }

}

//No ajusta la asistencia correctamente
private function processSessionMembers($session, $attendanceId, $newDescription) {
    global $DB;
    $zoomRecollector = new zoomRecollector($this->courseId);
    $students = $zoomRecollector->getStudentsByCourseid();
    if (empty($students)) { 
        error_log("No se encontraron estudiantes para la sesión {$session->id}.");
    } else {
        error_log("Procesando a " . count($students) . " estudiantes.");
           $filtered = array_filter($students, fn($s) => $s->getGroupId() == $session->groupid);
            $studentMap = new StudentMap($filtered);
            $this->insertStudents($studentMap, $attendanceId, [$session->groupid => $session->id]);
        }
    }



}