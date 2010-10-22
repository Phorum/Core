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

if (!defined('PHORUM')) return;

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
 *                    DB_CLOSE_CONN       close the connection, no return data
 *
 * @param $sql      - The SQL query to run or the parameter to quote if
 *                    DB_RETURN_QUOTED is used.
 *
 * @param $keyfield - When returning an array of rows, the indexes are
 *                    numerical by default (0, 1, 2, etc.). However, if
 *                    the $keyfield parameter is set, then from each
 *                    row the $keyfield index is taken as the key for the
 *                    return array. This way, you can create a direct
 *                    mapping between some id field and its row in the
 *                    return data. Mind that there is no error checking
 *                    at all, so you have to make sure that you provide
 *                    a valid $keyfield here!
 *
 * @param $flags    - Special flags for modifying the function's behavior.
 *                    These flags can be OR'ed if multiple flags are needed.
 *                    DB_NOCONNECTOK     Failure to connect is not fatal but
 *                                       lets the call return FALSE (useful
 *                                       in combination with DB_RETURN_CONN).
 *                    DB_MISSINGTABLEOK  Missing table errors not fatal.
 *                    DB_DUPFIELDNAMEOK  Duplicate field errors not fatal.
 *                    DB_DUPKEYNAMEOK    Duplicate key name errors not fatal.
 *                    DB_DUPKEYOK        Duplicate key errors not fatal.
 *
 * @return $res     - The result of the query, based on the $return parameter.
 */
function phorum_db_interact($return, $sql = NULL, $keyfield = NULL, $flags = 0)
{
    static $conn;

    // Close the database connection.
    if ($return == DB_CLOSE_CONN)
    {
        if (!empty($conn))
        {
            mysql_close($conn);
            $conn = null;
        }
        return;
    }

    // Setup a database connection if no database connection is available yet.
    if (empty($conn))
    {
        $PHORUM = $GLOBALS['PHORUM'];
        
        // we suppress errors from the mysql_connect command as errors
        // are catched differently.
        $conn = @mysql_connect(
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
        if(!empty($PHORUM['DBCONFIG']['charset'])) {
            mysql_query( "SET NAMES '{$PHORUM['DBCONFIG']['charset']}'",$conn);
        }

    }

    // RETURN: database connection handle
    if ($return === DB_RETURN_CONN) {
        return $conn;
    }

    // Return a quoted parameter.
    if ($return === DB_RETURN_QUOTED) {
        return mysql_real_escape_string($sql, $conn);
    }

    // By now, we really need a SQL query.
    if ($sql === NULL) trigger_error(
        'Internal error: phorum_db_interact(): ' .
        'missing sql query statement!', E_USER_ERROR
    );

    // Execute the SQL query.

    $tries = 0;

    $res = false;

    while($res === FALSE){

        // For queries where we are going to retrieve multiple rows, we
        // use an unuffered query result.

        $res = $return === DB_RETURN_ASSOCS ||
               $return === DB_RETURN_ROWS
             ? mysql_unbuffered_query($sql, $conn)
             : mysql_query($sql, $conn);

        if ($res === FALSE){

            $errno = mysql_errno($conn);

            // if we have an error due to a transactional storage engine,
            // retry the query for those errors up to 2 more times
            if ($tries<3 &&
                ($errno == 1422 ||  // 1422 Explicit or implicit commit is not allowed in stored function or trigger.
                 $errno == 1213 ||  // 1213 Deadlock found when trying to get lock; try restarting transaction
                 $errno == 1205)) { // 1205 Lock wait timeout

                $tries++;

            } else {

                // See if the $flags tell us to ignore the error.

                $ignore_error = FALSE;

                switch ($errno)
                {
                    // Table does not exist.
                    case 1146:
                      if ($flags & DB_MISSINGTABLEOK) $ignore_error = TRUE;
                      break;

                    // Table already exists.
                    case 1050:
                      if ($flags & DB_TABLEEXISTSOK) $ignore_error = TRUE;
                      break;

                    // Duplicate column name.
                    case 1060:
                      if ($flags & DB_DUPFIELDNAMEOK) $ignore_error = TRUE;
                      break;

                    // Duplicate key name.
                    case 1061:
                      if ($flags & DB_DUPKEYNAMEOK) $ignore_error = TRUE;
                      break;

                    // Duplicate entry for key.
                    case 1062:
                    // For MySQL server versions 5.1.15 up to 5.1.20.
                    // See bug #28842 (http://bugs.mysql.com/bug.php?id=28842)
                    case 1582:
                      if ($flags & DB_DUPKEYOK) $ignore_error = TRUE;
                      break;
                }

                // Handle this error if it's not to be ignored.
                if (! $ignore_error)
                {
                    $err = mysql_error($conn);

                    // RETURN: error message or NULL
                    if ($return === DB_RETURN_ERROR) return $err;

                    // Trigger an error.
                    phorum_database_error("$err ($errno): $sql");
                    exit;
                }

                // break while
                break;

            }


        }

    }



    if ($res === FALSE)
    {
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
        return $res ? mysql_num_rows($res) : 0;
    }

    // RETURN: array rows or single value
    elseif ($return === DB_RETURN_ROW ||
            $return === DB_RETURN_ROWS ||
            $return === DB_RETURN_VALUE)
    {
        // Keyfields are only valid for DB_RETURN_ROWS.
        if ($return !== DB_RETURN_ROWS) $keyfield = NULL;

        $rows = array();
        if ($res) {
            while ($row = mysql_fetch_row($res)) {
                if ($keyfield === NULL) {
                    $rows[] = $row;
                } else {
                    $rows[$row[$keyfield]] = $row;
                }
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
        if ($res) {
            while ($row = mysql_fetch_assoc($res)) {
                if ($keyfield === NULL) {
                    $rows[] = $row;
                } else {
                    $rows[$row[$keyfield]] = $row;
                }
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

/**
 * Return a single row from a query result. This function can be used
 * if a lot of rows have to be processed one by one, in which case the
 * DB_RETURN_ROWS and DB_RETURN_ASSOCS return types for the
 * {@link phorum_db_interact()} function might consume lots of memory.
 *
 * @param resource $res
 *     The result set resource handle. This is the return value of the
 *     function {@link phorum_db_interact()}, when running a query
 *     with the DB_RETURN_RES return type.
 *
 * @param integer $type
 *     A flag, which indicates what type of row has to be returned.
 *     One of {@link DB_RETURN_ASSOC} or {@link DB_RETURN_ROW}, which
 *     will let this function return respectively an associative array
 *     (field name -> value) or an array (field index -> value).
 *
 * @return mixed
 *     This function returns either an array containing a single row
 *     or NULL if there are no more rows to retrieve.
 */
function phorum_db_fetch_row($res, $type)
{
    if ($type === DB_RETURN_ASSOC) {
        $row = mysql_fetch_assoc($res);
    } elseif ($type === DB_RETURN_ROW) {
        $row = mysql_fetch_row($res);
    } else trigger_error(
        'Internal error: phorum_db_fetch_row(): ' .
        'illegal \$type parameter used', E_USER_ERROR
    );

    return $row ? $row : NULL;
}

?>
