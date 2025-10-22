<?php
require_once(__DIR__ . '/recordingBackup.php');
require_once(__DIR__ . '/courseQueue.php');

$processingCourses = pop_next_courses(5);

if (!empty($processingCourses)) {
    foreach ($processingCourses as $courseid) {
        recording_backup_run($courseid);
    }
} else {
    echo "⚠️ No courses to process today.\n";
}
