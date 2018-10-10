<?php

gatekeeper();

$user_guid = elgg_get_logged_in_user_guid();
$user = get_entity($user_guid);

$title = get_input('title');
$input_question_html = get_input('question_html');
$input_question_type = get_input('question_type');

switch ($input_question_type) {
    case 'urls_files':
        $question_urls = get_input('question_urls');
        $question_urls = array_map('trim', $question_urls);
        $question_urls_names = get_input('question_urls_names');
        $question_urls_names = array_map('trim', $question_urls_names);
        $i = 0;
        $input_question_urls = "";
        if ((count($question_urls) > 0) && (strcmp($question_urls[0], "") != 0)) {
            foreach ($question_urls as $url) {
                if ($i != 0)
                    $input_question_urls .= Chr(26);
                $input_question_urls .= $question_urls_names[$i] . Chr(24) . $question_urls[$i];
                $i = $i + 1;
            }
        }
        $number_question_urls = count($question_urls);
        break;
}
$file_counter = count($_FILES['upload']['name']);
$type_delivery = get_input('type_delivery');
if (strcmp($type_delivery, "online") == 0) {
    $input_response_type = get_input('response_type');
    $responses_visibility = get_input('responses_visibility');
    if (strcmp($responses_visibility, "on") == 0) {
        $responses_comments_visibility = get_input('responses_comments_visibility');
    }
}
$option_activate_value = get_input('option_activate_value');
$option_close_value = get_input('option_close_value');
if (strcmp($option_activate_value, 'task_activate_date') == 0) {
    $opendate = get_input('opendate');
    $opentime = get_input('opentime');
}
if (strcmp($option_close_value, 'task_close_date') == 0) {
    $closedate = get_input('closedate');
    $closetime = get_input('closetime');
}
$assessable = get_input('assessable');
$grading_visibility = get_input('grading_visibility');
$type_grading = get_input('type_grading');
if ((strcmp($assessable, "on") == 0) && (strcmp($type_grading, 'task_type_grading_marks') == 0)) {
    $not_response_is_zero = get_input('not_response_is_zero');
}
$task_rubric = get_input('task_rubric');
if (strcmp($type_grading, 'task_type_grading_marks') == 0) {
    $type_mark = get_input('type_mark');
    if (strcmp($type_mark, 'task_type_mark_numerical') == 0) {
        $max_mark = get_input('max_mark');
    }
    $mark_weight = get_input('mark_weight');
    if (strcmp($grading_visibility, "on") == 0) {
        $public_global_marks = get_input('public_global_marks');
    }
} else {
    if (strcmp($task_rubric, "on") == 0) {
        $max_game_points = get_input('max_game_points');
    }
}
if (strcmp($task_rubric, "on") == 0) {
    $rubric_guid = get_input('rubric_guid');
}

$feedback = get_input('feedback');
$subgroups = get_input('subgroups');
$tags = get_input('tasktags');
$access_id = get_input('access_id');
$container_guid = get_input('container_guid');
$container = get_entity($container_guid);

// Cache to the session
elgg_make_sticky_form('add_task');

