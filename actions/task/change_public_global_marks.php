<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    if ($task->public_global_marks) {
        $task->public_global_marks = false;
    } else {
        $task->public_global_marks = true;
    }

    //Forward
    forward($_SERVER['HTTP_REFERER']);
}

?>
