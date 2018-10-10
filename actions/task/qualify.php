<?php

gatekeeper();
elgg_load_library('task');

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task") {

    $container_guid = $task->container_guid;
    $user_guid = get_input('user_guid');
    $answer_time_user_response = get_input('answer_time_user_response');
    $container = get_entity($container_guid);
    $user = get_entity($user_guid);
    $offset = get_input('offset');

    // Cache to the session
    elgg_make_sticky_form('qualify_task');

    $good_grading = true;

    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (strcmp($task->type_mark, 'task_type_mark_numerical') == 0) {
            $max_grading = $task->max_mark;
        } else {
            $max_grading = 10;
        }
    } elseif ((strcmp($task->type_grading, 'task_type_grading_marks') != 0) && ($task->task_rubric)) {
        $max_grading = $task->max_game_points;
    }

    //Answers
    $user_response = "";
    if (!$task->subgroups) {
        if ($answer_time_user_response == 0) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid, 'metadata_name_value_pairs' => array('name' => 'answer_time', 'value' => $answer_time_user_response));
        }
    } else {
        if ($answer_time_user_response == 0) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $user_guid, 'metadata_name_value_pairs' => array('name' => 'answer_time', 'value' => $answer_time_user_response));
        }
    }
    $user_responses = elgg_get_entities_from_relationship($options);
    if (!empty($user_responses)) {
        $user_response = $user_responses[0];
    } else {
        // Initialise a new ElggObject to be the answer (offline task)
        $user_response = new ElggObject();
        $user_response->subtype = "task_answer";
        $user_response->owner_guid = elgg_get_logged_in_user_guid();
        ///////////////////////////////////////////////////////////
        // siempre no visibles
        if ($task->subgroups) {
            //user es el subgrupo
            $user_response->access_id = $user->teachers_acl;
        } else {
            $user_response->access_id = $container->teachers_acl;
        }
        ///////////////////////////////////////////////////////////
        if ($task->subgroups) {
            //user es el subgrupo
            $user_response->container_guid = $user_guid;
            $user_response->who_answers = 'subgroup';
        } else {
            $user_response->container_guid = $container_guid;
            $user_response->who_answers = 'member';
        }
        if (!$user_response->save()) {
            register_error(elgg_echo("task:answer_error_save"));
            forward($_SERVER['HTTP_REFERER']);
        }
        $user_response->answer_time = time();
        $user_response->content = "offline";
        $user_response->comments = "not_comments";
        $user_response->grading = 'not_qualified';
        $user_response->teacher_comments = 'not_teacher_comments';
        $access = elgg_set_ignore_access(true);
        $user_response->owner_guid = $user_guid;
        if (!$user_response->save()) {
            register_error(elgg_echo("task:answer_error_save"));
            forward($_SERVER['HTTP_REFERER']);
        }
        elgg_set_ignore_access($access);
        add_entity_relationship($taskpost, 'task_answer', $user_response->getGUID());
        $task->annotate('all_responses', "1", $task->access_id);

    }

    $user_response_guid = $user_response->getGUID();

    if (!$task->task_rubric) {
        $this_grading = get_input('grading');
        $this_grading = str_replace(",", ".", $this_grading);
        if (strcmp($this_grading, "") == 0) {
            $this_grading = "not_qualified";
        } else {
            if (($this_grading == -1) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') != 0)) {
                $this_grading = "not_qualified";
            } else {
                if ($this_grading < 0) {
                    $good_grading = false;
                } else {
                    $is_number = true;
                    if ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) {
                        $is_number = is_numeric($this_grading);
                    } elseif (strcmp($task->type_grading, 'task_type_grading_marks') != 0) {
                        $mask_integer = '^([[:digit:]]+)$';
                        if (ereg($mask_integer, $this_grading, $same)) {
                            if ((substr($same[1], 0, 1) == 0) && (strlen($same[1]) != 1)) {
                                $is_number = false;
                            }
                        } else {
                            $is_number = false;
                        }
                    }
                    if (!$is_number) {
                        $good_grading = false;
                    } else {
                        if (((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) && ($this_grading > $max_grading)) {
                            $good_grading = false;
                        }
                    }
                }
            }
        }
    } else {
        if (strcmp($task->type_delivery, 'online') == 0) {
            $task_guid = $user_response_guid;
        } else {
            $task_guid = $taskpost;
        }
        $rating = socialwire_rubric_get_rating($user_guid, $task_guid);
        if (!$rating) {
            $this_grading = "not_qualified";
        } else {
            $percentage = $rating->percentage;
            $this_grading = ($percentage * $max_grading * 1.0) / 100;
            if (strcmp($task->type_grading, 'task_type_grading_marks') != 0)
                $this_grading = round($this_grading);
        }
    }

    $teacher_comments = get_input('teacher_comments');
    if (strcmp($teacher_comments, "") == 0)
        $teacher_comments = "not_teacher_comments";

    if ($good_grading) {
        $access = elgg_set_ignore_access(true);
        $user_response->grading = $this_grading;
        if (strcmp($teacher_comments, "not_teacher_comments") != 0) {
            $user_response->teacher_comments = $teacher_comments;
        }
        elgg_set_ignore_access($access);

        // Remove the task post cache
        elgg_clear_sticky_form('qualify_task');

        if ((strcmp($this_grading, "not_qualified") != 0) || (strcmp($teacher_comments, "not_teacher_comments") != 0)) {
            system_message(elgg_echo("task:qualified"));
            //Notify
            //$site_guid = elgg_get_config('site_guid');
            //$username = $user->name;
            //$subject = sprintf(elgg_echo('task:qualify:user:email:subject'),$task->title);
            //$body = sprintf(elgg_echo('task:qualify:user:email:body'),$username,$task->title);
            //notify_user($user_guid,$site_guid,$subject,$body);
        }

        //Forward (before)
        //forward("task/view/$taskpost/?offset=$offset"); 

        //======================================
        //Now: forward to the next task response
        //======================================
        $group_guid = $task->container_guid;
        $group = get_entity($group_guid);
        $group_owner_guid = $group->owner_guid;
        if (!$task->subgroups) {
            $members = $group->getMembers(array('limit' => false));
        } else {
            $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
        }
        $members = task_my_sort($members, "name", false);
        $i = 0;
        $membersarray_guids = array();
        foreach ($members as $member) {
            $member_guid = $member->getGUID();
            if (($member_guid != $owner_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
                if (strcmp($task->type_delivery, 'online') == 0) {
                    if (!$task->subgroups) {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'limit' => 0, 'owner_guid' => $member_guid);
                    } else {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'container_guid' => $member_guid, 'limit' => 0);
                    }
                    $user_responses = elgg_get_entities_from_relationship($options);
                    if (!empty($user_responses)) {
                        $membersarray_guids[$i] = $member_guid;
                        if ($flag == 1) {
                            $next_member_guid = $member_guid;
                            break;
                        }
                        if ($membersarray_guids[$i] == $user_guid) {
                            $flag = 1;
                        }
                        $i = $i + 1;
                    }
                } else {
                    $membersarray_guids[$i] = $member_guid;
                    if ($flag == 1) {
                        $next_member_guid = $member_guid;
                        break;
                    }
                    if ($membersarray_guids[$i] == $user_guid) {
                        $flag = 1;
                    }
                    $i = $i + 1;
                }

            }
        }
        if (!empty($next_member_guid)) {
            $url = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/qualify/" . $taskpost . "/" . $next_member_guid . "/" . "0" . "/" . $offset . "/");
            system_message(elgg_echo('task:answer_currently_next'));
            forward($url);
        } else {
            // Ĺast task response
            system_message(elgg_echo('task:answer_no_more'));
            forward("task/view/$taskpost/?offset=$offset");
        }
        //======================================

    } else {
        register_error(elgg_echo("task:bad_qualify_grading"));
        //Forward
        forward($_SERVER['HTTP_REFERER']);
    }
}

?>
