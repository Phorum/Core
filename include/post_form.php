<?php

if(!defined("PHORUM")) return;

// check if the Phorum is in read only mode.
if($PHORUM["status"]=="read-only"){
    $PHORUM["DATA"]["MESSAGE"]=$PHORUM["DATA"]["LANG"]["ReadOnlyMessage"];
    include phorum_get_template("message");
    exit();
}

phorum_hook("post_form");

print '<a name="REPLY"></a>';

$goto_mode = "reply";
if (isset($PHORUM["args"]["quote"]) && $PHORUM["args"]["quote"])
    $goto_mode = "quote";
if (! isset($PHORUM["args"][2]))
    $PHORUM["args"][2] = $PHORUM["args"][1];
$PHORUM["args"][1] = $goto_mode;
$PHORUM["args"]["as_include"] = 1;
include("./posting.php");

?>
