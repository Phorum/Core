<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if (!defined('PHORUM')) return;

/**
 * This script implements the mysqli_replication extension for the
 * MySQL database layer.
 */

/**
 * The PhorumMysqlDB_mysqli_extension class, which implements the
 * mysqli_extension extension for the MySQL database layer.
 */
class PhorumMysqlDB_mysqli_replication extends PhorumDB
{
    /**
     * This method is the central method for handling database
     * interaction. The method can be used for setting up a database
     * connection, for running a SQL query and for returning query rows.
     * Which of these actions the method will handle and what the method
     * return data will be, is determined by the $return method parameter.
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
     *                                        failed or NULL if there was
     *                                        no error
     *                    DB_CLOSE_CONN       close the connection, no
     *                                        return data
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
     * @param $flags    - Special flags for modifying the method's behavior.
     *                    These flags can be OR'ed if multiple flags are needed.
     *                    DB_NOCONNECTOK     Failure to connect is not fatal
     *                                       but lets the call return FALSE
     *                                       (useful in combination with
     *                                       DB_RETURN_CONN).
     *                    DB_MISSINGTABLEOK  Missing table errors not fatal.
     *                    DB_DUPFIELDNAMEOK  Duplicate field errors not fatal.
     *                    DB_DUPKEYNAMEOK    Duplicate key name errors
     *                                       not fatal.
     *                    DB_DUPKEYOK        Duplicate key errors not fatal.
     *
     * @return $res     - The result of the query, based on the $return
     *                    parameter.
     */
    public function interact(
        $return, $sql = NULL, $keyfield = NULL, $flags = 0,
        $limit = 0, $offset = 0)
    {
        static $querytrack;
        static $conn_read;
        static $conn_write;
        global $PHORUM;

        // Close the database connection.
        if ($return == DB_CLOSE_CONN)
        {
            if (!empty($conn_read))
            {
                mysqli_close($conn_read);
                $conn_read = null;
            }
            if (!empty($conn_write))
            {
                mysqli_close($conn_write);
                $conn_write = null;
            }
            return;
        }

        $debug = empty($GLOBALS['PHORUM']['DBCONFIG']['dbdebug'])
               ? 0 : $GLOBALS['PHORUM']['DBCONFIG']['dbdebug'];

        if (!empty($debug)) {
            if (!isset($querytrack) || !is_array($querytrack)) {
                $querytrack = array(
                    'count'   => 0,
                    'time'    => 0,
                    'queries' => array()
                );
            }
        }

        $set_names = empty($PHORUM['DBCONFIG']['charset'])
                   ? NULL : "SET NAMES '{$PHORUM['DBCONFIG']['charset']}'";

        if (  !($flags & DB_MASTERQUERY) &&
             !empty($PHORUM['DBCONFIG']['slaves']) &&
             is_array($PHORUM['DBCONFIG']['slaves'])
          ) {

            if (empty($conn_read)) {

                $conn_read = $this->get_random_connection(
                    $PHORUM['DBCONFIG']['slaves']);

                if ($set_names) mysqli_query($conn_read, $set_names);
            }

            $conn = $conn_read;
        }
        else
        {
            // masterquery aka write-query
            // try to connect to the master

            if (empty($conn_write)) {

                if (!empty($PHORUM['DBCONFIG']['masters']) &&
                    is_array($PHORUM['DBCONFIG']['masters'])) {

                    $conn_write = $this->get_random_connection(
                        $PHORUM['DBCONFIG']['masters']);

                } else {
                    // we suppress errors from the mysqli_connect command
                    // as errors are catched differently.
                    $conn_write = mysqli_connect(
                        $PHORUM['DBCONFIG']['server'],
                        $PHORUM['DBCONFIG']['user'],
                        $PHORUM['DBCONFIG']['password'],
                        $PHORUM['DBCONFIG']['name'],
                        $PHORUM['DBCONFIG']['port'],
                        $PHORUM['DBCONFIG']['socket']
                        );
                }

                if ($set_names) mysqli_query($conn_write, $set_names);
            }

            $conn = $conn_write;
        }

        if ($debug && $set_names) {
            $querytrack['count'] += 2;
            if ($debug > 1) {
                $querytrack['queries'][] = array(
                    'number'     => '001',
                    'query'      => htmlspecialchars($set_names),
                    'raw_query'  => $set_names,
                    'time'       => '0.000'
                );
            }
        }

        // Setup a database connection if no database connection is
        // available yet.
        if (empty($conn))
        {
            if ($conn === FALSE) {
                if ($flags & DB_NOCONNECTOK) return FALSE;
                phorum_api_error(
                    PHORUM_ERRNO_DATABASE,
                    'Failed to connect to the database.'
                );
                exit;
            }

            // putting this here for testing mainly
            // All of Phorum should work in strict mode
            if (!empty($PHORUM["DBCONFIG"]["strict_mode"])){
                mysqli_query($conn, "SET SESSION sql_mode='STRICT_ALL_TABLES'");
            }

        }

        // RETURN: quoted parameter.
        if ($return === DB_RETURN_QUOTED) {
            return mysqli_real_escape_string($conn, $sql);
        }

        // RETURN: database connection handle
        if ($return === DB_RETURN_CONN) {
            return $conn;
        }

        // By now, we really need a SQL query.
        if ($sql === NULL) trigger_error(
            __METHOD__ . ': Internal error: ' .
            'missing sql query statement!', E_USER_ERROR
        );

        // Apply limit and offset to the query.
        settype($limit, 'int');
        settype($offset, 'int');
        if ($limit  > 0) $sql .= " LIMIT $limit";
        if ($offset > 0) $sql .= " OFFSET $offset";

        // Execute the SQL query.

        $tries = 0;

        $res = FALSE;

        while ($res === FALSE && $tries < 3)
        {
            // Time the query for debug level 2 and up.
            if ($debug > 1) {
                $t1 = microtime(TRUE);
            }
            // For queries where we are going to retrieve multiple rows, we
            // use an unuffered query result.
            if ($return === DB_RETURN_ASSOCS || $return === DB_RETURN_ROWS) {
                $res = FALSE;
                if (mysqli_real_query($conn, $sql) !== FALSE) {
                    $res = mysqli_use_result($conn);
                }
            } else {
                $res = mysqli_query($conn, $sql);
            }

            // Execute the SQL query.
            if ($debug) {
                $querytrack['count']++;
                if ($debug > 1) {
                    $t2 = microtime(TRUE);
                    $time = sprintf("%0.3f", $t2 - $t1);
                    $querytrack['time'] += $time;
                    $querytrack['queries'][] = array(
                        'number'     => sprintf("%03d", $querytrack['count']),
                        'query'      => htmlspecialchars($sql),
                        'raw_query'  => $sql,
                        'time'       => $time
                    );
                }
                $GLOBALS['PHORUM']['DATA']['DBDEBUG'] = $querytrack;
            }

            // Handle errors.
            if ($res === FALSE)
            {
                $errno = mysqli_errno($conn);

                // if we have an error due to a transactional storage engine,
                // retry the query for those errors up to 2 more times
                if ($tries < 3 &&
                    ($errno == 1422 ||  // 1422 Explicit or implicit commit
                                        // is not allowed in stored function
                                        // or trigger.
                    $errno == 1213 ||   // 1213 Deadlock found when trying
                                        // to get lock; try restarting
                                        // transaction
                    $errno == 1205)) {  // 1205 Lock wait timeout

                    $tries++;

                } else {
                    // See if the $flags tell us to ignore the error.
                    $ignore_error = FALSE;
                    switch ($errno)
                    {
                        // Table does not exist.
                        case 1146:
                            if ($flags & DB_MISSINGTABLEOK) {
                                $ignore_error = TRUE;
                            }
                            break;

                            // Table already exists.
                        case 1050:
                            if ($flags & DB_TABLEEXISTSOK) {
                                $ignore_error = TRUE;
                            }
                            break;

                            // Duplicate column name.
                        case 1060:
                            if ($flags & DB_DUPFIELDNAMEOK) {
                                $ignore_error = TRUE;
                            }
                            break;

                            // Duplicate key name.
                        case 1061:
                            if ($flags & DB_DUPKEYNAMEOK) {
                                $ignore_error = TRUE;
                            }
                            break;

                            // Duplicate entry for key.
                        case 1062:
                            // For MySQL server versions 5.1.15 up to 5.1.20.
                            // See bug #28842
                            // (http://bugs.mysql.com/bug.php?id=28842)
                        case 1582:
                            if ($flags & DB_DUPKEYOK) $ignore_error = TRUE;
                            break;
                    }

                    // Handle this error if it's not to be ignored.
                    if (! $ignore_error)
                    {
                        $err = mysqli_error($conn);

                        // RETURN: error message.
                        if ($return === DB_RETURN_ERROR) return $err;

                        // Trigger an error.
                        phorum_api_error(
                            PHORUM_ERRNO_DATABASE,
                            "$err ($errno): $sql"
                        );
                        exit;
                    }

                    // break while
                    break;
                }
            }
        }

        // RETURN: NULL (no error).
        if ($return === DB_RETURN_ERROR) {
            return NULL;
        }

        // RETURN: query resource handle
        if ($return === DB_RETURN_RES) {
            return $res;
        }

        // RETURN: number of rows
        if ($return === DB_RETURN_ROWCOUNT) {
            return $res ? mysqli_num_rows($res) : 0;
        }

        // RETURN: array rows or single value
        if ($return === DB_RETURN_ROW ||
            $return === DB_RETURN_ROWS ||
            $return === DB_RETURN_VALUE)
        {
            // Keyfields are only valid for DB_RETURN_ROWS.
            if ($return !== DB_RETURN_ROWS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = mysqli_fetch_row($res)) {
                    if ($keyfield === NULL) {
                        $rows[] = $row;
                    } else {
                        $rows[$row[$keyfield]] = $row;
                    }
                }
            }

            // Return all rows.
            if ($return === DB_RETURN_ROWS) {
                /* Might be FALSE in case of ignored errors. */
                if (!is_bool($res)) mysqli_free_result($res);
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
        if ($return === DB_RETURN_ASSOC ||
            $return === DB_RETURN_ASSOCS)
        {
            // Keyfields are only valid for DB_RETURN_ASSOCS.
            if ($return !== DB_RETURN_ASSOCS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    if ($keyfield === NULL) {
                        $rows[] = $row;
                    } else {
                        $rows[$row[$keyfield]] = $row;
                    }
                }
            }

            // Return all rows.
            if ($return === DB_RETURN_ASSOCS) {
                /* Might be FALSE in case of ignored errors. */
                if (!is_bool($res)) mysqli_free_result($res);
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
        if ($return === DB_RETURN_NEWID) {
            return mysqli_insert_id($conn);
        }

        trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal return type specified!', E_USER_ERROR
        );
    }

    /**
     * This method is the central method for handling database
     * interaction. The method can be used for setting up a database
     * connection, for running a SQL query and for returning query rows.
     * Which of these actions the method will handle and what the method
     * return data will be, is determined by the $return method parameter.
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
     *                                        failed or NULL if there was
     *                                        no error
     *                    DB_CLOSE_CONN       close the connection, no
     *                                        return data
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
     * @param $flags    - Special flags for modifying the method's behavior.
     *                    These flags can be OR'ed if multiple flags are needed.
     *                    DB_NOCONNECTOK     Failure to connect is not fatal
     *                                       but lets the call return FALSE
     *                                       (useful in combination with
     *                                       DB_RETURN_CONN).
     *                    DB_MISSINGTABLEOK  Missing table errors not fatal.
     *                    DB_DUPFIELDNAMEOK  Duplicate field errors not fatal.
     *                    DB_DUPKEYNAMEOK    Duplicate key name errors
     *                                       not fatal.
     *                    DB_DUPKEYOK        Duplicate key errors not fatal.
     *
     * @return $res     - The result of the query, based on the $return
     *                    parameter.
     */
    public function fetch_row($res, $type)
    {
        if ($type === DB_RETURN_ASSOC) {
            $row = mysqli_fetch_assoc($res);
        } elseif ($type === DB_RETURN_ROW) {
            $row = mysqli_fetch_row($res);
        } else trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal \$type parameter used', E_USER_ERROR
        );

        return $row ? $row : NULL;
    }

    protected function get_random_connection(&$db_array)
    {
        // loop the servers until we get a connect
        // this could slow you down if you have a lot of downed servers
        while (!$conn && count($db_array)){

            $rand_server = mt_rand(0, count($db_array));

            // just in case someone did non-contiguous keys
            if (!empty($db_array[$rand_server])) {

                $server_data = $db_array[$rand_server];

                // we suppress errors from the mysqli_connect command as errors
                // are catched differently.
                $conn = mysqli_connect(
                    $server_data['server'],
                    $server_data['user'],
                    $server_data['password'],
                    $server_data['name'],
                    $server_data['port'],
                    $server_data['socket']
                );
                if (!$conn){
                    // if we could not connect, remove this server
                    // from the array for this request.
                    unset($db_array[$rand_server]);

                    // to get the keys renumbered
                    sort($db_array);
                }
            }
        }

        return $conn;
    }
}
?>
