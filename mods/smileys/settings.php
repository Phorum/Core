<?php

if(!defined("PHORUM_ADMIN")) return;

$error="";
$curr="NEW";

$smileys_found=array();

if(file_exists("./smileys")){
    $d = dir("./smileys");
    while($entry=$d->read()) {
        if($entry != '.' && $entry !='..') {
            $smileys_found[]=$entry;
        }
    }
}

if(count($_POST) && $_POST["search"]!="" && $_POST["smiley"]!=""){

    $item = array("search"=>$_POST["search"], "smiley"=>$_POST["smiley"], "alt"=>$_POST["alt"], "uses"=> $_POST['uses']);

    if($_POST["curr"]!="NEW"){
        $PHORUM["mod_smileys"][$_POST["curr"]]=$item;
    } else {
        $PHORUM["mod_smileys"][]=$item;
    }

    if(empty($error)){
        if(!phorum_db_update_settings(array("mod_smileys"=>$PHORUM["mod_smileys"]))){
            $error="Database error while updating settings.";
        } else {
            echo "Smiley Updated<br />";
        }
    }
}

if(isset($_GET["curr"])){
    if(isset($_GET["delete"])){
        unset($PHORUM["mod_smileys"][$_GET["curr"]]);
        phorum_db_update_settings(array("mod_smileys"=>$PHORUM["mod_smileys"]));
        echo "Smiley-Replacement Deleted<br />";
    } else {
        $curr = $_GET["curr"];
    }
}


if($curr!="NEW"){
    extract($PHORUM["mod_smileys"][$curr]);
    $title="Edit Smiley-Replacement";
    $submit="Update";
    if(!isset($uses)) // default for old installs
        $uses=0;
} else {
    $string="";
    $smiley="";
    $uses=2;
    /*settype($string, "string");
    settype($smiley, "string");
    settype($uses,"integer");*/
    $title="Add A Smiley-Replacement";
    $submit="Add";
}

if ( isset ( $_POST['prefix'] ) ) { // i would strlen() it but it could be blank in theory, messy but reality
	$PHORUM['mod_smileys']['prefix'] = $_POST['prefix'];
	phorum_db_update_settings(array("mod_smileys"=>$PHORUM["mod_smileys"]));
	echo "Prefix update<br />";
}

if ( ! isset ( $PHORUM['mod_smileys']['prefix'] ) ) {
	// default so it will easily update old smileys mods
	$PHORUM['mod_smileys']['prefix'] = 'smileys/';
	phorum_db_update_settings(array("mod_smileys"=>$PHORUM["mod_smileys"]));
	echo "Smileys settings updated to include default prefix<br />";
}

include_once "./include/admin/PhorumInputForm.php";

echo "<script>
function change_image() {
var img_name=document.forms[0].smiley.options[document.forms[0].smiley.selectedIndex].value;
document.previewimage.src=\"smileys/\"+img_name;
}
</script>";

$frm =& new PhorumInputForm ("", "post", 'Change');

$frm->hidden("module", "modsettings");

$frm->hidden("mod", "smileys");

$frm->hidden("curr", $curr);

$frm->addbreak($title." Smiley Prefix");

$frm->addrow("Prefix to smileys", $frm->text_box("prefix", $PHORUM['mod_smileys']['prefix'], 50));

$frm->show();

$frm =& new PhorumInputForm ("", "post", $submit);

$frm->hidden("module", "modsettings");

$frm->hidden("mod", "smileys");

$frm->hidden("curr", "$curr");

$frm->addbreak($title);

$frm->addrow("String To Match", $frm->text_box("search", $search, 50));

$frm->addrow("Smiley-Replacement", $frm->select_tag_valaskey("smiley", $smileys_found, $smiley, "onChange=\"change_image();\""));

$frm->addrow("Alt. Tag For Image", $frm->text_box("alt", $alt, 50));


$frm->addrow("Used for", $frm->select_tag("uses",array(0=>'Body',1=>'Subject',2=>'Body AND Subject'),$uses));

$frm->show();

echo "Currently selected Smiley: <img src=\"images/trans.gif\" name=\"previewimage\" /><br />If you want to put a new smiley online, just drop it in the smileys-folder in the main-phorum-dir";

if($curr=="NEW"){

    echo "<hr class=\"PhorumAdminHR\" />";

    if(count($PHORUM["mod_smileys"])){

        echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
        echo "<tr>\n";
        echo "    <td class=\"PhorumAdminTableHead\">Search</td>\n";
        echo "    <td class=\"PhorumAdminTableHead\">Smiley Name</td>\n";
        echo "    <td class=\"PhorumAdminTableHead\">Smiley Image</td>\n";
        echo "    <td class=\"PhorumAdminTableHead\">Smiley Alt. Tag</td>\n";
        echo "    <td class=\"PhorumAdminTableHead\">Used for</td>\n";
        echo "    <td class=\"PhorumAdminTableHead\">&nbsp;</td>\n";
        echo "</tr>\n";

        foreach($PHORUM["mod_smileys"] as $key => $item){
            if ( ! is_long ( $key ) ) { // hack to allow extra settings, like prefix
                   continue;
            }
            if($item['uses'] == 2) {
                   $used_for_txt="Body AND Subject";
            } elseif($item['uses'] == 1) {
                   $used_for_txt="Subject";
            } else {
                   $used_for_txt="Body";
            }
            echo "<tr>\n";
            echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item["search"])."</td>\n";
            echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item["smiley"])."</td>\n";
            echo "    <td class=\"PhorumAdminTableRow\"><img src=\"".$PHORUM['mod_smileys']['prefix'].$item["smiley"]."\" /></td>\n";
            echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item["alt"])."</td>\n";
            echo "    <td class=\"PhorumAdminTableRow\">$used_for_txt</td>\n";
            echo "    <td class=\"PhorumAdminTableRow\"><a href=\"$_SERVER[PHP_SELF]?module=modsettings&mod=smileys&curr=$key&?edit=1\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$_SERVER[PHP_SELF]?module=modsettings&mod=smileys&curr=$key&delete=1\">Delete</a></td>\n";
            echo "</tr>\n";
        }

        echo "</table>\n";

    } else {

        echo "No smiley-replacements in list currently.";

    }

}

?>
