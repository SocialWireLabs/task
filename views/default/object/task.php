<?php

elgg_load_library('task');

$full = elgg_extract('full_view', $vars, FALSE);
$task = elgg_extract('entity', $vars, FALSE);
$user_type = elgg_extract('user_type', $vars, FALSE);
if (strcmp($user_type, "finished_qualified") == 0) {
    $grading = elgg_extract('grading', $vars, FALSE);
}

if (!$task) {
    return TRUE;
}

$owner = $task->getOwnerEntity();
$owner_icon = elgg_view_entity_icon($owner, 'tiny');
$owner_link = elgg_view('output/url', array('href' => $owner->getURL(), 'text' => $owner->name, 'is_trusted' => true));
$author_text = elgg_echo('byline', array($owner_link));
$tags = elgg_view('output/tags', array('tags' => $task->tags));
$date = elgg_view_friendly_time($task->time_created);
$metadata = elgg_view_menu('entity', array('entity' => $task, 'handler' => 'task', 'sort_by' => 'priority', 'class' => 'elgg-menu-hz'));
$subtitle = "$author_text $date $comments_link";

//////////////////////////////////////////////////
//Task information

$owner_guid = $owner->getGUID();
$user_guid = elgg_get_logged_in_user_guid();
$user = get_entity($user_guid);
$group_guid = $task->container_guid;
$group = get_entity($group_guid);
$group_owner_guid = $group->owner_guid;
$taskpost = $task->getGUID();

$opened = task_check_status($task);

$operator = false;
if (($owner_guid == $user_guid) || ($group_owner_guid == $user_guid) || (check_entity_relationship($user_guid, 'group_admin', $group_guid))) {
    $operator = true;
}

//Open interval
if ($opened) {
    if ((strcmp($task->option_activate_value, 'task_activate_date') == 0) && (strcmp($task->option_close_value, 'task_close_date') == 0)) {

        $friendlytime_from = date("j M Y", $task->activate_time) . " " . elgg_echo("task:at") . " " . date("G:i", $task->activate_time);
        $friendlytime_to = date("j M Y", $task->close_time) . " " . elgg_echo("task:at") . " " . date("G:i", $task->close_time);
        $open_interval = elgg_echo('task:opened_from') . ": " . $friendlytime_from . " " . elgg_echo('task:to') . ": " . $friendlytime_to;

    } elseif (strcmp($task->option_activate_value, 'task_activate_date') == 0) {
        $friendlytime_from = date("j M Y", $task->activate_time) . " " . elgg_echo("task:at") . " " . date("G:i", $task->activate_time);
        $open_interval = elgg_echo('task:opened_from') . ": " . $friendlytime_from;
    } elseif (strcmp($task->option_close_value, 'task_close_date') == 0) {
        $friendlytime_to = date("j M Y", $task->close_time) . " " . elgg_echo("task:at") . " " . date("G:i", $task->close_time);
        $open_interval = elgg_echo('task:opened_to') . ": " . $friendlytime_to;
    } else {
        $open_interval = elgg_echo('task:is_opened');
    }
} else {
    $open_interval = elgg_echo('task:is_closed');
    if (elgg_is_active_plugin('event_manager')) {
        $event_guid = $task->event_guid;
        if ($event = get_entity($event_guid)) {
            $now = time();
            if ($now > $task->close_time)
                $deleted = $event->delete();
        }
    }
}

