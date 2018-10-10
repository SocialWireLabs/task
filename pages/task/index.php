<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$owner = elgg_get_page_owner_entity();
$user_guid = elgg_get_logged_in_user_guid();
$user = get_entity($user_guid);

$title = elgg_echo(sprintf(elgg_echo('task:user'), $owner->name));

$group_guid = $owner->getGUID();
$group_owner_guid = $owner->owner_guid;

$operator = false;
if (($group_owner_guid == $user_guid) || (check_entity_relationship($user_guid, 'group_admin', $group_guid))) {
    $operator = true;
}

if ($operator)
    elgg_register_title_button('task', 'add');

$tasks = elgg_get_entities(array('type' => 'object', 'subtype' => 'task', 'limit' => false, 'container_guid' => $owner->getGUID()));

if (!$operator) {
    $i = 0;
    $j = 0;
    $k = 0;
    $my_not_finished_tasks = array();
    $my_finished_tasks = array();
    $my_finished_qualified_tasks = array();
    $my_finished_qualified_tasks_grading = array();
    foreach ($tasks as $task) {
        $container_guid = $task->container_guid;
        $options = array('relationship' => 'task_answer', 'relationship_guid' => $task->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        if ($options)
            $user_responses = elgg_get_entities_from_relationship($options);
        if (!empty($user_responses)) {
            $found = true;
            $user_response = $user_responses[0];
            $grading = $user_response->grading;
        } else {
            $found = false;
            $user_response = "";
        }
        if ($found) {
            if (strcmp($grading, "not_qualified") == 0) {
                $my_finished_tasks[$j] = $task;
                $j = $j + 1;
            } else {
                $my_finished_qualified_tasks[$k] = $task;
                $my_finished_qualified_tasks_grading[$k] = $grading;
                $k = $k + 1;
            }
        } else {
            $my_not_finished_tasks[$i] = $task;
            $i = $i + 1;
        }
    }
    $num_not_finished_tasks = $i;
    $num_finished_tasks = $j;
    $num_finished_qualified_tasks = $k;
}

$content = "";

if ($operator) {
    foreach ($tasks as $task) {
        $content .= elgg_view("object/task", array('full_view' => false, 'entity' => $task, 'user_type' => "operator"));
    }
} else {
    $i = 0;
    while ($i < $num_not_finished_tasks) {
        $content .= elgg_view("object/task", array('full_view' => false, 'entity' => $my_not_finished_tasks[$i], 'user_type' => "not_finished"));
        $i = $i + 1;
    }
    $j = 0;
    while ($j < $num_finished_tasks) {
        $content .= elgg_view("object/task", array('full_view' => false, 'entity' => $my_finished_tasks[$j], 'user_type' => "finished"));
        $j = $j + 1;
    }
    $k = 0;
    while ($k < $num_finished_qualified_tasks) {
        $content .= elgg_view("object/task", array('full_view' => false, 'entity' => $my_finished_qualified_tasks[$k], 'user_type' => "finished_qualified", 'grading' => $my_finished_qualified_tasks_grading[$k]));
        $k = $k + 1;
    }
}

$params = array('content' => $content, 'title' => $title);

if (elgg_instanceof($owner, 'group')) {
    $params['filter'] = '';
}

$body = elgg_view_layout('content', $params);
echo elgg_view_page($title, $body);

?>