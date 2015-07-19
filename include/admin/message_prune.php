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
//                                                                            //
//   Author: Maurice Makaay <maurice@phorum.org>                              //
//   Initial development of this message pruning interface was                //
//   generously sponsored by Juan Antonio Ruiz Zwollo.                        //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

define("ADMIN_MODULE", "message_prune");

require_once("./include/format_functions.php");

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

// ----------------------------------------------------------------------
// Possible filter rules description
// ----------------------------------------------------------------------

// Build the match list for the forums.
$forum_info=phorum_get_forum_info(2);
$forum_matches = array();
foreach ($forum_info as $id => $name) {
    $forum_matches[htmlspecialchars($name)] = "message.forum_id = $id";
}

$ruledefs = array
(
    "body" => array(
        "label"         => "Message body",
        "matches"       => array(
            "contains"            => "message.body  = *QUERY*",
            "does not contain"    => "message.body != *QUERY*",
        ),
        "queryfield"    => "string"
    ),

    "subject" => array(
        "label"         => "Message subject",
        "matches"       => array(
            "is"                  => "message.subject  =  QUERY",
            "is not"              => "message.subject !=  QUERY",
            "contains"            => "message.subject  = *QUERY*",
            "does not contain"    => "message.subject != *QUERY*",
        ),
        "queryfield"    => "string"
    ),

    "date" => array(
        "label"         => "Message date",
        "matches"       => array(
            "posted on"           => "function:prepare_filter_date",
            "posted on or before" => "function:prepare_filter_date",
            "posted before"       => "function:prepare_filter_date",
            "posted after"        => "function:prepare_filter_date",
            "posted on or after"  => "function:prepare_filter_date",
        ),
        "prepare_filter_date" => "message.datestamp",
        "queryfield"    => "date"
    ),

    "status" => array(
        "label"         => "Message status",
        "matches"       => array(
            "approved"
                              => "message.status = ".PHORUM_STATUS_APPROVED,
            "waiting for approval (on hold)"
                              => "message.status = ".PHORUM_STATUS_HOLD,
            "disapproved by moderator"
                              => "message.status = ".PHORUM_STATUS_HIDDEN,
            "hidden (on hold or disapproved)"
                              => "message.status != ".PHORUM_STATUS_APPROVED,
        ),
    ),

    "messagetype" => array(
        "label"         => "Message type",
        "matches"       => array(
            "thread starting messages"
                                  => "message.parent_id  = 0",
            "reply messages"      => "message.parent_id != 0",
        ),
    ),

    "forum" => array(
        "label"        => "Forum",
        "matches"      => $forum_matches,
    ),

    "author" => array(
        "label"         => "Author name",
        "matches"       => array(
            "is"                  => "message.author  =  QUERY",
            "is not"              => "message.author !=  QUERY",
            "contains"            => "message.author  = *QUERY*",
            "does not contain"    => "message.author != *QUERY*",
            "starts with"         => "message.author  =  QUERY*",
            "does not start with" => "message.author !=  QUERY*",
            "ends with"           => "message.author  = *QUERY",
            "does not end with"   => "message.author !=  QUERY*",
        ),
        "queryfield"    => "string"
    ),

    "username" => array(
        "label"         => "Author username",
        "matches"       => array(
            "is"                  => "user.username  =  QUERY",
            "is not"              => "user.username !=  QUERY",
            "contains"            => "user.username  = *QUERY*",
            "does not contain"    => "user.username != *QUERY*",
            "starts with"         => "user.username  =  QUERY*",
            "does not start with" => "user.username !=  QUERY*",
            "ends with"           => "user.username  = *QUERY",
            "does not end with"   => "user.username != *QUERY",
        ),
        "queryfield"    => "string"
    ),

    "user_id" => array(
        "label"         => "Author user id",
        "matches"       => array(
            "is"                  => "message.user_id  = QUERY",
            "is not"              => "message.user_id != QUERY",
        ),
        "queryfield"    => "string"
    ),

    "authortype" => array(
        "label"         => "Author type",
        "matches"       => array(
            "registered user"     => "message.user_id != 0",
            "anonymous user"      => "message.user_id  = 0",
            "moderator"           => "message.moderator_post = 1",
            "administrator"       => "user.admin = 1",
            "active user"         => "user.active = " . PHORUM_USER_ACTIVE,
            "deactivated user"    => "user.active = " . PHORUM_USER_INACTIVE,
        )
    ),

    "ipaddress" => array(
        "label"         => "Author IP/hostname",
        "matches"       => array(
            "is"                  => "message.ip  =  QUERY",
            "is not"              => "message.ip !=  QUERY",
            "starts with"         => "message.ip  =  QUERY*",
            "does not start with" => "message.ip !=  QUERY*",
            "ends with"           => "message.ip  = *QUERY",
            "does not end with"   => "message.ip != *QUERY",
        ),
        "queryfield"    => "string"
    ),

    "threadstate" => array(
        "label"         => "Thread status",
        "matches"       => array(
            "open for posting"    => "thread.closed = 0",
            "closed for posting"  => "thread.closed = 1",
        ),
    ),

    "threadlastpost" => array(
        "label"         => "Thread last post",
        "matches"       => array(
            "posted on or before" => "function:prepare_filter_date",
            "posted before"       => "function:prepare_filter_date",
            "posted after"        => "function:prepare_filter_date",
            "posted on or after"  => "function:prepare_filter_date",
        ),
        "prepare_filter_date" => "thread.modifystamp",
        "queryfield"    => "date",
    ),
);

