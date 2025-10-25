<?php
/**
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_attendancebot
 * @category    upgrade
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

function xmldb_attendancebot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // --- Existing upgrade step ---
    if ($oldversion < 2025092201) {
        $table = new xmldb_table('attendancebot');

        $field = new xmldb_field('camera', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('backuprecordings', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025092201, 'attendancebot');
    }

    // --- 🔹 NEW UPGRADE STEP FOR delete_source ---
    if ($oldversion < 2025102300) { // ⬅️ use today's date + 00 as a new version number
        $table = new xmldb_table('attendancebot');

        // Add the new field after backuprecordings
        $field = new xmldb_field('delete_source', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'backuprecordings');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mark the savepoint to avoid re-running this on future upgrades
        upgrade_mod_savepoint(true, 2025102300, 'attendancebot');
    }

    return true;
}

