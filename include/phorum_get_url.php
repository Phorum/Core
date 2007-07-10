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

// Standard pages and their options

$PHORUM["url_patterns"] = array(
    PHORUM_BASE_URL                 => array("", PHORUM_URL_NO_FORUM_ID, true),
    PHORUM_CHANGES_URL              => array("changes", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_CONTROLCENTER_ACTION_URL => array("control", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_CONTROLCENTER_URL        => array("control", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_CSS_URL                  => array("css", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_FEED_URL                 => array("feed", PHORUM_URL_NO_FORUM_ID, true),
    PHORUM_FOLLOW_ACTION_URL        => array("follow", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_FOLLOW_URL               => array("follow", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_INDEX_URL                => array("index", PHORUM_URL_NO_FORUM_ID, true),
    PHORUM_LIST_URL                 => array("list", PHORUM_URL_COND_FORUM_ID, true),
    PHORUM_LOGIN_ACTION_URL         => array("login", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_LOGIN_URL                => array("login", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_MODERATION_ACTION_URL    => array("moderation", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_MODERATION_URL           => array("moderation", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_PM_ACTION_URL            => array("pm", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_PM_URL                   => array("pm", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_POSTING_URL              => array("posting", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_POSTING_ACTION_URL       => array("posting", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_PROFILE_URL              => array("profile", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_REDIRECT_URL             => array("redirect", PHORUM_URL_NO_FORUM_ID, true),
    PHORUM_REGISTER_ACTION_URL      => array("register", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_REGISTER_URL             => array("register", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_REPORT_URL               => array("report", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_SEARCH_ACTION_URL        => array("search", PHORUM_URL_NO_FORUM_ID, false),
    PHORUM_SEARCH_URL               => array("search", PHORUM_URL_ADD_FORUM_ID, true),
    PHORUM_SUBSCRIBE_URL            => array("subscribe", PHORUM_URL_ADD_FORUM_ID, true),
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
                if ( !empty( $argv[1] ) && is_numeric( $argv[1] ) ) $suffix = "#msg-$argv[1]";
                break;


            case PHORUM_REPLY_URL:
                if(isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]){
                    $name = "read";
                    $suffix = "#REPLY";
                } else {
                    $name = "posting";
                    // For reply on a separate page, we call posting.php on its own.
                    // In that case argv[0] is the editor mode we want to use
                    // (reply in this case). Currently, the thread id is in argv[0],
                    // but we don't need that one for posting.php. So we simply
                    // replace argv[0] with the correct argument.
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

                // If a filename=... parameter is set, then change that parameter to
                // a URL path, unless this feature is not enabled in the admin setup.
                if (!empty($PHORUM['file_url_uses_pathinfo'])) {
                    foreach ($argv as $id => $arg) {
                        if (substr($arg, 0, 9) == 'filename=') {
                            $safe_file = urldecode(substr($arg, 9));
                            $safe_file = preg_replace('/[^\w\_\-\.]/', '_', $safe_file);
                            $safe_file = preg_replace('/_+/', '_', $safe_file);
                            $pathinfo = "/$safe_file";
                            unset($argv[$id]);
                        }
                    }
                }
                break;

            // this is for adding own generic urls
            case PHORUM_CUSTOM_URL:
                $name = array_shift($argv); // first arg is our page
                $add_forum_id = (bool) array_shift($argv); // second determining if we should add the forum_id
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

        if($add_get_vars){

            if($add_forum_id==PHORUM_URL_ADD_FORUM_ID){
                $query_string = $PHORUM["forum_id"].",";
            }

            if ( count( $argv ) > 0 ) {
                $query_string.= implode(",", $argv ).",";
            }

            if ( !empty( $PHORUM["DATA"]["GET_VARS"] ) && $add_get_vars ) {
                $query_string.= implode(",", $PHORUM["DATA"]["GET_VARS"] ).",";
            }

            if($query_string){
                $query_string = substr($query_string, 0, -1 );  // trim off ending ,
            }

            if ( function_exists( "phorum_custom_get_url" ) ) {

                $url = phorum_custom_get_url( $name, explode(",",$query_string), $suffix, $pathinfo );

            } else {

                if ($pathinfo !== null) $url .= $pathinfo;

                if ($query_string){
                    $url.= "?" . $query_string;
                }

                if ( !empty( $suffix ) ) $url .= $suffix;
            }
        }

    } else {
        trigger_error( "Unhandled page type ".$type.".", E_USER_WARNING );
    }

    return $url;

}

?>
