<?php

    if(!defined("PHORUM_ADMIN")) return;

    $error="";
    $curr="NEW";

    if(count($_POST) && $_POST["string"]!=""){

        if(preg_match("/^[^a-z]/i", $_POST["string"]) || preg_match("/[^a-z0-9_]/i", $_POST["string"])){
            $error="Field names can only contain letters, numbers and _.  They must start with a letter.";
        } else {  

            if($_POST["curr"]!="NEW"){
                $PHORUM["PROFILE_FIELDS"][$_POST["curr"]]=$_POST["string"];
            } else {
                $PHORUM["PROFILE_FIELDS"][]=$_POST["string"];
            }

            if(!phorum_db_update_settings(array("PROFILE_FIELDS"=>$PHORUM["PROFILE_FIELDS"]))){
                $error="Database error while updating settings.";
            } else {
                echo "Profile Field Updated<br />";
            }
        
        }
        
    }

    if(isset($_GET["curr"])){
        if(isset($_GET["delete"])){
            unset($PHORUM["PROFILE_FIELDS"][$_GET["curr"]]);
            phorum_db_update_settings(array("PROFILE_FIELDS"=>$PHORUM["PROFILE_FIELDS"]));
            echo "Profile Field Deleted<br />";
        } else {
            $curr = $_GET["curr"];
        }
    }


    if($curr!="NEW"){
        $string=$PHORUM["PROFILE_FIELDS"][$curr];
        $title="Edit Profile Field";
        $submit="Update";
    } else {
        settype($string, "string");
        $title="Add A Profile Field";
        $submit="Add";
    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";

    $frm =& new PhorumInputForm ("", "post", $submit);

    $frm->hidden("module", "customprofile");

    $frm->hidden("curr", "$curr");

    $frm->addbreak($title);

    $frm->addrow("Field Name", $frm->text_box("string", $string, 50));

    $frm->show();

    echo "This will only add the field to the list of allowed fields.  You will need to edit the register and profile templates to actually allow users to use the fields.  Use the name you enter here as the name property of the HTML form element.";

    if($curr=="NEW"){

        echo "<hr class=\"PhorumAdminHR\" />";

        if(count($PHORUM["PROFILE_FIELDS"])){

            echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
            echo "<tr>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Field</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">&nbsp;</td>\n";
            echo "</tr>\n";

            foreach($PHORUM["PROFILE_FIELDS"] as $key => $item){
                echo "<tr>\n";
                echo "    <td class=\"PhorumAdminTableRow\">$item</td>\n";
                echo "    <td class=\"PhorumAdminTableRow\"><a href=\"$_SERVER[PHP_SELF]?module=customprofile&curr=$key&?edit=1\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$_SERVER[PHP_SELF]?module=customprofile&curr=$key&delete=1\">Delete</a></td>\n";
                echo "</tr>\n";
            }

            echo "</table>\n";

        } else {

            echo "No custom fields currently allowed.";

        }

    }

?>