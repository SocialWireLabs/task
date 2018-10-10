<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task" && $task->canEdit()) {

    if ($task->responses_visibility) {
        $task->responses_visibility = false;
    } else {
        $task->responses_visibility = true;
    }

    $container_guid = $task->container_guid;
    $container = get_entity($container_guid);

    //Change access_id in answers
    $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'limit' => 0);
    $users_responses = elgg_get_entities_from_relationship($options);
    foreach ($users_responses as $one_response) {
        if ($task->subgroups) {
            $response_subgroup_guid = $one_response->container_guid;
            $response_subgroup = get_entity($response_subgroup_guid);
        }
        if (strcmp($task->type_delivery, 'online') == 0) {
            $files_response = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $one_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0));
            foreach ($files_response as $one_file) {
                if (!$task->responses_visibility) {
                    //compañeros y profesores
                    if ($task->subgroups)
                        $one_file->access_id = $response_subgroup->teachers_acl;
                    else
                        $one_file->access_id = $container->teachers_acl;
                } else {
                    $one_file->access_id = $task->access_id;
                }
                if (!$one_file->save()) {
                    register_error(elgg_echo('task:file_error_save'));
                    forward($_SERVER['HTTP_REFERER']);
                }
            }
        }
        if (!$task->responses_visibility) {
            //compañeros y profesores
            if ($task->subgroups)
                $one_response->access_id = $response_subgroup->teachers_acl;
            else
                $one_response->access_id = $container->teachers_acl;
        } else {
            $one_response->access_id = $task->access_id;
        }
        if (!$one_response->save()) {
            register_error(elgg_echo('task:answer_error_save'));
            forward($_SERVER['HTTP_REFERER']);
        }
    }

    //Forward
    forward($_SERVER['HTTP_REFERER']);
}

?>
