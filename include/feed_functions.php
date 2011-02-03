<?php

// functions to make the different feeds

function phorum_feed_make_rss($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    $buffer = "<?xml version=\"1.0\" encoding=\"{$PHORUM['DATA']['CHARSET']}\"?>\n";
    $buffer.= "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
    $buffer.= "    <channel>\n";
    $buffer.= "        <title>".htmlspecialchars($feed_title, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</title>\n";
    $buffer.= "        <description>".htmlspecialchars($feed_description, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</description>\n";
    $buffer.= "        <link>".htmlspecialchars($feed_url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</link>\n";
    $buffer.= "        <lastBuildDate>".htmlspecialchars(date("r"), ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</lastBuildDate>\n";
    $buffer.= "        <generator>".htmlspecialchars("Phorum ".PHORUM, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</generator>\n";

    // Lookup the plain text usernames for the authenticated authors.
    $users = $messages['users'];
    unset($messages['users']);
    unset($users[0]);
    $users = phorum_api_user_get_display_name($users, '', PHORUM_FLAG_PLAINTEXT);

    foreach($messages as $message) {

        $title = strip_tags($message["subject"]);
        if(empty($PHORUM["args"]["replies"])){
            switch($message["thread_count"]){
                case 1:
                    $title.= " (".$PHORUM["DATA"]["LANG"]["noreplies"].")";
                    break;
                case 2:
                    $title.= " (1 ".$PHORUM["DATA"]["LANG"]["reply"].")";
                    break;
                default:
                    $replies = $message["thread_count"] - 1;
                    $title.= " ($replies ".$PHORUM["DATA"]["LANG"]["replies"].")";
            }

            $date = date("r", $message["modifystamp"]);

        } else {

            $date = date("r", $message["datestamp"]);
        }

        $url = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"], $message["message_id"]);

        $category = $forums[$message["forum_id"]]["name"];

        $author = isset($users[$message['user_id']]) && $users[$message['user_id']] != '' ? $users[$message['user_id']] : $message['author'];

        $body = strtr($message['body'], "\001\002\003\004\005\006\007\010\013\014\016\017\020\021\022\023\024\025\026\027\030\031\032\033\034\035\036\037", "????????????????????????????");

        $buffer.= "        <item>\n";
        $buffer.= "            <guid>".htmlspecialchars($url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</guid>\n";
        $buffer.= "            <title>$title</title>\n";
        $buffer.= "            <link>".htmlspecialchars($url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</link>\n";
        $buffer.= "            <description><![CDATA[$body]]></description>\n";
        $buffer.= "            <dc:creator>".htmlspecialchars($author, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</dc:creator>\n";
        $buffer.= "            <category>".htmlspecialchars($category, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</category>\n";
        $buffer.= "            <pubDate>".htmlspecialchars($date, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</pubDate>\n";
        $buffer.= "        </item>\n";
    }

    $buffer.= "    </channel>\n";
    $buffer.= "</rss>\n";

    return $buffer;
}


function phorum_feed_make_atom($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    $self = $PHORUM["http_path"]."/feed.php?".$_SERVER["QUERY_STRING"];

    $buffer = "<?xml version=\"1.0\" encoding=\"{$PHORUM['DATA']['CHARSET']}\"?>\n";
    $buffer.= "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
    $buffer.= "    <title>".htmlspecialchars($feed_title, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</title>\n";
    $buffer.= "    <subtitle>".htmlspecialchars($feed_description, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</subtitle>\n";
    $buffer.= "    <link rel=\"self\" href=\"".htmlspecialchars($self, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\" />\n";
    $buffer.= "    <id>".htmlspecialchars($feed_url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</id>\n";
    $buffer.= "    <updated>".htmlspecialchars(date("c"), ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</updated>\n";
    $buffer.= "    <generator>".htmlspecialchars("Phorum ".PHORUM, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</generator>\n";

    // Lookup the plain text usernames for the authenticated authors.
    $users = $messages['users'];
    unset($messages['users']);
    unset($users[0]);
    $users = phorum_api_user_get_display_name($users, '', PHORUM_FLAG_PLAINTEXT);

    foreach($messages as $message) {

        $title = strip_tags($message["subject"]);
        if(empty($PHORUM["args"]["replies"])){
            switch($message["thread_count"]){
                case 1:
                    $title.= " (no ".$PHORUM["DATA"]["LANG"]["replies"].")";
                    break;
                case 2:
                    $title.= " (1 ".$PHORUM["DATA"]["LANG"]["reply"].")";
                    break;
                default:
                    $replies = $message["thread_count"] - 1;
                    $title.= " ($replies ".$PHORUM["DATA"]["LANG"]["replies"].")";
            }
        }

        $url = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"], $message["message_id"]);

        $category = $forums[$message["forum_id"]]["name"];

        $author = isset($users[$message['user_id']]) && $users[$message['user_id']] != '' ? $users[$message['user_id']] : $message['author'];

        $body = strtr($message['body'], "\001\002\003\004\005\006\007\010\013\014\016\017\020\021\022\023\024\025\026\027\030\031\032\033\034\035\036\037", "????????????????????????????");

        $buffer.= "    <entry>\n";
        $buffer.= "        <title type=\"html\">$title</title>\n";
        $buffer.= "        <link href=\"".htmlspecialchars($url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\" />\n";
        $buffer.= "        <category term=\"".htmlspecialchars($category, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\" />\n";
        $buffer.= "        <published>".date("c", $message["datestamp"])."</published>\n";
        $buffer.= "        <updated>".date("c", $message["modifystamp"])."</updated>\n";
        $buffer.= "        <id>".htmlspecialchars($url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</id>\n";
        $buffer.= "        <author>\n";
        $buffer.= "            <name>".htmlspecialchars($author, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</name>\n";
        $buffer.= "        </author>\n";
        $buffer.= "        <summary type=\"html\"><![CDATA[$body]]></summary>\n";
        $buffer.= "    </entry>\n";
    }

    $buffer.= "</feed>\n";

    return $buffer;

}


function phorum_feed_make_html($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    $buffer = "<div id=\"phorum_feed\">\n";
    $buffer.= "    <div id=\"phorum_feed_title\"><a href=\"".htmlspecialchars($feed_url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\" title=\"".htmlspecialchars($feed_description, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\">".htmlspecialchars($feed_title, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</div>\n";
    $buffer.= "    <div id=\"phorum_feed_date\">".htmlspecialchars(phorum_date($PHORUM['long_date'], time()), ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</lastBuildDate>\n";
    $buffer.= "    <ul>\n";

    unset($messages['users']);

    foreach($messages as $message) {

        $title = strip_tags($message["subject"]);

        if(empty($PHORUM["args"]["replies"])){

            switch($message["thread_count"]){
                case 1:
                    $title.= " (no ".$PHORUM["DATA"]["LANG"]["replies"].")";
                    break;
                case 2:
                    $title.= " (1 ".$PHORUM["DATA"]["LANG"]["reply"].")";
                    break;
                default:
                    $replies = $message["thread_count"] - 1;
                    $title.= " ($replies ".$PHORUM["DATA"]["LANG"]["replies"].")";
            }

        }

        $url = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"], $message["message_id"]);

        $body = phorum_strip_body($message["body"]);
        $body = substr($body, 0, 200);

        $buffer.= "        <li><a href=\"".htmlspecialchars($url, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\" title=\"".htmlspecialchars($message["body"], ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."\">".htmlspecialchars($title, ENT_COMPAT, $PHORUM['DATA']['HCHARSET'])."</a></li>\n";
    }

    $buffer.= "    </ul>\n";
    $buffer.= "</div>\n";

    return $buffer;
}


function phorum_feed_make_js($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    // build PHP array to later be turned into a JS object

    $feed["title"] = $feed_title;
    $feed["description"] = $feed_description;
    $feed["modified"] = phorum_date($PHORUM['short_date'], time());

    // Lookup the plain text usernames for the authenticated authors.
    $users = $messages['users'];
    unset($messages['users']);
    unset($users[0]);
    $users = phorum_api_user_get_display_name($users, '', PHORUM_FLAG_PLAINTEXT);

    foreach($messages as $message) {

        $author = isset($users[$message['user_id']]) && $users[$message['user_id']] != '' ? $users[$message['user_id']] : $message['author'];

        $item = array(

            "title" => strip_tags($message["subject"]),
            "author" => $author,
            "category" => $forums[$message["forum_id"]]["name"],
            "created" => phorum_date($PHORUM['short_date'], $message["datestamp"]),
            "modified" => phorum_date($PHORUM['short_date'], $message["modifystamp"]),
            "url" => phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"], $message["message_id"]),
            "description" => $message["body"]
        );

        if($message["thread_count"]){
            $replies = $message["thread_count"] - 1;
            $item["replies"] = $replies;
        }

        $feed["items"][] = $item;
    }

    // this is where we convert the array into js
    $buffer = phorum_array_to_javascript("phorum_feed", $feed);

    return $buffer;

}


// js helper functions

/****************************************************
 * phorum_array_to_javascript() support functions
 * Do not expect these functions to create complete javascript
 * code; use phorum_array_to_javascript() instead.
 *
 * phorum_conv_str_to_js() returns escaped string surrounded by single quotes
 * phorum_conv_array_to_js() returns nested arrays in javascript object shorthand
 *
 */
function phorum_conv_str_to_js($str, $raw = false) {
    $str = str_replace("\\", "\\\\", $str);
    $str = str_replace("'", "\\'", $str);
    $str = str_replace("\r\n", "\n", $str);
    $str = str_replace("\r", "\n", $str);
    $str = str_replace("\n", "\\n", $str);
    if (!$raw) {
        $str = "'$str'";
    }
    return $str;
}

function phorum_conv_array_to_js($array)
{
    $tmp = array();

    foreach($array as $vkey => $vval) {

        if (is_array($vval) || is_object($vval)) {
            $vval = phorum_conv_array_to_js($vval);

        } else if (is_string($vval)) {
            $vval = phorum_conv_str_to_js($vval);

        } else if (is_numeric($vval)) {
            $vval = $vval; // don't do anything, but we need to check it

        } else if (is_bool($vval)) {
            $vval = (($vval) ? "true" : "false");

        } else {
            $vval = "null";

        }

        if (preg_match("/^\w+\$/i", $vkey)) {
            $vkey = phorum_conv_str_to_js($vkey, true);
        } else {
            $vkey = phorum_conv_str_to_js($vkey);
        }
        $tmp[] = "$vkey:$vval";
    }

    return("{" . implode(", ", $tmp) . "}");
}

/****************************************************
 * string phorum_array_to_javascript(string $name, [mixed $var = null]);
 *
 * string $name : javascript variable name
 *   mixed $var : optional variable to convert
 *                if no variable given, $$name from the
 *                global symbol table will be used instead
 *
 * returns a string of javascript code
 *
 * Creating the nested shorthand javascript object syntax does
 * not allow us to use a recursive function here, the
 * phorum_conv_array_to_js() function serves that purpose for us.
 *
 */
function phorum_array_to_javascript($name, $var = null) {
    $buf = "";
    if ($var === null) $var = $GLOBALS[$name];

    // object or array
    if (is_array($var) || is_object($var)) {
        $buf .= "$name = {};\n";

        foreach($var as $key => $value) {
            $key = phorum_conv_str_to_js($key, true);

            if (is_array($value) || is_object($value)) {
                $buf .= "{$name}['$key'] = " . phorum_conv_array_to_js($value) . ";\n";

            } else if (is_string($value)) {
                $buf .= "{$name}['$key'] = " . phorum_conv_str_to_js($value) . ";\n";

            } else if (is_numeric($value)) {
                $buf .= "{$name}['$key'] = $value;\n";

            } else if (is_bool($value)) {
                $buf .= "{$name}['$key'] = " . (($value) ? "true" : "false") . ";\n";

            } else {
                $buf .= "{$name}['$key'] = null;\n";
            }
        }

    // string value
    } else if (is_string($var)) {
        $buf .= "$name = " . phorum_conv_str_to_js($var) . ";\n";

    // numeric
    } else if (is_numeric($var)) {
        $buf .= "$name = $var;\n";

    // boolean
    } else if (is_bool($var)) {
        $buf .= "$name = " . (($var) ? "true" : "false") . ";\n";

    } else {
        $buf .= "$name = null;\n";

    }

    return($buf);

}

?>
