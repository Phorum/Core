<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2003  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

    // Phorum 5 Admin

    define("PHORUM_ADMIN", 1);

    // set a sane error level for our admin.
    // this will make the coding time faster and
    // the code run faster.
    error_reporting  (E_ERROR | E_WARNING | E_PARSE);

    include_once "./common.php";
    include_once "./include/users.php";


    // check for a session

    phorum_user_check_session("phorum_admin_session");

    if(empty($PHORUM["http_path"]) || (isset($PHORUM['internal_version']) && $PHORUM['internal_version'] < PHORUMINTERNAL) || !isset($PHORUM['internal_version'])) {
        // this is an install
        $module="install";
    } elseif(!$GLOBALS["PHORUM"]["user"]["admin"]){
        // if not an admin
        unset($GLOBALS["PHORUM"]["user"]);
        $module="login";
    } else {
        // load the default module if none is specified
        if(!empty($_REQUEST["module"])){
            $module = basename($_REQUEST["module"]);
        } else {
            $module = "default";
        }

    }

    ob_start();
    if($module!="help") include_once "./include/admin/header.php";
    include_once "./include/admin/$module.php";
    if($module!="help") include_once "./include/admin/footer.php";
    ob_end_flush();


/////////////////////////////////////////////////

    function phorum_admin_error($error)
    {
        echo "<div class=\"PhorumAdminError\">$error</div>\n";
    }
    // phorum_get_language_info and phorum_get_template_info moved to common.php (used in the cc too)

    function phorum_get_folder_info()
    {
        $folders=array();
        $folder_data=array();

        $forums = phorum_db_get_forums();

        foreach($forums as $forum){
            if($forum["folder_flag"]){
                $path = $forum["name"];
                $parent_id=$forum["parent_id"];
                while($parent_id!=0  && $parent_id!=$forum["forum_id"]){
                    $path=$forums[$parent_id]["name"]."::$path";
                    $parent_id=$forums[$parent_id]["parent_id"];
                }
                $folders[$forum["forum_id"]]=$path;
            }
        }

        asort($folders);

        $tmp=array("--None--");

        foreach($folders as $id => $folder){
            $tmp[$id]=$folder;
        }

        $folders=$tmp;

        return $folders;

    }

    function phorum_get_forum_info($forums_only=0)
    {
        $folders=array();
        $folder_data=array();

        $forums = phorum_db_get_forums();

        foreach($forums as $forum){
            if($forums_only == 0 || $forum['folder_flag']==0)  {
                $path = $forum["name"];
                $parent_id=$forum["parent_id"];
                while($parent_id!=0){
                    $path=$forums[$forum["parent_id"]]["name"]."::$path";
                    $parent_id=$forums[$parent_id]["parent_id"];
                }
                $folders[$forum["forum_id"]]=$path;
            }
        }

        asort($folders);

        return $folders;

    }


    function help_link($title, $text)
    {
        $text=urlencode("<p style=\"font-weight: bold\">$title</p>$text");
        return "<a href=\"javascript:show_help('".urlencode($text)."');\"><img style=\"padding-left: 5px; padding-right: 5px; padding-bottom: 1px;\" align=\"absmiddle\" alt=\"Help\" border=\"0\" src=\"images/qmark.gif\" height=\"16\" width=\"16\" /></a>";
    }
    
    function phorum_upgrade_tables($fromversion,$toversion) {
    
          $PHORUM=$GLOBALS['PHORUM'];
          
          $msg="";
          $lastver=$fromversion;
          for($i=($fromversion+1);$i<=$toversion;$i++) {
             $upgradefile="./include/db/upgrade/{$PHORUM['DBCONFIG']['type']}/$i.php";
             if(file_exists($upgradefile)) {
                 $msg.="Upgrading from version $lastver to $i ... ";
                 $upgrade_queries=array();
                 include($upgradefile);
                 $err=phorum_db_run_queries($upgrade_queries);
                 if($err){
                    $msg.= "an error occured: $err ... continuing.<br />\n";
                 } else {
                    $msg.= "done.<br />\n";
                 }
                 phorum_db_update_settings(array("internal_version"=>$i));
                 $lastver=$i;
             }
          }
          return $msg;
    }
?>
