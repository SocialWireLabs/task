<?php

gatekeeper();

$taskpost = get_input('taskpost');
$user_guid = elgg_get_logged_in_user_guid();

$task = get_entity($taskpost);
$container_guid = $task->container_guid;
$container = get_entity($container_guid);

$owner = $task->getOwnerEntity();
$owner_guid = $owner->getGUID();
$group_guid = $container_guid;
$group = get_entity($group_guid);
$group_owner_guid = $group->owner_guid;

if (!$task->subgroups) {
    $members = $group->getMembers(array('limit' => false));
} else {
    $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
}

$i = 0;
$membersarray = array();
foreach ($members as $member) {
    $member_guid = $member->getGUID();
    if (($member_guid != $owner_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
        $membersarray[$i] = $member_guid;
        $i = $i + 1;
    }
}

foreach ($membersarray as $member_guid) {
    if (!$task->subgroups) {
        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $member_guid);
    } else {
        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $member_guid);
    }
    $user_responses = elgg_get_entities_from_relationship($options);
    $user_response = $user_responses[0];

    if (!empty($user_response)) {
        $access = elgg_set_ignore_access(true);
        $game_points = gamepoints_get_entity($user_response->guid);
        if ($game_points) {
            if (strcmp($user_response->grading, "not_qualified") != 0) {
                gamepoints_update($game_points->guid, $user_response->grading);
            } else {
                gamepoints_update($game_points->guid, "");
            }
        } else {
            if (strcmp($user_response->grading, "not_qualified") != 0) {
                $description = $task->title;
                gamepoints_add($member_guid, $user_response->grading, $user_response->guid, $container_guid, $task->subgroups, $description);
            }
        }
        elgg_set_ignore_access($access);
    }
}


//System message
system_message(elgg_echo("task:game_points_assigned"));
//Forward
forward("task/view/$taskpost");

?>