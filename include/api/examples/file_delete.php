<?php
# Delete a file.

if (!defined('PHORUM')) return;

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

if (phorum_api_file_check_delete_access($file_id)) {
    phorum_api_file_delete($file_id);
} else {
    die("Permission denied to delete file $file_id");
}
?>
