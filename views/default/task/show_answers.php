<?php

elgg_load_library('task');

if (!isset($vars['entity'])) {
    register_error(elgg_echo("task:notfound"));
    forward("mod/task/index.php");
}
$task = $vars['entity'];
$taskpost = $task->getGUID();
$offset = $vars['offset'];
$membersarray = $vars['membersarray'];
if (!empty($membersarray))
    $count = count($membersarray);
else
    $count = 0;

$limit = 10;
$this_limit = $offset + $limit;

$form_body = "";

///////////////////////////////////////////////////////////////////////
//Assign marks or game points
if ($task->assessable) {
    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (!task_check_status($task)) {
            $url_assign_marks = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/assign_marks?taskpost=" . $taskpost);
            $text_assign_marks = elgg_echo("task:assign_marks");
            $link_assign_marks = "<a href=\"{$url_assign_marks}\">{$text_assign_marks}</a>";
            $form_body .= $link_assign_marks;
        }
    } else {
        if (!task_check_status($task)) {
            $url_assign_game_points = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/assign_game_points?taskpost=" . $taskpost);
            $text_assign_game_points = elgg_echo("task:assign_game_points");
            $link_assign_game_points = "<a href=\"{$url_assign_game_points}\">{$text_assign_game_points}</a>";
            $form_body .= $link_assign_game_points;
        }
    }
}

//Export statistics
$url_export_statistics_pdf=elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/export_statistics_pdf?taskpost=" . $taskpost);
$export_statistics_pdf_text=elgg_echo("task:export_statistics_pdf");
$link_export_statistics_pdf="<a href=\"{$url_export_statistics_pdf}\">{$export_statistics_pdf_text}</a>";
if (!task_check_status($task)){
   if ($task->assessable)
      $form_body .= " | ".$link_export_statistics_pdf;
   else
      $form_body .= $link_export_statistics_pdf;
}


$wwwroot = elgg_get_config('wwwroot');
$img_template = '<img border="0" width="20" height="20" alt="%s" title="%s" src="' . $wwwroot . 'mod/task/graphics/%s" />';


if (!task_check_status($task)) {
    $form_body .= "         ";
    $url_zip = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/zip_all?taskpost=$taskpost");
    $text_zip = elgg_echo("task:zips");
    $img_zip = sprintf($img_template, $text_zip, $text_zip, "zip_icon_grey.jpeg");
    $link_zip = "<a href=\"{$url_zip}\">{$img_zip}</a>";
    $form_body .= $link_zip;

    //Get zips
    $url_get_zip = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/get_zips?taskpost=$taskpost");
    $text_get_zip = elgg_echo("task:get_zips");
    $img_get_zip = sprintf($img_template, $text_get_zip, $text_get_zip, "zip_icon.jpeg");
    $link_get_zip = "<a href=\"{$url_get_zip}\">{$img_get_zip}</a>";
    $form_body .= $link_get_zip;
}

//General comments
$num_comments = $task->countComments();
if ($num_comments > 0)
    $task_general_comments_label = elgg_echo('task:general_comments') . " (" . $num_comments . ")";
else
    $task_general_comments_label = elgg_echo('task:general_comments');
$form_body .= "<div class=\"contentWrapper\">";
$form_body .= "<div class=\"task_frame\">";
$form_body .= "<p align=\"left\"><a onclick=\"task_show_general_comments();\" style=\"cursor:hand;\">$task_general_comments_label</a></p>";
$form_body .= "<div id=\"commentsDiv\" style=\"display:none;\">";
$form_body .= elgg_view_comments($task);
$form_body .= "</div>";
$form_body .= "</div>";
$form_body .= "</div>";

///////////////////////////////////////////////////////////////////////
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

if (strcmp($question_body, "") != 0) {
    $task_question_body_label = elgg_echo('task:question');
    $form_body .= "<div class=\"contentWrapper\">";
    $form_body .= "<div class=\"task_frame_blue\">";
    $form_body .= "<p align=\"left\"><a onclick=\"task_show_question_body();\" style=\"cursor:hand;\">$task_question_body_label</a></p>";
    $form_body .= "<div id=\"questionbodyDiv\" style=\"display:block;\">";
    $form_body .= $question_body;
    $form_body .= "</div>";
    $form_body .= "</div>";
    $form_body .= "</div>";
}

////////////////////////////////////////////////////////////////////////////
//Responses

$form_body .= "<div class=\"contentWrapper\">";
$form_body .= "<div class=\"task_frame_green\">";

