<?php

$group = elgg_get_page_owner_entity();
$group_guid = $group->getGUID();
if ($group->task_enable == "no") {
    return true;
}

elgg_push_context('widgets');
$options = array('type' => 'object', 'subtype' => 'task', 'container_guid' => $group_guid, 'limit' => 6, 'full_view' => false, 'pagination' => false);
$content = elgg_list_entities($options);
elgg_pop_context();

if (!$content) {
    $content = '<p>' . elgg_echo('task:none') . '</p>';
}

$all_link = elgg_view('output/url', array('href' => "task/group/$group_guid/", 'text' => elgg_echo('link:view:all'), 'is_trusted' => true));

$new_link = elgg_view('output/url', array('href' => "task/add/$group_guid", 'text' => elgg_echo('task:add'), 'is_trusted' => true));

echo elgg_view('groups/profile/module', array('title' => elgg_echo('task:group'), 'content' => $content, 'all_link' => $all_link, 'add_link' => $new_link));

?>
