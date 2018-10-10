<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    if ($task->grading_visibility) {
        $task->grading_visibility = false;
    } else {
        $task->grading_visibility = true;
    }

    //Forward
    forward($_SERVER['HTTP_REFERER']);
}

?>
