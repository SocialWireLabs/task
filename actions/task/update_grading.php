<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/engine/start.php');

// Get input data
$user_guid = $_POST['user_guid'];
$taskpost = $_POST['taskpost'];
$grading = $_POST['grading'];

$user = get_entity($user_guid);
$task = get_entity($taskpost);
$container_guid = $task->container_guid;
$container = get_entity($container_guid);

if (!$task->subgroups) {
    $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
} else {
    $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $user_guid);
}

$user_responses = elgg_get_entities_from_relationship($options);

if (!empty($user_responses)) {
    $user_response = $user_responses[0];
} else {
    // Initialise a new ElggObject to be the answer (offline task)
    $now = time();
    $user_response = new ElggObject();
    $user_response->subtype = "task_answer";
    $user_response->owner_guid = elgg_get_logged_in_user_guid();
    // siempre no visibles
    if ($task->subgroups) {
        //user es el subgrupo
        $user_response->access_id = $user->teachers_acl;
        $user_response->container_guid = $user_guid;
        $user_response->who_answers = 'subgroup';
    } else {
        $user_response->access_id = $container->teachers_acl;
        $user_response->container_guid = $container_guid;
        $user_response->who_answers = 'member';
    }
    $user_response->save();
    $user_response->answer_time = $now;
    $user_response->content = "offline";
    $user_response->comments = "not_comments";
    $user_response->teacher_comments = 'not_teacher_comments';
    $access = elgg_set_ignore_access(true);
    $user_response->owner_guid = $user_guid;
    $user_response->save();
    elgg_set_ignore_access($access);
    add_entity_relationship($taskpost, 'task_answer', $user_response->getGUID());
    $task->annotate('all_responses', "1", $task->access_id);
}

if (($grading >= 0) && (strcmp($grading, "") != 0)) {

    if (strcmp($task->type_grading, "task_type_grading_marks") != 0) {
        $grading = round($grading);
    }
    $access = elgg_set_ignore_access(true);
    $user_response->grading = $grading;
    elgg_set_ignore_access($access);

    //Notify
    //$site_guid = elgg_get_config('site_guid');
    //$username = $user->name;
    //$subject = sprintf(elgg_echo('task:qualify:user:email:subject'),$task->title);
    //$body = sprintf(elgg_echo('task:qualify:user:email:body'),$username,$task->title);
    //notify_user($user_guid,$site_guid,$subject,$body);

} else {
    $access = elgg_set_ignore_access(true);
    $user_response->grading = "not_qualified";
    $access = elgg_set_ignore_access(true);
}

?>