if ($count > 0) {
    if (strcmp($task->type_delivery, "online") == 0)
        $form_body .= elgg_echo('task:responses') . " (" . $count . ")" . "<br>";
    else
        $form_body .= elgg_echo('task:responses');
    $i = 0;
    $k = 0;
    $membersguidsarray = array();
    foreach ($membersarray as $member) {
        if (($i >= $offset) && ($i < $this_limit)) {
            $member_guid = $member->getGUID();
            $membersguidsarray[$k] = $member_guid;
            $k = $k + 1;
            $url = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/qualify/" . $taskpost . "/" . $member_guid . "/" . "0" . "/" . $offset);
            $url_text = elgg_echo('task:response') . " " . elgg_echo('task:of') . " " . $member->name;
            $url_delete = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/task/delete_answer?taskpost=" . $taskpost . "&user_guid=" . $member_guid . "&offset=" . $offset);
            $wwwroot = elgg_get_config('wwwroot');
            $img_template = '<img border="0" width="16" height="16" alt="%s" title="%s" src="' . $wwwroot . 'mod/task/graphics/%s" />';
            $img_delete_msg = elgg_echo('task:delete_answer');
            $confirm_delete_msg = elgg_echo('task:delete_answer_confirm');
            $img_delete = sprintf($img_template, $img_delete_msg, $img_delete_msg, "delete.gif");
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

            $grading = "not_qualified";
            if (!empty($user_response)) {
                $grading = $user_response->grading;
            }

            if (!empty($user_response) || (strcmp($task->type_delivery, 'online') != 0)) {
                $link = "<a href=\"{$url}\">{$url_text}</a>";
                if ((!task_check_status($task)) && (!empty($user_response) && (strcmp($user_response->grading, "not_qualified") == 0)) && (strcmp($task->type_delivery, 'online') == 0))
                    $link .= " <a onclick=\"return confirm('$confirm_delete_msg')\" href=\"{$url_delete}\">{$img_delete}</a>";
            } else {
                $link = "$url_text";
            }

            $form_body .= elgg_view("task/show_answers_view", array(
                'entity' => $task,
                'user' => $member,
                'owner_response' => $user_response->owner_guid,
                'link' => $link,
                'grading' => $grading,
                'previous_user_responses' => $previous_user_responses
            ));
        }
        $i = $i + 1;
    }

    $form_body .= elgg_view("navigation/pagination", array('count' => $count, 'offset' => $offset, 'limit' => $limit));

} else {
    $form_body .= elgg_echo('task:responses') . "<br>";
    $form_body .= elgg_echo('task:not_responses');
}
$form_body .= "</div>";
$form_body .= "</div>";

if ((!$task->task_rubric) && (!task_check_status($task)) && ($count > 0)) {
    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (strcmp($task->type_mark, 'task_type_mark_numerical') == 0) {
            $max_grading = $task->max_mark;
        }
    }
    $form_body .= "<div class=\"contentWrapper\">";
    $form_body .= "<div class=\"task_frame_red\">";

    $l_array = count($membersguidsarray);
    echo "<script language='javascript'>
   var membersguidsarray_js= new Array($l_array)
   </script>";

    for ($k = 0; $k < $l_array; $k++) {
        echo "<script language='javascript'>
      membersguidsarray_js[$k]='$membersguidsarray[$k]'
      </script>";
    }

    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (strcmp($task->type_mark, 'task_type_mark_numerical') == 0) {
            $link_save_marks = "<a href=\"\" onclick=\"javascript:task_update_all_gradings(
            " . $k . ",
            " . membersguidsarray_js . ",
            " . $taskpost . ",
            " . $max_grading .
                ");return true;\">" .
                elgg_echo('task:save_marks') . "</a>";
        } else {
            $link_save_marks = "<a href=\"\" onclick=\"javascript:task_update_all_gradings(
            " . $k . ",
            " . membersguidsarray_js . ",
            " . $taskpost . ");return true;\">" .
                elgg_echo('task:save_marks') . "</a>";
        }
        $form_body .= $link_save_marks;
    } else {
        $link_save_game_points = "<a href=\"\" onclick=\"javascript:task_update_all_gradings(
         " . $k . ",
         " . membersguidsarray_js . ",
         " . $taskpost . ");return true;\">" .
            elgg_echo('task:save_game_points') . "</a>";
        $form_body .= $link_save_game_points;
    }

    //======================================================================================
    $g = 0;
    foreach ($membersarray as $member) {
        $membersguidsarray_full[$g] = $member->getGUID();
        $g = $g + 1;
    }
    $l_array_full = count($membersguidsarray_full);
    echo "<script language='javascript'>
   var membersguidsarray_full_js= new Array($l_array_full)
   </script>";
    for ($g = 0; $g < $l_array_full; $g++) {
        echo "<script language='javascript'>
      membersguidsarray_full_js[$g]='$membersguidsarray_full[$g]'
      </script>";
    }
    $form_body .= "<br>";
    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (strcmp($task->type_mark, 'task_type_mark_numerical') == 0) {
            $link_save_marks = "<a  href=\"\" onclick=\"
            javascript:task_update_all_gradings_as_one(
               " . $l_array_full . ",
               " . membersguidsarray_full_js . ",
               " . $taskpost . ",
               " . $max_grading .
                ");
            return true;\">" .
                elgg_echo('task:saveasone_marks') . "</a>";
        } else {
            $link_save_marks = "<a  href=\"\" onclick=\"
            javascript:task_update_all_gradings_as_one(
               " . $l_array_full . ",
               " . membersguidsarray_full_js . ",
               " . $taskpost . ");
            return true;\">" .
                elgg_echo('task:saveasone_marks') . "</a>";
        }
        $form_body .= $link_save_marks;
    } else {
        $link_save_game_points = "<a  href=\"\" onclick=\"
         javascript:task_update_all_gradings_as_one(
            " . $l_array_full . ",
            " . membersguidsarray_full_js . ",
            " . $taskpost . ");
         return true;\">" .
            elgg_echo('task:saveasone_gamepoints') . "</a>";
        $form_body .= $link_save_game_points;
    }
    $form_body .= "<input
      type=\"text\"
      name=\"grading_global\"
      value=\"" . $grading_global . "\"
      style=\"width: 80px\"/>";

    //======================================================================================

    $form_body .= "</div>";
    $form_body .= "</div>";
}

