<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
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

    $match_types = array("string", "PCRE");

    $forum_list=phorum_get_forum_info(2);
    $forum_list[0]="GLOBAL";

    if(count($_POST) && $_POST["string"]!=""){

        if($_POST["curr"]!="NEW"){
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forum_id'],$_POST["curr"]);
        } else {
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forum_id'],0);
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
        settype($string, "string");
        settype($type, "int");
        settype($pcre, "int");
        settype($forum_id,"int");
        $title="Add A Ban Item";
        $submit="Add";
    }

    if($error){
        phorum_admin_error($error);
    }

    if($_GET["curr"] && $_GET["delete"]){

        ?>

        <div class="PhorumInfoMessage">
            Are you sure you want to delete this entry?
            <form action="<?php echo $PHORUM["admin_http_path"] ?>" method="post">
                <input type="hidden" name="module" value="<?php echo $module; ?>" />
                <input type="hidden" name="curr" value="<?php echo $_GET['curr']; ?>" />
                <input type="hidden" name="delete" value="1" />
                <input type="submit" name="confirm" value="Yes" />&nbsp;<input type="submit" name="confirm" value="No" />
            </form>
        </div>

        <?php

    } else {

        include_once "./include/admin/PhorumInputForm.php";

        $frm =& new PhorumInputForm ("", "post", $submit);

        $frm->hidden("module", "banlist");

        $frm->hidden("curr", "$curr");

        $frm->addbreak($title);

        $frm->addrow("String To Match", $frm->text_box("string", $string, 50));

        $frm->addrow("Field To Match", $frm->select_tag("type", $ban_types, $type));

        $frm->addrow("Compare As", $frm->select_tag("pcre", $match_types, $pcre));

        $frm->addrow("Valid for Forum", $frm->select_tag("forum_id", $forum_list, $forum_id));

        $frm->show();

        echo "If using PCRE for comparison, \"String To Match\" should be a valid PCRE expression. See <a href=\"http://php.net/pcre\" target=\"_blank\">the PHP manual</a> for more information.";

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
                        $ta_class = "PhorumAdminTableRow".($ta_class == "PhorumAdminTableRow" ? "Alt" : "");
                        echo "<tr>\n";
                        echo "    <td class=\"".$ta_class."\"".($item["string"] == $t_last_string ? " style=\"color:red;\"" : "").">".htmlspecialchars($item['string'])."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$ban_types[$type]."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$match_types[$item["pcre"]]."</td>\n";
                        echo "    <td class=\"".$ta_class."\">".$forum_list[$item["forum_id"]]."</td>\n";
                        echo "    <td class=\"".$ta_class."\"><a href=\"{$PHORUM["admin_http_path"]}?module=banlist&curr=$key&edit=1\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"{$PHORUM["admin_http_path"]}?module=banlist&curr=$key&delete=1\">Delete</a></td>\n";
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
