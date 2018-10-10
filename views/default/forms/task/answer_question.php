<?php

$task = $vars['task'];
$question_body = $vars['question_body'];
$response_type = $vars['response_type'];
$response_text = $vars['response_text'];
$user_response_guid = $vars['user_response_guid'];
switch ($response_type) {
    case 'simple':
        $response_html = $vars['response_html'];
        break;
    case 'urls_files':
        $response_urls = $vars['response_urls'];
        $response_file = $vars['response_file'];
        $response_files = $vars['response_files'];
        break;
}
$previous_user_responses_body = $vars['previous_user_responses_body'];
$comments_body = $vars['comments_body'];

//Grading
if ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) {
    $max_grading_label = elgg_echo("task:max_mark_label");
    $max_grading = number_format($task->max_mark, 2);
} elseif ((strcmp($task->type_grading, 'task_type_grading_marks') != 0) && ($task->task_rubric)) {
    $max_grading_label = elgg_echo("task:max_game_points_label");
    $max_grading = $task->max_game_points;
}

if ((strcmp($question_body, "") != 0) || (strcmp($max_grading, "") != 0) || ($task->task_rubric)) {
    ?>
    <div class="task_frame_blue">

        <?php
        //Question body
        if (strcmp($question_body, "") != 0) {
            echo $question_body;
            echo "<br>";
        }
        if (strcmp($max_grading, "") != 0) {
            ?>
            <p><b><?php echo $max_grading_label . ": $max_grading"; ?></b></p>
            <?php
        }

        if ($task->task_rubric) {
            ?>
            <p><b>
                    <?php
                    echo elgg_echo("task:rubric_label");
                    $rubric = get_entity($task->rubric_guid);
                    echo elgg_view('rubric/show_rubric', array('entity' => $rubric, 'view_type' => 'show', 'url' => elgg_get_site_url(), 'task_guid' => '', 'container_guid' => $task->container_guid, 'student_guid' => '', 'rating' => ''));
                    ?>
                </b></p>
            <?php
        }
        ?>
    </div>
    <br>
    <?php
}
?>
<?php if ($task->subgroups) {
    $user_subgroup = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'container_guids' => $task->container_guid, 'relationship' => 'member', 'inverse_relationship' => false, 'relationship_guid' => $user_guid));
}
// Mostrar formulario de respuesta si el tipo de respuesta es individual o es por subgrupos y el usuario está inscrito en alguno
if (!$task->subgroups || ($task->subgroups && $user_subgroup[0])) {
    ?>
    <div class="task_frame_green">
        <?php

        //Previous user responses

        if (strcmp($previous_user_responses_body, "") != 0) {
            ?>
            <p><b><?php echo $previous_user_responses_body; ?></b></p>
            <?php
        }

        //Response

        ?>
        <p><b><?php echo elgg_echo("task:response_label"); ?></b></p>
        <?php

        if (!empty($user_response_guid)) {
            $user_response = get_entity($user_response_guid);
            $time_created = $user_response->time_created;
            $time_updated = $user_response->answer_time;

            $friendly_date_created = date('j M Y', $time_created);
            $friendly_time_created = date('G:i', $time_created);

            echo elgg_echo('task:response_created') . " " . $friendly_date_created . " " . elgg_echo('task:at') . " " . $friendly_time_created;

            if (($time_updated) && ($time_created != $time_updated)) {
                $friendly_date_updated = date('j M Y', $time_updated);
                $friendly_time_updated = date('G:i', $time_updated);

                echo "<br>";
                echo elgg_echo('task:response_updated') . " " . $friendly_date_updated . " " . elgg_echo('task:at') . " " . $friendly_time_updated;

            }
            echo "<br><br>";
        }

        ?>

        <?php
        switch ($response_type) {
            case 'simple':
                ?>
                <p><b><?php echo elgg_echo("task:response_html_label"); ?></b></p>
                <p><?php echo $response_html; ?></p>
                <?php
                break;
            case 'urls_files':
                ?>
                <p><b><?php echo elgg_echo("task:response_urls_label"); ?></b></p>
                <p><?php echo $response_urls; ?></p>

                <p><b><?php echo elgg_echo("task:response_files_label"); ?></b></p>
                <p><?php echo $response_file; ?></p>
                <?php
                if ($response_files) {
                    if ((count($response_files) > 0) && (strcmp($response_files[0]->title, "") != 0)) {
                        foreach ($response_files as $file) {
                            ?>
                            <div class="file_wrapper">
                                <a class="bold"
                                   onclick="changeFormValue(<?php echo $file->getGUID(); ?>), changeImage(<?php echo $file->getGUID(); ?>)">
                                    <img id="image_<?php echo $file->getGUID(); ?>"
                                         src="<?php echo elgg_get_site_url(); ?>mod/task/graphics/tick.jpeg">
                                </a>
                                <span><?php echo $file->title ?></span>
                                <?php
                                echo elgg_view("input/hidden", array('name' => $file->getGUID(), 'internalid' => $file->getGUID(), 'value' => '0'));
                                ?>
                            </div>
                            <br>
                            <?php
                        }
                    }
                }
                break;
        }
        ?>
        <br>

        <?php
        //Comments

        ?>
        <p><b><?php echo elgg_echo('task:comments_label'); ?></b></p>
        <p><?php echo $comments_body; ?></p><br>

    </div>
    <br>

<?php } ?>

<script type="text/javascript">

    function changeImage(num) {
        if (document.getElementById('image_' + num).src == "<?php echo elgg_get_site_url(); ?>mod/task/graphics/tick.jpeg")
            document.getElementById('image_' + num).src = "<?php echo elgg_get_site_url(); ?>mod/task/graphics/delete_file.jpeg";
        else
            document.getElementById('image_' + num).src = "<?php echo elgg_get_site_url(); ?>mod/task/graphics/tick.jpeg";
    }
</script>