// ----------------------------------------------------------------------
// Handle a posted form
// ----------------------------------------------------------------------

$messages   = null;        // selected messages (based on a filter)
$filters    = array();     // active filters
$filtermode = "and";       // active filter mode (and / or)

$read_url_template = phorum_get_url(
    PHORUM_FOREIGN_READ_URL, '%forum_id%', '%thread_id%','%message_id%');

// If there are messages to delete in the post data, then delete them
// from the database.
$delete_count = 0;
if (isset($_POST["deletemessage"]) && is_array($_POST["deletemessage"]))
{
    $msgids = array_keys($_POST["deletemessage"]);
    $msgs = phorum_db_get_message($msgids, "message_id", true);
    $deleted_messages = array();

    foreach ($msgs as $msg)
    {
        // if the message was already deleted, skip it
        if(isset($delete_messages[$msg["message_id"]])) continue;

        $PHORUM["forum_id"] = $msg["forum_id"];

        $delmode = $msg["parent_id"] == 0
                 ? PHORUM_DELETE_TREE
                 : PHORUM_DELETE_MESSAGE;

        // A hook to allow modules to implement extra or different
        // delete functionality.
        list($handled, $delids, $msgid, $msg, $delmode) = phorum_hook(
            "before_delete",
            array(false, 0, $msg["message_id"], $msg, $delmode)
        );

        // If the "before_delete" hook did not handle the delete action,
        // then we have to handle it here ourselves.
        if (! $handled)
        {
            // Delete the message or thread.
            $delids = phorum_db_delete_message($msg["message_id"], $delmode);

            // Cleanup the attachments for all deleted messages.
            foreach ($delids as $delid) {
                $files = phorum_db_get_message_file_list($delid);
                foreach($files as $file_id=>$data) {
                    phorum_api_file_delete($file_id);
                }
            }

            // For deleted threads, check if we have move notifications
            // to delete. We unset the forum id, so phorum_db_get_messages()
            // will return messages with the same thread id in
            // other forums as well (those are the move notifications).
            if ($delmode == PHORUM_DELETE_TREE) {
                $forum_id = $PHORUM["forum_id"];
                $PHORUM["forum_id"] = 0;
                $moved = phorum_db_get_messages($msg["message_id"]);
                $PHORUM["forum_id"] = $forum_id;
                foreach ($moved as $id => $data) {
                    if (!empty($data["moved"])) {
                        phorum_db_delete_message($id, PHORUM_DELETE_MESSAGE);
                    }
                }
            }
        }

        // Run a hook for performing custom actions after cleanup.
        phorum_hook("delete", $delids);

        // Keep track of deleted messages ids for counting the deleted
        // messages at the end. We can't simply add the number of messages
        // in the message array, because there might be overlap between
        // messages and threads here.
        foreach ($delids as $id) {
            $delete_messages[$id] = 1;
        }
    }

    $delete_count = count($delete_messages);
    phorum_admin_okmsg("Deleted $delete_count message(s) from the database.");
}

