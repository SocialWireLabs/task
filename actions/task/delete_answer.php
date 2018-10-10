<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

$this_user_guid = elgg_get_logged_in_user_guid();

if ($task->getSubtype() == "task") {
    $user_guid = get_input('user_guid');
    $user = get_entity($user_guid);
    $offset = get_input('offset');

    $opened = task_check_status($task);

    $owner = $task->getOwnerEntity();
    $group_guid = $task->container_guid;
    $group = get_entity($group_guid);
    $group_owner_guid = $group->owner_guid;

    $operator = false;
    if (($owner_guid == $this_user_guid) || ($group_owner_guid == $this_user_guid) || (check_entity_relationship($this_user_guid, 'group_admin', $group_guid))) {
        $operator = true;
    }

    if ((($opened) && (!$operator)) || ((!$opened) && ($operator))) {

        //Answer
        if (!$task->subgroups) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $user_guid);
        }
        $user_responses = elgg_get_entities_from_relationship($options);
        if (!empty($user_responses)) {
            $user_response = $user_responses[0];
            if (strcmp($user_response->grading, "not_qualified") == 0) {
                if (strcmp($task->type_delivery, 'online') == 0) {
                    $files_response = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0));
                    foreach ($files_response as $one_file) {
                        $deleted = $one_file->delete();
                        if (!$deleted) {
                            register_error(elgg_echo("task:filenotdeleted"));
                            if (empty($offset))
                                forward("task/view/$taskpost/");
                            else
                                forward("task/view/$taskpost/?offset=$offset");
                        }
                    }
                }
                $deleted = $user_response->delete();
                if (!$deleted) {
                    register_error(elgg_echo("task:answernotdeleted"));
                    if (empty($offset))
                        forward("task/view/$taskpost/");
                    else
                        forward("task/view/$taskpost/?offset=$offset");
                }
                //System message
                system_message(elgg_echo("task:answerdeleted"));
            } else {
                register_error(elgg_echo("task:response_qualified"));
            }
        }
    } else {
        if ($opened)
            register_error(elgg_echo("task:opened"));
        else
            register_error(elgg_echo("task:closed"));
    }
    //Forward
    if (empty($offset))
        forward("task/view/$taskpost/");
    else
        forward("task/view/$taskpost/?offset=$offset");
}

?>
