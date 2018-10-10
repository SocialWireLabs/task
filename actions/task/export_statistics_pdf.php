<?php 

elgg_load_library('task');

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);	

if($task){

	$format = get_input('format', 'A4');
	$font = get_input('font', 'times');

	$html .= "<h2><p>".htmlentities($task->title,false,'UTF-8',true)."</p></h2>";
	$html .= "<hr><hr><hr>";
  

	$group_guid = $task->container_guid;
	$group = get_entity($group_guid);

	$owner = $task->getOwnerEntity();
	$owner_guid = $owner->getGUID();
	$group_owner_guid = $group->owner_guid;

        $grading_label = elgg_echo("task:grading_label");
        $grading_statistics_label = elgg_echo("task:statistics");
        $max_grading_label = elgg_echo("task:max_grading");
        $min_grading_label = elgg_echo("task:min_grading");

	if (!$task->subgroups) {
    	   $members = $group->getMembers(array('limit' => false));
    	   $html .= "<h3>". elgg_echo("task:students")." - ";
    	   //$html .= "<hr>";
	} else {
	   $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
	   $html .= "<h3>". elgg_echo("task:groups")." - ";
	   //$html .= "<hr>";
	}

	$i = 0;
	$membersarray = array();
	foreach ($members as $member) {
	   $member_guid = $member->getGUID();
    	   if (($member_guid != $owner_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
       	      $membersarray[$i] = $member;
              $i = $i + 1;
    	   }
	}

  if (strcmp($task->type_grading, 'task_type_grading_marks') == 0){
    $html .= elgg_echo("task:type_grading_marks")."</h3><hr><br>";
  }else{
    $html .= elgg_echo("task:type_grading_game_points")."</h3><hr><br>";
  }

  $membersarray = task_my_sort($membersarray, "name", false);
  $i = 0;
  $marksarray = array();
	foreach ($membersarray as $member) {
		$member_guid = $member->getGUID();
    	if (!$task->subgroups) {
        	$options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $member_guid);
    	} else {
       	 	$options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $member_guid);
    	}
    	$user_responses = elgg_get_entities_from_relationship($options);
    	$user_response = $user_responses[0];
    	
    	if (!empty($user_response)) {
    		if (strcmp($user_response->grading, "not_qualified") != 0){
				$mark = $user_response->grading;
				$marksarray[$i] = $mark;
				$i = $i + 1;
			  }else{
				$mark = "--";
			  }
   		} else $mark = elgg_echo("task:noanswer"); 

   		$html .= "<p>". htmlentities($member->name,false,'UTF-8',true) .": ".$mark. "</p><br>";
   		if ($task->subgroups) {
   			$subgroup_guid = $member->getGUID();
   			$subgroup_members = elgg_get_entities_from_relationship(array('relationship' => 'member', 'inverse_relationship' => true, 'type' => 'user', 'relationship_guid' => $subgroup_guid, 'limit' => 0));
   			$html .="<p>". elgg_echo("task:members_of_subgroup"). "</p><br>";
   			foreach ($subgroup_members as $stu) {
   					$html .= htmlentities($stu->name,false,'UTF-8',true) . " ";
   			}
   			$html .= "</p><br>";
   		}
   		$html .= "<hr>";
   		
   }

    $nmarks = count($marksarray);
    $mean = array_sum($marksarray)/$nmarks;
    $max = max($marksarray);
    $min = min($marksarray);
    $freqarray = array_count_values($marksarray);
    arsort($freqarray);
    $moda = key($freqarray);
    $count_moda = $freqarray[$moda];
    $moda_array = $moda;
    foreach($marksarray as $one_mark){
       if($one_mark!=$moda){
          if ($freqarray[$one_mark]==$count_moda) {
	     $moda_array .= ";". $one_mark;
	  }
       }
    }

    
    sort($marksarray);
   
    $median = 0;
    if ($nmarks%2==0){
    	$half = $nmarks/2;
    	$median = ($marksarray[$half-1] +  $marksarray[$half])/2;
    }else{
    	$half = ($nmarks+1)/2;
    	$median = $marksarray[$half-1];
    }

    $html .= "<hr><hr><hr><h3>". htmlentities($grading_statistics_label,false,'UTF-8',true) ."</h3><hr><br>";
    $html .= elgg_echo("task:mean").": ". number_format($mean,2) ."<br>";
    $html .= htmlentities($max_grading_label,false,'UTF-8',true) .": ". $max ."<br>";
    $html .= htmlentities($min_grading_label,false,'UTF-8',true) .": ". $min ."<br>";
    $html .= elgg_echo("task:mode").": ". $moda_array ."<br>";
    $html .= elgg_echo("task:median").": ". $median;

	$pdf = new HTML2FPDF('P', 'mm', $format);
	$pdf->AddPage();
	$pdf->WriteHTML($html);
	$pdf->Output("task_statistics.pdf", 'D');
	exit;
} else {
   register_error(elgg_echo("task:notfound"));
   forward($_SERVER['HTTP_REFERER']);
}


 










