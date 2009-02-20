<?php

/**
 * THIS IS AN EXAMPLE OF HOW YOU WOULD WRAP PHORUM.
 * IT IS NOT A DROP IN SOLUTION.
 *
 * Phorum wrapper to create a portable, dynamic Phorum with a single code base
 * and to safely wrap Phorum to protect it from other applications.
 *
 * Images in your template will have to be redone to support these dynamic URLs.
 *
 */



/**
 * Include custom DB settings and custom URL handing functions
 * This also sets $PHORUM_DIR;
 */
require_once('./phorum_settings.php');

if(isset($PHORUM_DIR)){
    chdir($PHORUM_DIR);
} else {
    trigger_error("\$PHORUM_DIR not set.  Can't start Phorum", E_USER_ERROR);
}



/**
 * Check for a query string like: list,10.
 * If we see that, pull off the first part, list, and set the rest to
 * $PHORUM_CUSTOM_QUERY_STRING.  Phorum will use that variable instead
 * of the normal variable $_SERVER["QUERY_STRING"].
 */

if(preg_match("/^([a-z_]+)(,|$)/", $_SERVER["QUERY_STRING"], $match)){

    $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"] = str_replace($match[0], "", $_SERVER["QUERY_STRING"]);
    $page = basename($match[1]);


/**
 * Otherwise, assume we want the index
 */
} else {

    $page="index";
}


/**
 * If we have file that exists, lets load it up
 */
if(file_exists("./$page.php")){
    phorum_namespace($page);
}

/**
 * Because we have no idea what environment we may be running in, load up
 * Phorum inside a function.
 */
function phorum_namespace($page)
{
    global $PHORUM;  // globalize the $PHORUM array
    require_once("./$page.php");
}

?>
