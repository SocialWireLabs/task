<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task && $task->canEdit()) {
    $user_guid = get_input('user_guid');
    $answer_time_user_response = get_input('answer_time_user_response');
    $offset = get_input('offset');

    $user = get_entity($user_guid);

    $container_guid = $task->container_guid;
    $container = get_entity($container_guid);
    elgg_set_page_owner_guid($container_guid);

    elgg_push_breadcrumb($container->name, "task/group/$container_guid");
    elgg_push_breadcrumb($task->title, $task->getURL() . "?offset=" . $offset);

    //$title = elgg_echo('task:qualifypost');

    $title = elgg_echo('task:response_label');

    $content = elgg_view("forms/task/qualify", array('entity' => $task, 'user_guid' => $user_guid, 'answer_time_user_response' => $answer_time_user_response, 'offset' => $offset));
    $body = elgg_view_layout('content', array('filter' => '', 'content' => $content, 'title' => $title));
}
echo elgg_view_page($title, $body);

?>
