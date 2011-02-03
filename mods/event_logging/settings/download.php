<?php

if (!defined("PHORUM_ADMIN")) return;

include_once("./include/format_functions.php");

$strings = $PHORUM["DATA"]["MOD_EVENT_LOGGING"];

// Merge $_GET with $_POST. We could use $_REQUEST, but that one might have
// magic quoting applied to it. This way, we have sane data.
foreach ($_GET as $k => $v) {
    if (!isset($_POST[$k])) $_POST[$k] = $v;
}

// Are we running in filter mode?
$filter_mode = defined("LOGVIEWER_FILTER_MODE") ||
               isset($_POST["filter_mode"]);

// Some defaults for the page.
$default_loglevel = EVENTLOG_LVL_DEBUG;

$show_loglevel = array();
$show_categories = array();

$filter = NULL;

if ($filter_mode)
{
    $filter = array();

    // What log levels to show?
    // No log level selected at all will show all log levels.
    $loglevels = NULL;
    if (count($_POST)) {
        if (isset($_POST["show_loglevel"])) {
            $show_loglevel = $_POST["show_loglevel"];
            $loglevels = array_keys($show_loglevel);
        } else {
            $show_loglevel = array();
        }
    }
    $filter["loglevels"] = $loglevels;

    // What categories to show?
    // No category selected at all will show all categories.
    $categories = NULL;
    if (count($_POST)) {
        if (isset($_POST["show_category"])) {
            $show_category = $_POST["show_category"];
            $categories = array_keys($show_category);
        } else {
            $show_categories = array();
        }
    }
    $filter["categories"] = $categories;

    foreach (array("source", "user_id", "username", "ip") as $fld) {
        if (isset($_POST[$fld])) {
            $filter[$fld] = $_POST[$fld];
        }
    }
}
// Retrieve the total number of event logs.
$logcount = event_logging_countlogs($filter);

// Retrieve event logs
$logs = event_logging_getlogs(1, $GLOBALS["PHORUM"]["mod_event_logging"]["max_log_entries"], $filter);

// ----------------------------------------------------------------------
// Display event logs.
// ----------------------------------------------------------------------

// Clear out the admin interface HTML and start with a fresh slate for the logs
ob_end_clean();
ob_start();

// Set the default content type and file name
$content_type = "text/html";
$file_name = "event_logs.html";

if (isset($_POST["download_type"]) && $_POST["download_type"] == "text") {
    $content_type = "text/plain";
    $file_name = "event_logs.txt";
}

header("Status: 200");

// HTTP Content-Type header with the charset from the default language
if (isset($PHORUM["DATA"]['CHARSET'])) {
    header("Content-Type: ".$content_type."; " .
           "charset=".htmlspecialchars($PHORUM["DATA"]['CHARSET']));
}
header("Content-Disposition: attachment; filename=\"".$file_name."\"");

