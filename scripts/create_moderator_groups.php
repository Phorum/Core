#!/usr/bin/php
<?php

// This script creates a moderator group for every forum on the system.
// After running this script, you can use those groups for assigning
// moderators to the forums.

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);
define('phorum_page', 'create_moderator_groups');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';
require_once './include/api/forums.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

print "\nCreate forum moderator groups ...\n";

$forums = phorum_api_forums_get();

// Find out which forums already have a moderator group available.
$forum_has_moderator = array();
$groups=phorum_db_get_groups();
foreach ($groups as $id => $group) {
    foreach ($group['permissions'] as $forum_id => $permission) {
        if ($permission & PHORUM_USER_ALLOW_MODERATE_MESSAGES) {
            $forum_has_moderator[$forum_id] = TRUE;
        }
    }
}
print_r($forum_has_moderator);

foreach ($forums as $forum_id => $fdata)
{
    if (!empty($fdata['folder_flag'])) continue;

    print "> forum {$fdata['name']} ";

    if (!empty($forum_has_moderator[$forum_id])) {
        print "[USE EXISTING]\n";
        continue;
    }

    $path = $fdata['forum_path'];
    array_unshift($path);
    $name = implode('::', $path);

    $group_id = phorum_db_add_group("Moderate $name");
    if (!$group_id) die("Error adding group \"$name\".\n");

    phorum_db_update_group(array(
        'group_id' => $group_id,
        'open'     => 0,
        'permissions' => array(
            $forum_id => PHORUM_USER_ALLOW_READ |
                         PHORUM_USER_ALLOW_REPLY |
                         PHORUM_USER_ALLOW_NEW_TOPIC |
                         PHORUM_USER_ALLOW_EDIT |
                         PHORUM_USER_ALLOW_ATTACH |
                         PHORUM_USER_ALLOW_MODERATE_MESSAGES |
                         PHORUM_USER_ALLOW_MODERATE_USERS
        )
    ));
     
    print "[CREATED GROUP]\n";
}

print "\n\n";

?>
