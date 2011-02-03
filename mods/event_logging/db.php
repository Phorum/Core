<?php

if(!defined("PHORUM")) return;

// Load the definitions for the constants that we use.
require_once('./mods/event_logging/constants.php');

// The database schema version, which is used to handle
// installation and upgrades directly from the module.
define("EVENT_LOGGING_DB_VERSION", 1);

// The table name for storing event logs.
$GLOBALS["PHORUM"]["event_logging_table"] =
    "{$GLOBALS["PHORUM"]["DBCONFIG"]["table_prefix"]}_event_logging";

/**
 * This function will check if an upgrade of the database scheme is needed.
 * It is generic for all database layers.
 */
function event_logging_db_install()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $version = isset($PHORUM["mod_event_logging_installed"])
        ? $PHORUM["mod_event_logging_installed"] : 0;

    while ($version < EVENT_LOGGING_DB_VERSION)
    {
        // Initialize the settings array that we will be saving.
        $version++;
        $settings = array( "mod_event_logging_installed" => $version );

        $sqlfile = "./mods/event_logging/db/" .
                   $PHORUM["DBCONFIG"]["type"] . "/$version.php";

        if (! file_exists($sqlfile)) {
            print "<b>Unexpected situation on installing " .
                  "the Event Logging module</b>: " .
                  "unable to find the database schema setup script " .
                  htmlspecialchars($sqlfile);
            return false;
        }

        $sqlqueries = array();
        include($sqlfile);

        if (count($sqlqueries) == 0) {
            print "<b>Unexpected situation on installing " .
                  "the Event Logging module</b>: could not read any SQL " .
                  "queries from file " . htmlspecialchars($sqlfile);
            return false;
        }
        $err = phorum_db_run_queries($sqlqueries);
        if ($err) {
            print "<b>Unexpected situation on installing " .
                  "the Event Logging module</b>: running the " .
                  "install queries from file " . htmlspecialchars($sqlfile) .
                  " failed. The error was " . htmlspecialchars($err);
            return false;
        }

        // Save our settings.
        if (!phorum_db_update_settings($settings)) {
            print "<b>Unexpected situation on installing " .
                  "the Event Logging module</b>: updating the " .
                  "mod_event_logging_installed setting failed";
            return false;
        }
    }

    return true;
}

/**
 * Write a new message to the event logging table.
 *
 * This function will automatically fill the log information with
 * user_id, ip, hostname (if hostname resolving is enabled for the log module)
 * datestamp and vroot information. Other log info can be provided throught
 * the $loginfo argument.
 *
 * @param $loginfo - An array containing logging information. This array
 *                   can contain the following fields:
 *
 *                   message     A short log message on one line.
 *                   details     Details about the log message, which can
 *                               span multiple lines. This could for example
 *                               be used for providing a debug backtrace.
 *                   source      The source of the log message. This is a
 *                               free 32 char text field, which can be used
 *                               to specifiy what part of Phorum generated the
 *                               log message (e.g. "mod_smileys"). If no
 *                               source is provided, the "phorum_page"
 *                               constant will be used instead.
 *                   category    A high level category for the message.
 *                               Options for this field are:
 *                               EVENTLOG_CAT_APPLICATION (default)
 *                               EVENTLOG_CAT_DATABASE
 *                               EVENTLOG_CAT_SECURITY
 *                               EVENTLOG_CAT_SYSTEM
 *                               EVENTLOG_CAT_MODULE
 *                   loglevel    This indicates the severety of the message.
 *                               Options for this field are:
 *                               EVENTLOG_LVL_DEBUG
 *                                 Messages that are used by programmers
 *                                 for tracking low level Phorum operation.
 *                               EVENTLOG_LVL_INFO
 *                                 Messages that provide logging for events
 *                                 that occur during normal operation. These
 *                                 messages could be harvested for usage
 *                                 reporting and other types of reports.
 *                               EVENTLOG_LVL_WARNING
 *                                 Warning messages do not indicate errors,
 *                                 but they do report events that are not
 *                                 considered to belong to normal operation
 *                                 (e.g. a user which enters a wrong password
 *                                 or a duplicate message being posted).
 *                               EVENTLOG_LVL_ERROR
 *                                 Error messages indicate non urgent failures
 *                                 in Phorum operation. These should be
 *                                 relayed to administrators and/or developers
 *                                 to have them solved.
 *                               EVENTLOG_LVL_ALERT
 *                                 Alert messages indicate errors which should
 *                                 be corrected as soon as possible (e.g. loss
 *                                 of network connectivity or a full disk).
 *                                 These should be relayed to the system
 *                                 administrator).
 *
 *                   vroot       vroot for which a message is generated.
 *                   forum_id    forum_id for which a message is generated.
 *                   thread_id   thread_id for which a message is generated
 *                   message_id  message_id for which a message is generated
 *
 *                   user_id     Filled automatically, but can be overridden
 *                   ip          Filled automatically, but can be overridden
 *                   hostname    Filled automatically, but can be overridden
 *                   datestamp   Filled automatically, but can be overridden
 */