// If a filterdesc field is in the post data, then query the database
// based on this filterdesc. The results will be shown later on,
// below the filter form.
if (isset($_POST["filterdesc"]))
{
    // The filter rules are separated by "&" or "|" based on
    // respectively an "AND" or an "OR" query.
    $split = preg_split(
        '/([&|])/',
        $_POST["filterdesc"],
        -1, PREG_SPLIT_DELIM_CAPTURE
    );

    // The $split array should now contain an alternating list of
    // rules and AND/OR specifications. Walk over the list and
    // try to construct a metaquery to find messages based on
    // this filter.

    $meta = array();

    foreach ($split as $index => $spec)
    {
        // Even indexes contain a rule.
        if ($index % 2 == 0) {
            if (preg_match('/^(.*),(.*),(.*)$/', $spec, $m)) {
                $field = rawurldecode($m[1]);
                $match = rawurldecode($m[2]);
                $query = rawurldecode($m[3]);
                if (isset($ruledefs[$field]) &&
                    isset($ruledefs[$field]["matches"][$match]))
                {
                    $condition = $ruledefs[$field]["matches"][$match];

                    // Use a custom function for filling the metaquery.
                    if (substr($condition, 0, 9) == "function:"){
                        $func = substr($condition, 9);
                        if (!function_exists($func)) {
                            trigger_error(
                                "Internal error: filter function \"" .
                                htmlspecialchars($func) . "\" from the match ".
                                "specification for \"" .
                                htmlspecialchars($field) . "/" .
                                htmlspecialchars($match) .
                                "\" does not exist.", E_USER_ERROR);
                        } else {
                            $meta = call_user_func($func,$meta,$field,$match,$query);
                        }
                    }
                    // Standard metaquery addition.
                    else {
                        $meta[] = array(
                            "condition" => $condition,
                            "query"     => $query
                        );
                    }

                    // For rebuilding the filter form.
                    $filter = array($field, $match, $query);
                    $filters[] = $filter;

                    continue;
                }
            }
        }
        // Uneven indexes contain the AND/OR spec.
        else {
            if     ($spec == '&') {$meta[]="AND"; $filtermode="and"; continue;}
            elseif ($spec == '|') {$meta[]="OR" ; $filtermode="or" ; continue;}
        }

        trigger_error(
            'Internal error: illegal filter specification (' .
            'unexpected token "'.htmlspecialchars($spec).'")',
            E_USER_ERROR
        );
    }

    // Let the database layer turn the metaquery into a real query
    // and run it against the database.
    $messages = phorum_db_metaquery_messagesearch($meta);

    if ($messages === NULL) {
        phorum_admin_error("Internal error: failed to run a message search");
    }
}

// Custom filter preparation for the "date" filter.
function prepare_filter_date($meta, $field, $match, $query)
{
    $start_of_day = null;
    $end_of_day = null;

    global $ruledefs;
    if (!$ruledefs[$field] || !isset($ruledefs[$field]["prepare_filter_date"])){
        trigger_error(
            "Internal error: no date field configure in rule defs for field " .
            '"' . htmlspecialchars($field) . '"', E_USER_ERROR
        );
    }
    $dbfield = $ruledefs[$field]["prepare_filter_date"];

    $query = trim($query);
    if (preg_match('/^(\d\d\d\d)\D(\d\d?)\D(\d\d?)$/', $query, $m)) {
        $dy = $m[1]; $dm = $m[2]; $dd = $m[3];
        if ($dm >= 1 && $dm <= 31 && $dm >= 1 && $dm <= 12) {
            // Okay, we've got a possibly valid date. Determine the
            // start and end of this date.

            // First see what our timezone offset is for the logged in user.
            $offset = $PHORUM['tz_offset'];
            if ($PHORUM['user_time_zone'] &&
                isset($PHORUM['user']['tz_offset']) &&
                $PHORUM['user']['tz_offset'] != -99) {
                $offset = $PHORUM['user']['tz_offset'];
            }
            $offset *= 3600;

            // Compute the start and end epoch time for the date.
            $start_of_day = gmmktime(0,  0,  0,  $dm, $dd, $dy) + $offset;
            $end_of_day   = gmmktime(23, 59, 59, $dm, $dd, $dy) + $offset;
        }
    }

    if ($start_of_day !== null)
    {
        if ($match == "posted on") {
            $meta[] = "(";
            $meta[] = array(
                "condition" => "$dbfield >= QUERY",
                "query"     => $start_of_day
            );
            $meta[] = "AND";
            $meta[] = array(
                "condition" => "$dbfield <= QUERY",
                "query"     => $end_of_day
            );
            $meta[] = ")";
        }
        elseif ($match == "posted on or before") {
            $meta[] = array(
                "condition" => "$dbfield <= QUERY",
                "query"     => $end_of_day
            );
        }
        elseif ($match == "posted before") {
            $meta[] = array(
                "condition" => "$dbfield < QUERY",
                "query"     => $start_of_day
            );
        }
        elseif ($match == "posted after") {
            $meta[] = array(
                "condition" => "$dbfield > QUERY",
                "query"     => $end_of_day
            );
        }
        elseif ($match == "posted on or after") {
            $meta[] = array(
                "condition" => "$dbfield >= QUERY",
                "query"     => $start_of_day
            );
        }
        else {
            trigger_error(
                "prepare_filter_date(): illegal match \"" .
                htmlspecialchars($match) . "\"", E_USER_ERROR
            );
        }
    }
    else
    {
        // We have to insert a condition to not disturb the query.
        // We'll add a condition that will never match.
        $meta[] = array(
            "condition" => "$dbfield = 0",
            "query"     => null,
        );
    }

    return $meta;
}

