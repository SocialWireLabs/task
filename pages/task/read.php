<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$taskpost = get_input('guid');
$task = get_entity($taskpost);
$offset = get_input('offset');
if (empty($offset))
    $offset = 0;
$edit_response = get_input('edit_response');

if ($task) {
    $container_guid = $task->container_guid;
    $container = get_entity($container_guid);
    elgg_set_page_owner_guid($container_guid);

    elgg_push_breadcrumb($container->name, "task/group/$container_guid");
    elgg_push_breadcrumb($task->title);

    $owner = $task->getOwnerEntity();
    $owner_guid = $owner->getGUID();
    $user_guid = elgg_get_logged_in_user_guid();
    $user = get_entity($user_guid);
    $group_guid = $task->container_guid;
    $group = get_entity($group_guid);
    $group_owner_guid = $group->owner_guid;

    $operator = false;
    if (($owner_guid == $user_guid) || ($group_owner_guid == $user_guid) || ((elgg_is_active_plugin('group_tools')) && (check_entity_relationship($user_guid, 'group_admin', $group_guid)))) {
        $operator = true;
    }

    if (!$operator) {
        //$title = elgg_echo('task:answerpost');
        $title = elgg_echo('task:response_label');
    } else {
        $title = elgg_echo('task:qualifypost');
    }

    $content = elgg_view("object/task", array('full_view' => true, 'entity' => $task, 'edit_response' => $edit_response, 'entity_owner' => $container, 'offset' => $offset, 'user_guid' => $user_guid));
    $body = elgg_view_layout('content', array('filter' => '', 'content' => $content, 'title' => $title));
    echo elgg_view_page($task->title, $body);

} else {
    register_error(elgg_echo('task:notfound'));
    forward();
}


?>