function event_logging_writelog($loginfo)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Check the minimum log level. Only write to the log if the
    // log level of the event is at or above the configured minimum.
    $lvl = isset($loginfo["loglevel"]) ? (int)$loginfo["loglevel"] : 0;
    if ($lvl < $PHORUM["mod_event_logging"]["min_log_level"]) return;

    $loginfo = phorum_hook("event_logging_writelog", $loginfo);

    // The record that we will insert in the database.
    $record = array();

    // Handle messages that exceed the maximum message length.
    if ($loginfo["message"] !== NULL && strlen($loginfo["message"]) > 255) {
        if (!isset($loginfo["details"])) $loginfo["details"] = '';
        $loginfo["details"] = "Message:\n\n{$loginfo["message"]}\n\n" .
                              $loginfo["details"];
        $loginfo["message"] = substr($loginfo["message"], 0, 100) . "... (see event details for the full message)\n";
    } elseif (isset($loginfo["details"])) {
        $loginfo["details"] = "Message:\n\n{$loginfo["message"]}\n\n" .
                              $loginfo["details"];
    }

    // Add the fields from the $loginfo argument.
    foreach ($loginfo as $key => $val) {
        switch ($key)
        {
            case "datestamp":
            case "user_id":
            case "vroot":
            case "forum_id":
            case "thread_id":
            case "message_id":
            case "category":
            case "loglevel":
                settype($val, "int");
                $record[$key] = $val;
                break;

            case "message":
            case "details":
            case "source":
            case "ip":
            case "hostname":
                $record[$key] = "'" . phorum_db_interact(DB_RETURN_QUOTED, $val) . "'";
                break;

            default: phorum_database_error(
                "event_logging_log(): Illegal key " .
                "field \"$key\" in the \$loginfo argument"
            );
        }
    }

    // Add the message source.
    $from_module = FALSE;
    if (!isset($record["source"])) {
        list($source, $from_module) = event_logging_find_source(1);
        $record["source"] = "'" . phorum_db_interact(DB_RETURN_QUOTED, $source) . "'";
    }

    // Add the category.
    if (!isset($record["category"])) {
        $record["category"] = $from_module
                            ? EVENTLOG_CAT_MODULE
                            : EVENTLOG_CAT_APPLICATION;
    }

    // Add the datestamp.
    if (!isset($record["datestamp"])) {
        $record["datestamp"] = time();
    }

    // Add the IP address for the current visitor.
    if (!isset($record["ip"]) && isset($_SERVER["REMOTE_ADDR"])) {
        $ip = $_SERVER["REMOTE_ADDR"];
        $record["ip"] = "'" . phorum_db_interact(DB_RETURN_QUOTED, $ip) . "'";
    }

    // Add the hostname for the current visitor.
    if (!isset($record["hostname"]) && isset($record["ip"]) &&
        $PHORUM["mod_event_logging"]["resolve_hostnames"]) {
        $hostname = gethostbyaddr($ip);
        if ($hostname != $ip) {
            $record["hostname"] = "'" . phorum_db_interact(DB_RETURN_QUOTED, $hostname) . "'";
        }
    }

    // Add the user_id in case the visitor is an authenticated user.
    if (!isset($record["user_id"]) &&
        isset($PHORUM["user"]["user_id"]) && $PHORUM["user"]["user_id"]) {
        $record["user_id"] = $PHORUM["user"]["user_id"];
    }

    // Add the current vroot.
    if (!isset($record["vroot"]) && isset($PHORUM["vroot"])) {
        $record["vroot"] = $PHORUM["vroot"];
    }

    // Insert the logging record in the database.
    phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO {$PHORUM["event_logging_table"]}
                (".implode(', ', array_keys($record)).")
         VALUES (".implode(', ', $record).")", 
        NULL, 
        DB_MASTERQUERY 
    );
}

