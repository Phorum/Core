<?php
/**
 * This function is used for generating all the Phorum related URL's.
 *
 * Important note for the developers:
 * ----------------------------------
 * If anything changes in this file, then beware that the Phorum
 * PHP Extension code needs to be updated as well. Add a TODO marker
 * to the updated pieces of code if this update is not done immediately.
 * ----------------------------------
 */

/**
 * Descriptions of standard Phorum page URL types and their options.
 * The keys in this array describe the type of Phorum URL.
 * The values are arrays, containing the following three elements:
 * - The name of the Phorum page to link to;
 * - A constan, telling whether the forum_id has to be added to the URL;
 * - A boolean, telling whether the GET vars have to be added to the URL.
 */
$PHORUM["url_patterns"] = array(
    PHORUM_BASE_URL                 => array("",           PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_CHANGES_URL              => array("changes",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_CONTROLCENTER_ACTION_URL => array("control",    PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_CONTROLCENTER_URL        => array("control",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_CSS_URL                  => array("css",        PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_JAVASCRIPT_URL           => array("javascript", PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_FEED_URL                 => array("feed",       PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_FOLLOW_ACTION_URL        => array("follow",     PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_FOLLOW_URL               => array("follow",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_INDEX_URL                => array("index",      PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_LIST_URL                 => array("list",       PHORUM_URL_COND_FORUM_ID, true),
    PHORUM_LOGIN_ACTION_URL         => array("login",      PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_LOGIN_URL                => array("login",      PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_MODERATION_ACTION_URL    => array("moderation", PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_MODERATION_URL           => array("moderation", PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_PM_ACTION_URL            => array("pm",         PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_PM_URL                   => array("pm",         PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_POSTING_URL              => array("posting",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_POSTING_ACTION_URL       => array("posting",    PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_PROFILE_URL              => array("profile",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_REDIRECT_URL             => array("redirect",   PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_REGISTER_ACTION_URL      => array("register",   PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_REGISTER_URL             => array("register",   PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_REPORT_URL               => array("report",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_SEARCH_ACTION_URL        => array("search",     PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_SEARCH_URL               => array("search",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_SUBSCRIBE_URL            => array("subscribe",  PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_ADDON_URL                => array("addon",      PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_AJAX_URL                 => array("ajax",       PHORUM_URL_NO_FORUM_ID,   false),
);


function phorum_get_url()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $argv = func_get_args();

    $url = "";
    $suffix = "";
    $pathinfo = NULL;
    $add_forum_id = false;
    $add_get_vars = true;

    $type = array_shift( $argv );


    if(!isset($PHORUM["url_patterns"][$type])){

        // these URL types need extra care
        // please do not add anything to this unless it is a last resort

        switch($type){

            case PHORUM_READ_URL:
                $name = "read";
                $add_forum_id = true;
                $add_get_vars = true;
                if ( !empty( $argv[1] ) &&
                    (is_numeric( $argv[1] ) || $argv[1] == '%message_id%') )
                        $suffix = "#msg-$argv[1]";
                break;


            case PHORUM_REPLY_URL:
                if(isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]){
                    $name = "read";
                    $suffix = "#REPLY";
                } else {
                    $name = "posting";
                    // For reply on a separate page, we call posting.php on
                    // its own. In that case argv[0] is the editor mode we
                    // want to use (reply in this case). Currently, the thread
                    // id is in argv[0], but we don't need that one for
                    // posting.php. So we simply replace argv[0] with the
                    // correct argument.
                    $argv[0] = "reply";
                }
                $add_get_vars = true;
                $add_forum_id = true;
                break;


            case PHORUM_FOREIGN_READ_URL:
                $name = "read";
                $add_forum_id = false;
                $add_get_vars = true;
                if ( !empty( $argv[2] ) && is_numeric( $argv[2] ) ) $suffix = "#msg-$argv[2]";
                break;

            case PHORUM_FILE_URL:
                $name = "file";
                $add_forum_id = true;

                // If a filename=... parameter is set, then change that
                // parameter to a URL path, unless this feature is not
                // enabled in the admin setup.
                $unset = array();
                if (!empty($PHORUM['file_url_uses_pathinfo']))
                {
                    $file_id  = NULL;
                    $filename = NULL;
                    $download = '';

                    foreach ($argv as $id => $arg) {
                        if (substr($arg, 0, 5) == 'file=') {
                            $file_id = substr($arg, 5);
                            // %file_id% is sometimes used for creating URL
                            // templates, so we should not mangle that one.
                            if ($file_id != '%file_id%') {
                                settype($file_id, 'int');
                            }
                            $unset[] = $id;
                        } elseif (substr($arg, 0, 9) == 'filename=') {
                            $filename = urldecode(substr($arg, 9));
                            // %file_name% is sometimes used for creating URL
                            // templates, so we should not mangle that one.
                            if ($filename != '%file_name%') {
                                $filename = preg_replace('/[^\w\_\-\.]/', '_', $filename);
                                $filename = preg_replace('/_+/', '_', $filename);
                            }
                            $unset[] = $id;
                        } elseif (substr($arg, 0, 9) == 'download=') {
                            $download = 'download/';
                            $unset[] = $id;
                        }
                    }
                    if ($file_id !== NULL && $filename !== NULL) {
                        foreach ($unset as $id) unset($argv[$id]);
                        $add_forum_id = false;
                        $pathinfo = "/$download{$PHORUM['forum_id']}/$file_id/$filename";
                    }
                }
                break;

            // this is for adding own generic urls
            case PHORUM_CUSTOM_URL:
                // first arg is our page
                $name = array_shift($argv);
                // second arg determines if we should add the forum_id
                $add_forum_id = (bool) array_shift($argv);
                break;

        }

    } else {

        list($name, $add_forum_id, $add_get_vars) = $PHORUM["url_patterns"][$type];

        // add forum id if setting is conditional and there are no params
        if($add_forum_id==PHORUM_URL_COND_FORUM_ID && count($argv)==0){
            $add_forum_id=PHORUM_URL_ADD_FORUM_ID;
        }

    }

    if(isset($name)){

        $query_string = "";

        $url = $PHORUM["http_path"]."/";

        if($name){
            $url.= $name.".".PHORUM_FILE_EXTENSION;
        }

        if($add_forum_id==PHORUM_URL_ADD_FORUM_ID){
            $query_string = $PHORUM["forum_id"].",";
        }

        if ( count( $argv ) > 0 ) {
            $query_string.= implode(",", $argv ).",";
        }

        if($add_get_vars) {
            if ( !empty( $PHORUM["DATA"]["GET_VARS"] ) && $add_get_vars ) {
                $query_string.= implode(",", $PHORUM["DATA"]["GET_VARS"] ).",";
            }
        }

        if($query_string){
            $query_string = substr($query_string, 0, -1 );  // trim off ending ,
        }

        if ( function_exists( "phorum_custom_get_url" ) ) {
            $query_items = $query_string == ''
                         ? array() : explode(',', $query_string);
            $url = phorum_custom_get_url( $name, $query_items, $suffix, $pathinfo );

        } else {

            if ($pathinfo !== null) $url .= $pathinfo;

            if ($query_string){
                $url.= "?" . $query_string;
            }

            if ( !empty( $suffix ) ) $url .= $suffix;
        }

    } else {
        trigger_error( "Unhandled page type ".$type.".", E_USER_WARNING );
    }

    return $url;

}

/**
 * Generate a Phorum URL, without any URI authentication information in it.
 */
function phorum_get_url_no_uri_auth()
{
    global $PHORUM;

    $uri_auth = NULL;
    if (isset($PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM])) {
        $uri_auth = $PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM];
        unset($PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM]);
    }

    $argv = func_get_args();

    $url = call_user_func_array('phorum_get_url', $argv);

    if ($uri_auth !== NULL) {
        $PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM] = $uri_auth;
    }

    return $url;
}

/**
 * Determines the current pages URL
 *
 * Several places in code we need to produce the current URL for use in
 * redirects and use in forms.  This function does that to the best of
 * our ability
 *
 * @param   boolean $include_query_string
 *      If true, the query string is appended to the URL.
 *      If false the query string is left off
 *
 * @return  string
 *      The current URL
 *
 */
function phorum_get_current_url($include_query_string=true) {

    $url = "";

    // On some systems, the SCRIPT_URI is set, but using a different host
    // name than the one in HTTP_HOST (probably due to some mass virtual
    // hosting request rewriting). If that happens, we do not trust
    // the SCRIPT_URI. Otherwise, we use the SCRIPT_URI as the current URL.
    if (isset($_SERVER["SCRIPT_URI"]) &&
        (!isset($_SERVER['HTTP_HOST']) ||
         stripos($_SERVER['SCRIPT_URI'], "//$_SERVER[HTTP_HOST]/") !== FALSE)) {

        $url = $_SERVER["SCRIPT_URI"];

    } else {
        // On some systems, the port is also in the HTTP_HOST, so we
        // need to strip the port if it appears to be in there.
        if (preg_match('/^(.+):(.+)$/', $_SERVER['HTTP_HOST'], $m)) {
            $host = $m[1];
            if (!isset($_SERVER['SERVER_PORT'])) {
                $_SERVER['SERVER_PORT'] = $m[2];
            }
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }
        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="off") ? "https" : "http";
        $port = ($_SERVER["SERVER_PORT"]!=443 && $_SERVER["SERVER_PORT"]!=80) ? ':'.$_SERVER["SERVER_PORT"] : "";
        $url = $protocol.'://'.$host.$port.$_SERVER['PHP_SELF'];
    }

    if(!empty($_SERVER["PATH_INFO"]) && strpos($url, $_SERVER["PATH_INFO"]) !== false){
        $url = substr($url, 0, strlen($url) - strlen($_SERVER["PATH_INFO"]));
    }

    if($include_query_string && !empty($_SERVER["QUERY_STRING"])){
        $url .= "?".$_SERVER["QUERY_STRING"];
    }

    return $url;
}

?>
