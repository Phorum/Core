<?php

/**
 * We need to define the place where the real Phorum code lives.
 */
$PHORUM_DIR="/home/brian/htdocs/phorum-trunk";


/**
 * You can supply custom DB settings for this portable code base using the
 * $PHORUM_ALT_DBCONFIG array.  The PHORUM_WRAPPER constant must be set.
 */
/*
define("PHORUM_WRAPPER",1);

// optionally set the databse settings for this Phorum Install
$PHORUM_ALT_DBCONFIG=array(

   "type"          =>  "mysql",
   "name"          =>  "phorum",
   "server"        =>  "localhost",
   "user"          =>  "phorum",
   "password"      =>  "phorum",
   "table_prefix"  =>  "phorum_portable",
   "charset"       =>  "utf8"
);
*/

/**
 * Phorum will call this function to build the URLs instead of using
 * its internal URL creation functions
 */
function phorum_custom_get_url ($page, $query_items, $suffix, $pathinfo)
{
    global $PHORUM;

    $url = "$PHORUM[http_path]/portable/phorum.php";
    if ($pathinfo !== NULL) $url .= $pathinfo;
    $url .= "?$page";

    if(count($query_items)) $url.=",".implode(",", $query_items);

    if(!empty($suffix)) $url.=$suffix;

    return $url;
}

?>