/**
 * This function is used to create a SQL WHERE statement from a filter
 * description.
 *
 * @param $filter - The filter description.
 *
 * @return $where - A WHERE statement, representing the filter description.
 */
function event_logging_create_where($filter)
{
    if ($filter === NULL || !is_array($filter)) return '';
    $t = $GLOBALS["PHORUM"]["event_logging_table"]; // shorthand

    $where_parts = array();

    if (isset($filter["loglevels"])) {
        $where = '';
        if (is_array($filter["loglevels"]) && count($filter["loglevels"]))
        {
            foreach ($filter["loglevels"] as $id => $l) {
                $filter["loglevels"][$id] = (int)$l;
            }

            $where = count($filter["loglevels"]) == 1
                   ? "$t.loglevel = " . $filter["loglevels"][0]
                   : "$t.loglevel IN (".implode(",", $filter["loglevels"]).")";

            $where_parts[] = $where;
        }
    }

    if (isset($filter["categories"])) {
        $where = '';
        if (is_array($filter["categories"]) && count($filter["categories"]))
        {
            foreach ($filter["categories"] as $id => $l) {
                $filter["categories"][$id] = (int)$l;
            }

            $where = count($filter["categories"]) == 1
                   ? "$t.category = " . $filter["categories"][0]
                   : "$t.category IN (".implode(",", $filter["categories"]).")";

            $where_parts[] = $where;
        }
    }

    foreach (array(
        "source"   => "string",
        "user_id"  => "int",
        "username" => "string",
        "ip"       => "string",
        "message"  => "string",
        "details"  => "string",
    ) as $field => $type) {
        if (isset($filter[$field]) && trim($filter[$field]) != '') {
            $val = trim($filter[$field]);
            if ($field != 'username') $field = "$t.$field";
            if ($type == 'int') {
                $where_parts[] = "$field = " . (int) $val;
            } else {
                if (strstr($val, "*")) {
                    $val = str_replace('*', '%', $val);
                    $val = "'" . phorum_db_interact(DB_RETURN_QUOTED, $val) . "'";
                    $where_parts[] = "$field LIKE $val";
                } else {
                    $val = "'" . phorum_db_interact(DB_RETURN_QUOTED, $val) . "'";
                    $where_parts[] = "$field = $val";
                }
            }
        }
    }

    $where = '';
    if (count($where_parts) > 0) {
        $where = 'WHERE ' . implode(' AND ', $where_parts);
    }

    return $where;
}

/**
 * Return an array of all currently available sources in the event log.
 *
 * @return $sources - An array of sources. Keys and values are both the
 *                    name of the source.
 */
function event_logging_getsources()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $rows = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT DISTINCT(source)
         FROM   {$PHORUM["event_logging_table"]}
         ORDER  BY source ASC"
    );

    $sources = array();
    foreach ($rows as $row) {
        $sources[$row["source"]] = $row["source"];
    }

    return $sources;
}

/**
 * Count the number of available log lines, either for a filtered or an
 * unfiltered view.
 *
 * @param $filter - The filter to apply or NULL in case there is no filter.
 *
 * @return $count - The number of log lines.
 */