// ----------------------------------------------------------------------
// Javascript representation of the possible filter rules description
// ----------------------------------------------------------------------
?>
<script type="text/javascript">
//<![CDATA[

// The available filters and their configuration.
var ruledefs = {
<?php
$count = count($ruledefs);
foreach($ruledefs as $filter => $def) {
    $count--;
    print "  '$filter':{\n" .
          "    'label':'{$def["label"]}',\n" .
          "    'queryfield':" .
               (isset($def["queryfield"])?"'{$def["queryfield"]}'":"null") .
               ",\n" .
          "    'matches':{\n";
    $mcount = count($def["matches"]);
    $idx = 0;
    foreach ($def["matches"] as $k => $v) {
        print "      '$idx':'".addslashes($k)."'" . (--$mcount?",\n":"\n");
        $idx ++;
    }
    print "    }\n" .
          "  }" .
          ($count ? "," : "") . "\n";
} ?>
};
//]]>
</script>

<?php
// ----------------------------------------------------------------------
// Display the filter form
// ----------------------------------------------------------------------
?>

<div class="input-form-td-break">
  Filter messages / threads to delete
</div>
<div class="input-form-td-message">
<?php if (!count($_POST)) { ?>
  <strong>ATTENTION!</strong><br/>
  <div style="color:darkred">
    This script can delete A LOT of messages at once. So be careful
    which messages you select for deleting. Use it at your own risk.
    If you do not feel comfortable with this, please make a good
    database backup before deleting any messages.
  </div>
  <br/>
  The first step in deleting messages is setting up filters
  for finding the messages that you want to delete. Please add these
  filters below (use the plus button for adding and the minus button for
  deleting filters). After you are done, click the "Find messages" button
  to retrieve a list of messages that match the filters. No messages
  will be deleted during this step.<br/>
  <br/>
<?php } ?>
  <form id="filterform" method="post"
        action="<?php echo phorum_admin_build_url('base'); ?>"
        onsubmit="filter.getFilterDescription()">
  <input type="hidden" name="phorum_admin_token" value="<?php echo $PHORUM['admin_token'];?>" />
  
  <input type="hidden" name="module" value="<?php print ADMIN_MODULE ?>" />
  <input type="hidden" name="filterdesc" id="filterdesc" value="" />
  <div style="margin-bottom: 5px">
    <input id="filtermode_and" type="radio"
           <?php if ($filtermode=='and') { ?>checked="checked"<?php } ?>
           name="filtermode" value="and">
      <label for="filtermode_and">Match all of the following</label>
    </input>
    <input id="filtermode_or" type="radio"
           <?php if ($filtermode=='or') { ?>checked="checked"<?php } ?>
           name="filtermode" value="or">
      <label for="filtermode_or">Match any of the following</label>
    </input>
  </div>
  <table class="message_prune_filtertable">
    <tbody id="ruleset">
      <!-- Only used for pushing the query field cell 100% wide.
           It does not really work well if I try to do this from the
           dynamic td generation below. -->
      <tr>
        <th></th>
        <th></th>
        <th style="width: 100%"></th>
        <th></th>
        <th></th>
      </tr>
      <!-- filter rules will be added dynamically at this spot in this table -->
      <noscript>
      <tr>
        <td colspan="5">
          <strong>
            Please, enable JavaScript in your browser. This tool
            requires JavaScript for its operation.
          </strong>
        </td>
      </tr>
      </noscript>
    </tbody>
  </table>
  <input type="submit" value="Find messages" />
  </form>

