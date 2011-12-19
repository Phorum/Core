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
$default_pagelength = 20;
$default_loglevel   = EVENTLOG_LVL_DEBUG;

// The available page lengths.
$pagelengths = array(
    10   =>  "10 events per page",
    20   =>  "20 events per page",
    50   =>  "50 events per page",
    100  => "100 events per page",
    250  => "250 events per page",
);

// ----------------------------------------------------------------------
// Build event log filter.
// ----------------------------------------------------------------------

// The base URL for creating URL's to the filter page. This will be used
// later on, for making parts of the output clickable for adjusting the filter.
$filter_base = phorum_admin_build_url(array(
    'module=modsettings',
    'mod=event_logging',
    'el_action=filter'
)); 

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
            foreach ($loglevels as $l) {
                $filter_base .= '&show_loglevel['.urlencode($l).']';
            }
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
            foreach ($categories as $c) {
                $filter_base .= '&show_category['.urlencode($c).']';
            }
        } else {
            $show_categories = array();
        }
    }
    $filter["categories"] = $categories;

    foreach (array("source", "user_id", "username", "ip", "message", "details") as $fld) {
        if (isset($_POST[$fld])) {
            $filter[$fld] = $_POST[$fld];
            $filter_base .= "&$fld=".urlencode($_POST[$fld]);
        }
    }
}

// ----------------------------------------------------------------------
// Handle delete actions.
// ----------------------------------------------------------------------

// Clear logs requested?
if (isset($_POST["clear"]))
{
    if ($filter_mode) {
        event_logging_clearlogs($filter);
        phorum_admin_okmsg("The filtered event logs have been deleted.");
    } else {
        event_logging_clearlogs();
        phorum_admin_okmsg("All event logs have been deleted.");
    }
}

// ----------------------------------------------------------------------
// Collect data to display.
// ----------------------------------------------------------------------

// Which page to show?
if (isset($_POST["prevpage"])) {
    $page = (int)$_POST["curpage"] - 1;
} elseif (isset($_POST["nextpage"])) {
    $page = (int)$_POST["curpage"] + 1;
} else {
    $page = isset($_POST["page"]) ? (int)$_POST["page"] : 1;
}

// What page length to use?
$pagelength = isset($_POST["pagelength"])
           ? (int)$_POST["pagelength"]
           : $default_pagelength;
if (!isset($pagelengths[$pagelength])) $pagelength = $default_pagelength;
$filter_base .= '&pagelength=' . $pagelength;

// Retrieve the total number of event logs.
$logcount = event_logging_countlogs($filter);

// Compute the number of pages.
$pages = ceil($logcount / $pagelength);
if ($pages <= 0) $pages = 1;

// Create a page list for a drop down menu.
$pagelist = array();
for($p=1; $p<=$pages; $p++) {
    $pagelist[$p] = $p;
}

// Keep the current page within bounds.
if ($page <= 0) $page = 1;
if ($page > $pages) $page = $pages;
$filter_base .= '&page=' . $page;

// Retrieve event logs for the active page.
$logs = event_logging_getlogs($page, $pagelength, $filter);

// ----------------------------------------------------------------------
// Display header form for paging and filtering.
// ----------------------------------------------------------------------

include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", $filter_mode ? "Apply filter" : "Refresh page");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "event_logging");
$frm->hidden("curpage", $page);
$frm->hidden("el_action", $filter_mode ? "filter" : "logviewer");

$frm->addrow(
    "<span style=\"float:right;margin-right:10px\">" .
         $frm->select_tag("pagelength", $pagelengths, $pagelength,
                          'onchange="this.form.submit()"') .
        "&nbsp;&nbsp;&nbsp
         <input type=\"submit\" name=\"prevpage\" value=\"&lt;&lt;\"/>
         page " . $frm->select_tag("page", $pagelist, $page,
                  'onchange="this.form.submit()"') . " of $pages
         <input type=\"submit\" name=\"nextpage\" value=\"&gt;&gt;\"/>
     </span>Number of entries: $logcount"
);

