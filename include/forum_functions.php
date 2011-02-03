<?php

function phorum_build_forum_list() {

    $PHORUM = $GLOBALS["PHORUM"];

    // Check what forums the current user can read.
    $allowed_forums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST
    );

    $forum_picker = array();

    // build forum drop down data
    require_once('./include/api/forums.php');
    $forums = phorum_api_forums_get($allowed_forums);

    foreach($forums as $forum){
        $tmp_forums[$forum["forum_id"]]["forum_id"] = $forum["forum_id"];
        $tmp_forums[$forum["forum_id"]]["parent"] = $forum["parent_id"];
        $tmp_forums[$forum["parent_id"]]["children"][] = $forum["forum_id"];
        if (empty($forums[$forum["parent_id"]]["childcount"])) {
            $forums[$forum["parent_id"]]["childcount"] = 1;
        } else {
            $forums[$forum["parent_id"]]["childcount"]++;
        }
    }

    $order = array();
    $stack = array();
    $curr_id = $PHORUM['vroot'];
    while(count($tmp_forums)){
        if(empty($seen[$curr_id])){
            if($curr_id!=$PHORUM['vroot']){
                if ($forums[$curr_id]["active"]) {
                    $order[$curr_id] = $forums[$curr_id];
                }
                $seen[$curr_id] = true;
            }
        }
        array_unshift($stack, $curr_id);
        $data = $tmp_forums[$curr_id];
        if(isset($data["children"])){
            if(count($data["children"])){
                $curr_id = array_shift($tmp_forums[$curr_id]["children"]);
            } else {
                unset($tmp_forums[$curr_id]);
                array_shift($stack);
                $curr_id = array_shift($stack);
            }
        } else {
            unset($tmp_forums[$curr_id]);
            array_shift($stack);
            $curr_id = array_shift($stack);
        }
        if(!is_numeric($curr_id)) break;
    }

    foreach($order as $forum)
    {
        if($forum["folder_flag"])
        {
            // Skip empty folders.
            if (empty($forums[$forum['forum_id']]['childcount'])) continue;

            $url = phorum_get_url(PHORUM_INDEX_URL, $forum["forum_id"]);
        } else {
            $url = phorum_get_url(PHORUM_LIST_URL, $forum["forum_id"]);
        }

        $indent = count($forum["forum_path"]) - 2;
        if($indent < 0) $indent = 0;

        $forum_picker[$forum["forum_id"]] = array(
            "forum_id" => $forum["forum_id"],
            "parent_id" => $forum["parent_id"],
            "folder_flag" => $forum["folder_flag"],
            "name" => $forum["name"],
            "stripped_name" => strip_tags($forum["name"]),
            "indent" => $indent,
            "indent_spaces" => str_repeat("&nbsp;", $indent),
            "url" => $url,
            "path" => $forum["forum_path"]
        );
    }

    return $forum_picker;
}

?>
