<?php

$taskpost = get_input('taskpost');
$task = get_entity($taskpost);

if ($task) {

    $options = array('relationship' => 'zips_file_link', 'relationship_guid' => $taskpost, 'inverse_relationship' => false, 'type' => 'object', 'limit' => 0);
    $files_zips = elgg_get_entities_from_relationship($options);
    if (empty($files_zips)) {
        register_error(elgg_echo("task:zip_notfound"));
        forward($_SERVER['HTTP_REFERER']);
    } else {
        $file_zips = $files_zips[0];
        $file_zips_owner = $file_zips->getOwnerEntity();
        $file_zips_owner_time_created = date('Y/m/d', $file_zips_owner->time_created);
        $file_dir_root = elgg_get_config('dataroot');
        //$filename = $file_dir_root . "1" . "/" . $file_zips_owner->guid . "/" . $file_zips->filename;
        $filename=$file_zips->getFilenameOnFilestore();

        $all_users_filename = "task_archives.zip";
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=\"$all_users_filename\"");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($filename));
        $well = readfile($filename);
        if (!$well) {
            register_error(elgg_echo("task:zip_notfound"));
            forward($_SERVER['HTTP_REFERER']);
        }
    }
} else {
    register_error(elgg_echo("task:notfound"));
    forward($_SERVER['HTTP_REFERER']);
}
