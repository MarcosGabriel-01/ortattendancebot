<?php
define('QUEUE_FILE', __DIR__ . '/courseQueue.json');


function get_course_queue(): array {
    if (!file_exists(QUEUE_FILE)) {
        file_put_contents(QUEUE_FILE, json_encode([]));
        return [];
    }
    $json = file_get_contents(QUEUE_FILE);
    $queue = json_decode($json, true);
    return is_array($queue) ? $queue : [];
}

function save_course_queue(array $queue): void {
    file_put_contents(QUEUE_FILE, json_encode(array_values($queue), JSON_PRETTY_PRINT));
}

function add_courses_to_queue(array $courseIds): void {
    $queue = get_course_queue();
    $queue = array_unique(array_merge($queue, $courseIds));
    save_course_queue($queue);
}

function pop_next_courses(int $limit = 5): array {
    $queue = get_course_queue();
    $toProcess = array_slice($queue, 0, $limit);
    $queue = array_slice($queue, $limit);
    save_course_queue($queue);
    return $toProcess;
}
