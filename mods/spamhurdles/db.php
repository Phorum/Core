<?php

if(!defined("PHORUM")) return;

# The database scheme version, which is used to handle
# installation and upgrades from the module.
define("SPAMHURDLES_DB_VERSION", 1);

# The table name for storing spamhurdles information.
define("SPAMHURDLES_TABLE", "{$GLOBALS["PHORUM"]["DBCONFIG"]["table_prefix"]}_spamhurdles");

# Check if an installation or upgrade of the database scheme is needed.
function spamhurdles_db_install()
{
    $version = isset($GLOBALS["PHORUM"]["mod_spamhurdles_installed"]) 
        ? $GLOBALS["PHORUM"]["mod_spamhurdles_installed"] : 0;

    while ($version < SPAMHURDLES_DB_VERSION)
    {
        // Initialize the settings array that we will be saving.
        $version++;
        $settings = array( "mod_spamhurdles_installed" => $version );

        $sqlfile = "./mods/spamhurdles/db/" .
                   $GLOBALS["PHORUM"]["DBCONFIG"]["type"] . "/$version.php";
                   
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

function spamhurdles_db_query($sql, $fetchrow = false)
{
    $type = $GLOBALS["PHORUM"]["DBCONFIG"]["type"];

    switch ($type) 
    {
      case "mysql":
        $conn = phorum_db_mysql_connect();
        $res = mysql_query($sql, $conn);
        if ($fetchrow) {
          if ($res && mysql_num_rows($res)) {
            $res = mysql_fetch_array($res);
          } else {
            $res = NULL;
          }
        }
        break;

      case "mysqli":
        $conn = phorum_db_mysqli_connect();
        $res = mysqli_query($conn, $sql);
        if ($fetchrow) {
          if ($res && mysqli_num_rows($res)) {
            $res = mysqli_fetch_array($res);
          } else {
            $res = NULL;
          }
        }
        break;

      case postgresql:
        $conn = phorum_db_postgresql_connect();
        $res = pg_query($conn, $sql);
        if ($fetchrow) {
          if ($res && pg_num_rows($res)) {
            $res = pg_fetch_row($res);
          } else {
            $res = NULL;
          }
        }
        break;

      default:
        die("Spam Hurdles contains no database implementation for database " .
            "type \"".htmlspecialchars($type)."\" currently.");
    }

    return $res;
}

# Retrieve data from the database by key.
function spamhurdles_db_get($key)
{
    $sql = "SELECT data,expire_time FROM ".SPAMHURDLES_TABLE. " " .
           "WHERE id = '" . addslashes($key) . "'";

    $record = spamhurdles_db_query($sql, true);

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
    $sql = "INSERT INTO ".SPAMHURDLES_TABLE.
           " (id, data, create_time, expire_time) values (" .
           "'".addslashes($key)."', " .
           "'".addslashes(serialize($data))."', " .
           time() . ", " .
           (time() + $ttl) . ')';

    spamhurdles_db_query($sql);
}

# Remove data from the database.
function spamhurdles_db_remove($key)
{
    $sql = "DELETE FROM ".SPAMHURDLES_TABLE. " " .
           "WHERE id='".addslashes($key)."'";

    spamhurdles_db_query($sql);
}

# Remove expired entries from the database.
function spamhurdles_db_remove_expired()
{
    $sql = "DELETE FROM ".SPAMHURDLES_TABLE. " " .
           "WHERE expire_time < " . time();

    spamhurdles_db_query($sql);
}

?>
