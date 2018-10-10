<?php

gatekeeper();

$taskpost = get_input('guid');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    $container_guid = $task->container_guid;
    $container = get_entity($container_guid);
    $owner = get_entity($task->getOwnerGUID());
    $owner_guid = $owner->getGUID();

    //Delete question files
    if (strcmp($task->type_delivery, 'online') == 0) {
        $files = elgg_get_entities_from_relationship(array('relationship' => 'question_file_link', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0));
        foreach ($files as $one_file) {
            $deleted = $one_file->delete();
            if (!$deleted) {
                register_error(elgg_echo("task:filenotdeleted"));
                forward(elgg_get_site_url() . 'task/group/' . $container_guid);
            }
        }
    }
    //Delete answers
    $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'limit' => 0);
    $users_responses = elgg_get_entities_from_relationship($options);
    foreach ($users_responses as $one_response) {
        $one_response_guid = $one_response->getGUID();
        if (strcmp($task->type_delivery, 'online') == 0) {
            $files_response = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $one_response_guid, 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0));
            foreach ($files_response as $one_file) {
                $deleted = $one_file->delete();
                if (!$deleted) {
                    register_error(elgg_echo("task:filenotdeleted"));
                    forward(elgg_get_site_url() . 'task/group/' . $container_guid);
                }
            }
            if ($task->task_rubric) {
                $rubric_rates = elgg_get_entities_from_metadata(array('type' => 'object', 'subtype' => 'rubric_rating', 'limit' => 0, 'metadata_name_value_pairs' => array('name' => 'task_guid', 'value' => $one_response_guid)));
                $rubric_rate = $rubric_rates[0];
                $deleted = $rubric_rate->delete();
                if (!$deleted) {
                    register_error(elgg_echo("task:ratingrubricnotdeleted"));
                    forward(elgg_get_site_url() . 'task/group/' . $container_guid);
                }
            }
        }

        if (strcmp($task->type_grading, 'task_type_grading_game_points')) {
            $access = elgg_set_ignore_access(true);
            $game_points = gamepoints_get_entity($one_response_guid);
            if ($game_points) {
                $deleted = $game_points->delete();
                if (!$deleted) {
                    register_error(elgg_echo("task:gamepointsnotdeleted"));
                    forward(elgg_get_site_url() . 'task/group/' . $container_guid);
                }
            }
            elgg_set_ignore_access($access);
        }

        $deleted = $one_response->delete();
        if (!$deleted) {
            register_error(elgg_echo("task:answernotdeleted"));
            forward(elgg_get_site_url() . 'task/group/' . $container_guid);
        }
    }

    // Delete the event created with the task (if event_manager plugin)
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


    if (($task->task_rubric) && (strcmp($task->type_delivery, 'online') != 0)) {
        $rubric_rates = elgg_get_entities_from_metadata(array('type' => 'object', 'subtype' => 'rubric_rating', 'limit' => 0, 'metadata_name_value_pairs' => array('name' => 'task_guid', 'value' => $taskpost)));
        foreach ($rubric_rates as $rubric_rate) {
            $deleted = $rubric_rate->delete();
            if (!$deleted) {
                register_error(elgg_echo("task:ratingrubricnotdeleted"));
                forward(elgg_get_site_url() . 'task/group/' . $container_guid);
            }
        }
    }

    if (strcmp($task->type_grading, 'task_type_grading_marks')) {
        $access = elgg_set_ignore_access(true);
        $marks = socialwire_marks_get_marks(null, null, $taskpost);
        foreach ($marks as $mark) {
            $deleted = $mark->delete();
            if (!$deleted) {
                register_error(elgg_echo("task:marknotdeleted"));
                forward(elgg_get_site_url() . 'task/group/' . $container_guid);
            }
        }
        elgg_set_ignore_access($access);
    }

    // Delete it!
    $deleted = $task->delete();
    if ($deleted > 0) {
        system_message(elgg_echo("task:deleted"));
    } else {
        register_error(elgg_echo("task:notdeleted"));
    }
    forward(elgg_get_site_url() . 'task/group/' . $container_guid);
}

?>