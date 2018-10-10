<?php

elgg_load_library('task');

$task = $vars['entity'];
$taskpost = $task->getGUID();

if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
    $grading_label = elgg_echo('task:mark');
else
    $grading_label = elgg_echo('task:game_points');

$user = $vars['user'];
$offset = $vars['offset'];
$grading = $vars['grading'];
$user_guid = $user->getGUID();
$owner_response = $vars['owner_response'];

$info = "<div class=\"task_options\">";
if ((!$task->task_rubric) && (!task_check_status($task))) {
    if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
        if (strcmp($task->type_mark, 'task_type_mark_numerical') == 0) {
            $max_grading = $task->max_mark;
        }
    }
    if (strcmp($grading, "not_qualified") != 0) {
        if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
            $grading = number_format($grading, 2);
    } else {
        $grading = "";
    }
    $name_grading = "grading_" . $user_guid;
    $grading_input = task_grading_input($task, $grading, $name_grading);
    if ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) {
        $grading_input = "<input type=\"text\"  name=\"" . $name_grading . "\" value=\"" . $grading . "\"  style=\"width: 80px\"/>";
        $link_update_grading .= "<a  onclick=\"javascript:task_update_grading(" . $user_guid . "," . $taskpost . "," . $max_grading . ");return true;\">" . elgg_echo('task:save') . "</a>";
    } else {
        $link_update_grading .= "<a  onclick=\"javascript:task_update_grading(" . $user_guid . "," . $taskpost . ");return true;\">" . elgg_echo('task:save') . "</a>";
    }
    $info .= $grading_label . ": " . $grading_input . " " . $link_update_grading;
} else {
    if (strcmp($grading, "not_qualified") != 0) {
        if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
            $grading = number_format($grading, 2);
        $grading_output = task_grading_output($task, $grading);
        $info .= $grading_label . ": " . $grading_output;
    } else {
        $info .= elgg_echo('task:not_qualified');
    }
}
$info .= "</div>";

$member = $vars['user'];
$icon = elgg_view_entity_icon($member, 'small');

if (strcmp($task->type_delivery, 'online') == 0) {

    $info .= $vars['link'] . "<br>";

    $previous_user_responses = $vars['previous_user_responses'];
    if (!empty($previous_user_responses)) {
        $show_previous_responses_label = elgg_echo("task:previous_responses");
        $name_div = $user_guid;
        $info .= "<div class=\"contentWrapper\">";
        $info .= "<p align=\"left\"><a onclick=\"task_show_previous_responses($name_div);\" style=\"cursor:hand;\">$show_previous_responses_label</a></p>";
        $info .= "<div id=\"" . $name_div . "\" style=\"display:none\">";
        $i = 1;
        foreach ($previous_user_responses as $one_previous_user_response) {
            $previous_user_response = $previous_user_responses[$i];
            $grading = "";
            if (strcmp($previous_user_response->grading, 'not_qualified') != 0) {
                if (strcmp($task->type_grading, 'task_type_grading_marks') == 0) {
                    $grading = number_format($previous_user_response->grading, 2);
                } else {
                    $grading = $previous_user_response->grading;
                }
                $grading_output = task_grading_output($task, $grading);
                $grading = $grading_label . ": " . $grading_output;
            } else {
                $grading = elgg_echo("task:not_qualified");
            }
            $answer_time = $previous_user_response->answer_time;
            $friendly_answer_time = date("j M Y", $answer_time) . " " . elgg_echo("task:at") . " " . date("G:i", $answer_time);
            $url_previous = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/qualify/" . $taskpost . "/" . $user_guid . "/" . $answer_time . "/" . $offset);
            $url_text_previous = elgg_echo('task:previous_response') . " (" . $friendly_answer_time . ") " . $grading;
            $link_previous = "<a href=\"{$url_previous}\">{$url_text_previous}</a>";
            $info .= $link_previous . "<br>";
            $i = $i + 1;
        }
        $info .= "</div>";
        $info .= "</div>";
    }
} else {
    $info .= $vars['link'];
}

echo elgg_view_image_block($icon, $info);

/////////////////////////////////////////////////////////////////

?>

<script type="text/javascript">

    function task_show_previous_responses(name_div) {
        var resultsDiv_show_previous_responses = document.getElementById(name_div);
        if (resultsDiv_show_previous_responses.style.display == 'none') {
            resultsDiv_show_previous_responses.style.display = 'block';
        } else {
            resultsDiv_show_previous_responses.style.display = 'none';
        }
    }

    function task_update_grading(user_guid, taskpost, max_grading) {
        var url = "<?php echo elgg_get_site_url(); ?>mod/task/actions/task/update_grading.php";
        var name = 'grading_' + user_guid;
        var grading = document.getElementsByName(name).item(0).value;
        grading = grading.replace(',', '.');
        if (((max_grading != undefined) && (isNaN(grading) || (grading > max_grading) || (grading < 0))) || ((max_grading == undefined) && (isNaN(grading)))) {
            alert("<?php echo elgg_echo("task:bad_qualify_grading");?>");
        } else {
            var this_url = location.href;
            var postdata = {taskpost: taskpost, user_guid: user_guid, grading: grading};
            $.post(url, postdata);
            alert("<?php echo elgg_echo("task:qualified");?>");
        }
    }


</script>      