<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
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

if (!defined('PHORUM')) return;

/**
 * The other Phorum code does not care how the messages are stored.
 * The only requirement is that they are returned from these functions
 * in the right way.  This means each database can use as many or as
 * few tables as it likes.  It can store the fields anyway it wants.
 * The only thing to worry about is the table_prefix for the tables.
 * all tables for a Phorum install should be prefixed with the
 * table_prefix that will be entered in include/db/config.php.  This
 * will allow multiple Phorum installations to use the same database.
 */

// ----------------------------------------------------------------------
// API functions and definitions
// ----------------------------------------------------------------------

/**
 * The API functions and definitions are included from a separate file.
 * This way, the mysql and the mysqli database layers can share the same
 * API core code.
 */
include('./include/db/mysql_shared.php');


// ----------------------------------------------------------------------
// Database layer specific functions
// ----------------------------------------------------------------------

/**
 * This function is the central function for handling database interaction.
 * The function can be used for setting up a database connection, for running
 * a SQL query and for returning query rows. Which of these actions the
 * function will handle and what the function return data will be, is
 * determined by the $return function parameter.
 *
 * @param $return   - What to return. Options are the following constants:
 *                    DB_RETURN_CONN      a db connection handle
 *                    DB_RETURN_QUOTED    a quoted parameter
 *                    DB_RETURN_RES       result resource handle
 *                    DB_RETURN_ROW       single row as array
 *                    DB_RETURN_ROWS      all rows as arrays
 *                    DB_RETURN_ASSOC     single row as associative array
 *                    DB_RETURN_ASSOCS    all rows as associative arrays
 *                    DB_RETURN_VALUE     single row, single column
 *                    DB_RETURN_ROWCOUNT  number of selected rows
 *                    DB_RETURN_NEWID     new row id for insert query
 *                    DB_RETURN_ERROR     an error message if the query
 *                                        failed or NULL if there was no error
 * @param $sql      - The SQL query to run or the parameter to quote if
 *                    DB_RETURN_QUOTED is used.
 * @param $keyfield - When returning an array of rows, the indexes are
 *                    numerical by default (0, 1, 2, etc.). However, if
 *                    the $keyfield parameter is set, then from each
 *                    row the $keyfield index is taken as the key for the
 *                    return array. This way, you can create a direct
 *                    mapping between some id field and its row in the
 *                    return data. Mind that there is no error checking
 *                    at all, so you have to make sure that you provide
 *                    a valid $keyfield here!
 * @param $flags    - Special flags for modifying the function's behavior.
 *                    These flags can be OR'ed if multiple flags are needed.
 *                    DB_NOCONNECTOK      Failure to connect is not fatal but
 *                                        lets the call return FALSE (useful
 *                                        in combination with DB_RETURN_CONN).
 *                    DB_MISSINGTABLEOK   Missing table errors are not fatal.
 *                    DB_DUPFIELDNAMEOK   Duplicate field errors are not fatal.
 *                    DB_DUPKEYNAMEOK     Duplicate key errors are not fatal.
 *
 * @return $res     - The result of the query, based on the $return parameter.
 */
