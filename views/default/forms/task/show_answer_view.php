<?php

elgg_load_library('task');

$task = $vars['entity'];
$taskpost = $task->getGUID();

if (strcmp($task->type_grading, 'task_type_grading_marks') == 0)
    $grading_label = elgg_echo('task:mark');
else
    $grading_label = elgg_echo('task:game_points');

$user = $vars['user'];
$grading = $vars['grading'];

$info = "";

if (($task->grading_visibility) && ((($task->public_global_marks) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0))) {

    $info = "<div class=\"task_options\">";
    if (strcmp($grading, "") != 0) {
        $grading_output = task_grading_output($task, $grading);
        $info .= $grading_label . ": " . $grading_output;
    } else {
        $info .= elgg_echo('task:not_qualified');
    }
    $info .= "</div>";
}

$member = $vars['user'];
$icon = elgg_view_entity_icon($member, 'small');

$info .= $vars['link'] . "<br>";

if (strcmp($task->type_delivery, 'online') == 0) {

    $previous_user_responses = $vars['previous_user_responses'];
    if (!empty($previous_user_responses)) {
        $show_other_previous_responses_label = elgg_echo("task:previous_responses");
        $name_div = $user->getGUID();
        $info .= "<div class=\"contentWrapper\">";
        $info .= "<p align=\"left\"><a onclick=\"task_show_other_previous_responses($name_div);\" style=\"cursor:hand;\">$show_other_previous_responses_label</a></p>";
        $info .= "<div id=" . "\"$name_div\"" . "style=\"display:none\">";
        $i = 1;
        foreach ($previous_user_responses as $one_previous_user_response) {
            $previous_user_response = $previous_user_responses[$i];
            if (($task->grading_visibility) && ((($task->public_global_marks) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0))) {
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
            }
            $answer_time = $previous_user_response->answer_time;
            $friendly_answer_time = date("d/m/Y", $answer_time) . " " . elgg_echo("task:at") . " " . date("G:i", $answer_time);
            $url_previous = elgg_add_action_tokens_to_url(elgg_get_site_url() . "task/show_answer/" . $taskpost . "/" . $user->getGUID() . "/" . $answer_time . "/" . "0");
            if (($task->grading_visibility) && ((($task->public_global_marks) && (strcmp($task->type_grading, 'task_type_grading_marks') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0))) {
                $url_text_previous = elgg_echo('task:previous_response') . " (" . $friendly_answer_time . ") " . $grading;
            } else {
                $url_text_previous = elgg_echo('task:previous_response') . " (" . $friendly_answer_time . ")";
            }
            $link_previous = "<a href=\"{$url_previous}\">{$url_text_previous}</a>";
            $info .= $link_previous . "<br>";
            $i = $i + 1;
        }
        $info .= "</div>";
        $info .= "</div>";
    }
}

echo elgg_view_image_block($icon, $info);

/////////////////////////////////////////////////////////////////

?>

<script type="text/javascript">

    function task_show_other_previous_responses(name_div) {
        var resultsDiv_show_other_previous_responses = document.getElementById(name_div);
        if (resultsDiv_show_other_previous_responses.style.display == 'none') {
            resultsDiv_show_other_previous_responses.style.display = 'block';
        } else {
            resultsDiv_show_other_previous_responses.style.display = 'none';
        }
    }

</script>      