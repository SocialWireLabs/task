<?php

gatekeeper();

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task->getSubtype() == "task") {
    $now = time();
    if (task_check_status($task)) {

        $container_guid = $task->container_guid;
        $container = get_entity($container_guid);
        $user_guid = get_input('user_guid');
        $user = get_entity($user_guid);

        if ($task->subgroups) {
            $user_subgroup = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'container_guids' => $container_guid, 'relationship' => 'member', 'inverse_relationship' => false, 'relationship_guid' => $user_guid));
            $user_subgroup = $user_subgroup[0];
            $user_subgroup_guid = $user_subgroup->getGUID();
        }

        $response_type = $task->response_type;

        //Answers
        if (!$task->subgroups) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $user_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'container_guid' => $user_subgroup_guid, 'limit' => 0);
        }
        $user_responses = elgg_get_entities_from_relationship($options);
        if (!empty($user_responses)) {
            $user_response = $user_responses[0];
        } else {
            $user_response = "";
        }

        // Cache to the session
        elgg_make_sticky_form('answer_task');

        //Comments
        $comments = get_input('comments');
        if (strcmp($comments, "") == 0)
            $comments = "not_comments";

        //Response
        $response_text = get_input("response_text");
        if (strcmp($response_text, "") == 0)
            $response_text = "not_response";

        if (strcmp($response_type, "simple") == 0) {
            $response_html = get_input("response_html");
            if (strcmp($response_html, "") == 0) {
                register_error(elgg_echo('task:not_response'));
                forward($_SERVER['HTTP_REFERER']);
            }
            $response = $response_html;
        }

        if (strcmp($response_type, "urls_files") == 0) {
            $response_urls = get_input('response_urls');
            $response_urls = array_map('trim', $response_urls);
            $response_urls_names = get_input('response_urls_names');
            $response_urls_names = array_map('trim', $response_urls_names);
            $url_failed = false;
            if ((count($response_urls) > 0) && (strcmp($response_urls[0], "") != 0)) {
                foreach ($response_urls as $one_url) {
                    $xss_task = "<a rel=\"nofollow\" href=\"$one_url\" target=\"_blank\">$one_url</a>";
                    if ($xss_task != filter_tags($xss_task)) {
                        $url_failed = true;
                    }
                }
                $i = 0;
                $comp_response_urls = "";
                foreach ($response_urls as $one_url) {
                    if ($i != 0)
                        $comp_response_urls .= Chr(26);
                    if ($response_urls_names[$i] != "")
                        $comp_response_urls .= $response_urls_names[$i] . Chr(24) . $response_urls[$i];
                    else
                        $comp_response_urls .= $response_urls[$i] . Chr(24) . $response_urls[$i];
                    $i = $i + 1;
                }
                $response = $comp_response_urls;
            } else {
                $response = "";
            }
            if ($url_failed) {
                register_error(elgg_echo('task:url_failed'));
                forward($_SERVER['HTTP_REFERER']);
            }
        }

        if (strcmp($response, "") != 0)
            $this_response_content = $response_text . Chr(25) . $response;
        else
            $this_response_content = $response_text . Chr(25) . "not_response";


        $j = 0;
        $file_save_well = true;
        $file_response_guid = array();
        $file_response_counter = count($_FILES['upload_response_file']['name']);

        if (strcmp($response_type, "urls_files") == 0) {
            if (!empty($user_response)) {
                if (!$task->subgroups) {
                    $previous_response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'owner_guid' => $user_guid, 'limit' => 0));
                } else {
                    $previous_response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $user_response->getGUID(), 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'container_guid' => $user_subgroup_guid, 'limit' => 0));
                }
            }
        }

        if (strcmp($response_type, "urls_files") == 0) {

            $count_previous_response_files = 0;
            $count_deleted_previous_response_files = 0;
            foreach ($previous_response_files as $one_file) {
                $count_previous_response_files = $count_previous_response_files + 1;
                $value = get_input($one_file->getGUID());
                if ($value == '1') {
                    $count_deleted_previous_response_files = $count_deleted_previous_response_files + 1;
                }
            }
            if ((($file_response_counter == 0) || ($_FILES['upload_response_file']['name'][0] == "")) && ((count($response_urls) == 0) || (strcmp($response_urls[0], "") == 0)) && ($count_previous_response_files == $count_deleted_previous_response_files)) {
                register_error(elgg_echo('task:not_response'));
                forward($_SERVER['HTTP_REFERER']);
            }

        }

        if (((strcmp($response_type, "urls_files") == 0)) && ($file_response_counter > 0) && ($_FILES['upload_response_file']['name'][0] != "")) {
            $file_response_guids = "";
            for ($k = 0; $k < $file_response_counter; $k++) {
                $file_response[$k] = new ResponsesTaskPluginFile();
                $file_response[$k]->subtype = "task_response_file";
                $prefix = "file/";
                $filestorename = elgg_strtolower(time() . $_FILES['upload_response_file']['name'][$k]);
                $file_response[$k]->setFilename($prefix . $filestorename);
                $file_response[$k]->setMimeType($_FILES['upload_response_file']['type'][$k]);
                $file_response[$k]->originalfilename = $_FILES['upload_response_file']['name'][$k];
                $file_response[$k]->simpletype = elgg_get_file_simple_type($_FILES['upload_response_file']['type'][$k]);
                $file_response[$k]->open("write");
                if (isset($_FILES['upload_response_file']) && isset($_FILES['upload_response_file']['error'][$k])) {
                    $uploaded_file = file_get_contents($_FILES['upload_response_file']['tmp_name'][$k]);
                } else {
                    $uploaded_file = false;
                }
                $file_response[$k]->write($uploaded_file);
                $file_response[$k]->close();
                $file_response[$k]->title = $_FILES['upload_response_file']['name'][$k];
                if (!$task->responses_visibility) {
                    if ($task->subgroups)
                        $file_response[$k]->access_id = $user_subgroup->teachers_acl;
                    else
                        $file_response[$k]->access_id = $container->teachers_acl;
                } else {
                    $file_response[$k]->access_id = $task->access_id;
                }
                if ($task->subgroups)
                    $file_response[$k]->container_guid = $user_subgroup_guid;
                else
                    $file_response[$k]->container_guid = $container_guid;
                $file_response[$k]->owner_guid = $user_guid;

                $file_response_save = $file_response[$k]->save();
                if (!$file_response_save) {
                    $file_save_well = false;
                    break;
                } else {
                    $file_response_guid[$j] = $file_response[$k]->getGUID();
                    if ($k == 0)
                        $file_response_guids .= $file_response[$k]->getGUID();
                    else
                        $file_response_guids .= "," . $file_response[$k]->getGUID();
                    $j = $j + 1;
                }
            }
        }

        if (!$file_save_well) {
            foreach ($file_response_guid as $one_file_guid) {
                $one_file = get_entity($one_file_guid);
                $deleted = $one_file->delete();
                if (!$deleted) {
                    register_error(elgg_echo('task:filenotdeleted'));
                    forward($_SERVER['HTTP_REFERER']);
                }
            }
            register_error(elgg_echo('task:file_error_save'));
            forward($_SERVER['HTTP_REFERER']);
        }

        $found = false;
        if (!empty($user_response)) {
            if (strcmp($user_response->grading, 'not_qualified') == 0) {
                //Answer content
                $user_response->answer_time = $now;
                $user_response->content = $this_response_content;
                $user_response->comments = $comments;
                $user_response->teacher_comments = 'not_teacher_comments';
                $found = true;
            }
        }

        if (!$found) {
            // Initialise a new ElggObject to be the answer
            $answer = new ElggObject();
            $answer->subtype = "task_answer";
            if (!$task->responses_visibility) {
                if ($task->subgroups)
                    $answer->access_id = $user_subgroup->teachers_acl;
                else
                    $answer->access_id = $container->teachers_acl;
            } else {
                $answer->access_id = $task->access_id;
            }
            $answer->owner_guid = $user_guid;
            if ($task->subgroups) {
                $answer->container_guid = $user_subgroup_guid;
                $answer->who_answers = 'subgroup';
            } else {
                $answer->container_guid = $container_guid;
                $answer->who_answers = 'member';
            }
            if (!$answer->save()) {
                foreach ($file_response_guid as $one_file_guid) {
                    $one_file = get_entity($one_file_guid);
                    $deleted = $one_file->delete();
                    if (!$deleted) {
                        register_error(elgg_echo('task:filenotdeleted'));
                        forward($_SERVER['HTTP_REFERER']);
                    }
                }
                register_error(elgg_echo("task:answer_error_save"));
                forward($_SERVER['HTTP_REFERER']);
            }
            //Answer content
            $answer->answer_time = $now;
            $answer->content = $this_response_content;
            $answer->comments = $comments;
            $answer->grading = 'not_qualified';
            $answer->teacher_comments = 'not_teacher_comments';
            add_entity_relationship($taskpost, 'task_answer', $answer->getGUID());
            $task->annotate('all_responses', "1", $task->access_id);
        }

        if (strcmp($response_type, "urls_files") == 0) {
            if (!empty($user_response)) {
                if (strcmp($user_response->grading, 'not_qualified') == 0) {
                    foreach ($previous_response_files as $one_file) {
                        $value = get_input($one_file->getGUID());
                        if ($value == '1') {
                            $file1 = get_entity($one_file->getGUID());
                            $deleted = $file1->delete();
                            if (!$deleted) {
                                register_error(elgg_echo('task:filenotdeleted'));
                                forward($_SERVER['HTTP_REFERER']);
                            }
                        }
                    }
                }
            }

            $file_response_guids_array = explode(",", $file_response_guids);
            foreach ($file_response_guids_array as $one_file_guid) {
                if (!$found)
                    add_entity_relationship($answer->getGUID(), 'response_file_link', $one_file_guid);
                else
                    add_entity_relationship($user_response->getGUID(), 'response_file_link', $one_file_guid);
            }
        }

        // Remove the task post cache
        elgg_clear_sticky_form('answer_task');

        if (strcmp($selected_action, elgg_echo('task:answer')) == 0) {
            // System message
            system_message(elgg_echo("task:answered"));
            // Forward to the tasks listing page
            $url = elgg_get_site_url();
            forward($url . 'task/group/' . $container_guid);
        } else {
            forward("task/view/$taskpost");
        }

    } else {
        system_message(elgg_echo("task:closed"));
        forward($_SERVER['HTTP_REFERER']);
    }
}


?>
