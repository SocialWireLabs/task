<?php

$task = $vars['task'];
$user_guid = $vars['user_guid'];
$user = get_entity($user_guid);
$icon = elgg_view_entity_icon($user, 'small');
$user_response_guid = $vars['user_response_guid'];
$this_grading = $vars['this_grading'];
if (strcmp($task->type_delivery, 'online') == 0) {
    $response_type = $vars['response_type'];
    $response_text = $vars['response_text'];
    switch ($response_type) {
        case 'simple':
            $response_html = $vars['response_html'];
            break;
        case 'urls_files':
            $response_urls = $vars['response_urls'];
            $response_file_guids_array = $vars['response_file_guids_array'];
            break;
    }
    $comments_body = $vars['comments_body'];
}
$teacher_comments_body = $vars['teacher_comments_body'];

$form_body = "";

//Response
if (strcmp($task->type_delivery, 'online') == 0) {

    $form_body .= "<div class=\"task_frame_green\">";

    //$form_body .= "<p><b>" . elgg_echo('task:response_label') . "</p></b>";

    $form_body .= $icon . " " . $user->name;

    $user_response = get_entity($user_response_guid);
    $time_created = $user_response->time_created;
    $time_updated = $user_response->answer_time;

    $friendly_date_created = date('j M Y', $time_created);
    $friendly_time_created = date('G:i', $time_created);

    $form_body .= "<br><br>";
    $form_body .= elgg_echo('task:response_created') . " " . $friendly_date_created . " " . elgg_echo('task:at') . " " . $friendly_time_created;

    if (($time_updated) && ($time_created != $time_updated)) {
        $friendly_date_updated = date('j M Y', $time_updated);
        $friendly_time_updated = date('G:i', $time_updated);

        $form_body .= "<br><br>";
        $form_body .= elgg_echo('task:response_updated') . " " . $friendly_date_updated . " " . elgg_echo('task:at') . " " . $friendly_time_updated;

    }

    $form_body .= "<br><br>";

    if (strcmp($response_text, "") != 0) {
        $form_body .= "<p><b>" . elgg_echo('task:response_text_label_read') . "</p></b>";
        $form_body .= "<div class=\"task_question_frame\">";
        $form_body .= elgg_view('output/text', array('value' => $response_text));
        $form_body .= "</div><br>";
    }

    if ((strcmp($response_type, "simple") == 0) && (strcmp($response_html, "") != 0)) {
        $form_body .= "<p><b>" . elgg_echo('task:response_html_label_read') . "</p></b>";
        $form_body .= "<div class=\"task_question_frame\">";
        $form_body .= elgg_view('output/longtext', array('value' => $response_html));
        $form_body .= "</div><br>";
    }

    if (strcmp($response_type, "urls_files") == 0) {
        $form_body .= "<p><b>" . elgg_echo('task:response_urls_files_label') . "</p></b>";

        $form_body .= "<div class=\"task_question_frame\">";
        if (strcmp($response_urls, "") != 0) {
            $form_body .= $response_urls;
        }

        if ((count($response_file_guids_array) > 0) && (strcmp($response_file_guids_array[0], "") != 0)) {
            foreach ($response_file_guids_array as $one_file_guid) {
                $response_file = get_entity($one_file_guid);
                $params = $one_file_guid . "_response";
                $icon = questions_set_icon_url($response_file, "small");
                $url_file = elgg_get_site_url() . "mod/task/download.php?params=$params";
                $trozos = explode(".", $response_file->title);
                $ext = strtolower(end($trozos));
                if (($ext == 'jpg') || ($ext == 'png') || ($ext == 'gif') || ($ext == 'tif') || ($ext == 'tiff') || ($ext == 'jpeg'))
                    $form_body .= "<p align=\"center\"><a href=\"" . $url_file . "\">" . "<img src=\"" . $url_file . "\" width=\"600px\">" . "</a></p>";
                else
                    $form_body .= "<p><a href=\"" . $url_file . "\">" . "<img src=\"" . elgg_get_site_url() . $icon . "\">" . $response_file->title . "</a></p>";
            }
        }
        $form_body .= "</div><br>";
    }


    //Comments
    if (strcmp($task->type_delivery, 'online') == 0) {
        if (strcmp($comments_body, "") != 0) {
            $form_body .= $comments_body;
        }
    }
    $form_body .= "</div>";
    $form_body .= "<br>";
}

$form_body .= "<div class=\"task_frame_red\">";

if (strcmp($task->type_delivery, 'online') != 0) {

    $form_body .= $icon . " " . $user->name;

    $form_body .= "<br><br>";

}

//Grading

//Grading
if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
    $grading_label = elgg_echo("task:mark");
} else {
    $grading_label = elgg_echo("task:game_points");
}


$form_body .= "<p><b>" . $grading_label . ": " . "</b>";
if (!$task->task_rubric) {
    if (!task_check_status($task)) {
        $this_grading_input = task_grading_input($task, $this_grading, "grading");
        $form_body .= $this_grading_input;
    } else {
        if (strcmp($this_grading, "") != 0) {
            $this_grading_output = task_grading_output($task, $this_grading);
            $form_body .= $this_grading_output;
        } else {
            $form_body .= elgg_echo('task:not_qualified');
        }
    }
} else {
    $rubric = get_entity($task->rubric_guid);
    if (strcmp($task->type_delivery, 'online') == 0) {
        $task_guid = $user_response_guid;
    } else {
        $task_guid = $task->getGUID();
    }
    $rating = socialwire_rubric_get_rating($user_guid, $task_guid);
    if (task_check_status($task)) {
        if (!$rating) {
            $view_type = 'show';
        } else {
            $view_type = 'rated';
        }
    } else {
        if (!$rating) {
            $view_type = 'rate';
        } else {
            $view_type = 'edit_rated';
        }
    }
    $form_body .= elgg_view('rubric/show_rubric', array('entity' => $rubric, 'view_type' => $view_type, 'url' => elgg_get_site_url(), 'task_guid' => $task_guid, 'container_guid' => $task->container_guid, 'student_guid' => $user_guid, 'rating' => $rating));
}
$form_body .= "</p>";

if ((!$task->task_rubric) || (task_check_status($task))) {
    //Teacher comments
    if (strcmp($teacher_comments_body, "") != 0) {
        $form_body .= "<br>" . $teacher_comments_body;
    }
}
$form_body .= "</div>";
$form_body .= "<br>";
echo elgg_echo($form_body);

?>
