<?php

    if(!defined("PHORUM_ADMIN")) return;

    $error="";
    $curr="NEW";

    $ban_types = array(PHORUM_BAD_IPS=>"IP Address/Hostname", PHORUM_BAD_NAMES=>"Name/User Name", PHORUM_BAD_EMAILS=>"Email Address");

    $match_types = array("string", "PCRE");
    
    $forum_list=phorum_get_forum_info(true);
    $forum_list[0]="GLOBAL";
    
    if(count($_POST) && $_POST["string"]!=""){

        if($_POST["curr"]!="NEW"){
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forumid'],$_POST["curr"]);
        } else {
            $ret=phorum_db_mod_banlists($_POST['type'],$_POST['pcre'],$_POST['string'],$_POST['forumid'],0);
        }

        if(!$ret){
            $error="Database error while updating settings.";
        } else {
            echo "Ban Item Updated<br />";
        }
    }

    if(isset($_GET["curr"])){
        if(isset($_GET["delete"])){
            phorum_db_del_banitem($_GET['curr']);
            echo "Ban Item Deleted<br />";
        } else {
            $curr = $_GET["curr"];
        }
    }
    
    if($curr!="NEW"){
        extract(phorum_db_get_banitem($curr));
        $title="Edit Ban Item";
        $submit="Update";
    } else {
        settype($string, "string");
        settype($type, "int");
        settype($pcre, "int");
        settype($forumid,"int");
        $title="Add A Ban Item";
        $submit="Add";
    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";
        

    $frm =& new PhorumInputForm ("", "post", $submit);

    $frm->hidden("module", "banlist");

    $frm->hidden("curr", "$curr");

    $frm->addbreak($title);

    $frm->addrow("String To Match", $frm->text_box("string", $string, 50));

    $frm->addrow("Field To Match", $frm->select_tag("type", $ban_types, $type));

    $frm->addrow("Compare As", $frm->select_tag("pcre", $match_types, $pcre));
    
    $frm->addrow("Valid for Forum", $frm->select_tag("forumid", $forum_list, $forumid));

    $frm->show();

    echo "If using PCRE for comparison, \"Sting To Match\" should be a valid PCRE expression. See <a href=\"http://php.net/pcre\" target=\"_blank\">the PHP manual</a> for more information.";

    if($curr=="NEW"){
        
        $PHORUM['banlists']=phorum_db_get_banlists();
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
                foreach($content as $key => $item){
                    echo "<tr>\n";
                    echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item[string])."</td>\n";
                    echo "    <td class=\"PhorumAdminTableRow\">".$ban_types[$type]."</td>\n";
                    echo "    <td class=\"PhorumAdminTableRow\">".$match_types[$item["pcre"]]."</td>\n";
                    echo "    <td class=\"PhorumAdminTableRow\">".$forum_list[$item["forum_id"]]."</td>\n";
                    echo "    <td class=\"PhorumAdminTableRow\"><a href=\"$_SERVER[PHP_SELF]?module=banlist&curr=$key&edit=1\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$_SERVER[PHP_SELF]?module=banlist&curr=$key&delete=1\">Delete</a></td>\n";
                    echo "</tr>\n";
                }
            }

            echo "</table>\n";

        } else {

            echo "No bans in list currently.";

        }

    }

?>