</div>

<?php
// ----------------------------------------------------------------------
// Javascript filter form implementation
// ----------------------------------------------------------------------
?>

<script type="text/javascript">
//<![CDATA[

// Class PhorumFilterRule
// This class describes a single Phorum filter rule.
function PhorumFilterRule(conf)
{
    // Check if we have all required config information.
    if (conf == null) {
        throw("Illegal call of PhorumFilterRule(): no config set");
        return;
    }
    if (conf.parent == null) {
        throw("Illegal call of PhorumFilterRule(): no parent in the config");
        return;
    }
    if (conf.index == null) {
        throw("Illegal call of PhorumFilterRule(): no index in the config");
        return;
    }

    // Object properties -------------------------------------------------

    // Information relating to the PhorumFilter object which created this rule.
    this.parent = conf.parent;
    this.index  = conf.index;

    // The properties that represent the rule state.
    this.field = (conf.field ? conf.field : 'body');
    this.match = (conf.match ? conf.match : 'contains');
    this.query = (conf.query ? conf.query : '');
    this.query_input_type = null;

    // Object methods ----------------------------------------------------

    // Method for handling actions after selecting a rule field.
    this.onSelectFieldChange = function()
    {
        var idx;

        // Store the current rule state.
        idx = this.field_input.selectedIndex;
        this.field = this.field_input.options[idx].value;

        // Populate the match_input selection.
        for (idx=this.match_input.options.length; idx>=0; idx--) {
            this.match_input.options[idx] = null;
        }
        for (var id in ruledefs[this.field].matches) {
            var o = document.createElement('option');
            o.value = ruledefs[this.field].matches[id];
            o.innerHTML = o.value;
            if (o.value == this.match) o.selected = true;
            this.match_input.appendChild(o);
        }

        // Clean up the current query_input if we do not need a query
        // input or if we have to create a different type of query input.
        if (this.query_input_type == null ||
            (ruledefs[this.field].queryfield != null &&
             ruledefs[this.field].queryfield != this.query_input_type)) {
            if (this.query_input && this.query_input.calendar) {
                this.query_input.calendar = null;
            }
            if (this.query_input && this.query_input.helptext) {
                this.query_input_td.removeChild(this.query_input.helptext);
                this.query_input.helptext = null;
            }
            if (this.query_input) {
                this.query_input_td.removeChild(this.query_input);
                this.query_input = null;
                this.query_input_type = null;
            }
        }

        // If the rule type uses a query input, then we use a separate
        // table cell for that input. If we do not use that, we make
        // the match_input cell span two cells.
        if (ruledefs[this.field].queryfield == null)
        {
            // Take two cells for the match selection.
            this.match_input_td.colSpan = 2;

            // Remove the query input cell from the form.
            for (var i=0; i<this.container.childNodes.length; i++) {
                if (this.container.childNodes[i] == this.query_input_td) {
                    this.container.removeChild(this.query_input_td);
                }
            }
        }
        else
        {
            // Take one cell for the match selection.
            this.match_input_td.colSpan = 1;

            // Create a new query input if necessary.
            var create_new = false;
            if (this.query_input_type == null)
            {
                this.query_input = document.createElement('input');
                this.query_input.ruleobj = this;
                this.query_input.type = 'text';
                this.query_input.style.width='100%';
                this.query_input.value = this.query;

                this.query_input.onkeyup = function() {
                    this.ruleobj.onQueryInputChange(this.value);
                }
                this.query_input.onchange = function() {
                    this.ruleobj.onQueryInputChange(this.value);
                }

                this.query_input_type = ruledefs[this.field].queryfield;

                var create_new = true;
            }

            // Add the query cell + input to the table.
            this.query_input_td.appendChild(this.query_input);
            this.container.insertBefore(
                this.query_input_td,
                this.del_button_td
            );

            // Extra options for date fields.
            if (ruledefs[this.field].queryfield == 'date')
            {
                this.query_input_td.style.whiteSpace = 'nowrap';

                this.query_input.style.width = '90px';
                this.query_input.style.paddingLeft = '3px';
                this.query_input.style.fontSize = '11px';
                this.query_input.maxLength = 10;
                this.query_input.style.marginRight = '6px';

                if (create_new) this.query_input.helptext=document.createTextNode("yyyy/mm/dd");
                this.query_input_td.style.fontSize = '11px';
                this.query_input_td.appendChild(this.query_input.helptext);
            }
        }

        // Delegate further handling to onSelectMatchChange().
        this.onSelectMatchChange();
    }

    // Method for handling actions after selecting a rule match.
    this.onSelectMatchChange = function()
    {
        var idx;

        // Store the current rule state.
        idx = this.match_input.selectedIndex;
        this.match = this.match_input.options[idx].value;
    }

    // Method for handling actions after changing the query input.
    this.onQueryInputChange = function(data) {
        this.query = data;
    }

    // Method for destroying a rule object.
    this.destroy = function()
    {
        this.field_input_td.removeChild(this.field_input);
        this.container.removeChild(this.field_input_td);
        this.match_input_td.removeChild(this.match_input);
        this.container.removeChild(this.match_input_td);
        if (this.query_input) {
            this.query_input_td.removeChild(this.query_input);
        }
        for (var i=0; i<this.container.childNodes.length; i++) {
            if (this.container.childNodes[i] == this.query_input_td) {
                this.container.removeChild(this.query_input_td);
            }
        }
        this.add_button_td.removeChild(this.add_button);
        this.container.removeChild(this.add_button_td);
        this.del_button_td.removeChild(this.del_button);
        this.container.removeChild(this.del_button_td);

        this.add_button = null;
        this.add_button_td = null;
        this.del_button = null;
        this.del_button_td = null;
        this.field_input = null;
        this.field_input_td = null;
        this.match_input = null;
        this.match_input_td = null;
        this.query_input = null;
        this.query_input_td = null;
        this.container = null;

        this.type = null;
        this.match = null;
        this.query = null;
    }

    // Build the interface -----------------------------------------------

    // Create a new table row for holding the filter rule.
    this.container = document.createElement('tr');
        this.container.style.borderBottom = '1px dashed #ccc';

    // The field on which to match.
    this.field_input = document.createElement('select');
        this.field_input.ruleobj = this;
        this.field_input.onchange = function() {
            this.ruleobj.onSelectFieldChange();
        }

    // The type of match to use.
    this.match_input = document.createElement('select');
        this.match_input.ruleobj = this;
        this.match_input.onchange = function() {
            this.ruleobj.onSelectMatchChange();
        }

    // Button for adding a filter.
    this.add_button = document.createElement('img');
        this.add_button.src = '<?php print $PHORUM["http_path"] ?>/images/add.png';
        this.add_button.style.cursor = 'pointer';
        this.add_button.ruleobj = this;
        this.add_button.onclick = function() {
            this.ruleobj.parent.addFilterRule();
        }

    // Button for deleting a filter.
    this.del_button = document.createElement('img');
        this.del_button.src = '<?php print $PHORUM["http_path"] ?>/images/delete.png';
        this.del_button.style.cursor = 'pointer';
        this.del_button.ruleobj = this;
        this.del_button.onclick = function() {
            this.ruleobj.parent.deleteFilterRule(this.ruleobj);
        }

    // Add cells to the table row.
    this.field_input_td = document.createElement('td');
        this.field_input_td.style.padding= '5px';
        this.field_input_td.appendChild(this.field_input);
        this.container.appendChild(this.field_input_td);
    this.match_input_td = document.createElement('td');
        this.match_input_td.colspan = 2;
        this.match_input_td.style.padding = '5px';
        this.match_input_td.appendChild(this.match_input);
        this.container.appendChild(this.match_input_td);
    // Will be filled and displayed when necessary (based on file type).
    this.query_input_td = document.createElement('td');
        this.query_input_td.style.padding = '5px';
    this.del_button_td = document.createElement('td');
        this.del_button_td.style.padding = '5px 2px';
        this.del_button_td.appendChild(this.del_button);
        this.container.appendChild(this.del_button_td);
    this.add_button_td = document.createElement('td');
        this.add_button_td.style.padding = '5px 5px 5px 2px';
        this.add_button_td.appendChild(this.add_button);
        this.container.appendChild(this.add_button_td);

    // Populate the field_input selection.
    for (var id in ruledefs) {
        var o = document.createElement('option');
        o.innerHTML = ruledefs[id]["label"];
        o.value     = id;
        if (o.value == this.field) o.selected = true;
        this.field_input.appendChild(o);
    }

    // Create match select list and possibly a query
    // input by faking a filter field selection event.
    this.onSelectFieldChange();
}