function phorum_db_interact($return, $sql = NULL, $keyfield = NULL, $flags = 0)
{
    static $conn;

    // Return a quoted parameter.
    if ($return === DB_RETURN_QUOTED) {
        return mysql_escape_string($sql);
    }

    // Setup a database connection if no database connection is available yet.
    if (empty($conn))
    {
        $PHORUM = $GLOBALS['PHORUM'];

        $conn = mysql_connect(
            $PHORUM['DBCONFIG']['server'],
            $PHORUM['DBCONFIG']['user'],
            $PHORUM['DBCONFIG']['password'],
            TRUE
        );
        if ($conn === FALSE) {
            if ($flags & DB_NOCONNECTOK) return FALSE;
            phorum_database_error('Failed to connect to the database.');
            exit;
        }
        if (mysql_select_db($PHORUM['DBCONFIG']['name'], $conn) === FALSE) {
            if ($flags & DB_NOCONNECTOK) return FALSE;
            phorum_database_error('Failed to select the database.');
            exit;
        }
    }

    // RETURN: database connection handle
    if ($return === DB_RETURN_CONN) {
        return $conn;
    }

    // By now, we really need a SQL query.
    if ($sql === NULL) trigger_error(
        'Internal error: phorum_db_interact(): ' .
        'missing sql query statement!', E_USER_ERROR
    );

    // Execute the SQL query.
    if (($res = mysql_query($sql, $conn)) === FALSE)
    {
        // See if the $flags tell us to ignore the error.
        $ignore_error = FALSE;
        switch (mysql_errno($conn))
        {
            // Table does not exist.
            case 1146:
              if ($flags & DB_MISSINGTABLEOK) $ignore_error = TRUE;
              break;

            // Duplicate column name.
            case 1060:
              if ($flags & DB_DUPFIELDNAMEOK) $ignore_error = TRUE;
              break;

            // Duplicate key name.
            case 1061:
              if ($flags & DB_DUPKEYNAMEOK) $ignore_error = TRUE;
              break;
        }

        // Handle this error if it's not to be ignored.
        if (! $ignore_error)
        {
            $err = mysql_error($conn);

            // RETURN: error message or NULL
            if ($return === DB_RETURN_ERROR) return $err;     

            // Trigger an error.
            phorum_database_error("$err: $sql");
            exit;
        }
    }

    // RETURN: error message or NULL
    if ($return === DB_RETURN_ERROR) {
        return NULL;
    }

    // RETURN: query resource handle
    if ($return === DB_RETURN_RES) {
        return $res;
    }

    // RETURN: number of rows
    elseif ($return === DB_RETURN_ROWCOUNT) {
        return mysql_num_rows($res);
    }

    // RETURN: array rows or single value
    elseif ($return === DB_RETURN_ROW ||
            $return === DB_RETURN_ROWS ||
            $return === DB_RETURN_VALUE)
    {
        // Keyfields are only valid for DB_RETURN_ROWS.
        if ($return !== DB_RETURN_ROWS) $keyfield = NULL;

        $rows = array();
        while ($row = mysql_fetch_row($res)) {
            if ($keyfield === NULL) {
                $rows[] = $row;
            } else {
                $rows[$row[$keyfield]] = $row;
            }
        }

        // Return all rows.
        if ($return === DB_RETURN_ROWS) {
            return $rows;
        }

        // Return a single row.
        if ($return === DB_RETURN_ROW) {
            if (count($rows) == 0) {
                return NULL;
            } else {
                return $rows[0];
            }
        }

        // Return a single value.
        if (count($rows) == 0) {
            return NULL;
        } else {
            return $rows[0][0];
        }
    }

    // RETURN: associative array rows
    elseif ($return === DB_RETURN_ASSOC ||
            $return === DB_RETURN_ASSOCS)
    {
        // Keyfields are only valid for DB_RETURN_ASSOCS.
        if ($return !== DB_RETURN_ASSOCS) $keyfield = NULL;

        $rows = array();
        while ($row = mysql_fetch_assoc($res)) {
            if ($keyfield === NULL) {
                $rows[] = $row;
            } else {
                $rows[$row[$keyfield]] = $row;
            }
        }

        // Return all rows.
        if ($return === DB_RETURN_ASSOCS) {
            return $rows;
        }

        // Return a single row.
        if ($return === DB_RETURN_ASSOC) {
            if (count($rows) == 0) {
                return NULL;
            } else {
                return $rows[0];
            }
        }
    }

    // RETURN: new id after inserting a new record
    elseif ($return === DB_RETURN_NEWID) {
        return mysql_insert_id($conn);
    }

    trigger_error(
        'Internal error: phorum_db_interact(): ' .
        'illegal return type specified!', E_USER_ERROR
    );
}

?>
