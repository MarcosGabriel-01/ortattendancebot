<?php
$tasks = array(
    array(
        'classname' => 'mod_attendancebot\task\scheduler_task', // The class that defines the task to be executed.
        'blocking' => 0, // Set to 1 to make this task block other tasks from running simultaneously.
        'minute' => '0', // Run at the 0th minute.
        'hour' => '1', // Run at 1am.
        'day' => '*', // Run every day of the month.
        'month' => '*', // Run every month.
        'dayofweek' => '*', // Run every day of the week.
    ),
);
?>