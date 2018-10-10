<?php

define('APTO', 5);

function task_grading_input($task, $grading, $name_grading)
{
    if (((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0)) {
        $grading_input = "<input type=\"text\"  name=\"" . $name_grading . "\" value=\"" . $grading . "\"  style=\"width: 80px\"/>";
    } elseif ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_textual') == 0)) {
        if (strcmp($grading, "") != 0) {
            if ($grading >= HONOURS) {
                $grading = HONOURS;
            } elseif ($grading >= OUTSTANDING) {
                $grading = OUTSTANDING;
            } elseif ($grading >= VERYGOOD) {
                $grading = VERYGOOD;
            } elseif ($grading >= SUFFICIENT) {
                $grading = SUFFICIENT;
            } else {
                $grading = INSUFFICIENT;
            }
        } else {
            $grading = -1;
        }
        $options = array('name' => $name_grading, 'value' => $grading, 'options_values' => array('-1' => '', HONOURS => elgg_echo('mark:honours'), OUTSTANDING => elgg_echo('mark:outstanding'), VERYGOOD => elgg_echo('mark:verygood'), SUFFICIENT => elgg_echo('mark:sufficient'), INSUFFICIENT => elgg_echo('mark:insufficient')));
        $grading_input = elgg_view('input/dropdown', $options);
    } elseif ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_apto') == 0)) {
        if (strcmp($grading, "") != 0) {
            if ($grading >= APTO) {
                $grading = PASS;
            } else {
                $grading = FAIL;
            }
        } else {
            $grading = -1;
        }
        $options = array('name' => $name_grading, 'value' => $grading, 'options_values' => array('-1' => '', PASS => elgg_echo('mark:pass'), FAIL => elgg_echo('mark:fail')));
        $grading_input = elgg_view('input/dropdown', $options);
    }
    return $grading_input;
}


function task_grading_output($task, $grading)
{
    if (((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_numerical') == 0)) || (strcmp($task->type_grading, 'task_type_grading_marks') != 0)) {
        $grading_output = $grading;
    } elseif ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_textual') == 0)) {
        if ($grading >= HONOURS) {
            $grading_output = elgg_echo('mark:honours');
        } elseif ($grading >= OUTSTANDING) {
            $grading_output = elgg_echo('mark:outstanding');
        } elseif ($grading >= VERYGOOD) {
            $grading_output = elgg_echo('mark:verygood');
        } elseif ($grading >= SUFFICIENT) {
            $grading_output = elgg_echo('mark:sufficient');
        } else {
            $grading_output = elgg_echo('mark:insufficient');
        }
    } elseif ((strcmp($task->type_grading, 'task_type_grading_marks') == 0) && (strcmp($task->type_mark, 'task_type_mark_apto') == 0)) {
        if ($grading >= APTO)
            $grading_output = elgg_echo('mark:pass');
        else
            $grading_output = elgg_echo('mark:fail');
    }
    return $grading_output;
}

function task_my_sort($original, $field, $descending = false)
{
    if (!$original) {
        return $original;
    }
    $sortArr = array();
    foreach ($original as $key => $item) {
        $sortArr[$key] = $item->$field;
    }
    if ($descending) {
        arsort($sortArr);
    } else {
        asort($sortArr);
    }
    $resultArr = array();
    foreach ($sortArr as $key => $value) {
        $resultArr[$key] = $original[$key];
    }
    return $resultArr;
}


?>