// Class PhorumFilter
// This class describes a set of Phorum filter rules.
function PhorumFilter(conf)
{
    // Check if we have all required config information.
    if (conf == null) {
        throw("Illegal call of PhorumFilter(): no config set");
        return;
    }
    if (conf.parent == null) {
        throw("Illegal call of PhorumFilter(): no parent in the config");
        return;
    }

    // Object properties -------------------------------------------------

    this.rules = new Array();
    this.rulecount = 0;
    this.index = 0;
    this.parent = conf.parent;

    // Object methods ----------------------------------------------------

    this.addFilterRule = function(conf)
    {
        // Create a PhorumFilterRule object.
        if (conf == null) conf = {};
        conf.parent = this;
        conf.index  = this.index++;
        var ruleobj = new PhorumFilterRule(conf);

        // Add the rule to the filter.
        this.parent.appendChild(ruleobj.container);
        this.rules[ruleobj.index] = ruleobj;

        this.rulecount ++;
    }

    this.deleteFilterRule = function(ruleobj)
    {
        // Do not delete the last rule.
        if (this.rulecount == 1) return;

        // Delete the rule from the filter.
        this.parent.removeChild(ruleobj.container);
        this.rules[ruleobj.index] = null;
        ruleobj.destroy();

        this.rulecount --;
    }

    // Construct a textual description of the filter.
    this.getFilterDescription = function()
    {
        var filterdesc = '';

        // Determine the glue symbol to use.
        // & for AND matches, | for OR matches
        var glue = document.getElementById('filtermode_or').checked?'|':'&';

        // Walk over all available filters and create a
        // textual filter config line for them.
        for (var i = 0 ; i < this.index; i++) {
            if (this.rules[i] == null) continue;
            var rule = this.rules[i];
            if (filterdesc != '') filterdesc += glue;
            filterdesc += encodeURIComponent(rule.field) + "," +
                          encodeURIComponent(rule.match) + "," +
                          encodeURIComponent(rule.query);
        }

        document.getElementById('filterdesc').value = filterdesc;
        document.getElementById('filterform').submit();
    }
}

