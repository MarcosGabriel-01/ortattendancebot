<?php

require_once($CFG->dirroot . '/mod/attendancebot/classes/persistence/BasePersistance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/models/AttendanceLog.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentAttendance.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/StudentMap.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/utils/Statuses.php');
require_once($CFG->dirroot . '/mod/attendance/externallib.php');
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/BaseRecollector.php');
require_once($CFG->dirroot . '/mod/attendancebot/classes/recollectors/zoomRecollector.php');

class AttendancePersistance extends BasePersistance {

    private $courseId;
    private $descriptionBot = "registered by ORT AttendanceBot";
    private $checkCamera = false;

    public function __construct($courseId) {
        $this->courseId = $courseId;
    }

    public function persistStudents($students, $teachers = []) {
        $map = new StudentMap($students);
        $sortedStudentMap = $map->getMap();
        $attendanceId = getInstanceByModuleName('attendance', $this->courseId);

        $existingSessionIds = $this->getExistingSessionIds($attendanceId, $sortedStudentMap);
        $sessionsNgroups = $this->insertStudents($sortedStudentMap, $attendanceId, $existingSessionIds);
        $combinedSessionsMap = $this->mergeSessionsMaps($existingSessionIds, $sessionsNgroups);

        $this->insertTeachers($teachers, $attendanceId, $combinedSessionsMap);
        $absentStudents = $this->markAbsentStudents($sessionsNgroups, $sortedStudentMap);
        $this->markSessions($sessionsNgroups["sessionid"]);

        return $absentStudents;
    }

    private function mergeSessionsMaps(array $existingMap, array $sessionsNgroups): array {
        $combined = $existingMap;
        for ($i = 0; $i < count($sessionsNgroups['sessionid']); $i++) {
            $groupId = $sessionsNgroups['groupid'][$i];
            $sessionId = $sessionsNgroups['sessionid'][$i];
            $combined[$groupId] = $sessionId;
        }
        return $combined;
    }

    private function getExistingSessionIds($attendanceId, $studentMap): array {
        global $DB;
        $allStartTimes = [];
        foreach ($studentMap as $group) {
            foreach ($group as $student) {
                $allStartTimes[] = $student->getStartTime();
            }
        }

        if (empty($allStartTimes)) return [];

        $medianStartTime = (int)(array_sum($allStartTimes) / count($allStartTimes));
        $tolerance = 1800;
        $start = $medianStartTime - $tolerance;
        $end = $medianStartTime + $tolerance;

        $sql = "SELECT id, groupid FROM {attendance_sessions} 
                WHERE attendanceid = :attendanceid 
                AND sessdate >= :start AND sessdate <= :end";
        $params = ['attendanceid' => $attendanceId, 'start' => $start, 'end' => $end];

        $records = $DB->get_records_sql($sql, $params);

        $map = [];
        foreach ($records as $record) {
            if (!isset($map[$record->groupid])) {
                $map[$record->groupid] = $record->id;
            } else {
                $DB->delete_records('attendance_sessions', ['id' => $record->id]);
                mtrace("Eliminando sesión duplicada con id: {$record->id} para el grupo: {$record->groupid}");
            }
        }

        return $map;
    }

    private function insertStudents($studentMap, $attendanceId, $existingSessionMap) {
        global $DB;
        $inserts = [];
        $sessionsNgroups = ["sessionid" => [], "groupid" => []];
        $statuses = $this->getStatuses($attendanceId);

        foreach ($studentMap as $group) {
            $groupId = $group[0]->getGroupId();

            if (isset($existingSessionMap[$groupId])) {
                $sessionId = $existingSessionMap[$groupId];
                $this->markSessions([$sessionId]);

                $startTime = $group[0]->getStartTime();
                $endTime = $group[0]->getEndTime();
                $duration = $endTime - $startTime;

                $DB->update_record('attendance_sessions', [
                    'id' => $sessionId,
                    'description' => $this->descriptionBot,
                    'sessdate' => $startTime,
                    'duration' => $duration
                ]);
            } else {
                $sessionId = $this->getNewSession($group[0], $attendanceId);
            }

            if (!$sessionId) continue;

            $sessionsNgroups["sessionid"][] = $sessionId;
            $sessionsNgroups["groupid"][] = $groupId;

            $idBot = getInstanceByModuleName('attendancebot', $this->courseId);
            $botConfig = $DB->get_record('attendancebot', ['id' => $idBot], '*', MUST_EXIST);

            $requiredType = $botConfig->required_type;
            $minPercentage = (int)$botConfig->min_percentage;
            $minDuration = (int)$botConfig->min_required_minutes;
            $this->checkCamera = (bool)$botConfig->camera;

            foreach ($group as $student) {
                $statusId = $statuses->getAbscent();
                $requisitos = false;

                if ($requiredType === 'percentage') {
                    $requisitos = $student->getAttendancePercentage() >= $minPercentage;
                } elseif ($requiredType === 'time') {
                    $requisitos = $student->getDuration() >= $minDuration;
                }

                if ($requisitos) {
                    if ($this->checkCamera) {
                        if ($student->hasVideo()) {
                            $statusId = $student->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                        }
                    } else {
                        $statusId = $student->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                    }
                }

                $attendanceLog = new AttendanceLog($student->getUserId(), $sessionId, $statuses->getAll(), $statusId);
                $inserts[] = $attendanceLog;
                mtrace("Estudiante {$student->getUserId()} - Status asignado: $statusId");
            }
        }

        $DB->insert_records('attendance_log', $inserts);
        return $sessionsNgroups;
    }

