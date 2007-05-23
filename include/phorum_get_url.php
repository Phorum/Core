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
function phorum_get_url()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $argv = func_get_args();

    $url = "";
    $suffix = "";
    $args = "";
    $add_forum_id = false;
    $add_get_vars = true;

    $type = array_shift( $argv );

    switch ( $type ) {
        case PHORUM_LIST_URL:
            $page = "list";
            if ( empty( $argv ) ) $add_forum_id = true;
            break;
        case PHORUM_READ_URL:
            $page = "read";
            $add_forum_id = true;
            if ( !empty( $argv[1] ) && is_numeric( $argv[1] ) ) $suffix = "#msg-$argv[1]";
            break;
        case PHORUM_CHANGES_URL:
            $page = "changes";
            $add_forum_id = true;
            break;
        case PHORUM_FOREIGN_READ_URL:
            $page = "read";
            if ( !empty( $argv[2] ) && is_numeric( $argv[2] ) ) $suffix = "#msg-$argv[2]";
            break;
        case PHORUM_REPLY_URL:
            if(isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]){
                $page = "read";
                $suffix = "#REPLY";
            } else {
                $page = "posting";
                // For reply on a separate page, we call posting.php on its own.
                // In that case argv[0] is the editor mode we want to use
                // (reply in this case). Currently, the thread id is in argv[0],
                // but we don't need that one for posting.php. So we simply
                // replace argv[0] with the correct argument.
                $argv[0] = "reply";
            }
            $add_forum_id = true;
            break;
        case PHORUM_POSTING_URL:
            $page = "posting";
            $add_forum_id = true;
            break;
        case PHORUM_REDIRECT_URL:
            $page = "redirect";
            $add_forum_id = false;
            break;
        case PHORUM_SEARCH_URL:
            $page = "search";
            $add_forum_id = true;
            break;
        case PHORUM_SEARCH_ACTION_URL:
            $page = "search";
            $add_get_vars = true;
            break;
        case PHORUM_DOWN_URL: // TODO: still in use?
            $page = "down";
            $add_forum_id = true;
            break;
        case PHORUM_VIOLATION_URL: // TODO: still in use?
            $page = "violation";
            $add_forum_id = true;
            break;
        case PHORUM_INDEX_URL:
            $page = "index";
            break;
        case PHORUM_LOGIN_URL:
            $page = "login";
            $add_forum_id = true;
            break;
        case PHORUM_LOGIN_ACTION_URL:
            $page = "login";
            break;
        case PHORUM_REGISTER_URL:
            $page = "register";
            $add_forum_id = true;
            break;
        case PHORUM_REGISTER_ACTION_URL:
            $page = "register";
            break;
        case PHORUM_PROFILE_URL:
            $page = "profile";
            $add_forum_id = true;
            break;
        case PHORUM_SUBSCRIBE_URL:
            $page = "subscribe";
            $add_forum_id = true;
            break;
        case PHORUM_MODERATION_URL:
            $page = "moderation";
            $add_forum_id = true;
            break;
        case PHORUM_MODERATION_ACTION_URL:
            $page = "moderation";
            $add_get_vars = false;
            break;
        case PHORUM_PREPOST_URL:
            $page = "control";
            $argv[] = "panel=messages";
            $add_forum_id = true;
            break;
        case PHORUM_CONTROLCENTER_URL:
            $page = "control";
            $add_forum_id = true;
            break;
        case PHORUM_CONTROLCENTER_ACTION_URL:
            $page = "control";
            break;
        case PHORUM_PM_URL:
            $page = "pm";
            $add_forum_id = true;
            break;
        case PHORUM_PM_ACTION_URL:
            $page = "pm";
            break;
        case PHORUM_FILE_URL:
            $page = "file";
            $add_forum_id = true;
            break;
        case PHORUM_FOLLOW_URL:
            $page = "follow";
            $add_forum_id = true;
            break;
        case PHORUM_FOLLOW_ACTION_URL:
            $page = "follow";
            $add_forum_id = false;
            break;
        case PHORUM_REPORT_URL:
            $page = "report";
            $add_forum_id = true;
            break;
        case PHORUM_FEED_URL:
            switch(phorum_page){
                case "list":
                    $add_forum_id = true;
                    break;
                case "read":
                    $add_forum_id = true;
                    $thread_id = (int)$PHORUM["args"][1];
                    array_push($argv, $thread_id);
                    break;
            }
            $page = "feed";
            break;
        // this is for adding own generic urls
        case PHORUM_CUSTOM_URL:
            $page = array_shift($argv); // first arg is our page
            $add_forum_id_tmp=array_shift($argv); // second determining if we should add the forum_id
            $add_forum_id = $add_forum_id_tmp?true:false;
            break;

        case PHORUM_BASE_URL:
            // only to flag phorum_custom_get_url() that base url is requested
            $page = '';
            break;

        case PHORUM_ADDON_URL:
            $page = "addon";
            $add_forum_id = true;
            if (!isset($argv[0])) {
               trigger_error('Missing "module" argument for PHORUM_ADDON_URL');
            }
            if (substr($argv[0], 0, 7) != "module=") {
                $argv[0] = "module={$argv[0]}";
            }
            break;

        default:
            trigger_error( "Unhandled page type.", E_USER_WARNING );
            break;
    }

    // build the query string
    $query_items = array();

    if ( $add_forum_id ) {
        $query_items[] = ( int )$PHORUM["forum_id"];
    }

    if ( count( $argv ) > 0 ) {
        $query_items = array_merge( $query_items, $argv );
    }

    if ( !empty( $PHORUM["DATA"]["GET_VARS"] ) && $add_get_vars ) {
        $query_items = array_merge( $query_items, $PHORUM["DATA"]["GET_VARS"] );
    }
    // build the url
    if ( !function_exists( "phorum_custom_get_url" ) ) {
        if ($type == PHORUM_BASE_URL) return $PHORUM["http_path"] . '/';

        $url = "$PHORUM[http_path]/$page." . PHORUM_FILE_EXTENSION;

        if ( count( $query_items ) ) $url .= "?" . implode( ",", $query_items );

        if ( !empty( $suffix ) ) $url .= $suffix;
    } else {
        $url = phorum_custom_get_url( $page, $query_items, $suffix );
    }

    return $url;
}

?>