if ($filter_mode)
{
    $frm->hidden("filter_mode", 1);

    $loglevel_checkboxes = '';
    foreach ($strings["LOGLEVELS"] as $l => $s) {
        $loglevel_checkboxes .= '<span style="white-space: nowrap">' . $frm->checkbox("show_loglevel[$l]", "1", "", isset($show_loglevel[$l])?1:0, "id=\"llcb_$l\"") . ' <label for="llcb_'.$l.'"><img align="absmiddle" src="'.$PHORUM["http_path"].'/mods/event_logging/images/loglevels/'.$l.'.png"/> ' . $s . '</label></span> ';
    }
    $row = $frm->addrow("Log levels to display", $loglevel_checkboxes);
    $frm->addhelp($row, "Log levels to display", "By using these checkboxes, you can limit the log levels that are displayed. If you do not check any of them, then no filtering will be applied and all log levels will be displayed.");

    $category_checkboxes = '';
    foreach ($strings["CATEGORIES"] as $l => $s) {
        $category_checkboxes .= '<span style="white-space: nowrap">' . $frm->checkbox("show_category[$l]", "1", "", isset($show_category[$l])?1:0, "id=\"cacb_$l\"") . ' <label for="cacb_'.$l.'">' . $s . '</label></span> ';
    }
    $row = $frm->addrow("Categories to display", $category_checkboxes);
    $frm->addhelp($row, "Categories to display", "By using these checkboxes, you can limit the log categories that are displayed. If you do not check any of them, then no filtering will be applied and all log categories will be displayed.");

    $sources = array("" => "");
    $sources = array_merge($sources, event_logging_getsources());
    $row = $frm->addrow("Source to display", $frm->select_tag("source", $sources, isset($_POST["source"]) ? $_POST["source"] : ""));

    $row = $frm->addrow("Filter by user", "User ID " . $frm->text_box("user_id", isset($_POST["user_id"]) ? $_POST["user_id"] : "", 10) . " Username " .  $frm->text_box("username", isset($_POST["username"]) ? $_POST["username"] : "", 20));
    $frm->addhelp($row, "Filter by user", "Using these fields, you can specify for what user you want to display the event logs.<br/><br/>The User ID must be the exact numeric id for the user.<br/><br/>In the username field, you can use the \"*\" wildcard (e.g. searching for \"john*\" would find both the users \"johndoe\" and \"johnny\").");

    $row = $frm->addrow("Filter by IP address", $frm->text_box("ip", isset($_POST["ip"]) ? $_POST["ip"] : "", 20));
    $frm->addhelp($row, "Filter by IP address", "Using this field, you can specify for what IP address you want to display the event logs.<br/><br/>In the field, you can use the \"*\" wildcard (e.g. searching for \"172.16.12.*\" will find both the IP addresses \"172.16.12.1\" and \"172.16.12.211\").");

    $row = $frm->addrow("Filter by message", $frm->text_box("message", isset($_POST["message"]) ? htmlspecialchars($_POST["message"]) : "", 40));
    $frm->addhelp($row, "Filter by message", "Filter all events where the message matches the specified text.<br/><br/>In the field, you can use the \"*\" wildcard (e.g. searching for \"fail*\" would find both entries with \"failure\" and \"failacity\").");

    $row = $frm->addrow("Filter by details", $frm->text_box("details", isset($_POST["details"]) ? htmlspecialchars($_POST["details"]) : "", 40));
    $frm->addhelp($row, "Filter by details", "Filter all events where the details match the specified text .<br/><br/>In the field, you can use the \"*\" wildcard (e.g. searching for \"fail*\" would find both entries with \"failure\" and \"failacity\").");

}

$frm->show();

// Use some javascript to create an additional submit button,
// which can be used for clearing logs from the database.
?>
<script type="text/javascript">
var buttons = document.getElementsByTagName('input');
for (var i = 0; i < buttons.length; i++) {
    if (buttons[i].type == 'submit' && (
          buttons[i].value == 'Refresh page' ||
          buttons[i].value == 'Apply filter'
        )) {
        var container = buttons[i].parentNode;

        var filter_mode = buttons[i].value == 'Apply filter';

        var newbutton   = document.createElement('input');
        newbutton.name  = 'clear';
        newbutton.type  = 'submit';
        newbutton.value = filter_mode
                        ? 'Delete event logs based on filter'
                        : 'Delete all event logs';
        newbutton.style.marginLeft = '5px';
        newbutton.onclick = function() {
         return confirm(
          filter_mode
          ? 'This will delete the event logs that match the filter. Continue?'
          : 'This will delete all event logs. Continue?'
         );
        };

        container.appendChild(newbutton);

        var newdropdown = document.createElement('select');
        newdropdown.name = 'download';
        newdropdown.onchange = function () {
          if (this.selectedIndex > 0) {
            document.forms[1].el_action.value = "download";
            var newhidden = document.createElement('input');
            newhidden.type = "hidden";
            newhidden.name = "download_type";
            newhidden.value = this.options[this.selectedIndex].value;
            container.appendChild(newhidden);
            document.forms[1].submit();
            this.selectedIndex = 0;
            document.forms[1].el_action.value = "filter";
          }
        }
        newdropdown.style.marginLeft = '5px';
        var newdropdown_opthead = document.createElement('option');
        newdropdown_opthead.appendChild(document.createTextNode("Download events as:"));
        var newdropdown_op1 = document.createElement('option');
        newdropdown_op1.value = 'html';
        newdropdown_op1.appendChild(document.createTextNode("HTML"));
        var newdropdown_op2 = document.createElement('option');
        newdropdown_op2.value = 'text';
        newdropdown_op2.appendChild(document.createTextNode("Text"));
        newdropdown.appendChild(newdropdown_opthead);
        newdropdown.appendChild(newdropdown_op1);
        newdropdown.appendChild(newdropdown_op2);
        container.appendChild(newdropdown);        

        break;
    }
}
</script>
<?php

// ----------------------------------------------------------------------
// Display event logs.
// ----------------------------------------------------------------------

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
          <a title="Extend filter using this source" href="'.htmlspecialchars($filter_base.'&source='.urlencode($loginfo["source"])).'">'.htmlspecialchars($loginfo["source"]).'</a>
        </td>
        <td valign="middle" style="font-size: 10px">
          <a title="Extend filter using this category" href="'.htmlspecialchars($filter_base.'&show_category['.urlencode($loginfo["category"]).']=1').'">'.$cat.'</a>
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
             ? "User ID = <a title=\"Extend filter using this User ID\" href=\"".htmlspecialchars("$filter_base&user_id=".urlencode($loginfo["user_id"]))."\">{$loginfo["user_id"]}</a>" .
                ($loginfo["username"] !== NULL
                 ? ', username = ' . htmlspecialchars($loginfo["username"])
                 : '') .
                '&nbsp;[&nbsp;<a target="_new" href="'.phorum_get_url(PHORUM_PROFILE_URL, $loginfo["user_id"]).'">view user\'s profile</a>&nbsp]'
             : "Anonymous user") . '<br/>' .
            'User IP address = <a title="Extend filter using this IP address" href="'.htmlspecialchars($filter_base.'&ip='.urlencode($loginfo["ip"])).'">'. $loginfo["ip"] . '</a>' .
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

