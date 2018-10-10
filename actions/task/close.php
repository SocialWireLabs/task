<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);
$edit = get_input('edit');

if ($task->getSubtype() == "task" && $task->canEdit()) {

    $task->option_close_value = 'task_not_close';
    $task->opened = false;
    $task->action = true;

    if (elgg_is_active_plugin('event_manager')) {
        $event_guid = $task->event_guid;
        if ($event = get_entity($event_guid)) {
            $deleted = $event->delete();
            if (!$deleted) {
                register_error(elgg_echo("task:eventmanagernotdeleted"));
                forward(elgg_get_site_url() . 'task/group/' . $container_guid);
            }
        }
    }

    //System message 
    system_message(elgg_echo("task:closed_listing"));
    //Forward
    if (strcmp($edit, 'no') == 0) {
        forward($_SERVER['HTTP_REFERER']);
    } else {
        forward("task/edit/$taskpost");
    }
}

?>