function event_logging_countlogs($filter = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $where = event_logging_create_where($filter);

    $rows = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT count(*) AS count
         FROM   {$PHORUM["event_logging_table"]}
                LEFT JOIN {$PHORUM["user_table"]}
                ON {$PHORUM["user_table"]}.user_id = {$PHORUM["event_logging_table"]}.user_id
         $where"
    );

    $count = $rows[0]['count'];

    // Limit the maximum number of event logs.
    $max = (int)$PHORUM["mod_event_logging"]["max_log_entries"];
    if ($max > 0 && $count > $max)
    {
        // Find the log_id at which we need to chop off old logs.
        $rows = phorum_db_interact(
            DB_RETURN_ASSOCS,
            "SELECT log_id
             FROM   {$PHORUM["event_logging_table"]}
             ORDER  BY log_id DESC
             LIMIT  1
             OFFSET $max"
        );

        // Delete old logs.
        if (isset($rows[0]["log_id"])) {
            phorum_db_interact(
                DB_RETURN_RES,
                "DELETE FROM {$PHORUM["event_logging_table"]}
                 WHERE  log_id <= {$rows[0]["log_id"]}", 
                NULL, 
                DB_MASTERQUERY 
            );
        }

        $count = $max;
    }

    return $count;
}

/**
 * Retrieve event logs from the database, either for a filtered or an
 * unfiltered view.
 *
 * @param $page       - The page number to retrieve logs for.
 * @param $pagelength - The number of log messages per page.
 * @param $filter     - The filter to apply or NULL in case there is no filter.
 *
 * @return $logs      - An array of event logs.
 */
function event_logging_getlogs($page = 1, $pagelength = 20, $filter = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($page, "int");
    settype($pagelength, "int");
    settype($loglevel, "int");

    $offset = ($page-1)*$pagelength;

    $where = event_logging_create_where($filter);

    $sql = "SELECT {$PHORUM["event_logging_table"]}.log_id     AS log_id,
                   {$PHORUM["event_logging_table"]}.source     AS source,
                   {$PHORUM["event_logging_table"]}.category   AS category,
                   {$PHORUM["event_logging_table"]}.loglevel   AS loglevel,
                   {$PHORUM["event_logging_table"]}.message    AS message,
                   {$PHORUM["event_logging_table"]}.details    AS details,
                   {$PHORUM["event_logging_table"]}.ip         AS ip,
                   {$PHORUM["event_logging_table"]}.hostname   AS hostname,
                   {$PHORUM["event_logging_table"]}.user_id    AS user_id,
                   {$PHORUM["event_logging_table"]}.datestamp  AS datestamp,
                   {$PHORUM["event_logging_table"]}.vroot      AS vroot,
                   {$PHORUM["event_logging_table"]}.forum_id   AS forum_id,
                   {$PHORUM["event_logging_table"]}.thread_id  AS thread_id,
                   {$PHORUM["event_logging_table"]}.message_id AS message_id,
                   {$PHORUM["user_table"]}.username            AS username,
                   {$PHORUM["user_table"]}.email               AS email,
                   {$PHORUM["forums_table"]}.name              AS forum
            FROM   {$PHORUM["event_logging_table"]}
                   LEFT JOIN {$PHORUM["user_table"]}
                   ON {$PHORUM["user_table"]}.user_id = {$PHORUM["event_logging_table"]}.user_id
                   LEFT JOIN {$PHORUM["forums_table"]}
                   ON {$PHORUM["forums_table"]}.forum_id = {$PHORUM["event_logging_table"]}.forum_id
            $where
            ORDER  BY log_id DESC";
     
    if ($pagelength > 0) {
        $sql .= "
            LIMIT  $pagelength
            OFFSET $offset";
    }
            

    return phorum_db_interact(DB_RETURN_ASSOCS, $sql);
}

/**
 * Delete logs from the database, possibly using a filter to limit the
 * logs that are deleted.
 *
 * @param $filter - The filter to apply or NULL in case there is no filter.
 */
function event_logging_clearlogs($filter = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $where = event_logging_create_where($filter);

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM["event_logging_table"]} $where", 
        NULL, 
        DB_MASTERQUERY 
    );
}

/**
 * Update the forum info for a certain message_id.
 */
function event_logging_update_message_id_info($message_id, $forum_id, $thread_id) {
    $PHORUM = $GLOBALS["PHORUM"];

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM["event_logging_table"]}
         SET    forum_id = " . (int)$forum_id . ",
                thread_id = " . (int)$thread_id . "
         WHERE  message_id = " . (int)$message_id, 
         NULL, 
         DB_MASTERQUERY 
    );
}

?>