///////////////////////////////////////////////////////////////////
//Links to actions
if (($task->canEdit()) && ($operator)) {
    if ($opened) {
        //Close
        $url_close = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/close?edit=no&taskpost=" . $taskpost);
        $word_close = elgg_echo("task:close_in_listing");
        $link_open_close = "<a href=\"{$url_close}\">{$word_close}</a>";
    } else {
        //Open
        $url_open = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/open?taskpost=" . $taskpost);
        $word_open = elgg_echo("task:open_in_listing");
        $link_open_close = "<a href=\"{$url_open}\">{$word_open}</a>";
    }
    //Responses visibility 
    if (strcmp($task->type_delivery, "online") == 0) {
        if ($task->responses_visibility) {
            $word_responses_visibility = elgg_echo("task:enable_responses_visibility");
            $explain_responses_visibility = elgg_echo("task:explain_responses_hide");
        } else {
            $word_responses_visibility = elgg_echo("task:disable_responses_visibility");
            $explain_responses_visibility = elgg_echo("task:explain_responses_show");
        }
        $url_responses_visibility = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/change_responses_visibility?taskpost=" . $taskpost);
        $link_responses_visibility = "<a title=\"{$explain_responses_visibility}\" href=\"{$url_responses_visibility}\">{$word_responses_visibility}</a>";
    }
    //Responses comments visibility 
    if ((strcmp($task->type_delivery, "online") == 0) && ($task->responses_visibility)) {
        if ($task->responses_comments_visibility) {
            $word_responses_comments_visibility = elgg_echo("task:enable_responses_comments_visibility");
            $explain_responses_comments_visibility = elgg_echo("task:explain_responses_comments_hide");
        } else {
            $word_responses_comments_visibility = elgg_echo("task:disable_responses_comments_visibility");
            $explain_responses_comments_visibility = elgg_echo("task:explain_responses_comments_show");
        }
        $url_responses_comments_visibility = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/change_responses_comments_visibility?taskpost=" . $taskpost);
        $link_responses_comments_visibility = "<a title=\"{$explain_responses_comments_visibility}\" href=\"{$url_responses_comments_visibility}\">{$word_responses_comments_visibility}</a>";
    }
    //Grading visibility 
    if ($task->grading_visibility) {
        $word_grading_visibility = elgg_echo("task:enable_grading_visibility");
        $explain_grading_visibility = elgg_echo("task:explain_grading_hide");
    } else {
        $word_grading_visibility = elgg_echo("task:disable_grading_visibility");
        $explain_grading_visibility = elgg_echo("task:explain_grading_show");
    }
    $url_grading_visibility = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/change_grading_visibility?taskpost=" . $taskpost);
    $link_grading_visibility = "<a title=\"{$explain_grading_visibility}\" href=\"{$url_grading_visibility}\">{$word_grading_visibility}</a>";
    //Public global marks
    if (($task->grading_visibility) && (strcmp($task->type_grading, "task_type_grading_marks") == 0)) {
        if ($task->public_global_marks) {
            $word_public_global_marks = elgg_echo("task:enable_public_global_marks");
            $explain_public_global_marks = elgg_echo("task:explain_public_global_marks_hide");
        } else {
            $word_public_global_marks = elgg_echo("task:disable_public_global_marks");
            $explain_public_global_marks = elgg_echo("task:explain_public_global_marks_show");
        }
        $url_public_global_marks = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/change_public_global_marks?taskpost=" . $taskpost);
        $link_public_global_marks = "<a title=\"{$explain_public_global_marks}\" href=\"{$url_public_global_marks}\">{$word_public_global_marks}</a>";
    }
}

if ($operator) {
    if (!$task->subgroups) {
        $members = $group->getMembers(array('limit' => false));
    } else {
        $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
    }
    $members = task_my_sort($members, "name", false);
    $i = 0;
    $membersarray = array();
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
                    $membersarray[$i] = $member;
                    $i = $i + 1;
                }
            } else {
                $membersarray[$i] = $member;
                $i = $i + 1;
            }
        }
    }

    $num_responses = $i;
    if ($num_responses != 1) {
        $label_num_responses = elgg_echo('task:num_responses');
    } else {
        $label_num_responses = elgg_echo('task:num_response');
    }
}

$num_comments = $task->countComments();
if ($num_comments != 1) {
    $label_num_comments = elgg_echo('task:num_comments');
} else {
    $label_num_comments = elgg_echo('task:num_comment');
}