if (strcmp($option_activate_value, 'task_activate_date') == 0) {
    $mask_time = "[0-2][0-9]:[0-5][0-9]";
    if (!ereg($mask_time, $opentime, $same)) {
        register_error(elgg_echo("task:bad_times"));
        forward($_SERVER['HTTP_REFERER']);
    }
}
if (strcmp($option_close_value, 'task_close_date') == 0) {
    $mask_time = "[0-2][0-9]:[0-5][0-9]";
    if (!ereg($mask_time, $closetime, $same)) {
        register_error(elgg_echo("task:bad_times"));
        forward($_SERVER['HTTP_REFERER']);
    }
}
$now = time();
if (strcmp($option_activate_value, 'task_activate_now') == 0) {
    $activate_time = $now;
} else {
    $opentime_array = explode(':', $opentime);
    $opentime_h = trim($opentime_array[0]);
    $opentime_m = trim($opentime_array[1]);
    $opendate_array = explode('-', $opendate);
    $opendate_y = trim($opendate_array[0]);
    $opendate_m = trim($opendate_array[1]);
    $opendate_d = trim($opendate_array[2]);
    $activate_date = mktime(0, 0, 0, $opendate_m, $opendate_d, $opendate_y);
    $activate_time = mktime($opentime_h, $opentime_m, 0, $opendate_m, $opendate_d, $opendate_y);

    if ($activate_time < 1) {
        register_error(elgg_echo("task:bad_times"));
        forward($_SERVER['HTTP_REFERER']);
    }
}
if (strcmp($option_close_value, 'task_not_close') == 0) {
    $close_time = $now + 60 * 60 * 24 * 365 * 2;
} else {
    $closetime_array = explode(':', $closetime);
    $closetime_h = trim($closetime_array[0]);
    $closetime_m = trim($closetime_array[1]);
    $closedate_array = explode('-', $closedate);
    $closedate_y = trim($closedate_array[0]);
    $closedate_m = trim($closedate_array[1]);
    $closedate_d = trim($closedate_array[2]);
    $close_date = mktime(0, 0, 0, $closedate_m, $closedate_d, $closedate_y);
    $close_time = mktime($closetime_h, $closetime_m, 0, $closedate_m, $closedate_d, $closedate_y);

    if ($close_time < 1) {
        register_error(elgg_echo("task:bad_times"));
        forward($_SERVER['HTTP_REFERER']);
    }
}
if ($activate_time >= $close_time) {
    register_error(elgg_echo("task:error_times"));
    forward($_SERVER['HTTP_REFERER']);
}

//////////////////////////////////////////////////////////////////////////

if (strcmp($type_grading, 'task_type_grading_marks') == 0) {

    //Integer mark_weight (0<mark_weight<100)
    /*$is_integer = true;
    $mask_integer='^([[:digit:]]+)$';
    if (ereg($mask_integer,$mark_weight,$same)){
       if ((substr($same[1],0,1)==0)&&(strlen($same[1])!=1)){
          $is_integer=false;
       }
    } else {
       $is_integer=false;
    }
    if (!$is_integer){
       register_error(elgg_echo("task:bad_mark_weight"));
       forward($_SERVER['HTTP_REFERER']);
    }*/
    $is_number = is_numeric($mark_weight);
    if (!$is_number) {
        register_error(elgg_echo("task:bad_mark_weight"));
        forward($_SERVER['HTTP_REFERER']);
    }
    if ($mark_weight > 100) {
        register_error(elgg_echo("task:bad_mark_weight"));
        forward($_SERVER['HTTP_REFERER']);
    }
} else {
    if (strcmp($task_rubric, "on") == 0) {
        //Integer question max game points
        $is_integer = true;
        $mask_integer = '^([[:digit:]]+)$';
        if (ereg($mask_integer, $max_game_points, $same)) {
            if ((substr($same[1], 0, 1) == 0) && (strlen($same[1]) != 1)) {
                $is_integer = false;
            }
        } else {
            $is_integer = false;
        }
        if (!$is_integer) {
            register_error(elgg_echo("task:bad_max_game_points"));
            forward($_SERVER['HTTP_REFERER']);
        }
    }
}

// Convert string of tags into a preformatted array
$tagarray = string_to_tag_array($tags);

// Make sure the title is not blank
if (strcmp($title, "") == 0) {
    register_error(elgg_echo("task:title_blank"));
    forward($_SERVER['HTTP_REFERER']);
}

