<?php

gatekeeper();
if (is_callable('group_gatekeeper'))
    group_gatekeeper();

$container_guid = (int)get_input('container_guid');
$container = get_entity($container_guid);

$question_type = get_input('question_type');
if (empty($question_type))
    $question_type = "simple";

elgg_set_page_owner_guid($container_guid);

elgg_push_breadcrumb(elgg_echo('add'));

$title = elgg_echo('task:addpost');
$content = elgg_view("forms/task/add", array('container_guid' => $container_guid, 'question_type' => $question_type));
$body = elgg_view_layout('content', array('filter' => '', 'content' => $content, 'title' => $title));
echo elgg_view_page($title, $body);

?>