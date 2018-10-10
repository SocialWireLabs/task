<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);
$user_guid = elgg_get_logged_in_user_guid();
$user = get_entity($user_guid);

$container_guid = $task->container_guid;
$container = get_entity($container_guid);

elgg_set_page_owner_guid($container_guid);

elgg_push_breadcrumb($task->title, $task->getURL());
elgg_push_breadcrumb(elgg_echo('edit'));

if ($task && $task->canEdit()) {
    $title = elgg_echo('task:editpost');
    $content = elgg_view("forms/task/edit", array('entity' => $task));
}

$body = elgg_view_layout('content', array('filter' => '', 'content' => $content, 'title' => $title));
echo elgg_view_page($title, $body);

?>