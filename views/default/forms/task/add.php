<div class="contentWrapper">

    <?php
    $user_guid = elgg_get_logged_in_user_guid();
    $container_guid = $vars['container_guid'];
    $container = get_entity($container_guid);
    $action = "task/add";
    if (!elgg_is_sticky_form('add_task')) {
        $title = "";
        $question_html = "";
        $question_type = $vars['question_type'];
        switch ($question_type) {
            case 'urls_files':
                $question_comp_urls = array();
                break;
        }
        $type_delivery = 'online';
        $response_type = 'simple';
        $responses_visibility = false;
        $responses_comments_visibility = false;

        //$opendate =  "";
        $opendate = date("Y-m-d", time());

        $closedate = "";
        //$closedate =  date("Y-m-d", time()+24*60*60);
        $opentime = "00:00";
        $closetime = "00:00";
        $option_activate_value = 'task_activate_now';
        $option_close_value = 'task_not_close';
        $assessable = true;
        $not_response_is_zero = false;
        $grading_visibility = true;
        $type_grading = 'task_type_grading_marks';
        $max_mark = "10";
        $type_mark = 'task_type_mark_numerical';
        $mark_weight = "100";
        $max_game_points = "";
        $public_global_marks = false;
        $task_rubric = false;
        $rubric_guid = "";
        $feedback = "";
        $subgroups = false;
        $tags = "";
        // Acceso por defecto para el subgrupo si estamos en un subgrupo
        if ($container->getContainerEntity() instanceof ElggGroup)
            $access_id = $container->teachers_acl;
        // Acceso por defecto para el grupo si estamos en un grupo
        else
            $access_id = $container->group_acl;
    } else {
        $title = elgg_get_sticky_value('add_task', 'title');
        $question_html = elgg_get_sticky_value('add_task', 'question_html');
        $question_type = elgg_get_sticky_value('add_task', 'question_type');
        switch ($question_type) {
            case 'urls_files':
                $question_urls_names = elgg_get_sticky_value('add_task', 'question_urls_names');
                $question_urls = elgg_get_sticky_value('add_task', 'question_urls');
                $i = 0;
                $question_comp_urls = array();
                foreach ($question_urls as $url) {
                    $question_comp_urls[$i] = $question_urls_names[$i] . Chr(24) . $question_urls[$i];
                    $i = $i + 1;
                }
                break;
        }
        $type_delivery = elgg_get_sticky_value('add_task', 'type_delivery');
        $response_type = elgg_get_sticky_value('add_task', 'response_type');
        if (strcmp($type_delivery, 'online') == 0) {
            $responses_visibility = elgg_get_sticky_value('add_task', 'responses_visibility');
            $responses_comments_visibility = elgg_get_sticky_value('add_task', 'responses_comments_visibility');
        }
        $opendate = elgg_get_sticky_value('add_task', 'opendate');
        $closedate = elgg_get_sticky_value('add_task', 'closedate');
        $opentime = elgg_get_sticky_value('add_task', 'opentime');
        $closetime = elgg_get_sticky_value('add_task', 'closetime');
        $option_activate_value = elgg_get_sticky_value('add_task', 'option_activate_value');
        $option_close_value = elgg_get_sticky_value('add_task', 'option_close_value');
        $assessable = elgg_get_sticky_value('add_task', 'assessable');
        $not_response_is_zero = elgg_get_sticky_value('add_task', 'not_response_is_zero');
        $grading_visibility = elgg_get_sticky_value('add_task', 'grading_visibility');
        $type_grading = elgg_get_sticky_value('add_task', 'type_grading');
        if (strcmp($type_grading, 'task_type_grading_marks') == 0) {
            $type_mark = elgg_get_sticky_value('add_task', 'type_mark');
            if (strcmp($type_mark, 'task_type_mark_numerical') == 0) {
                $max_mark = elgg_get_sticky_value('add_task', 'max_mark');
            }
            $mark_weight = elgg_get_sticky_value('add_task', 'mark_weight');
            if ($grading_visibility)
                $public_global_marks = elgg_get_sticky_value('add_task', 'public_global_marks');
        } else {
            $max_game_points = elgg_get_sticky_value('add_task', 'max_game_points');
        }
        $task_rubric = elgg_get_sticky_value('add_task', 'task_rubric');
        $rubric = elgg_get_sticky_value('add_task', 'rubric_guid');
        $feedback = elgg_get_sticky_value('add_task', 'feedback');
        $subgroups = elgg_get_sticky_value('add_task', 'subgroups');
        $tags = elgg_get_sticky_value('add_task', 'tasktags');
        $access_id = elgg_get_sticky_value('add_task', 'access_id');
    }

    elgg_clear_sticky_form('add_task');

    if (strcmp($mark_weight, "") == 0)
        $mark_weight = 0;

    if (strcmp($max_game_points, "") == 0)
        $max_game_points = 0;

    $options_response_type = array();
    $options_response_type[0] = elgg_echo('task:response_type_simple');
    $options_response_type[1] = elgg_echo('task:response_type_urls_files');
    $op_response_type = array();
    $op_response_type[0] = "simple";
    $op_response_type[1] = "urls_files";
    $checked_radio_response_type_0 = "";
    $checked_radio_response_type_1 = "";

    switch ($response_type) {
        case 'simple':
            $checked_radio_response_type_0 = "checked = \"checked\"";
            break;
        case 'urls_files':
            $checked_radio_response_type_1 = "checked = \"checked\"";
            break;
    }

    ?>
    <br/>
    <?php

    $tabs = array('simple' => array('title' => elgg_echo('task:question_simple'), 'url' => '?question_type=simple', 'selected' => $question_type == 'simple'),
        'urls_files' => array('title' => elgg_echo('task:question_urls_files'), 'url' => '?question_type=urls_files', 'selected' => $question_type == 'urls_files'));

    echo elgg_view('navigation/tabs', array('tabs' => $tabs));

    ?>
    <br/>
    <?php

    $options_activate = array();
    $options_activate[0] = elgg_echo('task:activate_now');
    $options_activate[1] = elgg_echo('task:activate_date');
    $op_activate = array();
    $op_activate[0] = 'task_activate_now';
    $op_activate[1] = 'task_activate_date';
    if (strcmp($option_activate_value, $op_activate[0]) == 0) {
        $checked_radio_activate_0 = "checked = \"checked\"";
        $checked_radio_activate_1 = "";
        $style_display_activate = "display:none";
    } else {
        $checked_radio_activate_0 = "";
        $checked_radio_activate_1 = "checked = \"checked\"";
        $style_display_activate = "display:block";
    }
    $options_close = array();
    $options_close[0] = elgg_echo('task:not_close');
    $options_close[1] = elgg_echo('task:close_date');
    $op_close = array();
    $op_close[0] = 'task_not_close';
    $op_close[1] = 'task_close_date';
    if (strcmp($option_close_value, $op_close[0]) == 0) {
        $checked_radio_close_0 = "checked = \"checked\"";
        $checked_radio_close_1 = "";
        $style_display_close = "display:none";
    } else {
        $checked_radio_close_0 = "";
        $checked_radio_close_1 = "checked = \"checked\"";
        $style_display_close = "display:block";
    }
    $opendate_label = elgg_echo('task:opendate');
    $closedate_label = elgg_echo('task:closedate');
    $opentime_label = elgg_echo('task:opentime');
    $closetime_label = elgg_echo('task:closetime');


    $options_type_delivery = array();
    $options_type_delivery[0] = elgg_echo('task:type_delivery_online');
    $options_type_delivery[1] = elgg_echo('task:type_delivery_offline');
    $op_type_delivery = array();
    $op_type_delivery[0] = 'online';
    $op_type_delivery[1] = 'offline';
    if (strcmp($type_delivery, 'online') == 0) {
        $checked_radio_type_delivery_0 = "checked = \"checked\"";
        $checked_radio_type_delivery_1 = "";
        $style_display_type_delivey = "display:block";
    } else {
        $checked_radio_type_delivery_0 = "";
        $checked_radio_type_delivery_1 = "checked = \"checked\"";
        $style_display_type_delivery = "display:none";
    }

    $assessable_label = elgg_echo('task:assessable_label');
    if ($assessable) {
        $selected_assessable = "checked = \"checked\"";
        $style_display_assessable = "display:block";
    } else {
        $selected_assessable = "";
        $style_display_assessable = "display:none";
    }

    $not_response_is_zero_label = elgg_echo('task:not_response_is_zero_label');
    if ($not_response_is_zero) {
        $selected_not_response_is_zero = "checked = \"checked\"";
    } else {
        $selected_not_response_is_zero = "";
    }

    $grading_visibility_label = elgg_echo('task:grading_visibility_label');
    if ($grading_visibility) {
        $selected_grading_visibility = "checked = \"checked\"";
        $style_display_grading_visibility = "display:block";
    } else {
        $selected_grading_visibility = "";
        $style_display_grading_visibility = "display:none";
    }

    $type_grading_label = elgg_echo('task:type_grading_label');
    $options_type_grading = array();
    $options_type_grading[0] = elgg_echo('task:type_grading_marks');
    $options_type_grading[1] = elgg_echo('task:type_grading_game_points');
    $op_type_grading = array();
    $op_type_grading[0] = 'task_type_grading_marks';
    $op_type_grading[1] = 'task_type_grading_game_points';
    if (strcmp($type_grading, $op_type_grading[0]) == 0) {
        $checked_radio_type_grading_0 = "checked = \"checked\"";
        $checked_radio_type_grading_1 = "";
        $style_display_type_grading = "display:block";
        $style_display_type_grading_2 = "display:none";
        $style_display_type_grading_3 = "display:block";
    } else {
        $checked_radio_type_grading_0 = "";
        $checked_radio_type_grading_1 = "checked = \"checked\"";
        $style_display_type_grading = "display:none";
        $style_display_type_grading_2 = "display:block";
        $style_display_type_grading_3 = "display:none";
    }

    $max_mark_label = elgg_echo('task:max_mark_label');
    $max_mark_array = array('10' => '10', '100' => '100');
    $options_max_mark = array();
    $options_max_mark[0] = elgg_echo('10');
    $options_max_mark[1] = elgg_echo('100');
    $op_max_mark = array();
    $op_max_mark[0] = '10';
    $op_max_mark[1] = '100';
    if (strcmp($max_mark, $op_max_mark[0]) == 0) {
        $checked_radio_max_mark_0 = "checked = \"checked\"";
        $checked_radio_max_mark_1 = "";
    }
    if (strcmp($max_mark, $op_max_mark[1]) == 0) {
        $checked_radio_max_mark_0 = "";
        $checked_radio_max_mark_1 = "checked = \"checked\"";
    }
    $type_mark_label = elgg_echo('task:type_mark_label');
    $options_type_mark = array();
    $options_type_mark[0] = elgg_echo('task:type_mark_numerical');
    $options_type_mark[1] = elgg_echo('task:type_mark_textual');
    $options_type_mark[2] = elgg_echo('task:type_mark_apto');
    $op_type_mark = array();
    $op_type_mark[0] = 'task_type_mark_numerical';
    $op_type_mark[1] = 'task_type_mark_textual';
    $op_type_mark[2] = 'task_type_mark_apto';
    if (strcmp($type_mark, $op_type_mark[0]) == 0) {
        $checked_radio_type_mark_0 = "checked = \"checked\"";
        $checked_radio_type_mark_1 = "";
        $checked_radio_type_mark_2 = "";
        $style_display_type_mark = "display:block";
    }
    if (strcmp($type_mark, $op_type_mark[1]) == 0) {
        $checked_radio_type_mark_0 = "";
        $checked_radio_type_mark_1 = "checked = \"checked\"";
        $checked_radio_type_mark_2 = "";
        $style_display_type_mark = "display:none";
    }
    if (strcmp($type_mark, $op_type_mark[2]) == 0) {
        $checked_radio_type_mark_0 = "";
        $checked_radio_type_mark_1 = "";
        $checked_radio_type_mark_2 = "checked = \"checked\"";
        $style_display_type_mark = "display:none";
    }

    $mark_weight_label = elgg_echo('task:mark_weight_label');

    $public_global_marks_label = elgg_echo('task:public_global_marks_label');
    if ($public_global_marks) {
        $selected_public_global_marks = "checked = \"checked\"";
    } else {
        $selected_public_global_marks = "";
    }

    $max_game_points_label = elgg_echo('task:max_game_points_label');

    if (strcmp($type_delivery, 'online') == 0) {
        $responses_visibility_label = elgg_echo('task:responses_visibility_label');
        if ($responses_visibility) {
            $selected_responses_visibility = "checked = \"checked\"";
            $style_display_responses_visibility = "display:block";
        } else {
            $selected_responses_visibility = "";
            $style_display_responses_visibility = "display:none";
        }

        $responses_comments_visibility_label = elgg_echo('task:responses_comments_visibility_label');
        if ($responses_comments_visibility) {
            $selected_responses_comments_visibility = "checked = \"checked\"";
        } else {
            $selected_responses_comments_visibility = "";
        }
    }

    $rubric_label = elgg_echo('task:rubric_label');
    if ($task_rubric) {
        $selected_rubric = "checked = \"checked\"";
        $style_display_rubric = "display:block";
        $style_display_rubric_2 = "display:block";
    } else {
        $selected_rubric = "";
        $style_display_rubric = "display:none";
        $style_display_rubric_2 = "display:none";
    }

    $feedback_label = elgg_echo('task:feedback_label');
    if (strcmp($feedback, "not_feedback") == 0)
        $feedback = "";
    $feedback_textbox = elgg_view('input/longtext', array('name' => 'feedback', 'value' => $feedback));

    $subgroups_label = elgg_echo('task:subgroups_label');
    if ($subgroups) {
        $selected_subgroups = "checked = \"checked\"";
    } else {
        $selected_subgroups = "";
    }

    $tag_label = elgg_echo('tags');
    $tag_input = elgg_view('input/tags', array('name' => 'tasktags', 'value' => $tags));
    $access_label = elgg_echo('access');
    $access_input = elgg_view('input/access', array('name' => 'access_id', 'value' => $access_id));

    ?>

    <form action="<?php echo elgg_get_site_url() . "action/" . $action ?>" name="add_task" enctype="multipart/form-data"
          method="post">

        <?php echo elgg_view('input/securitytoken'); ?>

        <p>
            <b><?php echo elgg_echo("task:title_label"); ?></b><br>
            <?php echo elgg_view("input/text", array('name' => 'title', 'value' => $title)); ?>
        </p>

        <p>
            <b> <?php echo elgg_echo("task:form_question_simple"); ?> </b>
            <?php echo elgg_view("input/longtext", array('name' => 'question_html', 'value' => $question_html)); ?>
        </p>

        <?php
        switch ($question_type) {
            case 'urls_files':
                ?>
                <p>
                <b> <?php echo elgg_echo("task:form_question_urls"); ?></b><br>
                <?php
                if ((count($question_comp_urls) > 0) && (strcmp($question_comp_urls[0], "") != 0)) {
                    $i = 0;
                    foreach ($question_comp_urls as $url) {
                        ?>
                        <p class="clone_urls">
                            <?php
                            $comp_url = explode(Chr(24), $url);
                            $comp_url = array_map('trim', $comp_url);
                            $url_name = $comp_url[0];
                            $url_value = $comp_url[1];
                            /* echo ("<b>" . elgg_echo("task:form_question_url_name") . "</b>");
                            echo elgg_view("input/text", array("name" => "question_urls_names[]","value" => $url_name));*/
                            echo("<b>" . elgg_echo("task:form_question_url") . "</b>");
                            echo elgg_view("input/text", array("name" => "question_urls[]", "value" => $url_value));
                            if ($i > 0) {
                                ?>
                                <!-- remove url -->
                                <a class="remove" href="#"
                                   onclick="$(this).parent().slideUp(function(){ $(this).remove() }); return false"><?php echo elgg_echo("delete"); ?></a>
                                <?php
                            }
                            ?>
                        </p>
                        <?php
                        $i = $i + 1;
                    }
                } else {
                    ?>
                    <p class="clone_urls">
                        <?php
                        $comp_url = explode(Chr(24), $question_comp_urls);
                        $comp_url = array_map('trim', $comp_url);
                        $url_name = $comp_url[0];
                        $url_value = $comp_url[1];
                        /*echo ("<b>" . elgg_echo("task:form_question_url_name") . "</b>");
                        echo elgg_view("input/text", array("name" => "question_urls_names[]","value" => $url_name));*/
                        echo("<b>" . elgg_echo("task:form_question_url") . "</b>");
                        echo elgg_view("input/text", array("name" => "question_urls[]", "value" => $url_value));
                        ?>
                    </p>
                    <?php
                }
                ?>
                <!-- add link to add more urls which triggers a jquery clone function -->
                <a href="#" class="add" rel=".clone_urls"><?php echo elgg_echo("task:add_url"); ?></a>
                <br><br>
                </p>
                <p>
                    <b>
                        <?php echo elgg_echo("task:form_question_files"); ?>
                    </b>
                    <br>
                    <?php echo elgg_view("input/file", array('name' => 'upload[]', 'class' => 'multi')); ?>
                </p>
                <?php
                break;
        }
        ?>

        <!-- add the add/delete functionality  -->
        <script type="text/javascript">
            // remove function for the jquery clone plugin
            $(function () {
                var removeLink = '<a class="remove" href="#" onclick="$(this).parent().slideUp(function(){ $(this).remove() }); return false"><?php echo elgg_echo("delete");?></a>';
                $('a.add').relCopy({append: removeLink});
            });
        </script>

        <br>
        <p>
            <b><?php echo elgg_echo("task:type_delivery_label"); ?></b><br>
            <?php echo "<input type=\"radio\" name=\"type_delivery\" value=$op_type_delivery[0] $checked_radio_type_delivery_0 onChange=\"task_show_type_delivery()\">$options_type_delivery[0]"; ?>
            <br>
            <?php echo "<input type=\"radio\" name=\"type_delivery\" value=$op_type_delivery[1] $checked_radio_type_delivery_1 onChange=\"task_show_type_delivery()\">$options_type_delivery[1]"; ?>
            <br>
        </p><br>
        <div id="resultsDiv_type_delivery" style="<?php echo $style_display_type_delivery; ?>;">
            <p>
                <b><?php echo elgg_echo("task:response_type_label"); ?></b><br>
                <?php echo "<input type=\"radio\" name=\"response_type\" value=$op_response_type[0] $checked_radio_response_type_0>$options_response_type[0]"; ?>
                <br>
                <?php echo "<input type=\"radio\" name=\"response_type\" value=$op_response_type[1] $checked_radio_response_type_1>$options_response_type[1]"; ?>
                <br>
            </p><br>
            <p>
                <b>
                    <?php echo "<input type = \"checkbox\" name = \"responses_visibility\" onChange=\"task_show_responses_visibility()\" $selected_responses_visibility> $responses_visibility_label"; ?>
                </b>
            </p><br>
            <div id="resultsDiv_responses_visibility" style="<?php echo $style_display_responses_visibility; ?>;">
                <p>
                    <b>
                        <?php echo "<input type = \"checkbox\" name = \"responses_comments_visibility\" $selected_responses_comments_visibility> $responses_comments_visibility_label"; ?>
                    </b>
                </p><br>
            </div>
        </div>

        <table class="task_dates_table">
            <tr>
                <td>
                    <p>
                        <b><?php echo elgg_echo('task:activate_label'); ?></b><br>
                        <?php echo "<input type=\"radio\" name=\"option_activate_value\" value=$op_activate[0] $checked_radio_activate_0 onChange=\"task_show_activate_time()\">$options_activate[0]"; ?>
                        <br>
                        <?php echo "<input type=\"radio\" name=\"option_activate_value\" value=$op_activate[1] $checked_radio_activate_1 onChange=\"task_show_activate_time()\">$options_activate[1]"; ?>
                        <br>
                    <div id="resultsDiv_activate" style="<?php echo $style_display_activate; ?>;">
                        <?php echo $opendate_label; ?><br>
                        <?php echo elgg_view('input/date', array('autocomplete' => 'off', 'class' => 'task-compressed-date', 'name' => 'opendate', 'value' => $opendate)); ?>
                        <?php echo "<br>" . $opentime_label; ?> <br>
                        <?php echo "<input type = \"text\" name = \"opentime\" value = $opentime>"; ?>
                    </div>
                    </p><br>
                </td>
                <td>
                    <p>
                        <b><?php echo elgg_echo('task:close_label'); ?></b><br>
                        <?php echo "<input type=\"radio\" name=\"option_close_value\" value=$op_close[0] $checked_radio_close_0 onChange=\"task_show_close_time()\">$options_close[0]"; ?>
                        <br>
                        <?php echo "<input type=\"radio\" name=\"option_close_value\" value=$op_close[1] $checked_radio_close_1 onChange=\"task_show_close_time()\">$options_close[1]"; ?>
                        <br>
                    <div id="resultsDiv_close" style="<?php echo $style_display_close; ?>;">
                        <?php echo $closedate_label; ?><br>
                        <?php echo elgg_view('input/date', array('autocomplete' => 'off', 'class' => 'task-compressed-date', 'name' => 'closedate', 'value' => $closedate)); ?>
                        <?php echo "<br>" . $closetime_label; ?> <br>
                        <?php echo "<input type = \"text\" name = \"closetime\" value = $closetime>"; ?>
                    </div>
                    </p><br>
                </td>
            </tr>
        </table>
        <p>
            <b>
                <?php echo "<input type = \"checkbox\" name = \"assessable\" onChange=\"task_show_assessable()\" $selected_assessable> $assessable_label"; ?>
            </b>
        </p><br>
        <div id="resultsDiv_assessable" style="<?php echo $style_display_assessable; ?>;">
            <div id="resultsDiv_type_grading_3" style="<?php echo $style_display_type_grading_3; ?>;">
                <p>
                    <b>
                        <?php echo "<input type = \"checkbox\" name = \"not_response_is_zero\" $selected_not_response_is_zero> $not_response_is_zero_label"; ?>
                    </b>
                </p><br>
            </div>
        </div>
        <p>
            <b>
                <?php echo $type_grading_label; ?>
            </b><br>
            <?php echo "<input type=\"radio\" name=\"type_grading\" value=$op_type_grading[0] $checked_radio_type_grading_0 onChange=\"task_show_type_grading()\">$options_type_grading[0]"; ?>
            <br>
            <?php echo "<input type=\"radio\" name=\"type_grading\" value=$op_type_grading[1] $checked_radio_type_grading_1 onChange=\"task_show_type_grading()\">$options_type_grading[1]"; ?>
            <br>
        </p>
        <div id="resultsDiv_type_grading" style="<?php echo $style_display_type_grading; ?>;">
            <p>
                <b>
                    <?php echo $type_mark_label; ?>
                </b><br>
                <?php echo "<input type=\"radio\" name=\"type_mark\" value=$op_type_mark[0] $checked_radio_type_mark_0 onChange=\"task_show_type_mark(0)\">$options_type_mark[0]"; ?>
                <br>
                <?php echo "<input type=\"radio\" name=\"type_mark\" value=$op_type_mark[1] $checked_radio_type_mark_1 onChange=\"task_show_type_mark(1)\">$options_type_mark[1]"; ?>
                <br>
                <?php echo "<input type=\"radio\" name=\"type_mark\" value=$op_type_mark[2] $checked_radio_type_mark_2 onChange=\"task_show_type_mark(2)\">$options_type_mark[2]"; ?>
                <br>
            </p>
            <div id="resultsDiv_type_mark" style="<?php echo $style_display_type_mark; ?>;">
                <b>
                    <?php echo $max_mark_label; ?>
                </b><br>
                <?php echo "<input type=\"radio\" name=\"max_mark\" value=$op_max_mark[0] $checked_radio_max_mark_0>$options_max_mark[0]"; ?>
                <br>
                <?php echo "<input type=\"radio\" name=\"max_mark\" value=$op_max_mark[1] $checked_radio_max_mark_1>$options_max_mark[1]"; ?>
                <br>
                </p>
            </div>
            <p>
            <p>
                <b><?php echo $mark_weight_label; ?></b>
                <?php echo "<input type = \"text\" name = \"mark_weight\" value = $mark_weight>"; ?>
            </p>
            <br>
            <p>
                <b>
                    <?php echo "<input type = \"checkbox\" name = \"grading_visibility\" onChange=\"task_show_grading_visibility()\" $selected_grading_visibility> $grading_visibility_label"; ?>
                </b>
            </p><br>
            <div id="resultsDiv_grading_visibility" style="<?php echo $style_display_grading_visibility; ?>;">
                <p>
                    <b>
                        <?php echo "<input type = \"checkbox\" name = \"public_global_marks\" $selected_public_global_marks> $public_global_marks_label"; ?>
                    </b>
                </p><br>
            </div>
        </div>
        <?php
        if (elgg_is_active_plugin('rubric')) {
            ?>
            <div id="resultsDiv_type_grading_2" style="<?php echo $style_display_type_grading_2; ?>;">
                <div id="resultsDiv_rubric_2" style="<?php echo $style_display_rubric_2; ?>;">
                    <p>
                        <b><?php echo $max_game_points_label; ?></b>
                        <?php echo "<input type = \"text\" name = \"max_game_points\" value = $max_game_points>"; ?>
                    </p><br>
                </div>
            </div>
            <?php
        }
        if (elgg_is_active_plugin('rubric')) {
            ?>
            <p>
                <b>
                    <?php echo "<input type = \"checkbox\" name = \"task_rubric\" $selected_rubric onChange=\"task_show_rubric()\"> $rubric_label"; ?>
                </b>
            </p>
            <div id="resultsDiv_rubric" style="<?php echo $style_display_rubric; ?>;">
                <?php
                $members = $container->getMembers(array('limit' => false));
                $rubrics = elgg_get_entities_from_metadata(array('type' => 'object', 'subtype' => 'rubric', 'limit' => false, 'owner_guid' => $user_guid));
                foreach ($members as $member) {
                    $member_guid = $member->getGUID();
                    $group_owner_guid = $container->owner_guid;
                    if (($member_guid != $user_guid) && (($group_owner_guid == $member_guid) || (check_entity_relationship($member_guid, 'group_admin', $container_guid)))) {
                        $other_rubrics = elgg_get_entities_from_metadata(array('type' => 'object', 'subtype' => 'rubric', 'limit' => false, 'owner_guid' => $member_guid, 'container_guid' => $container_guid));
                        if ($rubrics)
                            $rubrics = array_merge($rubrics, $other_rubrics);
                        else
                            $rubrics = $other_rubrics;
                    }
                }
                ?>
                <p>
                    <select name="rubric_guid">
                        <?php
                        foreach ($rubrics as $one_rubric) {
                            $one_rubric_guid = $one_rubric->getGUID();
                            $one_rubric_title = $one_rubric->title;
                            ?>
                            <option
                                value="<?php echo $one_rubric_guid; ?>" <?php if ($one_rubric_guid == $rubric_guid) echo "selected=\"selected\""; ?>> <?php echo $one_rubric_title; ?> </option>
                            <?php
                        }
                        ?>
                    </select>
                </p>
                </br>
            </div>
            <?php
        }
        ?>
        <p>
            <b><?php echo $feedback_label; ?></b>
            <?php echo $feedback_textbox; ?>
        </p><br>

        <?php if (!($container->getContainerEntity() instanceof ElggGroup)) { ?>
            <p>
                <b>
                    <?php echo "<input type = \"checkbox\" name = \"subgroups\" $selected_subgroups> $subgroups_label"; ?>
                </b>
            </p><br>
        <?php } ?>

        <p>
            <b>
                <?php echo $tag_label; ?></b><br>
            <?php echo $tag_input; ?></p><br>
        <p>
            <b><?php echo $access_label; ?></b><br>
            <?php echo $access_input; ?>
        </p>

        <?php
        $submit_input_publish = elgg_view('input/submit', array('name' => 'submit', 'value' => elgg_echo("task:publish")));
        echo $submit_input_publish;
        ?>

        <input type="hidden" name="container_guid" value="<?php echo $container_guid; ?>">
        <input type="hidden" name="question_type" value="<?php echo $question_type; ?>">

    </form>

    <script language="javascript">
        function task_show_activate_time() {
            var resultsDiv_activate = document.getElementById('resultsDiv_activate');
            if (resultsDiv_activate.style.display == 'none') {
                resultsDiv_activate.style.display = 'block';
            } else {
                resultsDiv_activate.style.display = 'none';
            }
        }
        function task_show_close_time() {
            var resultsDiv_close = document.getElementById('resultsDiv_close');
            if (resultsDiv_close.style.display == 'none') {
                resultsDiv_close.style.display = 'block';
            } else {
                resultsDiv_close.style.display = 'none';
            }
        }
        function task_show_type_delivery() {
            var resultsDiv_type_delivery = document.getElementById('resultsDiv_type_delivery');

            if (resultsDiv_type_delivery.style.display == 'none') {
                resultsDiv_type_delivery.style.display = 'block';
            } else {
                resultsDiv_type_delivery.style.display = 'none';
            }
        }
        function task_show_type_grading() {
            var resultsDiv_type_grading = document.getElementById('resultsDiv_type_grading');
            var resultsDiv_type_grading_2 = document.getElementById('resultsDiv_type_grading_2');
            var resultsDiv_type_grading_3 = document.getElementById('resultsDiv_type_grading_3');
            if (resultsDiv_type_grading.style.display == 'none') {
                resultsDiv_type_grading.style.display = 'block';
                resultsDiv_type_grading_2.style.display = 'none';
                resultsDiv_type_grading_3.style.display = 'block';
            } else {
                resultsDiv_type_grading.style.display = 'none';
                resultsDiv_type_grading_2.style.display = 'block';
                resultsDiv_type_grading_3.style.display = 'none';
            }
        }
        function task_show_assessable() {
            var resultsDiv_assessable = document.getElementById('resultsDiv_assessable');

            if (resultsDiv_assessable.style.display == 'none') {
                resultsDiv_assessable.style.display = 'block';
            } else {
                resultsDiv_assessable.style.display = 'none';
            }
        }
        function task_show_grading_visibility() {
            var resultsDiv_grading_visibility = document.getElementById('resultsDiv_grading_visibility');

            if (resultsDiv_grading_visibility.style.display == 'none') {
                resultsDiv_grading_visibility.style.display = 'block';
            } else {
                resultsDiv_grading_visibility.style.display = 'none';
            }
        }
        function task_show_responses_visibility() {
            var resultsDiv_responses_visibility = document.getElementById('resultsDiv_responses_visibility');

            if (resultsDiv_responses_visibility.style.display == 'none') {
                resultsDiv_responses_visibility.style.display = 'block';
            } else {
                resultsDiv_responses_visibility.style.display = 'none';
            }
        }
        function task_show_type_mark(item) {
            var resultsDiv_type_mark = document.getElementById('resultsDiv_type_mark');

            if (item == 0) {
                resultsDiv_type_mark.style.display = 'block';
            } else {
                resultsDiv_type_mark.style.display = 'none';
            }
        }
        function task_show_rubric() {
            var resultsDiv_rubric = document.getElementById('resultsDiv_rubric');
            var resultsDiv_rubric_2 = document.getElementById('resultsDiv_rubric_2');
            if (resultsDiv_rubric.style.display == 'none') {
                resultsDiv_rubric.style.display = 'block';
                resultsDiv_rubric_2.style.display = 'block';
            } else {
                resultsDiv_rubric.style.display = 'none';
                resultsDiv_rubric_2.style.display = 'none';
            }
        }
    </script>

    <script type="text/javascript" src="<?php echo elgg_get_site_url(); ?>mod/task/lib/jquery.MultiFile.js"></script>
    <!-- multi file jquery plugin -->
    <script type="text/javascript" src="<?php echo elgg_get_site_url(); ?>mod/task/lib/reCopy.js"></script>
    <!-- copy field jquery plugin -->
    <script type="text/javascript" src="<?php echo elgg_get_site_url(); ?>mod/task/lib/js_functions.js"></script>

</div>
