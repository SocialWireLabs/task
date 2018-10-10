<div class="contentWrapper">

    <?php

    elgg_load_library('task');

    if (isset($vars['entity'])) {

        $taskpost = $vars['entity']->getGUID();
        $task = $vars['entity'];
        $action = "task/answer";
        $user_guid = $vars['user_guid'];

        $user = get_entity($user_guid);
        $container_guid = $task->container_guid;
        $container = get_entity($container_guid);
        if ($task->subgroups) {
            $user_subgroup = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'container_guids' => $container_guid, 'relationship' => 'member', 'inverse_relationship' => false, 'relationship_guid' => $user_guid));
            if ($user_subgroup[0])
                $user_subgroup_guid = $user_subgroup[0]->getGUID();
        }

        $response_type = $task->response_type;

        ////////////////////////////////////////////////////////////////////////
        //Previous information

        ?>
        <div class="task_frame">
            <?php

            //General comments
            $num_comments = $task->countComments();
            if ($num_comments > 0)
                $task_general_comments_label = elgg_echo('task:general_comments') . " (" . $num_comments . ")";
            else
                $task_general_comments_label = elgg_echo('task:general_comments');
            ?>
            <p align="left"><a onclick="task_show_general_comments();"
                               style="cursor:hand;"><?php echo $task_general_comments_label; ?></a></p>
            <div id="commentsDiv" style="display:none;">
                <?php echo elgg_view_comments($task); ?>
            </div>

        </div><br>

        <?php
        ////////////////////////////////////////////////////////////////////////aaa
        //Form
        ?>

    <form action="<?php echo elgg_get_site_url() . "action/" . $action ?>" name="answer_task"
          enctype="multipart/form-data" method="post">

        <?php
        echo elgg_view('input/securitytoken');

        /////////////////////////////////////////////
        //Other responses

        $form_body_other_responses = "";

        if ($task->responses_visibility) {
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
                $is_other = true;
                if (!$task->subgroups) {
                    if ($member_guid == $user_guid)
                        $is_other = false;
                } else {
                    if ($member_guid == $user_subgroup_guid)
                        $is_other = false;
                }
                if (($is_other) && ($owner_guid != $member_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
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
                        $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'container_guid' => $member_guid, 'limit' => 0);
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

                    if (!empty($user_response)) {
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

        /////////////////////////////////////////////
        //Answers
        if (!$task->subgroups) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'container_guid' => $user_subgroup_guid, 'limit' => 0);
        }
        $user_responses = elgg_get_entities_from_relationship($options);
        $user_response = "";
        $previous_user_responses = array();
        $first = true;
        $i = 1;
        foreach ($user_responses as $one_response) {
            if ($first) {
                $user_response = $one_response;
                if (strcmp($user_response->grading, "not_qualified") != 0) {
                    $previous_user_responses[$i] = $one_response;
                    $i = $i + 1;
                }
                $first = false;
            } else {
                $previous_user_responses[$i] = $one_response;
                $i = $i + 1;
            }
        }

        ///////////////////////////////////////////////////////////////////
        //Previous user responses body

        $previous_user_responses_body = "";
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
                $friendly_answer_time = date("j M Y", $answer_time) . " " . elgg_echo("task:at") . " " . date("G:i", $answer_time);
                if (!$task->subgroups) {
                    $url_previous = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/show_answer/" . $taskpost . "/" . $user_guid . "/" . $answer_time . "/" . "0");
                } else {
                    $url_previous = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/show_answer/" . $taskpost . "/" . $user_subgroup_guid . "/" . $answer_time . "/" . "0");
                }
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

        ///////////////////////////////////////////////////////////////////
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
                            $question_body .= "<div align=\"center\">" . elgg_view('output/sw_url_preview', array('value' => $url_value,)) . "</div>";
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
        //Responses

        if (!empty($user_response))
            $user_response_guid = $user_response->getGUID();

        $this_response = explode(Chr(25), $user_response->content);
        $this_response = array_map('trim', $this_response);
        if (elgg_is_sticky_form('answer_task')) {
            $this_response_text = elgg_get_sticky_value('answer_task', 'response_text');
        } else {
            $this_response_text = $this_response[0];
            if (strcmp($this_response_text, "not_response") == 0)
                $this_response_text = "";
        }

        $response_text = elgg_view('input/text', array('name' => 'response_text', 'value' => $this_response_text));

        if (strcmp($response_type, "simple") == 0) {
            if (elgg_is_sticky_form('answer_task')) {
                $this_response_html = elgg_get_sticky_value('answer_task', 'response_html');
            } else {
                $this_response_html = $this_response[1];
                if (strcmp($this_response_html, "not_response") == 0)
                    $this_response_html = "";
            }
            $response_html = elgg_view('input/longtext', array('name' => 'response_html', 'value' => $this_response_html));
        }

        if (strcmp($response_type, "urls_files") == 0) {
            $name_response = "response_urls" . "[]";
            $name_response_names = "response_urls_names" . "[]";
            if (elgg_is_sticky_form('answer_task')) {
                $this_response_urls_names = elgg_get_sticky_value('answer_task', 'response_urls_names');
                $this_response_urls = elgg_get_sticky_value('answer_task', 'response_urls');
                $i = 0;
                $this_response_comp_urls = array();
                foreach ($this_response_urls as $url) {
                    $this_response_comp_urls[$i] = $this_response_urls_names[$i] . Chr(24) . $this_response_urls[$i];
                    $i = $i + 1;
                }
            } else {
                $this_response_comp_urls = $this_response[1];
                if (strcmp($this_response_comp_urls, "not_response") != 0) {
                    $this_response_comp_urls = explode(Chr(26), $this_response_comp_urls);
                    $this_response_comp_urls = array_map('trim', $this_response_comp_urls);
                } else {
                    $this_response_comp_urls = "";
                }
            }
            $response_urls = "";
            if ((count($this_response_comp_urls) > 0) && (strcmp($this_response_comp_urls[0], "") != 0)) {
                $j = 0;
                foreach ($this_response_comp_urls as $url) {
                    $response_urls .= "<p class=\"clone_this_response_urls_" . "\">";
                    $comp_url = explode(Chr(24), $url);
                    $comp_url = array_map('trim', $comp_url);
                    $url_name = $comp_url[0];
                    $url_value = $comp_url[1];
                    /* $response_urls .= elgg_echo("task:response_url_name_label");
                  $response_urls .= elgg_view("input/text", array('name' => $name_response_names,'value' => $url_name)); */
                    $response_urls .= elgg_echo("task:response_url_label");
                    $response_urls .= elgg_view("input/text", array('name' => $name_response, 'value' => $url_value));
                    if ($j > 0) {
                        $response_urls .= "<!-- remove url --><a class=\"remove\" href=\"#\" onclick=\"$(this).parent().slideUp(function(){ $(this).remove() }); return false\">" . elgg_echo("delete") . "</a>";
                    }
                    $response_urls .= "<br></p>";
                    $j = $j + 1;
                }
            } else {
                $response_urls .= "<p class=\"clone_this_response_urls_" . "\">";
                $comp_url = explode(Chr(24), $this_response_comp_urls);
                $comp_url = array_map('trim', $comp_url);
                $url_name = $comp_url[0];
                $url_value = $comp_url[1];
                /* $response_urls .= elgg_echo("task:response_url_name_label");
                $response_urls .= elgg_view("input/text", array('name' => $name_response_names,'value' => $url_name)); */
                $response_urls .= elgg_echo("task:response_url_label");
                $response_urls .= elgg_view("input/text", array('name' => $name_response, 'value' => $url_value));
                $response_urls .= "</p>";
            }
            $response_urls .= "<!-- add link to add more urls which triggers a jquery clone function --><a href=\"#\" class=\"add\" rel=\".clone_this_response_urls_" . "\">" . elgg_echo("task:add_url") . "</a>";
            $response_urls .= "<br /><br /></p>";

            if (!empty($user_response)) {
                if (!$task->subgroups) {
                    $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'owner_guid' => $user_guid, 'limit' => 0));
                } else {
                    $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'container_guid' => $user_subgroup_guid, 'limit' => 0));
                }
            } else {
                $response_files = "";
            }
            $name_response = "upload_response_file" . "[]";
            $response_file = elgg_view("input/file", array('name' => $name_response, 'class' => 'multi'));
        }

        ////////////////////////////////////////////////////////////////////
        //Comments

        $comments = "";
        if (elgg_is_sticky_form('answer_task')) {
            $comments = elgg_get_sticky_value('answer_task', 'comments');
        } else {
            $comments = $user_response->comments;
        }
        if (strcmp($comments, "not_comments") == 0)
            $comments = "";

        $comments_body = elgg_view('input/longtext', array('name' => 'comments', 'value' => $comments));

        elgg_clear_sticky_form('answer_task');

        ////////////////////////////////////////////////////////////////////////
        //Body

        switch ($response_type) {
            case 'simple':
                echo elgg_view("forms/task/answer_question", array('task' => $task, 'user_response_guid' => $user_response_guid, 'question_body' => $question_body, 'response_type' => $response_type, 'response_text' => $response_text, 'response_html' => $response_html, 'previous_user_responses_body' => $previous_user_responses_body, 'comments_body' => $comments_body));
                break;
            case 'urls_files':
                echo elgg_view("forms/task/answer_question", array('task' => $task, 'user_response_guid' => $user_response_guid, 'question_body' => $question_body, 'response_type' => $response_type, 'response_text' => $response_text, 'response_urls' => $response_urls, 'response_file' => $response_file, 'response_files' => $response_files, 'previous_user_responses_body' => $previous_user_responses_body, 'comments_body' => $comments_body));
                break;
        }

        ////////////////////////////////////////////////////////////////////
        //Submit

        if ($user_subgroup[0] || !$task->subgroups) {

            $task_answer = elgg_echo('task:answer');
            $submit_input_answer = elgg_view('input/submit', array('name' => 'submit', 'value' => $task_answer));
            $entity_hidden = elgg_view('input/hidden', array('name' => 'taskpost', 'value' => $taskpost));
            $entity_hidden .= elgg_view('input/hidden', array('name' => 'user_guid', 'value' => $user_guid));

            ?>
            <p><?php echo $submit_input_answer . $entity_hidden;
                ?></p><br>

            <!-- add the add_response/delete_response functionality  -->
            <script type="text/javascript">
                // remove function for the jquery clone plugin
                $(function () {
                    var removeLink = '<a class="remove" href="#" onclick="$(this).parent().slideUp(function(){ $(this).remove() }); return false"><?php echo elgg_echo("delete");?></a>';
                    $('a.add').relCopy({append: removeLink});
                });
            </script>

            </form>

        <?php }

        ////////////////////////////////////////////////////////////////////
        //Response Discussion Comments

        if (($task->responses_visibility) && (!empty($user_response))) {
            if (strcmp($user_response->grading, "not_qualified") == 0) {
                $task_response_discussion_comments_label = elgg_echo('task:response_discussion_comments');
                ?>
                <p align="left"><a onclick="task_show_response_discussion_comments();"
                                   style="cursor:hand;"><?php echo $task_response_discussion_comments_label; ?></a></p>
                <div id="response_commentsDiv" style="display:none;">
                    <?php echo elgg_view_comments($user_response); ?>
                </div>
                <br>
                <?php
            }
        }

        ?>
        <form>
            <?php

            ////////////////////////////////////////////////////////////////////
            //Other responses
            if (($task->responses_visibility) && ($form_body_other_responses)) {
                echo "<p><b>" . elgg_echo('task:other_responses') . "</b></p>";
                echo elgg_echo($form_body_other_responses);
            }

            ?>

        </form>

        <?php
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

<script type="text/javascript"
        src="<?php echo elgg_get_site_url(); ?>mod/task/lib/jquery.MultiFile.js"></script><!-- multi file jquery plugin -->
<script type="text/javascript"
        src="<?php echo elgg_get_site_url(); ?>mod/task/lib/reCopy.js"></script><!-- copy field jquery plugin -->
<script type="text/javascript" src="<?php echo elgg_get_site_url(); ?>mod/task/lib/js_functions.js"></script>
