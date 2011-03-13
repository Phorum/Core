<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

if ( !defined( "PHORUM" ) ) return;

/**
 * Formats forum messages.
 *
 * @param array $data
 *     An array containing an array of messages to be formatted.
 *
 * @param array $author_specs
 *     By default, the formatting function will create  author info
 *     data out of the fields "user_id", "author" and "email".
 *     This will create $data["URL"]["PROFILE"] if needed (either pointing
 *     to a user profile for registered users or the email address of
 *     anonymous users that left an email address in the forum) and will
 *     do formatting on the $data["author"] field.
 *
 *     By providing extra $author_specs, this formatting can be done on
 *     more author fields. This argument should be an array, containing
 *     arrays with five fields: the field that contains a user_id,
 *     the field for the name of the author and the field for the email
 *     address (can be NULL if none available), the name of the field
 *     to store the author name in and the name of the URL field to store
 *     the profile/email link in. For the default author field like
 *     describe above, this array would be:
 *
 *     array("user_id", "author", "email", "author", "PROFILE");
 *
 * @return data - The formatted messages.
 */
function phorum_format_messages ($data, $author_specs = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Prepare author specs.
    if ($author_specs === NULL) $author_specs = array();
    $author_specs[] = array("user_id","author","email","author","PROFILE");

    // Prepare the bad-words replacement code.
    $bad_word_check= false;

    $banlists = NULL;
    if(!empty($PHORUM['cache_banlists']) && !empty($PHORUM['banlist_version'])){
        $cache_key = $PHORUM['forum_id'];
        $banlists=phorum_cache_get('banlist',$cache_key,$PHORUM['banlist_version']);
    }
    // not found or no caching enabled
    if($banlists === NULL ) {
        $banlists = phorum_db_get_banlists();

        if(!empty($PHORUM['cache_banlists']) && !empty($PHORUM['banlist_version'])) {
            phorum_cache_put('banlist',$cache_key,$banlists,7200,$PHORUM['banlist_version']);
        }
    }

    if (isset($banlists[PHORUM_BAD_WORDS]) && is_array($banlists[PHORUM_BAD_WORDS])) {
        $replace_vals  = array();
        $replace_words = array();
        foreach ($banlists[PHORUM_BAD_WORDS] as $item) {
            $replace_words[] = "/\b".preg_quote($item['string'],'/')."(ing|ed|s|er|es)*\b/i";
            $replace_vals[]  = PHORUM_BADWORD_REPLACE;
            $bad_word_check  = true;
        }
    }

    // A special <br> tag to keep track of breaks that are added by phorum.
    $phorum_br = '<phorum break>';

    // prepare url-templates used later on
    $profile_url_template = phorum_get_url(PHORUM_PROFILE_URL, '%spec_data%');

    // Apply Phorum's formatting rules to all messages.
    foreach( $data as $key => $message )
    {
        // Normally, the message_id must be set, since we should be handling
        // message data. It might not be set however, because sometimes
        // the message formatting is called using some fake message data
        // for formatting something else than a message.
        if (!isset($message['message_id'])) {
            $data[$key]['message_id'] = $message['message_id'] = $key;
        }

        // Work on the message body ========================

        if (isset($message["body"]))
        {
            $body = $message["body"];

            // Convert legacy <> urls into bare urls.
            $body = preg_replace("/<((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%]+?)>/i", "$1", $body);

            // Escape special HTML characters.
            $escaped_body = htmlspecialchars($body, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

            if($escaped_body == ""){

                if(function_exists("iconv")){
                    // we are gonna guess and see if we get lucky
                    $escaped_body = iconv("ISO-8859-1", $PHORUM["DATA"]["HCHARSET"], $body);
                } else {
                    // we let htmlspecialchars use its defaults
                    $escaped_body = htmlspecialchars($body);
                }

            }

            $body = $escaped_body;

            // Replace newlines with $phorum_br temporarily.
            // This way the mods know what Phorum did vs the user.
            $body = str_replace("\n", "$phorum_br\n", $body);

            // Run bad word replacement code.
            if($bad_word_check) {
               $body = preg_replace($replace_words, $replace_vals, $body);
            }

            $data[$key]["body"] = $body;
        }

        // Work on the other fields ========================

        // Run bad word replacement code on subject and author.
        if($bad_word_check) {
            if (isset($message["subject"]))
                $data[$key]["subject"] = preg_replace($replace_words, $replace_vals, $data[$key]["subject"]);
            if (isset($message["author"]))
                $data[$key]["author"] = preg_replace($replace_words, $replace_vals, $data[$key]["author"]);
        }

        // Escape special HTML characters in fields.
        if (isset($message["email"]))
            $data[$key]["email"] = htmlspecialchars($data[$key]["email"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        if (isset($message["subject"]))
            $data[$key]["subject"] = htmlspecialchars($data[$key]["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

        // Do author formatting for all provided author fields.
        foreach ($author_specs as $spec)
        {
            // Use "Anonymous user" as the author name if there's no author
            // name available for some reason.
            if (!isset($message[$spec[1]]) || $message[$spec[1]] == '') {
                $data[$key][$spec[3]] = $PHORUM["DATA"]["LANG"]["AnonymousUser"];
            }
            // Author info for registered user.
            elseif (!empty($message[$spec[0]])) {
                $url = str_replace('%spec_data%',$message[$spec[0]],$profile_url_template);
                $data[$key]["URL"][$spec[4]] = $url;
                $data[$key][$spec[3]] =
                    (empty($PHORUM["custom_display_name"])
                     ? htmlspecialchars($message[$spec[1]], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                     : $message[$spec[1]]);
            }
            // For anonymous user which left an email address.
            // We only show the address if addresses aren't hidden globally,
            // if the active user is an administrator or if the active user
            // is a moderator with the PHORUM_MOD_EMAIL_VIEW constant enabled.
            elseif ( $spec[2] !== NULL && !empty($message[$spec[2]]) &&
                     (empty($PHORUM['hide_email_addr']) ||
                      !empty($PHORUM["user"]["admin"]) ||
                      (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES) && PHORUM_MOD_EMAIL_VIEW) ||
                      (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS) && PHORUM_MOD_EMAIL_VIEW)) ) {
                $data[$key][$spec[3]] = htmlspecialchars($message[$spec[1]], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
                $email_url = phorum_html_encode("mailto:".$message[$spec[2]]);
                $data[$key]["URL"]["PROFILE"] = $email_url;
            }
            // For anonymous user.
            else {
                $data[$key][$spec[3]] = htmlspecialchars($message[$spec[1]], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
        }
    }

    // A hook for module writers to apply custom message formatting.
    if (isset($PHORUM["hooks"]["format"]))
        $data = phorum_hook("format", $data);

    // A hook for module writers for doing post formatting fixups.
    if (isset($PHORUM["hooks"]["format_fixup"]))
        $data = phorum_hook("format_fixup", $data);

    // Clean up after the mods are done.
    foreach( $data as $key => $message ) {

        // Clean up line breaks inside pre and xmp tags. These tags
        // take care of showing newlines as breaks themselves.
        if (isset($message["body"])) {
            foreach (array("pre","goep","xmp") as $tagname) {
                if (preg_match_all( "/(<$tagname.*?>).+?(<\/$tagname>)/si", $message["body"], $matches)) {
                    foreach ($matches[0] as $match) {
                        $stripped = str_replace ($phorum_br, "", $match);
                        $message["body"] = str_replace ($match, $stripped, $message["body"]);
                    }
                }
            }
            // Remove line break after div, quote and code tags. These
            // tags have their own line break. Without this, there would
            // be to many white lines.
            $message["body"] = preg_replace(
                "/\s*(<\/?(?:div|xmp|blockquote|pre)[^>]*>)\s*\Q$phorum_br\E/",
                "$1", $message["body"]
            );

            // Normalize the Phorum line breaks that are left.
            $data[$key]["body"] = str_replace($phorum_br, "<br />", $message["body"]);
        }
    }

    return $data;
}

/**
 * Formats an epoch timestamp to a date/time for displaying on screen.
 *
 * @param picture - The time formatting to use, in strftime() format
 * @param ts - The epoch timestamp to format
 * @return datetime - The formatted date/time string
 */
function phorum_date( $picture, $ts )
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Setting locale.
    if (!isset($PHORUM['locale']))
        $PHORUM['locale']="EN";
    setlocale(LC_TIME, $PHORUM['locale']);

    // Format the date.
    if ($PHORUM["user_time_zone"] && isset($PHORUM["user"]["tz_offset"]) && $PHORUM["user"]["tz_offset"]!=-99) {
        $ts += $PHORUM["user"]["tz_offset"] * 3600;
        return gmstrftime( $picture, $ts );
    } else {
        $ts += $PHORUM["tz_offset"] * 3600;
        return strftime( $picture, $ts );
    }
}

/**
 * Formats an epoch timestamp to a relative time phrase
 *
 * @param ts - The epoch timestamp to format
 * @return phrase - The formatted phrase
 */
function phorum_relative_date($time)
{

    $PHORUM = $GLOBALS["PHORUM"];

    $today = strtotime(phorum_date('%Y-%m-%d', time()));

    $reldays = ($time - $today)/86400;

    if ($reldays >= 0 && $reldays < 1) {

        return $PHORUM["DATA"]["LANG"]["relative_today"];

    } else if ($reldays >= 1 && $reldays < 2) {

        return $PHORUM["DATA"]["LANG"]["relative_tomorrow"];

    } else if ($reldays >= -1 && $reldays < 0) {

        return $PHORUM["DATA"]["LANG"]["relative_yesterday"];
    }


    if (abs($reldays) < 30) {

        // less than a month

        $reldays = floor($reldays);

        if($reldays==1){
            $return = $PHORUM["DATA"]["LANG"]["relative_one_day"];
        } else {
            $return = abs($reldays)." ".$PHORUM["DATA"]["LANG"]["relative_days"];
        }

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } elseif (abs($reldays) < 60) {

        // weeks ago

        $relweeks = floor(abs($reldays/7));

        $return = $relweeks." ".$PHORUM["DATA"]["LANG"]["relative_weeks"];

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } elseif (abs($reldays) < 365) {

        // months ago

        $relmonths = floor(abs($reldays/30));

        $return = $relmonths." ".$PHORUM["DATA"]["LANG"]["relative_months"];

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } else {

        // years ago

        $relyears = floor(abs($reldays/365));

        if($relyears==1){
            $return = $PHORUM["DATA"]["LANG"]["relative_one_year"];
        } else {
            $return = $relyears." ".$PHORUM["DATA"]["LANG"]["relative_years"];
        }

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    }

}

/**
 * Strips HTML <tags> and BBcode [tags] from the body.
 *
 * @param body - The block of body text to strip
 * @return stripped - The stripped body
 */
function phorum_strip_body( $body, $strip_tags = true)
{
	if($strip_tags) {
	    // Strip HTML <tags>
	    $stripped = preg_replace("|</*[a-z][^>]*>|i", "", $body);
	    // Strip BB Code [tags]
	    $stripped = preg_replace("|\[/*[a-z][^\]]*\]|i", "", $stripped);
	} else {
		$stripped = $body;
	}


    // do badwords check
    // Prepare the bad-words replacement code.
    $bad_word_check= false;

    $banlists = NULL;
    if(!empty($PHORUM['cache_banlists']) && !empty($PHORUM['banlist_version'])){
        $cache_key = $PHORUM['forum_id'];
        $banlists=phorum_cache_get('banlist',$cache_key,$PHORUM['banlist_version']);
    }
    // not found or no caching enabled
    if($banlists === NULL ) {
        $banlists = phorum_db_get_banlists();

        if(!empty($PHORUM['cache_banlists']) && !empty($PHORUM['banlist_version'])) {
            phorum_cache_put('banlist',$cache_key,$banlists,7200,$PHORUM['banlist_version']);
        }
    }

    if (isset($banlists[PHORUM_BAD_WORDS]) && is_array($banlists[PHORUM_BAD_WORDS])) {
        $replace_vals  = array();
        $replace_words = array();
        foreach ($banlists[PHORUM_BAD_WORDS] as $item) {
            $replace_words[] = "/\b".preg_quote($item['string'],'/')."(ing|ed|s|er|es)*\b/i";
            $replace_vals[]  = PHORUM_BADWORD_REPLACE;
            $bad_word_check  = true;
        }
    }

    if ($bad_word_check) {
        $stripped = preg_replace($replace_words, $replace_vals, $stripped);
    }
    return $stripped;
}

/**
 * Formats a file size in bytes to a human readable format. Human
 * readable formats are MB (MegaByte), KB (KiloByte) and byte.
 *
 * @param bytes - The number of bytes
 * @param formatted - The formatted size
 */
function phorum_filesize( $bytes )
{
    if ($bytes >= 1024*1024) {
        return round($bytes/1024/1024, 2) . "&nbsp;MB";
    } elseif ($bytes >= 1024) {
        return round($bytes/1024, 1) . "&nbsp;KB";
    } else {
        return $bytes . ($bytes == 1 ? "&nbsp;byte" : "&nbsp;bytes");
    }
}

?>
