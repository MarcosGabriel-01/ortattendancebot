<?php
/**
 * Plugin version and other meta-data
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_ortattendancebot';
$plugin->version = 2025102902;
$plugin->requires = 2022112800;
$plugin->release = '1.0.0';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
    'mod_attendance' => ANY_VERSION,
];
