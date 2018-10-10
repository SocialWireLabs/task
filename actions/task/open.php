<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    $task->option_activate_value = 'task_activate_now';
    $task->opened = true;
    $task->action = true;

    //Event using the event_manager plugin if it is active
    if (elgg_is_active_plugin('event_manager') && strcmp($task->option_close_value, 'task_not_close') != 0) {

        $event_guid = $task->event_guid;
        if (!($event = get_entity($event_guid))) {
            $event = new Event();
        }

        $event->title = sprintf(elgg_echo("task:event_manager_title"), $task->title);
        $event->description = $task->input_question_html;
        $event->container_guid = $task->container_guid;
        $event->access_id = $task->access_id;
        $event->save();
        $event->tags = string_to_tag_array($tags);
        $event->comments_on = 0;
        $event->registration_ended = 1;
        $event->show_attendees = 0;
        $event->max_attendees = "";
        $event->start_day = $task->close_date;
        $event->start_time = $task->close_time;
        $event->end_ts = $task->close_time + 1;
        $event->organizer = elgg_get_logged_in_user_entity()->getDisplayName();
        $event->setAccessToOwningObjects($access_id);

        // Save it, if it is new
        if (!get_entity($event_guid)) {
            if ($event->save()) {
                $event_guid = $event->getGUID();
                $task->event_guid = $event_guid;
            } else
                register_error(elgg_echo("task:event_manager_error_save"));
        }
    }

    //System message 
    system_message(elgg_echo("task:opened_listing"));
    //Forward
    forward($_SERVER['HTTP_REFERER']);
}

?>
