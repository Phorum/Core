<?php
# Store a personal file for a user.

if (!defined('PHORUM')) return;

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

$file = array(
    "filename"  => "myfile.ext",   // the name of the file
    "filesize"  => 2048,           // the size of the file in bytes
    "file_data" => $file_data,     // the contents of the file
    "link"      => PHORUM_LINK_USER
);

if (!phorum_api_file_check_write_access($file) ||
    !phorum_api_file_store($file)) {
    die("Storing the file failed. The error was: " . phorum_api_strerror());
}
?>