// Question urls
if (strcmp($input_question_type, "urls_files") == 0) {
    $blank_question_url = false;
    $questionurlsarray = array();
    $i = 0;
    foreach ($question_urls as $one_url) {
        $questionurlsarray[$i] = $one_url;
        if (strcmp($one_url, "") == 0) {
            $blank_question_url = true;
            break;
        }
        $i = $i + 1;
    }
    if (!$blank_question_url) {
        foreach ($question_urls_names as $one_url_name) {
            if (strcmp($one_url_name, "") == 0) {
                $blank_question_url = true;
                break;
            }
        }
    }
    if (($blank_question_url) && ($number_question_urls > 1)) {
        register_error(elgg_echo("task:url_blank"));
        forward($_SERVER['HTTP_REFERER']);
    }
    $same_question_url = false;
    $i = 0;
    while (($i < $number_question_urls) && (!$same_question_url)) {
        $j = $i + 1;
        while ($j < $number_question_urls) {
            if (strcmp($questionurlsarray[$i], $questionurlsarray[$j]) == 0) {
                $same_question_url = true;
                break;
            }
            $j = $j + 1;
        }
        $i = $i + 1;
    }
    if ($same_question_url) {
        register_error(elgg_echo("task:url_repetition"));
        forward($_SERVER['HTTP_REFERER']);
    }
    if (!$question_url_blank) {
        foreach ($question_urls as $url) {
            $xss_task = "<a rel=\"nofollow\" href=\"$url\" target=\"_blank\">$url</a>";
            if ($xss_task != filter_tags($xss_task)) {
                register_error(elgg_echo('task:url_failed'));
                forward($_SERVER['HTTP_REFERER']);
            }
        }
    }
}

if ((strcmp($input_question_type, "urls_files") == 0) && ($_FILES['upload']['name'][0] == "") && ($number_question_urls == 0)) {
    register_error(elgg_echo('task:not_question_urls_files'));
    forward($_SERVER['HTTP_REFERER']);
}

if (($file_counter > 0) && ($_FILES['upload']['name'][0] != "")) {
    $file_save_well = true;
    $file = array();
    for ($i = 0; $i < $file_counter; $i++) {
        $file[$i] = new QuestionsTaskPluginFile();
        $file[$i]->subtype = "task_question_file";
        $prefix = "file/";
        $filestorename = elgg_strtolower(time() . $_FILES['upload']['name'][$i]);
        $file[$i]->setFilename($prefix . $filestorename);
        $file[$i]->setMimeType($_FILES['upload']['type'][$i]);
        $file[$i]->originalfilename = $_FILES['upload']['name'][$i];
        $file[$i]->simpletype = elgg_get_file_simple_type($_FILES['upload']['type'][$i]);
        $file[$i]->open("write");
        if (isset($_FILES['upload']) && isset($_FILES['upload']['error'][$i])) {
            $uploaded_file = file_get_contents($_FILES['upload']['tmp_name'][$i]);
        } else {
            $uploaded_file = false;
        }
        $file[$i]->write($uploaded_file);
        $file[$i]->close();
        $file[$i]->title = $_FILES['upload']['name'][$i];
        $file[$i]->owner_guid = $user_guid;
        $file[$i]->container_guid = $container_guid;
        $file[$i]->access_id = $access_id;
        $file_save = $file[$i]->save();
        if (!$file_save) {
            $file_save_well = false;
            break;
        }
    }
    if (!$file_save_well) {
        foreach ($file as $one_file) {
            $deleted = $one_file->delete();
            if (!$deleted) {
                register_error(elgg_echo('task:filenotdeleted'));
                forward($_SERVER['HTTP_REFERER']);
            }
        }
        register_error(elgg_echo('task:file_error_save'));
        forward($_SERVER['HTTP_REFERER']);
    }
}

//////////////////////////////////////////////////////////////////////////

// Initialise a new ElggObject
$task = new ElggObject();

// Tell the system it's a task post
$task->subtype = "task";

// Set its owner, container and group
$task->owner_guid = $user_guid;
$task->container_guid = $container_guid;
$task->group_guid = $container_guid;