echo elgg_echo($form_body);

?>

<script type="text/javascript">
    function task_show_general_comments() {
        var commentsDiv = document.getElementById('commentsDiv');
        if (commentsDiv.style.display == 'none') {
            commentsDiv.style.display = 'block';
        } else {
            commentsDiv.style.display = 'none';
        }
    }
    function task_show_question_body() {
        var questionbodyDiv = document.getElementById('questionbodyDiv');
        if (questionbodyDiv.style.display == 'none') {
            questionbodyDiv.style.display = 'block';
        } else {
            questionbodyDiv.style.display = 'none';
        }
    }
    //======================================================================================
    function task_update_all_gradings(k, membersguidsarray, taskpost, max_grading) {
        var url = "<?php echo elgg_get_site_url(); ?>mod/task/actions/task/update_grading.php";
        var i = 0;
        while (i < k) {
            var user_guid = membersguidsarray[i];
            var name = 'grading_' + user_guid;
            var grading = document.getElementsByName(name).item(0).value;
            grading = grading.replace(',', '.');
            if (((max_grading != undefined) && (isNaN(grading) || (grading > max_grading) || (grading < 0))) || ((max_grading == undefined) && (isNaN(grading)))) {
                alert("<?php echo elgg_echo("task:bad_qualify_grading");?>");
                return;
            } else {
                var this_url = location.href;
                var postdata = {taskpost: taskpost, user_guid: user_guid, grading: grading};
                $.post(url, postdata);
            }
            i++;
        }
        alert("<?php echo elgg_echo("task:qualified");?>");
    }
    //======================================================================================
    function task_update_all_gradings_as_one(k, membersguidsarray, taskpost, max_grading) {
        var r = confirm("<?php echo elgg_echo("task:areyousure");?>");
        if (r == false)
            return;
        var url = "<?php echo elgg_get_site_url(); ?>mod/task/actions/task/update_grading.php";
        var i = 0;
        while (i < k) {
            var user_guid = membersguidsarray[i];
            var name = 'grading_global';
            var grading = document.getElementsByName(name).item(0).value;
            grading = grading.replace(',', '.');
            if (((max_grading != undefined) && (isNaN(grading) || (grading > max_grading) || (grading < 0))) || ((max_grading == undefined) && (isNaN(grading)))) {
                alert("<?php echo elgg_echo("task:bad_qualify_grading");?>");
                return;
            } else {
                var this_url = location.href;
                var postdata = {taskpost: taskpost, user_guid: user_guid, grading: grading};
                $.post(url, postdata);
            }
            i++;
        }
        alert("<?php echo elgg_echo("task:qualified");?>");
    }
    //======================================================================================

</script>