// Create the filter object.
var filter = new PhorumFilter({
    "parent": document.getElementById("ruleset")
});

// Add filter rules.
<?php
if (count($filters)) {
    foreach ($filters as $filter) {
        print "filter.addFilterRule({\n" .
              "    'field': '".addslashes($filter[0])."',\n" .
              "    'match': '".addslashes($filter[1])."',\n" .
              "    'query': '".addslashes($filter[2])."'\n" .
              "});\n";
    }
} else {
    print "filter.addFilterRule();\n";
}
?>

//]]>
</script>

<?php
// ----------------------------------------------------------------------
// Show selected messages.
// ----------------------------------------------------------------------

if (isset($messages) && is_array($messages))
{
  if (count($messages)) { ?>
    <script type="text/javascript">
    //<![CDATA[

    function toggle_msginfo(id)
    {
        var d = document.getElementById('msginfo_'+id);
        d.style.display = d.style.display != 'block' ? 'block' : 'none';
        return false;
    }

    function select_all_messages()
    {
        var f = document.getElementById('selectform');
        for (var i = 0; i < f.elements.length; i++) {
            if (f.elements[i].type == 'checkbox') {
                f.elements[i].checked = true;
            }
        }
        return false;
    }

    function delete_selected_messages()
    {
        var count = 0;
        var f = document.getElementById('selectform');
        for (var i = 0; i < f.elements.length; i++) {
            if (f.elements[i].type == 'checkbox' &&
                f.elements[i].checked) {
                count ++;
            }
        }
        if (count == 0) {
            alert('Please select the message(s) that you want to delete.');
            return false;
        }

        return confirm(
            'Delete the ' + count + ' selected message(s) ' +
            '/ thread(s) from the database?'
        );
    }

    //]]>
    </script>

    <form id="selectform" method="post"
          action="<?php echo phorum_admin_build_url('base'); ?>">
    <input type="hidden" name="phorum_admin_token" value="<?php echo $PHORUM['admin_token'];?>" />
    
    <input type="hidden" name="module" value="<?php print ADMIN_MODULE ?>" />
    <input type="hidden" name="filterdesc" id="filterdesc" value="<?php
        // Remember the filter description if one is available
        // (should be at this point).
        if (isset($_POST["filterdesc"])) {
            print htmlspecialchars($_POST["filterdesc"]);
        }
    ?>" />

    <div class="input-form-td-break" style="margin-bottom: 10px">
      Select messages / threads to delete
      (<?php print count($messages) ?>
       result<?php if (count($messages)!=1) print "s" ?> found)
    </div>

    <div class="input-form-td-message">
      Here you see all messages and threads that were found, based on the
      above filters. You can still modify the filters if you like.
      To delete messages or threads, you have to check the checkboxes in front
      of them and click on "Delete selected". If you need more info about a
      certain item, then click on the subject for expanding the view.<br/>
      <br/>
      The icon and color tell you if are handling a
      <span style="color:#009">message</span>
      (<img align="top" src="<?php print $PHORUM["http_path"] ?>/images/comment.png"/>)
      or a <span style="color:#c30">thread</span>
      (<img align="top" src="<?php print $PHORUM["http_path"] ?>/images/comments.png"/>).
      <br/>
      <br/>
      <?php if (count($messages) > 10) { ?>
      <input type="button" value="Select all"
             onclick="return select_all_messages()" />
      <input type="submit" value="Delete selected"
             onclick="return delete_selected_messages()" />
      <br/><br/>
      <?php } ?>
      <table style="width:96%; border-collapse:collapse">
      <?php

      // Add the messages to the form.
      foreach ($messages as $id => $data) {
        $icon  = $data["parent_id"] == 0 ? "comments.png" : "comment.png";
        $color = $data["parent_id"] == 0 ? "#c30" : "#009";
        $alt   = $data["parent_id"] == 0 ? "thread" : "message";

        $strippedbody = nl2br(htmlspecialchars($data["body"]));

        $url = str_replace(array('%forum_id%', '%thread_id%','%message_id%'),
                           array($data['forum_id'], $data['thread'],$data['message_id']),
                           $read_url_template);
        ?>
        <tr>
          <td valign="top" style="border-bottom:1px dashed #ccc">
            <input type="checkbox" name="deletemessage[<?php print $id ?>]"/>
          </td>
          <td valign="top" style="width:100%;border-bottom:1px dashed #ccc">
            <span style="float:right">
              <?php print htmlspecialchars($data["author"]) ?>
              <?php print phorum_date($PHORUM['short_date'], $data["datestamp"]) ?>
            </span>
            <img align="top"
                 title="<?php print $alt ?>" alt="<?php print $alt ?>"
                 src="<?php print $PHORUM["http_path"]."/images/".$icon ?>"/>
              <a style="text-decoration: none" href="#"
                 onclick="return toggle_msginfo(<?php print $id ?>)">
                <span style="color:<?php print $color?>">
                    <?php print htmlspecialchars($data["subject"]) ?>
                </span>
              </a>
            <div class="message_prune_msginfo" id="msginfo_<?php print $id ?>">
              <?php
              if ($data["user_id"]) {
                  print "Posted by authenticated user \"".
                        htmlspecialchars($data["user_username"]) .
                        "\" (user_id ".$data["user_id"].") from ".$data['ip']."<br/>";
              }
              print "Date and time: " . phorum_date($PHORUM['short_date_time'], $data["datestamp"]) . "<br/>";
              // Might not be available (for announcements).
              // I won't put a lot of stuff in here for handling announcements,
              // because 5.2 handles them differently than 5.1.
              if (isset($forum_info[$data["forum_id"]])) {
                  print "Forum: ".  $forum_info[$data["forum_id"]] . "<br/>";
              }
              if ($data["parent_id"] == 0) {
                  print "Messages in this thread: {$data["thread_count"]}<br/>";
                  if ($data["thread_count"] > 1) {
                      print "Thread's last post: " .
                            phorum_date($PHORUM['short_date_time'], $data["thread_modifystamp"]) . "<br/>";
                  }
              }
              ?>
              <a target="_blank" href="<?php print $url ?>">Open this message in a new window</a><br/>
              <div class="message_prune_msginfo_body">
                <?php print $strippedbody ?>
              </div>
            </div>
          </td>
        </tr> <?php
      } ?>

      </table>
      <br/>
      <input type="button" value="Select all"
             onclick="return select_all_messages()" />
      <input type="submit" value="Delete selected"
             onclick="return delete_selected_messages()" />
    </div>
    </form>
    <?php
  // count($messages) == 0
  } else { ?>
    <div class="input-form-td-break" style="margin-bottom: 10px">
    No messages were found
    </div>
    <div class="input-form-td-message">
    Your current filter does not match any message in your database.
    </div>

    <?php
  }
}
?>