if ($content_type == "text/html") {
?>
<html>
<head>
<title>Phorum Event Logs</title>
<?php

// meta data with the charset from the default language
if (isset($PHORUM["DATA"]['CHARSET'])) {
    echo "<meta content=\"text/html; charset=".$PHORUM["DATA"]["CHARSET"]."\" http-equiv=\"Content-Type\">\n";
}
?>
</head>
<body>
<?php
if ($logcount == 0) { ?>
    <div style="text-align: center;
                padding:20px;
                margin-top: 15px;
                font-weight: bold;
                background-color: #f0f0f0;
                border: 1px solid #999">
      No event logs found ...
    </div>
    <?php

    return;
}

?>
<script type="text/javascript">
//<![CDATA[
function toggle_detail_visibility(log_id)
{
    elt = document.getElementById("detail_" + log_id);
    if (elt) {
        if (elt.style.display == 'none') {
            elt.style.display = 'block';
        } else {
            elt.style.display = 'none';
        }
    }

    return false;
}

//]]>
</script>

<table style="width:100%;border-collapse:collapse" cellpadding="5">
 <tbody>
  <tr>
    <th>&nbsp;</th>
    <th align="left" style="white-space:nowrap">Date</th>
    <th align="left" style="white-space:nowrap">Time</th>
    <th align="left" style="white-space:nowrap">Source</th>
    <th align="left" style="white-space:nowrap">Category</th>
    <th align="left" style="width:100%">Message</th>
    <th>&nbsp;</th>
  </tr>
<?php

foreach ($logs as $loginfo)
{
    $u = $loginfo["user_id"] === NULL ? "Anonymous" : "User ID {$loginfo["user_id"]}";
    $icon  = $PHORUM["http_path"] . "/mods/event_logging/images/loglevels/" .
             $loginfo["loglevel"] . ".png";
    $title = $strings["LOGLEVELS"][$loginfo["loglevel"]];
    $cat   = $strings["CATEGORIES"][$loginfo["category"]];

    // Find detailed info.
    $details = NULL;
    if ($loginfo["details"] !== NULL) {
        $details = $loginfo["details"];
    }

    // Adding whitespace if the message contains ridiculously long words,
    // so the interface layout won't be wrecked.
    $message = $loginfo["message"];
    while (preg_match('/(\S{40})\S/', $message, $m)) {
        $message = str_replace($m[1], $m[1] . " ", $message);
    }

    // If the event log is linked to a forum message, the generate
    // the URL that points to that message.
    $message_url = NULL;
    if ($loginfo["message_id"] !== NULL && $loginfo["message_id"] > 0) {
        $message_url = phorum_get_url(
            PHORUM_FOREIGN_READ_URL,
            $loginfo["forum_id"],
            $loginfo["thread_id"],
            $loginfo["message_id"]
        );
    }

    if (!isset($PHORUM['short_time'])) {
        $f = str_replace($PHORUM['short_date'],'',$PHORUM['short_date_time']);
        $f = preg_replace('/^\s+|\s+$/', '', $f);
        $PHORUM['short_time'] = $f;
    }

    print '
      <tr>
        <td valign="middle" style="white-space:nowrap">
          <img alt="'.$title.'" title="'.$title.'" src="'.$icon.'"/>
        </td>
        <td valign="left" style="white-space:nowrap; font-size: 10px">'.
          phorum_date($PHORUM['short_date'], $loginfo["datestamp"]).
       '</td>
        <td valign="left" style="white-space:nowrap; font-size: 10px">'.
          phorum_date($PHORUM['short_time'], $loginfo["datestamp"]).
       '</td>
        <td valign="middle" style="white-space:nowrap; font-size: 10px">
          '.htmlspecialchars($loginfo["source"]).'
        </td>
        <td valign="middle" style="font-size: 10px">
          '.$cat.'
        </td>
        <td valign="middle" style="font-size: 12px">'.
          htmlspecialchars($message).
       '</td>
        <td valign="middle">
          <a href="#" onclick="return toggle_detail_visibility('.$loginfo["log_id"].')"><small>details</small></a>
        </td>
      </tr>
      <tr>
        <td style="border-bottom: 1px solid #888"></td>
        <td colspan="6" style="border-bottom: 1px solid #888">
          <div style="display:none;overflow:auto;border:1px solid #aaa; padding:10px; margin-bottom: 10px" id="detail_'.$loginfo["log_id"].'">

            <b>User info:</b><br/><br/>' .

            ($loginfo["user_id"]
             ? "User ID = {$loginfo["user_id"]}" .
                ($loginfo["username"] !== NULL
                 ? ', username = ' . htmlspecialchars($loginfo["username"])
                 : '')
             : "Anonymous user") . '<br/>' .
            'User IP address = '. $loginfo["ip"] .
            ($loginfo["hostname"] !== NULL
             ? ', hostname = ' . htmlspecialchars($loginfo["hostname"])
             : '') . '<br/>' .

            ($message_url !== NULL
             ? '<br/><b>Related message:</b><br/>
                Forum = '.$loginfo["forum"].'<br/>
                Message ID = '.$loginfo["message_id"].'<br/>
                [&nbsp;<a target="_new" href="'.htmlspecialchars($message_url).'">view message</a>&nbsp;]<br/>'
             : '') .

            ($details !== NULL
             ? '<br/><b>Additional details:</b><br/><br/>' .
               nl2br(htmlspecialchars($details)) . '<br/>'
             : '') .
            '<br/>
          </div>
        </td>
      </tr>';
}

?>
 </tbody>
</table>
</body>
</html>
<?php
} else if ($content_type == "text/plain") {
    if ($logcount == 0) {
        print "No event logs found ...";
    } else {
        foreach ($logs as $loginfo)
        {
            $u = $loginfo["user_id"] === NULL ? "Anonymous" : "User ID {$loginfo["user_id"]}";
            $icon  = $PHORUM["http_path"] . "/mods/event_logging/images/loglevels/" .
                     $loginfo["loglevel"] . ".png";
            $title = $strings["LOGLEVELS"][$loginfo["loglevel"]];
            $cat   = $strings["CATEGORIES"][$loginfo["category"]];
        
            // Find detailed info.
            $details = NULL;
            if ($loginfo["details"] !== NULL) {
                $details = $loginfo["details"];
            }
        
            // Adding whitespace if the message contains ridiculously long words,
            // so the interface layout won't be wrecked.
            $message = $loginfo["message"];
            while (preg_match('/(\S{40})\S/', $message, $m)) {
                $message = str_replace($m[1], $m[1] . " ", $message);
            }
        
            // If the event log is linked to a forum message, the generate
            // the URL that points to that message.
            $message_url = NULL;
            if ($loginfo["message_id"] !== NULL && $loginfo["message_id"] > 0) {
                $message_url = phorum_get_url(
                    PHORUM_FOREIGN_READ_URL,
                    $loginfo["forum_id"],
                    $loginfo["thread_id"],
                    $loginfo["message_id"]
                );
            }
        
            if (!isset($PHORUM['short_time'])) {
                $f = str_replace($PHORUM['short_date'],'',$PHORUM['short_date_time']);
                $f = preg_replace('/^\s+|\s+$/', '', $f);
                $PHORUM['short_time'] = $f;
            }
        
            print "==========================\n".
                  "Log level: $title\n".
                  "Date: ".phorum_date($PHORUM['short_date'], $loginfo["datestamp"])."\n".
                  "Time: ".phorum_date($PHORUM['short_time'], $loginfo["datestamp"])."\n".
                  "Source: ".$loginfo["source"]."\n".
                  "Category: ".$cat."\n".
                  "Message: ".$message."\n".
                  "User info: ".
                    ($loginfo["user_id"]
                     ? "User ID = ".$loginfo["user_id"].
                        ($loginfo["username"] !== NULL
                         ? ', username = ' . $loginfo["username"]
                         : '')
                     : "Anonymous user") . ", " .
                    "User IP address = ". $loginfo["ip"] .
                    ($loginfo["hostname"] !== NULL
                     ? ", hostname = " . $loginfo["hostname"]
                     : "") . "\n" .
        
                    ($message_url !== NULL
                     ? "Related message: Forum = ".$loginfo["forum"].", Message ID = ".$loginfo["message_id"].
                        ", URL = ".$message_url."\n"
                     : "") .
        
                    ($details !== NULL
                     ? "Additional details:\n" .
                       $details
                     : "") .
                    "\n\n";
        }
    }
}
ob_end_flush();
exit();
?>
