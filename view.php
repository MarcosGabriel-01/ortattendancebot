<?php
/**
 * View page for attendancebot - Clean version
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'ortattendancebot');
$attendancebot = $DB->get_record('ortattendancebot', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/ortattendancebot:view', $context);

$PAGE->set_url('/mod/ortattendancebot/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($attendancebot->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

if ($action && confirm_sesskey()) {
    require_capability('moodle/course:manageactivities', $context);
    
    echo '<div class="alert alert-info">';
    
    try {
        $result = null;
        
        switch ($action) {
            case 'retroactive_fetch':
            case 'retroactivefetch':
                require_once(__DIR__ . '/classes/handlers/meeting_handler.php');
                $handler = new \mod_ortattendancebot\handlers\meeting_handler($attendancebot, $course);
                $result = $handler->fetch_retroactive();
                break;
                
            case 'queue_yesterday':
            case 'queueyesterday':
                require_once(__DIR__ . '/classes/handlers/meeting_handler.php');
                $handler = new \mod_ortattendancebot\handlers\meeting_handler($attendancebot, $course);
                $result = $handler->fetch_yesterday();
                break;
                
            case 'process_attendance':
            case 'processattendance':
                require_once(__DIR__ . '/classes/handlers/attendance_handler.php');
                $handler = new \mod_ortattendancebot\handlers\attendance_handler($attendancebot, $course);
                $result = $handler->process();
                break;
                
            case 'process_backup':
            case 'processbackup':
                require_once(__DIR__ . '/classes/handlers/backup_handler.php');
                $handler = new \mod_ortattendancebot\handlers\backup_handler($attendancebot, $course);
                $result = $handler->process();
                break;
                
            case 'clear_queue':
            case 'clearqueue':
                require_once(__DIR__ . '/classes/handlers/cleanup_handler.php');
                $handler = new \mod_ortattendancebot\handlers\cleanup_handler($attendancebot, $course);
                $result = $handler->clear_queue();
                break;
                
            case 'clear_attendance':
            case 'clearattendance':
                require_once(__DIR__ . '/classes/handlers/cleanup_handler.php');
                $handler = new \mod_ortattendancebot\handlers\cleanup_handler($attendancebot, $course);
                $result = $handler->clear_attendance();
                break;
        }
        
        
        if ($result) {
            echo '<h3>' . ucwords(str_replace('_', ' ', $result['action'])) . '</h3>';
            
            if (!empty($result['message'])) {
                echo '<p>' . htmlspecialchars($result['message']) . '</p>';
            }
            
            if (isset($result['meetings'])) {
                echo '<p>' . get_string('result_total_meetings', 'mod_ortattendancebot') . ': <strong>' . $result['total_meetings'] . '</strong></p>';
                if (!empty($result['meetings'])) {
                    echo '<table class="table table-striped"><thead><tr>';
                    echo '<th>' . get_string('table_meeting_id', 'mod_ortattendancebot') . '</th>';
                    echo '<th>' . get_string('table_topic', 'mod_ortattendancebot') . '</th>';
                    echo '<th>' . get_string('table_start_time', 'mod_ortattendancebot') . '</th>';
                    echo '</tr></thead><tbody>';
                    foreach ($result['meetings'] as $m) {
                        echo '<tr><td>' . htmlspecialchars($m['id']) . '</td><td>' . htmlspecialchars($m['topic']) . '</td><td>' . htmlspecialchars($m['start_time']) . '</td></tr>';
                    }
                    echo '</tbody></table>';
                }
            }
            
            if (isset($result['queued'])) {
                echo '<div class="alert alert-success">';
                echo '<p>✓ ' . get_string('result_queued', 'mod_ortattendancebot') . ': ' . $result['queued'] . '</p>';
                echo '<p>⚠ ' . get_string('result_already_queued', 'mod_ortattendancebot') . ': ' . $result['skipped'] . '</p>';
                if (isset($result['filtered_out'])) {
                    echo '<p>✗ ' . get_string('result_filtered_out', 'mod_ortattendancebot') . ': ' . $result['filtered_out'] . '</p>';
                }
                echo '</div>';
            }
            
            if (isset($result['output'])) {
                echo '<pre style="background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: auto;">';
                echo htmlspecialchars($result['output']);
                echo '</pre>';
            }
            
            if (isset($result['deleted'])) {
                echo '<div class="alert alert-success">✓ ' . get_string('result_deleted', 'mod_ortattendancebot') . ': ' . $result['deleted'] . ' items</div>';
            }
            
            if (isset($result['sessions_deleted'])) {
                echo '<div class="alert alert-success">';
                echo '<p>✓ ' . get_string('result_sessions_deleted', 'mod_ortattendancebot') . ': ' . $result['sessions_deleted'] . '</p>';
                echo '<p>✓ ' . get_string('result_logs_deleted', 'mod_ortattendancebot') . ': ' . $result['logs_deleted'] . '</p>';
                echo '</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<h4>' . get_string('error_general', 'mod_ortattendancebot') . '</h4>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
    echo '<a href="' . $PAGE->url . '" class="btn btn-primary">« ' . get_string('action_back', 'mod_ortattendancebot') . '</a>';
    echo '</div>';
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading($attendancebot->name);

if ($attendancebot->intro) {
    echo $OUTPUT->box(format_module_intro('ortattendancebot', $attendancebot, $cm->id), 'generalbox');
}

echo '<div class="card mb-3"><div class="card-body">';
echo '<h4>' . get_string('view_configuration', 'mod_ortattendancebot') . '</h4>';
echo '<p><strong>' . get_string('view_date_range', 'mod_ortattendancebot') . ':</strong> ' . userdate($attendancebot->start_date, '%Y-%m-%d') . ' to ' . userdate($attendancebot->end_date, '%Y-%m-%d') . '</p>';
echo '<p><strong>' . get_string('view_time_window', 'mod_ortattendancebot') . ':</strong> ' . gmdate('H:i', $attendancebot->start_time) . ' - ' . gmdate('H:i', $attendancebot->end_time) . ' UTC</p>';
echo '<p><strong>' . get_string('view_status', 'mod_ortattendancebot') . ':</strong> ' . ($attendancebot->enabled ? '<span class="badge badge-success">' . get_string('status_enabled', 'mod_ortattendancebot') . '</span>' : '<span class="badge badge-secondary">' . get_string('status_disabled', 'mod_ortattendancebot') . '</span>') . '</p>';
echo '</div></div>';

if (has_capability('moodle/course:manageactivities', $context)) {
    $sesskey = sesskey();
    
    echo '<div class="card mb-3"><div class="card-body">';
    echo '<h4>' . get_string('view_actions', 'mod_ortattendancebot') . '</h4>';
    echo '<div class="btn-group" role="group">';
    echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'retroactive_fetch', 'sesskey' => $sesskey]) . '" class="btn btn-primary">🔄 ' . get_string('action_fetch_all', 'mod_ortattendancebot') . '</a>';
    echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'queue_yesterday', 'sesskey' => $sesskey]) . '" class="btn btn-secondary">📥 ' . get_string('action_queue_yesterday', 'mod_ortattendancebot') . '</a>';
    echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'process_attendance', 'sesskey' => $sesskey]) . '" class="btn btn-success">✅ ' . get_string('action_process_attendance', 'mod_ortattendancebot') . '</a>';
    if ($attendancebot->backup_recordings) {
        echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'process_backup', 'sesskey' => $sesskey]) . '" class="btn btn-info">💾 ' . get_string('action_process_backup', 'mod_ortattendancebot') . '</a>';
    }
    echo '</div></div></div>';
    
    echo '<div class="card mb-3 border-warning"><div class="card-body">';
    echo '<h4 class="text-warning">⚠️ ' . get_string('view_testing_controls', 'mod_ortattendancebot') . '</h4>';
    echo '<p class="text-muted">' . get_string('view_testing_warning', 'mod_ortattendancebot') . '</p>';
    echo '<div class="btn-group" role="group">';
    echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'clear_queue', 'sesskey' => $sesskey]) . '" class="btn btn-warning" onclick="return confirm(\'' . get_string('confirm_clear_queue', 'mod_ortattendancebot') . '\');">🗑️ ' . get_string('action_clear_queue', 'mod_ortattendancebot') . '</a>';
    echo '<a href="' . new moodle_url($PAGE->url, ['action' => 'clear_attendance', 'sesskey' => $sesskey]) . '" class="btn btn-danger" onclick="return confirm(\'' . get_string('confirm_clear_attendance', 'mod_ortattendancebot') . '\');">🗑️ ' . get_string('action_clear_attendance', 'mod_ortattendancebot') . '</a>';
    echo '</div></div></div>';
}

require_once(__DIR__ . '/classes/services/queue_service.php');
$queue_service = new \mod_ortattendancebot\services\queue_service();
$queue_items = $queue_service->get_all($attendancebot->id);

echo '<div class="card mb-3"><div class="card-body">';
echo '<h4>' . get_string('view_attendance_queue', 'mod_ortattendancebot') . '</h4>';

if (empty($queue_items)) {
    echo '<p class="text-muted">' . get_string('view_no_queue', 'mod_ortattendancebot') . '</p>';
} else {
    echo '<p>' . get_string('view_found_meetings', 'mod_ortattendancebot', count($queue_items)) . '</p>';
    echo '<table class="table table-striped">';
    echo '<thead><tr>';
    echo '<th>' . get_string('table_meeting_id', 'mod_ortattendancebot') . '</th>';
    echo '<th>' . get_string('table_date', 'mod_ortattendancebot') . '</th>';
    echo '<th>' . get_string('table_status', 'mod_ortattendancebot') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($queue_items as $item) {
        $status = $item->processed ? '<span class="badge badge-success">✓ ' . get_string('status_processed', 'mod_ortattendancebot') . '</span>' : '<span class="badge badge-warning">⏳ ' . get_string('status_pending', 'mod_ortattendancebot') . '</span>';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($item->meeting_id) . '</td>';
        echo '<td>' . userdate($item->meeting_date, '%Y-%m-%d %H:%M') . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div></div>';

echo $OUTPUT->footer();
