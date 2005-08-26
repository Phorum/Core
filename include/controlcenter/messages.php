<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

if (!$PHORUM["DATA"]["MESSAGE_MODERATOR"]) {
    phorum_redirect_by_url(phorum_get_url(PHORUM_CONTROLCENTER_URL));
    exit();
} 

// some needed vars
$numunapproved = 0;
$oldforum = $PHORUM['forum_id'];

$mod_forums = phorum_user_access_list(PHORUM_USER_ALLOW_MODERATE_MESSAGES);
$gotforums = (count($mod_forums) > 0);

$PHORUM['DATA']['PREPOST'] = array();

if ($gotforums)
    $foruminfo = phorum_db_get_forums($mod_forums);
else
    $foruminfo = phorum_db_get_forums();

foreach($mod_forums as $forum => $rest) {
    $checkvar = 1; 
    // Get the threads
    $rows = array(); 
    // get the thread set started
    $rows = phorum_db_get_unapproved_list($forum); 
    // loop through and read all the data in.
    foreach($rows as $key => $row) {
        $numunapproved++;
        $rows[$key]['forumname'] = $foruminfo[$forum]['name'];
        $rows[$key]['checkvar'] = $checkvar;
        if ($checkvar)
            $checkvar = 0;
        $rows[$key]['forum_id'] = $forum;
        $rows[$key]["url"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $forum, $row["thread"], $row['message_id']);
        // we need to fake the forum_id here
        $PHORUM["forum_id"] = $forum;
        $rows[$key]["approve_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_APPROVE_MESSAGE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum);
        $rows[$key]["approve_tree_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_APPROVE_MESSAGE_TREE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum);
        $rows[$key]["delete_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum);
        $PHORUM["forum_id"] = $oldforum;
        $rows[$key]["short_datestamp"] = phorum_date($PHORUM["short_date"], $row["datestamp"]);

        if ($row["user_id"]) {
            $url = phorum_get_url(PHORUM_PROFILE_URL, $row["user_id"]);
            $rows[$key]["profile_url"] = $url;
            $rows[$key]["linked_author"] = "<a href=\"$url\">$row[author]</a>";
        } else {
            $rows[$key]["profile_url"] = "";
            $rows[$key]["linked_author"] = $row["author"];
        } 
    } 
    // $PHORUM['DATA']['FORUMS'][$forum]['forum_id']=$forum;
    $PHORUM['DATA']['PREPOST'] = array_merge($PHORUM['DATA']['PREPOST'], $rows);
} 

if ($numunapproved) {
    $template = "cc_prepost";
} else {
    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["NoUnapprovedMessages"];
} 

?>
