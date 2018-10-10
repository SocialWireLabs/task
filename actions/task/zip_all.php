<?php

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task) {

    $user_guid = elgg_get_logged_in_user_guid();
    $container = get_entity($task->container_guid);

    $owner = $task->getOwnerEntity();
    $owner_guid = $owner->getGUID();
    $group_guid = $container->guid;
    $group = get_entity($group_guid);
    $group_owner_guid = $group->owner_guid;

    set_time_limit(0);
    ini_set('memory_limit', '256M');
    $name_zip = tempnam(sys_get_temp_dir(), "zip");
    $zip = new ZipArchive();
    $zip->open($name_zip, ZIPARCHIVE::OVERWRITE);

    $some_response = false;

    if (!$task->subgroups) {
        $members = $group->getMembers(array('limit' => false));
    } else {
        $members = elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'), 'limit' => 0, 'container_guids' => $group_guid));
    }

    $i = 0;
    $membersarray = array();
    foreach ($members as $member) {
        $member_guid = $member->getGUID();
        if (($member_guid != $owner_guid) && ($group_owner_guid != $member_guid) && (!check_entity_relationship($member_guid, 'group_admin', $group_guid))) {
            $membersarray[$i] = $member_guid;
            $i = $i + 1;
        }
    }

    foreach ($membersarray as $member_guid) {
        $member = get_entity($member_guid);

        if (!$task->subgroups) {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'owner_guid' => $member_guid);
        } else {
            $options = array('relationship' => 'task_answer', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_answer', 'order_by' => 'e.time_created desc', 'limit' => 0, 'container_guid' => $member_guid);
        }
        $user_responses = elgg_get_entities_from_relationship($options);

        if (!empty($user_responses)) {
            $num_user_responses=0;
            foreach ($user_responses as $one_response) {
                if($num_user_responses==0){
                    if (!$task->subgroups) {
                        $one_response_guid = $one_response->getGUID();
                        $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $one_response_guid, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'owner_guid' => $member_guid, 'limit' => 0));
                    } else {
                        $response_files = elgg_get_entities_from_relationship(array('relationship' => 'response_file_link', 'relationship_guid' => $one_response_guid, 'inverse_relationship' => false, 'type' => 'object', 'subtype' => 'task_response_file', 'container_guid' => $member_guid, 'limit' => 0));
                    }

                    if ((count($response_files) > 0) && (strcmp($response_files[0]->title, "") != 0)) {
                        if (!$some_response)
                            $some_response = true;
                        foreach ($response_files as $file) {
                            $file_owner = $file->getOwnerEntity();
                            $file_owner_time_created = date('Y/m/d', $file_owner->time_created);
                            $file_dir_root = elgg_get_config('dataroot');
                            //$this_filename = $file_dir_root . $file_owner_time_created . "/" . $file_owner->guid . "/" . $file->filename;
                            //$this_filename = $file_dir_root . "1" . "/" . $file_owner->guid . "/" . $file->filename;
                            $this_filename = $file->getFilenameOnFilestore();
                            if (is_readable($this_filename)) {
                                $zip->addFile($this_filename, $member->username . "/" . $one_response_guid . "/" . $file->title);
                            } else {
                                register_error(elgg_echo("task:file_not_readable") . " (" . $this_filename . ")");
                            }
                        }
                    }
                    $num_user_responses++;
                } else{
                    break;
                }
            }
        }
    }

    $zip->close();

    if ($some_response) {

        $options = array('relationship' => 'zips_file_link', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0);
        $previous_files_zips = elgg_get_entities_from_relationship($options);
        foreach ($previous_files_zips as $one_file) {
            $deleted = $one_file->delete();
            if (!$deleted) {
                register_error(elgg_echo("task:filenotdeleted"));
                forward($_SERVER['HTTP_REFERER']);
            }
        }

        $file_zips = new ZipsTaskPluginFile();
        $file_zips->subtype = "task_zips_file";
        $prefix = "file/";
        $name = "zips_tasks_" . $taskpost;
        $filestorename = elgg_strtolower(time() . $name);
        $file_zips->setFilename($prefix . $filestorename);
        $file_zips->originalfilename = $name;
        //$unique_file_path = str_replace($filestorename,"",file_zips->getFilenameOnFilestore());
        //if (!file_exists($unique_file_path))
        //   mkdir($unique_file_path,0700,true);
        $file_zips->open("write");
        //$content = file_get_contents($name_zip);
        //$file_zips->write($content);
        $file_zips->close();
	rename($name_zip, $file_zips->getFilenameOnFilestore());
        $file_zips->title = $name;
        $file_zips->owner_guid = $user_guid;
        $file_zips->container_guid = $task->container_guid;
        $file_zips->access_id = $task->access_id;
        $file_zips->save();

        add_entity_relationship($taskpost, 'zips_file_link', $file_zips->getGUID());
    }

    //unlink($name_zip);

    if (!$some_response) {
        register_error(elgg_echo("task:responses_notfound"));
        forward($_SERVER['HTTP_REFERER']);
    }

} else {
    register_error(elgg_echo("task:notfound"));
    forward($_SERVER['HTTP_REFERER']);
}
