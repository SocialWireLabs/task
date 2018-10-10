<div class="contentWrapper">

    <?php

    elgg_load_library('task');

    if (isset($vars['entity'])) {
        $taskpost = $vars['entity']->getGUID();
        $task = $vars['entity'];
        $answer_time_user_response = $vars['answer_time_user_response'];

        $user_guid = $vars['user_guid'];
        $user = get_entity($user_guid);
        $container_guid = $task->container_guid;

        $my_results = false;
        $my_guid = elgg_get_logged_in_user_guid();
        $my_user = get_entity($my_guid);

        if ($task->subgroups) {
            $subgroups = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'container_guids' => $container_guid, 'relationship' => 'member', 'inverse_relationship' => false, 'relationship_guid' => $my_user->getGUID()));
            $my_guid = $subgroups[0]->guid;
            if ($my_guid == $user_guid)
                $my_results = true;
        } else {
            if ($my_guid == $user_guid)
                $my_results = true;
        }

        /////////////////////////////////////////////
        //Other responses
        $form_body_other_responses = "";

        if (($my_guid == $user_guid) && ($task->responses_visibility) && ($answer_time_user_response == 0)) {
            $owner = $task->getOwnerEntity();
            $owner_guid = $owner->getGUID();
            $group_guid = $task->container_guid;
            $group = get_entity($group_guid);
            $group_owner_guid = $group->owner_guid;

            if (!$task->subgroups) {
                $members = $group->getMembers(array('limit' => false));
            } else {
                $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
            }

            $offset = $vars['offset'];
            $limit = 10;
            $this_limit = $limit + $offset;

            $i = 0;
            $membersarray = array();
            foreach ($members as $member) {
                $member_guid = $member->getGUID();

                if (($member_guid != $my_guid) && ($member_guid != $owner_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
                    if (!$task->subgroups) {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'limit' => 0, 'owner_guid' => $member_guid);
                    } else {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'limit' => 0, 'container_guid' => $member_guid);
                    }
                    $user_responses = elgg_get_entities_from_relationship($options);
                    if (!empty($user_responses)) {
                        $membersarray[$i] = $member;
                        $i = $i + 1;
                    }
                }
            }

            $count = $i;

            $i = 0;
            foreach ($membersarray as $member) {
                if (($i >= $offset) && ($i < $this_limit)) {
                    $member_guid = $member->getGUID();
                    $url = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/show_answer/" . $taskpost . "/" . $member_guid . "/" . "0" . "/" . "0");
                    $url_text = elgg_echo('task:response') . " " . elgg_echo('task:of') . " " . $member->name;
                    if (!$task->subgroups) {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $member_guid);
                    } else {
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $member_guid);
                    }
                    $user_responses = elgg_get_entities_from_relationship($options);

                    $user_response = "";
                    $previous_user_responses = array();
                    $j = 0;
                    foreach ($user_responses as $one_response) {
                        if ($j == 0) {
                            $user_response = $one_response;
                        } else {
                            $previous_user_responses[$j] = $one_response;
                        }
                        $j = $j + 1;
                    }
                    $response_grading = "";
                    if (($task->grading_visibility) && ((($task->public_global_marks) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0))) {
                        if (!empty($user_response)) {
                            $response_grading = $user_response->grading;
                            if (strcmp($response_grading, "not_qualified") == 0) {
                                $response_grading = "";
                            } else {
                                if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
                                    $response_grading = number_format($response_grading, 2);
                            }
                        }
                    }

                    if ((!empty($user_response)) && (strcmp($task->type_delivery, 'online') == 0)) {
                        $link = "<a href=\"{$url}\">{$url_text}</a>";
                    } else {
                        $link = "$url_text";
                    }

                    $form_body_other_responses .= elgg_view("forms/task/show_answer_view", array('entity' => $task, 'user' => $member, 'grading' => $response_grading, 'link' => $link, 'previous_user_responses' => $previous_user_responses));
                }
                $i = $i + 1;
            }

            $form_body_other_responses .= elgg_view("navigation/pagination", array('count' => $count, 'limit' => $limit, 'offset' => $offset));

        }

        ////////////////////////////////////////////

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

        ///////////////////////////////////////////////////////////////////
        //Previous user responses body
        if (strcmp($task->type_delivery, 'online') == 0) {
            $previous_user_responses_body = "";
            if ($my_guid == $user_guid) {
                //Previous user responses
                $previous_user_responses = array();
                $j = 0;
                foreach ($user_responses as $one_response) {
                    if ($j > 0) {
                        $previous_user_responses[$j] = $one_response;  //SW
                    }
                    $j = $j + 1;
                }
                if (!empty($previous_user_responses)) {
                    $show_previous_responses_label = elgg_echo("task:previous_responses");
                    $previous_user_responses_body .= "<p align=\"left\"><a onclick=\"task_show_previous_responses();\" style=\"cursor:hand;\">$show_previous_responses_label</a></p>";
                    $previous_user_responses_body .= "<div class=\"contentWrapper\">";
                    $previous_user_responses_body .= "<div id=\"resultsDiv_show_previous_responses\" style=\"display:none\">";
                    $i = 1;
                    foreach ($previous_user_responses as $one_previous_user_response) {
                        $previous_user_response = $previous_user_responses[$i];
                        if ($task->grading_visibility) {
                            $response_grading = "";
                            if (strcmp($previous_user_response->grading, 'not_qualified') != 0) {
                                if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
                                    $grading_label = elgg_echo('task:mark');
                                    $response_grading = number_format($previous_user_response->grading, 2);
                                } else {
                                    $grading_label = elgg_echo('task:game_points');
                                    $response_grading = $previous_user_response->grading;
                                }
                                $response_grading_output = task_grading_output($task, $response_grading);
                                $response_grading = $grading_label . ": " . $response_grading_output;
                            } else {
                                $response_grading = elgg_echo("task:not_qualified");
                            }
                        }
                        $answer_time = $previous_user_response->answer_time;
                        $friendly_answer_time = date("d/m/Y", $answer_time) . " " . elgg_echo("task:at") . " " . date("G:i", $answer_time);
                        $url_previous = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/show_answer/" . $taskpost . "/" . $user_guid . "/" . $answer_time . "/" . "0");
                        if ($task->grading_visibility) {
                            $url_text_previous = elgg_echo('task:previous_response') . " (" . $friendly_answer_time . ") " . $response_grading;
                        } else {
                            $url_text_previous = elgg_echo('task:previous_response') . " (" . $friendly_answer_time . ")";
                        }
                        $link_previous = "<a href=\"{$url_previous}\">{$url_text_previous}</a>";
                        $previous_user_responses_body .= $link_previous . "<br>";
                        $i = $i + 1;
                    }
                    $previous_user_responses_body .= "</div>";
                    $previous_user_responses_body .= "</div>";
                }
            }
        }

        if (!empty($user_response) || (strcmp($task->type_delivery, 'online') != 0)) {

            ////////////////////////////////////////////////////////////
            //Question body
            $question_body = "";
            if (strcmp($task->question_html, "") != 0) {
                $question_body .= "<p>" . "<b>" . elgg_echo('task:question_simple_read') . "</b>" . "</p>";
                $question_body .= "<div class=\"task_question_frame\">";
                $question_body .= elgg_view('output/longtext', array('value' => $task->question_html));
                $question_body .= "</div>";
                if (strcmp($task->question_type, "simple") != 0)
                    $question_body .= "<br>";
            }
            switch ($task->question_type) {
                case 'urls_files':
                    $question_body .= "<p>" . "<b>" . elgg_echo('task:question_urls_files_read') . "</b>" . "</p>";
                    $question_body .= "<div class=\"task_question_frame\">";
                    $question_urls = explode(Chr(26), $task->question_urls);
                    $question_urls = array_map('trim', $question_urls);
                    $files = elgg_get_entities_from_relationship(array('relationship' => 'question_file_link', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_question_file', 'limit' => 0));

                    if ((count($question_urls) > 0) && (strcmp($question_urls[0], "") != 0)) {
                        foreach ($question_urls as $one_url) {
                            $comp_url = explode(Chr(24), $one_url);
                            $comp_url = array_map('trim', $comp_url);
                            $url_name = $comp_url[0];
                            $url_value = $comp_url[1];
                            if (elgg_is_active_plugin("sw_embedlycards")) {
                                $question_body .= "<div>
                     <a class='embedly-card' href='$url_value'></a>
                     </div>";
                            } else if (elgg_is_active_plugin("hypeScraper"))
                                $question_body .= elgg_view('output/sw_url_preview', array('value' => $url_value,));
                            $question_body .= "<a rel=\"nofollow\" href=\"$url_value\" target=\"_blank\">$url_value</a><br>";
                        }
                    }

                    if ((count($files) > 0) && (strcmp($files[0]->title, "") != 0)) {
                        foreach ($files as $one_file) {
                            $params = $one_file->getGUID() . "_question";
                            $icon = questions_set_icon_url($one_file, "small");
                            $url_file = elgg_get_site_url() . "mod/task/download.php?params=$params";
                            $trozos = explode(".", $one_file->title);
                            $ext = strtolower(end($trozos));
                            if (($ext == 'jpg') || ($ext == 'png') || ($ext == 'gif') || ($ext == 'tif') || ($ext == 'tiff') || ($ext == 'jpeg'))
                                $question_body .= "<p align=\"center\"><a href=\"" . $url_file . "\">" . "<img src=\"" . $url_file . "\" width=\"600px\">" . "</a></p>";
                            else
                                $question_body .= "<p><a href=\"" . $url_file . "\">" . "<img src=\"" . elgg_get_site_url() . $icon . "\">" . $one_file->title . "</a></p>";
                        }
                    }
                    $question_body .= "</div>";
                    break;
            }

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

            ///////////////////////////////////////////////////////////
            //Comments
            if (strcmp($task->type_delivery, 'online') == 0) {
                $comments_body = "";
                if (strcmp($user_response->comments, "not_comments") != 0) {
                    $comments_body .= "<p><b>" . elgg_echo('task:comments_label') . "</p></b>";
                    $comments_body .= "<div class=\"task_question_frame\">";
                    $comments_body .= elgg_view('output/longtext', array('value' => $user_response->comments));
                    $comments_body .= "</div>";
                }
            }

            //Teacher comments
            $teacher_comments_body = "";
            if (!empty($user_response)) {
                if (($task->grading_visibility) && (strcmp($user_response->teacher_comments, "not_teacher_comments") != 0) && (strcmp($user_response->teacher_comments, "") != 0)) {
                    $teacher_comments_body .= "<p><b>" . elgg_echo('task:teacher_comments_label') . "</p></b>";
                    $teacher_comments_body .= "<div class=\"task_question_frame\">";
                    $teacher_comments_body .= elgg_view('output/longtext', array('value' => $user_response->teacher_comments));
                    $teacher_comments_body .= "</div>";
                }
            }

            //Feedback
            $feedback_body = "";
            if (strcmp($task->feedback, "not_feedback") != 0) {
                $feedback_body .= "<p><b>" . elgg_echo('task:feedback_label') . "</p></b>";
                $feedback_body .= "<div class=\"task_question_frame\">";
                $feedback_body .= elgg_view('output/longtext', array('value' => $task->feedback));
                $feedback_body .= "</div>";
            }

            $form_body = "";

            //////////////////////////////////////////////////////
            //Previous information

            if (($my_results) && ($answer_time_user_response == 0)) {

                $form_body .= "<div class=\"task_frame\">";

                //General comments
                $num_comments = $task->countComments();
                if ($num_comments > 0)
                    $task_general_comments_label = elgg_echo('task:general_comments') . " (" . $num_comments . ")";
                else
                    $task_general_comments_label = elgg_echo('task:general_comments');

                $form_body .= "<p align=\"left\"><a onclick=\"task_show_general_comments();\" style=\"cursor:hand;\">$task_general_comments_label</a></p>";
                $form_body .= "<div id=\"commentsDiv\" style=\"display:none;\">";
                $form_body .= elgg_view_comments($task);
                $form_body .= "</div>";

                $form_body .= "</div>";

                $form_body .= "<br>";
            }

            //////////////////////////////////////////////////////
            //Body

            if (strcmp($task->type_delivery, 'online') != 0) {
                $form_body .= elgg_view("forms/task/show_answer_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'my_results' => $my_results, 'answer_time_user_response' => $answer_time_user_response, 'question_body' => $question_body, 'this_grading' => $this_grading, 'teacher_comments_body' => $teacher_comments_body, 'feedback_body' => $feedback_body));
            } else {
                switch ($response_type) {
                    case 'simple':
                        $form_body .= elgg_view("forms/task/show_answer_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'my_results' => $my_results, 'answer_time_user_response' => $answer_time_user_response, 'question_body' => $question_body, 'this_grading' => $this_grading, 'response_type' => $response_type, 'response_text' => $response_text, 'response_html' => $response_html, 'comments_body' => $comments_body, 'teacher_comments_body' => $teacher_comments_body, 'feedback_body' => $feedback_body, 'previous_user_responses_body' => $previous_user_responses_body));
                        break;
                    case 'urls_files':
                        $form_body .= elgg_view("forms/task/show_answer_question", array('task' => $task, 'user_guid' => $user_guid, 'user_response_guid' => $user_response_guid, 'my_results' => $my_results, 'answer_time_user_response' => $answer_time_user_response, 'question_body' => $question_body, 'this_grading' => $this_grading, 'response_type' => $response_type, 'response_text' => $response_text, 'response_urls' => $response_urls, 'response_file_guids_array' => $response_file_guids_array, 'comments_body' => $comments_body, 'teacher_comments_body' => $teacher_comments_body, 'feedback_body' => $feedback_body, 'previous_user_responses_body' => $previous_user_responses_body));
                        break;
                }
            }
            echo elgg_echo($form_body);

            if (($task->responses_visibility) && (!empty($user_response))) {
                ////////////////////////////////////////////////////////////////////
                //Response Discussion Comments
                if (($my_results) || ((strcmp($task->type_delivery, 'online') == 0) && ($task->responses_comments_visibility))) {
                    $task_response_discussion_comments_label = elgg_echo('task:response_discussion_comments');
                    ?>
                    <p align="left"><a onclick="task_show_response_discussion_comments();"
                                       style="cursor:hand;"><?php echo $task_response_discussion_comments_label; ?></a>
                    </p>
                    <div id="response_commentsDiv" style="display:none;">
                        <?php echo elgg_view_comments($user_response); ?>
                    </div>
                    <br>
                    <?php
                }
            }
        } else {
            $form_body .= "<p>" . elgg_echo('task:not_previous_response') . "</p>";
            echo elgg_echo($form_body);
        }

        ////////////////////////////////////////////////////////////////////
        //Other responses
        if (($my_guid == $user_guid) && ($task->responses_visibility) && ($answer_time_user_response == 0) && ($form_body_other_responses)) {
            echo "<p><b>" . elgg_echo('task:other_responses') . "</b></p>";
            echo elgg_echo($form_body_other_responses);
        }

    }

    ?>
</div>

<script type="text/javascript">
    function task_show_general_comments() {
        var commentsDiv = document.getElementById('commentsDiv');
        if (commentsDiv.style.display == 'none') {
            commentsDiv.style.display = 'block';
        } else {
            commentsDiv.style.display = 'none';
        }
    }

    function task_show_previous_responses() {
        var resultsDiv_show_previous_responses = document.getElementById('resultsDiv_show_previous_responses');
        if (resultsDiv_show_previous_responses.style.display == 'none') {
            resultsDiv_show_previous_responses.style.display = 'block';
        } else {
            resultsDiv_show_previous_responses.style.display = 'none';
        }
    }

    function task_show_response_discussion_comments() {
        var response_commentsDiv = document.getElementById('response_commentsDiv');
        if (response_commentsDiv.style.display == 'none') {
            response_commentsDiv.style.display = 'block';
        } else {
            response_commentsDiv.style.display = 'none';
        }
    }

</script>
