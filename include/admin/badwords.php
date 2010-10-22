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

    // retrieving the forum-info
    $forum_list=phorum_get_forum_info(2);

    $forum_list[0]="GLOBAL";

    // conversion of old data if existing
    if(isset($PHORUM["bad_words"]) && count($PHORUM['bad_words'])) {
        echo "upgrading badwords<br />";
        foreach($PHORUM['bad_words'] as $key => $data) {
            phorum_db_mod_banlists(PHORUM_BAD_WORDS ,0 ,$data ,0 ,0);
            unset($PHORUM["bad_words"][$key]);
        }
        phorum_db_update_settings(array("bad_words"=>$PHORUM["bad_words"]));
    }

    if(count($_POST) && $_POST["string"]!=""){

        if($_POST["curr"]!="NEW"){
            $ret=phorum_db_mod_banlists(PHORUM_BAD_WORDS ,0 ,$_POST["string"] ,$_POST['forum_id'] , $_POST['comments'], $_POST['curr']);
        } else {
            $ret=phorum_db_mod_banlists(PHORUM_BAD_WORDS ,0 ,$_POST["string"] ,$_POST['forum_id'] , $_POST['comments'], 0);
        }

        if(!$ret){
            $error="Database error while updating badwords.";
        } else {
            if ($_POST["curr"]!="NEW"){
                phorum_admin_okmsg("Bad Word Updated");
            } else {
                phorum_admin_okmsg("Bad Word Added");
            }
        }
    }

    if(isset($_POST["curr"]) && isset($_POST["delete"]) && $_POST["confirm"]=="Yes"){
        phorum_db_del_banitem((int)$_POST['curr']);
        phorum_admin_okmsg("Bad Word Deleted");
    }

    if(isset($_GET["curr"])){
        $curr = $_GET["curr"];
    }

    if($curr!="NEW"){
        extract(phorum_db_get_banitem($curr));
        $title="Edit Bad Word Item";
        $submit="Update";
    } else {
        $title="Add A Bad Word";
        $submit="Add";
    }
    
    settype($comments, "string");
    settype($forum_id,"int");
    settype($string, "string");
    settype($type, "int");
    settype($pcre, "int");

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


        // load bad-words-list
        $banlists=phorum_db_get_banlists();
        $bad_words=$banlists[PHORUM_BAD_WORDS];

        include_once "./include/admin/PhorumInputForm.php";

        $frm = new PhorumInputForm ("", "post", $submit);

        $frm->hidden("module", "badwords");

        $frm->hidden("curr", "$curr");

        $row = $frm->addbreak($title);
        if ($curr == 'NEW') $frm->addmessage(
            "This feature can be used to mask bad words in forum messages
             with \"".PHORUM_BADWORD_REPLACE."\". All bad words will
             automatically be replaced by that string. If you want to use
             a different string (e.g. \"CENSORED\" or \"*****\"), then you
             can change the definition of the constant
             \"PHORUM_BADWORD_REPLACE\" in the Phorum file
             include/constants.php."
        );

        $row = $frm->addrow("Bad Word", $frm->text_box("string", $string, 50));
        $frm->addhelp($row, "Bad Word",
            "The word that you want to mask in forum messages.
             Rules that apply to the matching are:
             <ul>
               <li><b>Only the full word</b> is matched, so \"foo\" would
                   not mask (part of) \"foobar\";</li>
               <li>The match is <b>case insensitive</b>, so \"foo\" would also
                   mask \"FoO\".</li>
             </ul>");

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

        echo "<hr class=\"PhorumAdminHR\" />";

        if(count($bad_words)){

            echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
            echo "<tr>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Word</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Valid for Forum</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">&nbsp;</td>\n";
            echo "</tr>\n";

            foreach($bad_words as $key => $item){
                $edit_url = phorum_admin_build_url(array('module=badwords','edit=1',"curr=$key"));
                $delete_url = phorum_admin_build_url(array('module=badwords','delete=1',"curr=$key"));
                                        
                $ta_class = "PhorumAdminTableRow".($ta_class == "PhorumAdminTableRow" ? "Alt" : "");
                echo "<tr>\n";
                echo "    <td class=\"".$ta_class."\">".htmlspecialchars($item[string])."</td>\n";
                echo "    <td class=\"".$ta_class."\">".$forum_list[$item["forum_id"]]."</td>\n";
                echo "    <td class=\"".$ta_class."\"><a href=\"$edit_url\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$delete_url\">Delete</a></td>\n";
                echo "</tr>\n";
            }

            echo "</table>\n";

        } else {

            echo "No bad words in list currently.";

        }
    }
?>
