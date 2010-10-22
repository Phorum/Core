<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

    if(!defined("PHORUM_ADMIN")) return;

    $error="";
    $curr="NEW";

    $ban_types = array(PHORUM_BAD_IPS=>"IP Address/Hostname", PHORUM_BAD_NAMES=>"Name/User Name", PHORUM_BAD_EMAILS=>"Email Address", PHORUM_BAD_USERID=>"User-Id (registered User)", PHORUM_BAD_SPAM_WORDS=>"Illegal Words (SPAM)");

    $match_types = array("String", "PCRE");

    $forum_list=phorum_get_forum_info(2);
    $forum_list[0]="GLOBAL";

    if(count($_POST) && $_POST["string"]!=""){

        if($_POST["curr"]!="NEW"){
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forum_id'],$_POST['comments'],$_POST['curr']);
            if(isset($PHORUM['cache_banlists']) && $PHORUM['cache_banlists']) {
                // we need to increase the version in that case to
                // invalidate them all in the cache.
                // TODO: I think I have to work out a way to make the same
                // work with vroots
                if($_POST['forum_id'] == 0) {
                    $PHORUM['banlist_version'] = $PHORUM['banlist_version'] + 1;
                    phorum_db_update_settings(array('banlist_version'=>$PHORUM['banlist_version']));
                } else {
                    // remove the one for that forum
                    phorum_cache_remove('banlist',$_POST['forum_id']);
                }
            }
        } else {
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forum_id'],$_POST['comments'],0);
        }

        if(!$ret){
            $error="Database error while updating settings.";
        } else {
            phorum_admin_okmsg("Ban Item Updated");
        }
    }

    if(isset($_POST["curr"]) && isset($_POST["delete"]) && $_POST["confirm"]=="Yes"){
        phorum_db_del_banitem((int)$_POST['curr']);
        phorum_admin_okmsg("Ban Item Deleted");
    }

    if(isset($_GET["curr"])){
        $curr = (int)$_GET["curr"];
    }

    if($curr!="NEW"){
        extract(phorum_db_get_banitem($curr));
        $title="Edit Ban Item";
        $submit="Update";
    } else {
        $title="Add A Ban Item";
        $submit="Add";
    }

    settype($string, "string");
    settype($comments, "string");
    settype($type, "int");
    settype($pcre, "int");
    settype($forum_id,"int");

    if($error){
        phorum_admin_error($error);
    }

    if($_GET["curr"] && $_GET["delete"]){

        ?>

        <div class="PhorumInfoMessage">
            Are you sure you want to delete this entry?
            <form action="<?php echo phorum_admin_build_url('base'); ?>" method="post">
            	<input type="hidden" name="phorum_admin_token" value="<?php echo $PHORUM['admin_token'];?>" />
                <input type="hidden" name="module" value="<?php echo $module; ?>" />
                <input type="hidden" name="curr" value="<?php echo htmlspecialchars($_GET['curr']) ?>" />
                <input type="hidden" name="delete" value="1" />
                <input type="submit" name="confirm" value="Yes" />&nbsp;<input type="submit" name="confirm" value="No" />
            </form>
        </div>

        <?php

    } else {

        include_once "./include/admin/PhorumInputForm.php";

        $frm = new PhorumInputForm ("", "post", $submit);

        $frm->hidden("module", "banlist");

        $frm->hidden("curr", "$curr");

        $frm->addbreak($title);

        if ($curr == "NEW") $frm->addmessage(
            "Ban items can be used to deny new user registrations and
             posting of (private) messages, based on various criteria.
             If a ban item applies to a user action, then this action
             will be fully blocked by Phorum. This can for example be used
             to block user registrations and postings from certain IP
             addresses or to prevent certain words from being used in
             forum messages.<br />
             <br />
             If you want to fully ban a user, then it's best to
             set \"Active\" to \"No\" for the user in the
             \"Edit Users\" interface."
        );

        $frm->addrow("String To Match", $frm->text_box("string", $string, 50));

        $row = $frm->addrow("Field To Match", $frm->select_tag("type", $ban_types, $type));
        $frm->addhelp($row, "Field To Match", "
            Below, you will find an overview of what
            ban items are used by what Phorum actions:<br/>
            <br/>
            <b>User registration</b>:<br/>
            \"Name/User Name\" checks the new username<br/>
            \"Email Address\" checks the new email address<br/>
            \"IP Address/Hostname\" checks the visitor's IP<br/>
            <br/>
            <b>Posting forum messages by anonymous users</b><br/>
            \"Name/User Name\" checks the author's name<br/>
            \"Email Address\" checks the author's email address<br/>
            \"Illegal Words (SPAM)\" checks the subject and body<br/>
            \"IP Address/Hostname\" checks the author's IP<br/>
            <br/>
            <b>Posting forum messages by registered users</b><br/>
            \"Name/User Name\" checks the author's username<br/>
            \"User-Id (registered User)\" checks the author's user id<br/>
            \"Email Address\" checks the author's email address<br/>
            \"IP Address/Hostname\" checks the author's IP<br/>
            \"Illegal Words (SPAM)\" checks the subject and body<br/>
            <br/>
            <b>Posting private messages</b><br/>
            \"Name/User Name\" checks the sender's username<br/>
            \"User-Id (registered User)\" checks the sender's user id<br/>
            \"Email Address\" checks the sender's email address<br/>
            \"IP Address/Hostname\" checks the sender's IP
        ");

        $row = $frm->addrow("Compare As", $frm->select_tag("pcre", $match_types, $pcre) .  "<div style=\"font-size:x-small\">If using PCRE for comparison, \"String To Match\" should be a valid PCRE expression.<br/>See <a href=\"http://php.net/pcre\" target=\"_blank\">the PHP manual</a> for more information about PCRE.</div>");

        $frm->addhelp($row, "Compare As", "
            This setting can be used to specify the matching method
            that has to be used for the ban item. There are two options:<br/>
            <br/>
            <ul>
              <li><b>String</b><br/>
                  The exact string from the \"String To Match\" field
                  will be used for matching. Wildcards are not available
                  for the String field type.<br/><br/></li>

              <li><b>PCRE</b><br/>
                  The \"String To Match\" field will be treated as
                  a <a href=\"http://www.php.net/pcre\">Perl Compatible
                  Regular Expression</a>.</li>
            </ul>
        ");

        $frm->addrow("Valid for Forum", $frm->select_tag("forum_id", $forum_list, $forum_id));

        $row = $frm->addrow(
            'Comments',
            $frm->textarea('comments', $comments, 50, 7)
        );
        $frm->addhelp($row, "Comments",
            "This field can be used to add some comments to the ban (why you
             created it, when you did this, when the ban can be deleted, etc.)
             These comments will only be shown on this page and are meant as
             a means for the administrator to do some bookkeeping."
        );

        $frm->show();

        if($curr=="NEW"){

            $PHORUM['banlists']=phorum_db_get_banlists(true);
            unset($PHORUM['banlists'][PHORUM_BAD_WORDS]);

            echo "<hr class=\"PhorumAdminHR\" />";

            if(count($PHORUM['banlists'])){

                echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
                echo "<tr>\n";
                echo "    <td class=\"PhorumAdminTableHead\">String</td>\n";
                echo "    <td class=\"PhorumAdminTableHead\">Field</td>\n";
                echo "    <td class=\"PhorumAdminTableHead\">Compare Method</td>\n";
                echo "    <td class=\"PhorumAdminTableHead\">Valid for Forum</td>\n";
                echo "    <td class=\"PhorumAdminTableHead\">&nbsp;</td>\n";
                echo "</tr>\n";



                foreach($PHORUM["banlists"] as $type => $content){
                    $t_last_string = '';
                    foreach($content as $key => $item){
                        $edit_url = phorum_admin_build_url(array('module=banlist','edit=1',"curr=$key"));
                        $delete_url = phorum_admin_build_url(array('module=banlist','delete=1',"curr=$key"));
                        $ta_class = "PhorumAdminTableRow".($ta_class == "PhorumAdminTableRow" ? "Alt" : "");
                        echo "<tr>\n";
                        echo "    <td class=\"".$ta_class."\"".($item["string"] == $t_last_string ? " style=\"color:red;\"" : "").">".htmlspecialchars($item['string'])."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$ban_types[$type]."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$match_types[$item["pcre"]]."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$forum_list[$item["forum_id"]]."</td>\n";
                        echo "    <td class=\"".$ta_class."\"><a href=\"$edit_url\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$delete_url\">Delete</a></td>\n";
                        echo "</tr>\n";
                        $t_last_string = $item["string"];
                    }
                }

                echo "</table>\n";

            } else {

                echo "No bans in list currently.";

            }

        }
    }
?>
