<div class="contentWrapper">

    <?php

    elgg_load_library('task');

    if (isset($vars['entity'])) {

        $task = $vars['entity'];
        $taskpost = $task->getGUID();
        $answer_time_user_response = $vars['answer_time_user_response'];

        $user_guid = $vars['user_guid'];
        $user = get_entity($user_guid);
        $container_guid = $task->container_guid;
        $container = get_entity($container_guid);

        //Answers
        $user_response = "";
        $user_response_guid = "";
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
            $user_response_guid = $user_response->getGUID();
        }

        $form_boyd = "";

        if (!empty($user_response) || (strcmp($task->type_delivery, 'online') != 0)) {
            ///////////////////////////////////////////////////////////////////
            //Grading

            if (empty($user_response)) {
                $this_grading = "";
            } else {
                if (strcmp($user_response->grading, "not_qualified") == 0) {
                    $this_grading = "";
                } else {
                    $this_grading = $user_response->grading;
                    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
                        $this_grading = number_format($this_grading, 2);
                }
            }

            if (!$task->task_rubric) {
                if (!task_check_status($task)) {
                    if (elgg_is_sticky_form('qualify_task'))
                        $this_grading = elgg_get_sticky_value('qualify_task', 'grading');
                }
            }

            ///////////////////////////////////////////////////////////////////
            //Responses

            if (strcmp($task->type_delivery, 'online') == 0) {
                $response_type = $task->response_type;

                if (strcmp($response_type, "urls_files") == 0) {
                    if (!$task->subgroups) {
                        $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'owner_guid' => $user_guid, 'limit' => 0));
                    } else {
                        $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'container_guid' => $user_guid, 'limit' => 0));
                    }
                    $response_file_guids = "";
                    if ((count($response_files) > 0) && (strcmp($response_files[0]->title, "") != 0)) {
                        foreach ($response_files as $file) {
                            if (strcmp($response_file_guids, "") == 0)
                                $response_file_guids .= $file->getGUID();
                            else
                                $response_file_guids .= "," . $file->getGUID();
                        }
                    }
                }

                $this_response = explode(Chr(25), $user_response->content);
                $this_response = array_map('trim', $this_response);
                $response_text = $this_response[0];

                if (strcmp($response_text, "not_response") == 0)
                    $response_text = "";

                if (strcmp($response_type, "simple") == 0) {
                    $response_html = $this_response[1];
                    if (strcmp($response_html, "not_response") == 0)
                        $response_html = "";
                }

                if (strcmp($response_type, "urls_files") == 0) {
                    $this_response = $this_response[1];
                    if (strcmp($this_response, "not_response") == 0) {
                        $response_urls = "";
                    } else {
                        $this_response_urls = explode(Chr(26), $this_response);
                        $this_response_urls = array_map('trim', $this_response_urls);
                        $response_urls = "";
                        if ((count($this_response_urls) > 0) && (strcmp($this_response_urls[0], "") != 0)) {
                            foreach ($this_response_urls as $one_url) {
                                $comp_url = explode(Chr(24), $one_url);
                                $comp_url = array_map('trim', $comp_url);
                                $url_name = $comp_url[0];
                                $url_value = $comp_url[1];
                                if (elgg_is_active_plugin("sw_embedlycards")) {
                                    $response_urls .= "<div>
                        <a class='embedly-card' href='$url_value'></a>
                        </div>";
                                } else if (elgg_is_active_plugin("hypeScraper"))
                                    $response_urls .= elgg_view('output/sw_url_preview', array('value' => $url_value,));
                                $response_urls .= "<a rel=\"nofollow\" href=\"$url_value\" target=\"_blank\">$url_value</a><br>";
                            }
                        }
                    }
                    $response_file_guids_array = explode(",", $response_file_guids);
                }
            }

            //Comments
            if (strcmp($task->type_delivery, 'online') == 0) {
                $comments_body = "";
                if (strcmp($user_response->comments, "not_comments") != 0) {
                    $comments_label = elgg_echo('task:comments_label');
                    $comments_body .= "<p>" . "<b>" . $comments_label . "</b>" . "</p>";
                    $comments_body .= "<div class=\"task_question_frame\">";
                    $comments_body .= elgg_view('output/longtext', array('value' => $user_response->comments));
                    $comments_body .= "</div>";
                }
            }

            //Teacher comments
            $teacher_comments_body = "";
            if (!task_check_status($task)) {
                $teacher_comments_label = elgg_echo('task:teacher_comments_label');
                $teacher_comments_body .= "<p>" . "<b>" . $teacher_comments_label . "</b>" . "</p>";
                if (elgg_is_sticky_form('qualify_task')) {
                    $teacher_comments = elgg_get_sticky_value('qualify_task', 'teacher_comments');
                } else {
                    if (!empty($user_response)) {
                        if (strcmp($user_response->teacher_comments, "not_teacher_comments") == 0)
                            $teacher_comments = "";
                        else
                            $teacher_comments = $user_response->teacher_comments;
                    } else {
                        $teacher_comments = "";
                    }
                }
                $teacher_comments_textbox = elgg_view('input/longtext', array('name' => 'teacher_comments', 'value' => $teacher_comments));
                $teacher_comments_body .= "<p>" . $teacher_comments_textbox . "</p><br>";
            } else {
                if (!empty($user_response)) {
                    if (strcmp($user_response->teacher_comments, "not_teacher_comments") != 0) {
                        $teacher_comments_label = elgg_echo('task:teacher_comments_label');
                        $teacher_comments_body .= "<p>" . "<b>" . $teacher_comments_label . "</b>" . "</p>";
                        $teacher_comments_body .= "<div class=\"task_question_frame\">";
                        $teacher_comments_body .= elgg_view('output/longtext', array('value' => $user_response->teacher_comments));
                        $teacher_comments_body .= "</div>";
                    }
                }
            }

            //////////////////////////////////////////////////////////
            //Body

            $form_body = "";
            if (strcmp($task->type_delivery, 'online') != 0) {
                $form_body .= elgg_view("forms/task/qualify_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'grading' => $grading, 'this_grading' => $this_grading, 'teacher_comments_body' => $teacher_comments_body));
            } else {
                switch ($response_type) {
                    case 'simple':
                        $form_body .= elgg_view("forms/task/qualify_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'grading' => $grading, 'this_grading' => $this_grading, 'response_type' => $response_type, 'response_text' => $response_text, 'response_html' => $response_html, 'comments_body' => $comments_body, 'teacher_comments_body' => $teacher_comments_body));
                        break;
                    case 'urls_files':
                        $form_body .= elgg_view("forms/task/qualify_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'grading' => $grading, 'this_grading' => $this_grading, 'response_type' => $response_type, 'response_text' => $response_text, 'response_urls' => $response_urls, 'response_file_guids_array' => $response_file_guids_array, 'comments_body' => $comments_body, 'teacher_comments_body' => $teacher_comments_body));
                        break;
                }
            }

            //Submit
            $action = "task/qualify";
            $submit_qualify = elgg_echo('task:qualify');
            $submit_input_qualify = elgg_view('input/submit', array('name' => 'submit', 'value' => $submit_qualify));
            $entity_hidden = elgg_view('input/hidden', array('name' => 'taskpost', 'value' => $taskpost));
            $entity_hidden .= elgg_view('input/hidden', array('name' => 'user_guid', 'value' => $user_guid));
            $entity_hidden .= elgg_view('input/hidden', array('name' => 'answer_time_user_response', 'value' => $answer_time_user_response));
            $entity_hidden .= elgg_view('input/hidden', array('name' => 'offset', 'value' => $vars['offset']));

            $return_button_text = elgg_echo('task:return');
            $return_button_link = elgg_get_site_url() . 'task/view/' . $taskpost . '/?offset=' . $vars['offset'];
            $return_button = elgg_view('input/button', array('name' => 'return',
                'class' => 'elgg-button-cancel', 'value' => $return_button_text));
            $return_button = "<a href=" . $return_button_link . ">" . $return_button . "</a>";

            if (!task_check_status($task)) {
                if (!$task->task_rubric) {
                    $form_body .= "<p>" . $submit_input_qualify . $entity_hidden . $return_button . "</p>";
                    ?>
                    <form name="qualify_task" action="<?php echo elgg_get_site_url(); ?>action/<?php echo $action; ?>"
                          enctype="multipart/form-data" method="post">
                        <?php
                        echo elgg_view('input/securitytoken');
                        echo elgg_echo($form_body);
                        ?>
                    </form>
                    <?php
                } else {
                    echo elgg_echo($form_body);
                    //Teacher comments
                    if (strcmp($teacher_comments_body, "") != 0) {
                        $form_body_submit = $teacher_comments_body;
                    }
                    $form_body_submit .= "</div>";
                    $form_body_submit .= "<br>";
                    $form_body_submit .= "<p>" . $submit_input_qualify . $entity_hidden . "</p>";
                    ?>
                    <form name="qualify_task" action="<?php echo elgg_get_site_url(); ?>action/<?php echo $action; ?>"
                          enctype="multipart/form-data" method="post">
                        <?php
                        echo elgg_view('input/securitytoken');
                        echo elgg_echo($form_body_submit);
                        ?>
                    </form>
                    <?php
                }
            } else {
                echo elgg_echo($form_body);
            }

            if (($task->responses_visibility) && (!empty($user_response))) {
                echo "<br>";
                ////////////////////////////////////////////////////////////////////
                // Response Discussion Comments
                $task_response_discussion_comments_label = elgg_echo('task:response_discussion_comments');
                ?>
                <p align="left"><a onclick="task_show_response_discussion_comments();"
                                   style="cursor:hand;"><?php echo $task_response_discussion_comments_label; ?></a></p>
                <div id="response_commentsDiv" style="display:none;">
                    <?php echo elgg_view_comments($user_response);
                    ?>
                </div>
                <br>
                <?php
            }
        } else {
            $form_body .= "<p>" . elgg_echo('task:not_response') . "</p>";
            echo elgg_echo($form_body);
        }

        //=======================================================================================================================
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
                            if (count($membersarray_guids) > 1)
                                $prev_member_guid = $membersarray_guids[$i - 2];
                            $next_member_guid = $member_guid;
                            break;
                        }
                        if ($membersarray_guids[$i] == $user_guid) {
                            $flag = 1;
                            // If it is the last
                            if ($i > 0)
                                $prev_member_guid = $membersarray_guids[$i - 1];
                        }
                        $i = $i + 1;
                    }
                } else {
                    $membersarray_guids[$i] = $member_guid;
                    if ($flag == 1) {
                        if (count($membersarray_guids) > 1)
                            $prev_member_guid = $membersarray_guids[$i - 2];
                        $next_member_guid = $member_guid;
                        break;
                    }
                    if ($membersarray_guids[$i] == $user_guid) {
                        $flag = 1;
                        // If it is the last
                        if ($i > 0)
                            $prev_member_guid = $membersarray_guids[$i - 1];
                    }
                    $i = $i + 1;
                }
            }
        }

        if (!empty($prev_member_guid)) {
            $url_prev = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/qualify/" . $taskpost . "/" . $prev_member_guid . "/" . "0" . "/" . $vars['offset'] . "/");
            $url_text_prev = elgg_echo('task:answer_previous');
            echo elgg_echo("<br><a style=\"float:left\" href={$url_prev}>{$url_text_prev}</a>");
        }
        if (!empty($next_member_guid)) {
            $url_next = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/qualify/" . $taskpost . "/" . $next_member_guid . "/" . "0" . "/" . $vars['offset'] . "/");
            $url_text_next = elgg_echo('task:answer_next');
            echo elgg_echo("<a style=\"float:right\" href={$url_next}>{$url_text_next}</a>");
        }
        //======================================

        elgg_clear_sticky_form('qualify_task');

    }

    /////////////////////////////////////////////////////////////////

    ?>
</div>

<script type="text/javascript">
    function task_show_response_discussion_comments() {
        var response_commentsDiv = document.getElementById('response_commentsDiv');
        if (response_commentsDiv.style.display == 'none') {
            response_commentsDiv.style.display = 'block';
        } else {
            response_commentsDiv.style.display = 'none';
        }
    }
</script>
