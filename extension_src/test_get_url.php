<?php

dl("phorum.so");

global $PHORUM;

$PHORUM["forum_id"] = 7;
$PHORUM["http_path"] = "http://phorum.example.com/forums";
$PHORUM["args"] = array(
    "1" => "1234",
    "2" => "2345"
);

define('PHORUM', 'dev');
include(dirname(__FILE__) . "/../include/constants.php");

define('phorum_page', 'read');

var_dump(phorum_get_url(PHORUM_BASE_URL));
var_dump(phorum_get_url(PHORUM_INDEX_URL, 10, "type=ok"));
var_dump(phorum_get_url(PHORUM_LIST_URL, 1, "cleanup=what"));
var_dump(phorum_get_url(PHORUM_LIST_URL));
var_dump(phorum_get_url(PHORUM_LIST_URL, 1, 2, 3, "thisisa=value", "someswitch", 4, 5));
var_dump(phorum_get_url(PHORUM_READ_URL, 2, 10, "markread=1"));
var_dump(phorum_get_url(PHORUM_READ_URL, "test=nomessageid"));
var_dump(phorum_get_url(PHORUM_FEED_URL, "type=rss"));
var_dump(phorum_get_url(PHORUM_REGISTER_URL));
var_dump(phorum_get_url(PHORUM_REGISTER_ACTION_URL));


?>
