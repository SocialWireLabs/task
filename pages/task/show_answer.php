<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task) {
    $user_guid = get_input('user_guid');
    $answer_time_user_response = get_input('answer_time_user_response');
    $offset = get_input('offset');
    if (empty($offset))
        $offset = 0;

    $container_guid = $task->container_guid;
    $container = get_entity($container_guid);
    elgg_set_page_owner_guid($container_guid);

    elgg_push_breadcrumb($container->name, "task/group/$container_guid");
    elgg_push_breadcrumb($task->title);

    $title = elgg_echo('task:answerpost');
    $content = elgg_view("forms/task/show_answer", array('entity' => $task, 'user_guid' => $user_guid, 'answer_time_user_response' => $answer_time_user_response, 'offset' => $offset));
    $body = elgg_view_layout('content', array('filter' => '', 'content' => $content, 'title' => $title));
}
echo elgg_view_page($task->title, $body);

?>