// Set its access
$task->access_id = $access_id;

// Set its title
$task->title = $title;

// Set its description
$task->description = "";

// Save the task post
if (!$task->save()) {
    if (($file_counter > 0) && ($_FILES['upload']['name'][0] != "")) {
        foreach ($file as $one_file) {
            $deleted = $one_file->delete();
            if (!$deleted) {
                register_error(elgg_echo('task:filenotdeleted'));
                forward($_SERVER['HTTP_REFERER']);
            }
        }
    }
    register_error(elgg_echo("task:error_save"));
    forward($_SERVER['HTTP_REFERER']);
}

$taskpost = $task->getGUID();

// Set question fields
$task->question_html = $input_question_html;
$task->question_type = $input_question_type;
switch ($input_question_type) {
    case 'urls_files':
        $task->question_urls = $input_question_urls;
        break;
}
if (($file_counter > 0) && ($_FILES['upload']['name'][0] != "")) {
    for ($i = 0; $i < $file_counter; $i++) {
        add_entity_relationship($taskpost, 'question_file_link', $file[$i]->getGUID());
    }
}

// Set times
$task->option_activate_value = $option_activate_value;
$task->option_close_value = $option_close_value;
if (strcmp($option_activate_value, 'task_activate_now') != 0) {
    $task->activate_date = $activate_date;
    $task->activate_time = $activate_time;
    $task->form_activate_date = $activate_date;
    $task->form_activate_time = $opentime;
}
if (strcmp($option_close_value, 'task_not_close') != 0) {
    $task->close_date = $close_date;
    $task->close_time = $close_time;
    $task->form_close_date = $close_date;
    $task->form_close_time = $closetime;
}
if ((strcmp($option_activate_value, 'task_activate_date') == 0) && (strcmp($option_close_value, 'task_close_date') == 0)) {
    if (($now >= $activate_time) && ($now < $close_time)) {
        $task->opened = true;
    } else {
        $task->opened = false;
    }
} elseif (strcmp($option_activate_value, 'task_activate_date') == 0) {
    if ($now >= $activate_time) {
        $task->opened = true;
    } else {
        $task->opened = false;
    }
} elseif (strcmp($option_close_value, 'task_close_date') == 0) {
    if ($now < $close_time) {
        $task->opened = true;
    } else {
        $task->opened = false;
    }
} else {
    $task->opened = true;
}

//Set evaluators
$task->evaluators = "teachers";

//Set type of delivery
if (strcmp($type_delivery, "online") == 0) {
    $task->type_delivery = "online";
} else {
    $task->type_delivery = "offline";
}
if (strcmp($type_delivery, "online") == 0) {
    // Set type of response
    $task->response_type = $input_response_type;
    // Set responses visibility
    if (strcmp($responses_visibility, "on") == 0) {
        $task->responses_visibility = true;
        // Set responses comments visibility
        if (strcmp($responses_comments_visibility, "on") == 0) {
            $task->responses_comments_visibility = true;
        } else {
            $task->responses_comments_visibility = false;
        }
    } else {
        $task->responses_visibility = false;
    }
}

// Set assessable
if (strcmp($assessable, "on") == 0) {
    $task->assessable = true;
} else {
    $task->assessable = false;
}

// Set grading visibility
if (strcmp($grading_visibility, "on") == 0) {
    $task->grading_visibility = true;
} else {
    $task->grading_visibility = false;
}

