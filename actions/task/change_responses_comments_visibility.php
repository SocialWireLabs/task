<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    if ($task->responses_comments_visibility) {
        $task->responses_comments_visibility = false;
    } else {
        $task->responses_comments_visibility = true;
    }

    //Forward
    forward($_SERVER['HTTP_REFERER']);
}

?>
