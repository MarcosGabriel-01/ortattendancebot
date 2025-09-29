<?php

class RoleUtils  {


    public static function getAllowedTeacherRoles(): array  {
        return [3, 4, 9, 12, 14, 15];
        
    }

    public static function isTeacher(int $userId, int $courseId): bool  {

        global $DB;

        $roles = self::getAllowedTeacherRoles();
        list($inSql, $params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'role');
        $params['userid'] = $userId;
        $params['contextlevel'] = 50;
        $params['instanceid'] = $courseId;

        $sql = "SELECT 1
                FROM {role_assignments} ra
                JOIN {context} ctx ON ctx.id = ra.contextid
                WHERE ra.userid = :userid
                  AND ctx.contextlevel = :contextlevel
                  AND ctx.instanceid = :instanceid
                  AND ra.roleid $inSql";

        return $DB->record_exists_sql($sql, $params);

    }
}
