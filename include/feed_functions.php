<?php

// functions to make the different feeds

function phorum_feed_make_rss($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    $buffer = "<?xml version=\"1.0\" encoding=\"{$PHORUM['DATA']['CHARSET']}\"?>\n";
    $buffer.= "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
    $buffer.= "    <channel>\n";
    $buffer.= "        <title>".htmlspecialchars($feed_title)."</title>\n";
    $buffer.= "        <description>".htmlspecialchars($feed_description)."</description>\n";
    $buffer.= "        <link>".htmlspecialchars($feed_url)."</link>\n";
    $buffer.= "        <lastBuildDate>".htmlspecialchars(date("r"))."</lastBuildDate>\n";
    $buffer.= "        <generator>".htmlspecialchars("Phorum ".PHORUM)."</generator>\n";

    foreach($messages as $message) {

        $title = $message["subject"];
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

            $date = date("r", $message["modifystamp"]);

        } else {

            $date = date("r", $message["datestamp"]);
        }

        $url = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"], $message["message_id"]);

        $category = $forums[$message["forum_id"]]["name"];

        $buffer.= "        <item>\n";
        $buffer.= "            <guid>".htmlspecialchars($url)."</guid>\n";
        $buffer.= "            <title>".htmlspecialchars($title)."</title>\n";
        $buffer.= "            <link>".htmlspecialchars($url)."</link>\n";
        $buffer.= "            <description><![CDATA[".$message["body"]."]]></description>\n";
        $buffer.= "            <dc:creator>".htmlspecialchars($message["author"])."</dc:creator>\n";
        $buffer.= "            <category>".htmlspecialchars($category)."</category>\n";
        $buffer.= "            <pubDate>".htmlspecialchars($date)."</pubDate>\n";
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
    $buffer.= "    <title>".htmlspecialchars($feed_title)."</title>\n";
    $buffer.= "    <subtitle>".htmlspecialchars($feed_description)."</subtitle>\n";
    $buffer.= "    <link rel=\"self\" href=\"".htmlspecialchars($self)."\" />\n";
    $buffer.= "    <id>".htmlspecialchars($feed_url)."</id>\n";
    $buffer.= "    <updated>".htmlspecialchars(date("c"))."</updated>\n";
    $buffer.= "    <generator>".htmlspecialchars("Phorum ".PHORUM)."</generator>\n";

    foreach($messages as $message) {

        if($message["thread_count"]<1) continue;

        $title = $message["subject"];
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

        $buffer.= "    <entry>\n";
        $buffer.= "        <title>".htmlspecialchars($title)."</title>\n";
        $buffer.= "        <link href=\"".htmlspecialchars($url)."\" />\n";
        $buffer.= "        <category term=\"".htmlspecialchars($category)."\" />\n";
        $buffer.= "        <published>".htmlspecialchars(date("c", $message["datestamp"]))."</published>\n";
        $buffer.= "        <updated>".htmlspecialchars(date("c", $message["modifystamp"]))."</updated>\n";
        $buffer.= "        <id>".htmlspecialchars($url)."</id>\n";
        $buffer.= "        <author>\n";
        $buffer.= "            <name>".htmlspecialchars($message["author"])."</name>\n";
        $buffer.= "        </author>\n";
        $buffer.= "        <summary type=\"html\"><![CDATA[".$message["body"]."]]></summary>\n";
        $buffer.= "    </entry>\n";
    }

    $buffer.= "</feed>\n";

    return $buffer;

}


function phorum_feed_make_html($messages, $forums, $feed_url, $feed_title, $feed_description) {

    $PHORUM = $GLOBALS["PHORUM"];

    $buffer = "<div id=\"phorum_feed\">\n";
    $buffer.= "    <div id=\"phorum_feed_title\"><a href=\"".htmlspecialchars($feed_url)."\" title=\"".htmlspecialchars($feed_description)."\">".htmlspecialchars($feed_title)."</div>\n";
    $buffer.= "    <div id=\"phorum_feed_date\">".htmlspecialchars(phorum_date($PHORUM['long_date'], time()))."</lastBuildDate>\n";
    $buffer.= "    <ul>\n";

    foreach($messages as $message) {

        $title = $message["subject"];

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

        $buffer.= "        <li><a href=\"".htmlspecialchars($url)."\" title=\"".htmlspecialchars($message["body"])."\">".htmlspecialchars($title)."</a></li>\n";
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

    foreach($messages as $message) {

        $item = array(

            "title" => $message["subject"],
            "author" => $message["author"],
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
