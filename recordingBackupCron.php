<?php
require_once(__DIR__ . '/recordingBackup.php');  // Orchestrator
require_once(__DIR__ . '/courseQueue.php');      // Queue manager

// Get next 5 courses from the queue
$processingCourses = pop_next_courses(5);

if (!empty($processingCourses)) {
    foreach ($processingCourses as $courseid) {
        recording_backup_run($courseid);
    }
} else {
    echo "⚠️ No courses to process today.\n";
}