    private function insertTeachers($teachers, $attendanceId, $existingSessionMap) {
        global $DB;
        $statuses = $this->getStatuses($attendanceId);
        $inserts = [];

        $idBot = getInstanceByModuleName('attendancebot', $this->courseId);
        $config = $DB->get_record('attendancebot', ['id' => $idBot], '*', MUST_EXIST);
        $this->checkCamera = (bool)$config->camera;

        foreach ($teachers as $teacher) {
            $userId = $teacher->getUserId();
            $groupId = $teacher->getGroupId();
            $sessionId = $existingSessionMap[$groupId] ?? null;

            if (!$sessionId) continue;

            $statusId = $statuses->getAbscent();
            $requisitos = false;

            if ($config->required_type === 'percentage') {
                $requisitos = $teacher->getAttendancePercentage() >= $config->min_percentage;
            } elseif ($config->required_type === 'time') {
                $requisitos = $teacher->getDuration() >= $config->min_required_minutes;
            }

            if ($requisitos) {
                if ($this->checkCamera) {
                    if ($teacher->hasVideo()) {
                        $statusId = $teacher->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                    }
                } else {
                    $statusId = $teacher->getIsLate() ? $statuses->getLate() : $statuses->getPresent();
                }
            }

            $attendanceLog = new AttendanceLog($userId, $sessionId, $statuses->getAll(), $statusId);
            $inserts[] = $attendanceLog;
        }

        if (!empty($inserts)) {
            $DB->insert_records('attendance_log', $inserts);
        }
    }

    private function getNewSession($student, $attendanceId) {
        global $DB;
        $attendanceExternal = new mod_attendance_external();
        $startTime = $student->getStartTime();
        $endTime = $student->getEndTime();
        $duration = $endTime - $startTime;

        $params = [
            'sessionid' => 0,
            'groupid' => $student->getGroupId(),
            'sessdate' => $startTime,
            'duration' => $duration,
            'description' => $this->descriptionBot,
            'timemodified' => time(),
            'studentscanmark' => 0,
            'teacher' => 0,
            'subnet' => '',
            'automark' => 0,
            'automarkcompleted' => 1,
            'statusset' => 0,
        ];

        $result = $attendanceExternal->add_session($attendanceId, $params);
        return $result['sessionid'] ?? false;
    }

    private function markAbsentStudents($sessionsNgroups, $studentMap) {
        $absents = [];

        for ($i = 0; $i < count($sessionsNgroups["groupid"]); $i++) {
            $groupId = $sessionsNgroups["groupid"][$i];
            $sessionId = $sessionsNgroups["sessionid"][$i];

            if (!isset($studentMap[$groupId])) continue;

            foreach ($studentMap[$groupId] as $student) {
                if ($student->getAttendancePercentage() < 1) {
                    $absents[] = [
                        'student_id' => $student->getUserId(),
                        'session_id' => $sessionId,
                        'group_id' => $groupId,
                    ];
                }
            }
        }

        return $absents;
    }

    private function markSessions($sessionIds) {
        global $DB;
        list($inSql, $inParams) = $DB->get_in_or_equal($sessionIds, SQL_PARAMS_NAMED);
        $DB->set_field_select('attendance_sessions', 'description', $this->descriptionBot, "id $inSql", $inParams);
    }

    private function getStatuses($attendanceId): Statuses {
        return new Statuses($attendanceId);
    }

    public function validate_and_fix_sessions() {
        global $DB;

        $idAttendance = getInstanceByModuleName('attendance', $this->courseId);
        $sessions = $DB->get_records('attendance_sessions', ['attendanceid' => $idAttendance]);

        foreach ($sessions as $session) {
            if ($session->description !== $this->descriptionBot) continue;

            $sessionLogs = $DB->get_records('attendance_log', ['sessionid' => $session->id]);

            if (empty($sessionLogs)) {
                mtrace("Empty logs found for session {$session->id}, deleting...");
                $DB->delete_records('attendance_sessions', ['id' => $session->id]);
            } else {
                foreach ($sessionLogs as $log) {
                    if (!isset($log->statusid)) {
                        mtrace("Invalid log for session {$session->id} - user {$log->userid}");
                        $DB->delete_records('attendance_log', ['id' => $log->id]);
                    }
                }
            }
        }
    }

    public function correctHistoricalSessions() {
        global $DB;

        $idAttendance = getInstanceByModuleName('attendance', $this->courseId);
        $sessions = $DB->get_records('attendance_sessions', ['attendanceid' => $idAttendance]);

        foreach ($sessions as $session) {
            if ($session->description !== $this->descriptionBot) continue;
            if ($session->duration && $session->duration >= 60) continue;

            $zoomData = (new zoomRecollector())->getSessionZoomData($this->courseId, $session->sessdate);

            if (!empty($zoomData['start_time']) && !empty($zoomData['end_time'])) {
                $duration = $zoomData['end_time'] - $zoomData['start_time'];
                if ($duration > 60) {
                    $DB->update_record('attendance_sessions', [
                        'id' => $session->id,
                        'sessdate' => $zoomData['start_time'],
                        'duration' => $duration
                    ]);
                    mtrace("Updated session {$session->id} with duration $duration");
                }
            }
        }
    }
}
