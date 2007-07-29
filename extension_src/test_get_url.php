<?php

if (! extension_loaded('phorum')) {
    @dl('phorum.so');
}

# For testing custom get url support, uncomment this code.
#function phorum_custom_get_url()
#{
#    print_r(func_get_args());
#    return "CUSTOM URL\n";
#}

global $PHORUM;

$PHORUM["forum_id"] = 7;
$PHORUM["http_path"] = "http://phorum.example.com/forums";
$PHORUM["args"][1] = '2222';
$PHORUM["GET_VARS"] = array("ape=nut");

define('PHORUM', 'dev');
include(dirname(__FILE__) . "/../include/constants.php");

define('phorum_page', 'read');

for($i=0;$i<1;$i++)
{
var_dump(phorum_get_url(PHORUM_FILE_URL, "file=123", "filename=%file_name%"));
var_dump(phorum_get_url(PHORUM_FEED_URL, "type=rss", "replies=1"));
$PHORUM["file_url_uses_pathinfo"] = 0;
var_dump(phorum_get_url(PHORUM_FILE_URL, "file=1", "filename=test.jpg"));
$PHORUM["file_url_uses_pathinfo"] = 1;
var_dump(phorum_get_url(PHORUM_FILE_URL, "file=1", "filename=twofileargs.php", "filename=test++funny_chars*flattening.jpg", "last=arg"));
var_dump(phorum_get_url(PHORUM_FILE_URL, "filename=onefilearg.php", "file=1"));
var_dump(phorum_get_url(PHORUM_FILE_URL));
var_dump(phorum_get_url(PHORUM_FILE_URL, "filename=********.gif"));

var_dump(phorum_get_url(PHORUM_BASE_URL));
var_dump(phorum_get_url(PHORUM_INDEX_URL, 10, "type=ok"));
var_dump(phorum_get_url(PHORUM_LIST_URL, 1, "cleanup=what"));
var_dump(phorum_get_url(PHORUM_LIST_URL));
var_dump(phorum_get_url(PHORUM_LIST_URL, 1, 2, 3, "thisisa=value", "someswitch", 4, 5));
var_dump(phorum_get_url(PHORUM_READ_URL, 10, 12, "markread=1"));
var_dump(phorum_get_url(PHORUM_FOREIGN_READ_URL, 2, 10, 12, "markread=1"));
var_dump(phorum_get_url(PHORUM_READ_URL, "test=nomessageid"));
var_dump(phorum_get_url(PHORUM_REGISTER_URL));
var_dump(phorum_get_url(PHORUM_REGISTER_ACTION_URL));

$PHORUM["reply_on_read_page"] = 0;
var_dump(phorum_get_url(PHORUM_REPLY_URL, 10, 12));
$PHORUM["reply_on_read_page"] = 1;
var_dump(phorum_get_url(PHORUM_REPLY_URL, 10, 12));

var_dump(phorum_get_url(PHORUM_CUSTOM_URL, "custompage", 1, "my=arg", "is=cool"));
var_dump(phorum_get_url(PHORUM_CUSTOM_URL, "custompage", 0, "my=arg", "is=cool"));
var_dump(phorum_get_url(PHORUM_CUSTOM_URL, "custompage"));

var_dump(phorum_get_url(PHORUM_ADDON_URL, "mymodule"));
var_dump(phorum_get_url(PHORUM_ADDON_URL, "module=mymodule"));
var_dump(phorum_get_url(PHORUM_ADDON_URL, "mymodule", "with", "extra", "args"));
var_dump(phorum_get_url(PHORUM_ADDON_URL, "module=mymodule", "more=args", 1,2,3));

}

?>
