<?php

require_once(dirname(dirname(dirname(__FILE__))) . "/engine/start.php");

$params = get_input("params");
$params = explode('_', $params);
$file_guid = $params[0];
$file_type = $params[1];

// Get the file
if (strcmp($file_type, "question") == 0) {
    $file = new QuestionsTaskPluginFile($file_guid);
} else {
    $file = new ResponsesTaskPluginFile($file_guid);
}

if ($file) {
    $mime = $file->mimetype;
    if (!$mime) {
        $mime = "application/octet-stream";
    }
    $filename = $file->originalfilename;
    header("Pragma: public");
    header("Content-type: $mime");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $contents = $file->grabFile();
    $splitString = str_split($contents, 8192);
    foreach ($splitString as $chunk)
        echo $chunk;
    exit;
} else {
    register_error(elgg_echo("task:file_downloadfailed"));
    forward($_SERVER['HTTP_REFERER']);
}

?>