if ($full) {
    if (!task_check_status($task)) {
        $title = "<div class=\"task_title\"><a class=\"closed_title_task\" href=\"{$task->getURL()}\">{$task->title}</a></div>";
    } else {
        $title = "<div class=\"task_title\"><a class=\"opened_title_task\" href=\"{$task->getURL()}\">{$task->title}</a></div>";
    }
    $params = array('entity' => $task, 'title' => $title, 'metadata' => $metadata, 'subtitle' => $subtitle, 'tags' => $tags);
    $params = $params + $vars;
    $summary = elgg_view('object/elements/summary', $params);
    $body = "";

    ///////////////////////////////////////////////////////////////
    //Task information

    $body .= $open_interval;

    if ($task->assessable) {
        $body .= "<br>" . elgg_echo('task:assessable');
    } else {
        $body .= "<br>" . elgg_echo('task:notassessable');
    }

    //Links to actions
    if (($task->canEdit()) && ($operator)) {
        $body .= "<br>" . $link_open_close;
        if (strcmp($task->type_delivery, "online") == 0) {
            $body .= "<br>" . $link_responses_visibility;
            if ($task->responses_visibility)
                $body .= " " . $link_responses_comments_visibility;
        }
        $body .= "<br>" . $link_grading_visibility;
        if ($task->grading_visibility)
            $body .= " " . $link_public_global_marks;
    }

    $body .= "<br><br>";

    ///////////////////////////////////////////////////////////

    if ($operator) {

        $body .= elgg_view('task/show_answers', array('entity' => $task, 'offset' => $vars['offset'], 'membersarray' => $membersarray, 'num_responses' => $num_responses));

    } else {

        $container_guid = $task->container_guid;
        $container = get_entity($container_guid);

        if ($task->subgroups) {
            $user_subgroup = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'container_guids' => $container_guid, 'relationship' => 'member', 'inverse_relationship' => false, 'relationship_guid' => $user_guid));
            if ($user_subgroup[0])
                $subgroup_guid = $user_subgroup[0]->getGUID();
            else {
                $word_link = elgg_echo('task:link');
                $subgroups_url = elgg_get_site_url() . "lbr_subgroups/index/{$container_guid}";
                $subgroups_link = "<a href={$subgroups_url}>{$word_link}</a>";
                $text_alert = elgg_echo('task:alert');
                $text_annotate = elgg_echo('task:annotate');
                $body .= "<b>{$text_alert}</b>{$text_annotate}{$subgroups_link}.<br><br>";
            }
        }
        if (!$task->subgroups) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $subgroup_guid);
        }
        $user_responses = elgg_get_entities_from_relationship($options);

        if ((strcmp($task->type_delivery, 'online') == 0) && ($opened) && (strcmp($vars['edit_response'], "yes") != 0) && (!empty($user_responses))) {
            //Edit response
            $url_edit_response = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/view/" . $taskpost . "/yes");
            $word_edit_response = elgg_echo("task:edit_response");
            $link_edit_response = "<a href=\"{$url_edit_response}\">{$word_edit_response}</a>";
            $body .= "<br>" . $link_edit_response;
            // Delete response
            if (strcmp($user_responses[0]->grading, "not_qualified") == 0) {
                if (!$task->subgroups) {
                    $url_delete_response = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/delete_answer?taskpost=" . $taskpost . "&user_guid=" . $user_guid);
                } else {
                    $url_delete_response = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/delete_answer?taskpost=" . $taskpost . "&user_guid=" . $subgroup_guid);
                }
                $word_delete_response = elgg_echo('task:delete_answer');
                $word_confirm_delete_response = elgg_echo('task:delete_answer_confirm');
                $link_delete_response = "<a onclick=\"return confirm('$word_confirm_delete_response')\" href=\"{$url_delete_response}\">{$word_delete_response}</a>";
                $body .= " " . $link_delete_response;
            }
        }

        if ((strcmp($task->type_delivery, 'online') == 0) && ($opened) && ((empty($user_responses) || (strcmp($vars['edit_response'], "yes") == 0)))) {
            $body .= elgg_view('forms/task/answer', array('entity' => $task, 'user_guid' => $user_guid, 'offset' => $vars['offset']));
        } else {
            if (!$task->subgroups) {
                $body .= elgg_view('forms/task/show_answer', array('entity' => $task, 'user_guid' => $user_guid, 'answer_time_user_response' => "0", 'offset' => $vars['offset']));
            } else {
                $body .= elgg_view('forms/task/show_answer', array('entity' => $task, 'user_guid' => $subgroup_guid, 'answer_time_user_response' => "0", 'offset' => $vars['offset']));
            }
        }
    }
    //////////////////////////////////////////////////

    echo elgg_view('object/elements/full', array('summary' => $summary, 'icon' => $owner_icon, 'body' => $body));

} else {
    if (!task_check_status($task)) {
        $title = "<div class=\"task_title\"><a class=\"closed_title_task\" href=\"{$task->getURL()}\">{$task->title}</a></div>";
    } else {
        $title = "<div class=\"task_title\"><a class=\"opened_title_task\" href=\"{$task->getURL()}\">{$task->title}</a></div>";
    }
    $params = array('entity' => $task, 'title' => $title, 'metadata' => $metadata, 'subtitle' => $subtitle, 'tags' => $tags);
    $params = $params + $vars;
    $list_body = elgg_view('object/elements/summary', $params);

    $body = "";

    ///////////////////////////////////////////////////////////////
    //Task information
    $body .= $open_interval;

    if ($task->assessable) {
        $body .= "<br>" . elgg_echo('task:assessable') . "<br>";
    } else {
        $body .= "<br>" . elgg_echo('task:notassessable') . "<br>";
    }


    //SW
    /*
    if (($operator)&&(strcmp($task->type_delivery,"online")==0)){ 
       $body .= $num_responses . " " . $label_num_responses . ", ";
    }
    */

    $body .= $num_comments . " " . $label_num_comments;

    if (!$operator) {
        if (strcmp($user_type, "finished") == 0) {
            if (strcmp($task->type_delivery, "online") == 0)
                $body .= "<br>" . elgg_echo('task:finished');
        }
        if (strcmp($user_type, "finished_qualified") == 0) {
            if ($task->grading_visibility) {
                if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
                    $grading = number_format($grading, 2);
                    $label_grading = elgg_echo('task:mark');
                } else {
                    $label_grading = elgg_echo('task:game_points');
                }
                $grading_output = task_grading_output($task, $grading);
                $body .= "<br>" . elgg_echo('task:finished_qualified') . " (" . $label_grading . ": " . $grading_output . ")";
            } else {
                $body .= "<br>" . elgg_echo('task:finished_qualified');
            }
        }
    }

    //Links to actions
    if (($task->canEdit()) && ($operator)) {
        $body .= "<br>" . $link_open_close;
        if (strcmp($task->type_delivery, "online") == 0) {
            $body .= "<br>" . $link_responses_visibility;
            if ($task->responses_visibility)
                $body .= " " . $link_responses_comments_visibility;
        }
        $body .= "<br>" . $link_grading_visibility;
        if ($task->grading_visibility)
            $body .= " " . $link_public_global_marks;
    }

    $list_body .= $body;

    echo elgg_view_image_block($owner_icon, $list_body);
}

?>
