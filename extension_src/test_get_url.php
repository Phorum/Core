<?php

dl("phorum.so");

global $PHORUM;

$PHORUM["forum_id"] = 7;
$PHORUM["http_path"] = "http://phorum.example.com/forums";
$PHORUM["args"] = array(
    "1" => "1234",
    "2" => "2345"
);

define('PHORUM_FILE_EXTENSION', 'php');
define('PHORUM_INDEX_URL', 1);
define('PHORUM_LIST_URL',  2);
define('PHORUM_READ_URL',  3);
define('PHORUM_FEED_URL',  4);

define('phorum_page', 'read');

var_dump(phorum_ext_get_url(PHORUM_INDEX_URL, 10, "type=ok"));
var_dump(phorum_ext_get_url(PHORUM_LIST_URL, 1, "cleanup=what"));
var_dump(phorum_ext_get_url(PHORUM_LIST_URL, 1, 2, 3, "thisisa=value", "someswitch", 4, 5));
var_dump(phorum_ext_get_url(PHORUM_READ_URL, 2, 10, "markread=1"));
var_dump(phorum_ext_get_url(PHORUM_READ_URL, "test=nomessageid"));
var_dump(phorum_ext_get_url(PHORUM_FEED_URL, "type=rss"));
##
#$i = phorum_ext_get_url(PHORUM_INDEX_URL);
#print " = $i\n";

?>
