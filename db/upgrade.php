<?php
/**
 * Upgrade steps for mod_ortattendancebot
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_ortattendancebot_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025102902) {
        
        
        $table = new xmldb_table('ortattendancebot');
        
        $field = new xmldb_field('backup_recordings', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'end_time');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('recordings_path', XMLDB_TYPE_TEXT, null, null, null, null, null, 'backup_recordings');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('delete_source', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'recordings_path');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        
        $table = new xmldb_table('ortattendancebot_backup_queue');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attendancebotid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('meeting_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('meeting_name', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('recording_id', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('recording_url', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('file_size', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('backed_up', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('last_attempt', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('local_path', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('moodle_file_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_ortattendancebot', XMLDB_KEY_FOREIGN, ['attendancebotid'], 'ortattendancebot', ['id']);
        
        $table->add_index('idx_attendancebotid', XMLDB_INDEX_NOTUNIQUE, ['attendancebotid']);
        $table->add_index('idx_backed_up', XMLDB_INDEX_NOTUNIQUE, ['backed_up']);
        $table->add_index('idx_meeting_id', XMLDB_INDEX_NOTUNIQUE, ['meeting_id']);
        $table->add_index('idx_attempts', XMLDB_INDEX_NOTUNIQUE, ['attempts']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        
        $table = new xmldb_table('ortattendancebot_cleanup_queue');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attendancebotid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('meeting_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recording_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_ortattendancebot', XMLDB_KEY_FOREIGN, ['attendancebotid'], 'ortattendancebot', ['id']);
        
        $table->add_index('idx_deleted', XMLDB_INDEX_NOTUNIQUE, ['deleted']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_mod_savepoint(true, 2025102902, 'ortattendancebot');
    }

    return true;
}
