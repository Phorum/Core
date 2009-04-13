<?php

if(!defined("PHORUM")) return;

# The database scheme version, which is used to handle
# installation and upgrades from the module.
define("SPAMHURDLES_DB_VERSION", 1);

# The table name for storing spamhurdles information.
$GLOBALS['PHORUM']['spamhurdles_table'] =
    "{$GLOBALS["PHORUM"]["DBCONFIG"]["table_prefix"]}_spamhurdles";

# Check if an installation or upgrade of the database scheme is needed.
function spamhurdles_db_init()
{
    $layerpath = "./mods/spamhurdles/db/{$GLOBALS["PHORUM"]["DBCONFIG"]["type"]}";

    // Allow db layers to provide an initialization script of their own.
    // The main goal for this script is to allow a db layer to override the
    // $PHORUM['spamhurdles_table'] variable.
    if (file_exists("$layerpath/db.php")) require_once("$layerpath/db.php");

    $version = isset($GLOBALS["PHORUM"]["mod_spamhurdles_installed"])
        ? $GLOBALS["PHORUM"]["mod_spamhurdles_installed"] : 0;

    while ($version < SPAMHURDLES_DB_VERSION)
    {
        // Initialize the settings array that we will be saving.
        $version++;
        $settings = array( "mod_spamhurdles_installed" => $version );

        $sqlfile = "$layerpath/$version.php";

        if (! file_exists($sqlfile)) {
            print "<b>Unexpected situation on installing " .
                  "the Spam Hurdles module</b>: unable to find the database " .
                  "scheme setup script " . htmlspecialchars($sqlfile);
            return false;
        }

        $sqlqueries = array();
        include($sqlfile);

        if (count($sqlqueries) == 0) {
            print "<b>Unexpected situation on installing " .
                  "the Spam Hurdles module</b>: could not read any SQL " .
                  "queries from file " . htmlspecialchars($sqlfile);
            return false;
        }
        $err = phorum_db_run_queries($sqlqueries);
        if ($err) {
            print "<b>Unexpected situation on installing " .
                  "the Spam Hurdles module</b>: running the " .
                  "install queries from file " . htmlspecialchars($sqlfile) .
                  " failed";
            return false;
        }

        // Save our settings.
        if (!phorum_db_update_settings($settings)) {
            print "<b>Unexpected situation on installing " .
                  "the Spam Hurdles module</b>: updating the " .
                  "mod_spamhurdles_installed setting failed";
            return false;
        }
    }

    return true;
}

# Retrieve data from the database by key.
function spamhurdles_db_get($key)
{
    $sql = "SELECT data,expire_time FROM {$GLOBALS['PHORUM']['spamhurdles_table']}
            WHERE id = '" . addslashes($key) . "'";

    $record = phorum_db_interact(DB_RETURN_ROW, $sql);

    // If a record was found, then return the data in case the record
    // isn't expired. If the record is expired, then delete it from
    // the database.
    if ($record) {
        if ($record[1] > time()) {
            return unserialize($record[0]);
        } else {
            spamhurdles_db_remove($key);
        }
    }

    return NULL;
}

# Store data in the database.
function spamhurdles_db_put($key, $data, $ttl)
{
    // Try to insert a new spamhurdles record.
    $res = phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO {$GLOBALS['PHORUM']['spamhurdles_table']}
                (id, data, create_time, expire_time)
         VALUES (" .
            "'".addslashes($key)."', " .
            "'".addslashes(serialize($data))."', " .
            time() . ", " .
            (time() + $ttl) .
         ")",
        NULL,
        DB_DUPKEYOK | DB_MASTERQUERY
    );

    // If no result was returned, then the query failed. This probably
    // means that we already have the spamhurdles record in the database.
    // So instead of inserting a record, we need to update one here.
    if (!$res) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$GLOBALS['PHORUM']['spamhurdles_table']}
             SET    data        = '".addslashes(serialize($data))."',
                    create_time = ".time().",
                    expire_time = ".(time() + $ttl)."
             WHERE  id          = '".addslashes($key)."'",
            NULL,
            DB_MASTERQUERY
        );
    }
}

# Remove data from the database.
function spamhurdles_db_remove($key)
{
    $sql = "DELETE FROM {$GLOBALS['PHORUM']['spamhurdles_table']}
            WHERE id='".addslashes($key)."'";

    phorum_db_interact(DB_RETURN_RES, $sql, NULL, DB_MASTERQUERY);
}

# Remove expired entries from the database.
function spamhurdles_db_remove_expired()
{
    $sql = "DELETE FROM {$GLOBALS['PHORUM']['spamhurdles_table']}
            WHERE expire_time < " . time();

    phorum_db_interact(DB_RETURN_RES, $sql, NULL, DB_MASTERQUERY);
}

?>