// Set type of grading
$task->type_grading = $type_grading;
if (strcmp($type_grading, 'task_type_grading_marks') == 0) {
    $task->type_mark = $type_mark;
    if (strcmp($type_mark, 'task_type_mark_numerical') == 0) {
        $task->max_mark = $max_mark;
    }
    //Information for plugin marks
    switch ($type_mark) {
        case 'task_type_mark_numerical':
            if ($max_mark == 10)
                $task->mark_type = NUMERIC10;
            else
                $task->mark_type = NUMERIC100;
            break;
        case 'task_type_mark_textual':
            $task->mark_type = STRINGUNI;
            break;
        case 'task_type_mark_apto':
            $task->mark_type = BOOLEAN;
            break;
    }
    $task->mark_weight = $mark_weight;
    if (strcmp($grading_visibility, "on") == 0) {
        //Set public global marks
        if (strcmp($public_global_marks, "on") == 0) {
            $task->public_global_marks = true;
        } else {
            $task->public_global_marks = false;
        }
    }
} else {
    if (strcmp($task_rubric, "on") == 0) {
        // Set max game points
        $task->max_game_points = $max_game_points;
    }
}

//Set not_response_is_zero
if (($task->assessable) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0)) {
    if (strcmp($not_response_is_zero, "on") == 0) {
        $task->not_response_is_zero = true;
    } else {
        $task->not_response_is_zero = false;
    }
}

// Set rubric
if (strcmp($task_rubric, "on") == 0) {
    $task->task_rubric = true;
    $task->rubric_guid = $rubric_guid;
} else {
    $task->task_rubric = false;
}

// Set feedback
if (strcmp($feedback, "") != 0)
    $task->feedback = $feedback;
else
    $task->feedback = "not_feedback";

// Set subgroups
if (strcmp($subgroups, "on") == 0) {
    $task->subgroups = true;
    $task->who_answers = 'subgroup';
} else {
    $task->subgroups = false;
    $task->who_answers = 'member';
}

// Now let's add tags.
if (is_array($tagarray)) {
    $task->tags = $tagarray;
}

// Remove the task post cache
elgg_clear_sticky_form('add_task');

// System message
system_message(elgg_echo("task:created"));
//River
elgg_create_river_item(array(
    'view' => 'river/object/task/create',
    'action_type' => 'create',
    'subject_guid' => $user_guid,
    'object_guid' => $taskpost
));
//Nofity
if ($access_id != 0) {
    $username = $user->name;
    $site_guid = elgg_get_config('site_guid');
    $site = get_entity($site_guid);
    $sitename = $site->name;
    $group = $container;
    $groupname = $container->name;
    $link = $task->getURL();
    $subject = sprintf(elgg_echo('task:create:group:email:subject'), $username, $sitename, $groupname);
    $group_members = $group->getMembers(array('limit' => false));
    foreach ($group_members as $member) {
        $member_guid = $member->getGUID();
        if ($member_guid != $task->owner_guid) {
            $body = sprintf(elgg_echo('task:create:group:email:body'), $member->name, $username, $sitename, $groupname, $title, $link);
            notify_user($member_guid, $task->owner_guid, $subject, $body, array('action' => 'create', 'object' => $task));
        }
    }
}

//Event using the event_manager plugin if it is active
if (elgg_is_active_plugin('event_manager') && strcmp($option_close_value, 'task_not_close') != 0) {

    $event = new Event();
    $event->title = sprintf(elgg_echo("task:event_manager_title"), $task->title);
    $event->description = $task->getURL();
    $event->container_guid = $container_guid;
    $event->access_id = $access_id;
    $event->save();
    $event->tags = string_to_tag_array($tags);
    $event->comments_on = 0;
    $event->registration_ended = 1;
    $event->show_attendees = 0;
    $event->max_attendees = "";
    $event->start_day = $close_date;
    $event->start_time = $close_time;
    $event->end_ts = $close_time + 1;
    $event->organizer = $user->getDisplayName();
    $event->setAccessToOwningObjects($access_id);

    // added because we need an update event
    if ($event->save()) {
        $event_guid = $event->getGUID();
        $task->event_guid = $event_guid;
    } else
        register_error(elgg_echo("task:event_manager_error_save"));
}

//Forward
forward(elgg_get_site_url() . 'task/group/' . $container_guid);

?>
