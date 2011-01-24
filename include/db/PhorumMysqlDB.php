<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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

/**
 * This script implements the MySQL database layer for Phorum.
 */

/**
 * The PhorumMysqlDB class, which implements the MySQL database
 * layer for Phorum.
 */
class PhorumMysqlDB extends PhorumDB
{
    /**
     * For the MySQL layer, multiple types of low level implementations
     * are available. The implementation to use is determined by the
     * db layer configuration option "mysql_php_extension".
     *
     * This constructor for the PhorumMysqlDB will load the required
     * low level implementation code at construction time.
     */
    public function __construct()
    {
        global $PHORUM;

        $ext = NULL;

        // Check for a configured extension to use.
        if (isset($PHORUM['DBCONFIG']['mysql_php_extension'])) {
           $ext = basename($PHORUM['DBCONFIG']['mysql_php_extension']);
        }
        // Check for the mysqli extension.
        elseif (function_exists('mysqli_connect')) {
           $ext = "mysqli";
        }
        // Check for the mysql extension.
        elseif (function_exists('mysql_connect')) {
           $ext = "mysql";

           // build the right hostname for the mysql extension
           // not having separate args for port and socket
           if (!empty($PHORUM['DBCONFIG']['socket'])) {
               $PHORUM['DBCONFIG']['server'].=":".$PHORUM['DBCONFIG']['socket'];
           } elseif (!empty($PHORUM['DBCONFIG']['port'])) {
               $PHORUM['DBCONFIG']['server'].=":".$PHORUM['DBCONFIG']['port'];
           }
        }
        // No PHP MySQL extension was found.
        // We are very much out of luck.
        else trigger_error(
           "The Phorum MySQL database layer is unable to determine the PHP " .
           "MySQL extension to use. This might indicate that there is no " .
           "extension loaded from the php.ini.",
           E_USER_ERROR
        );

        // Load the specific code for the PHP extension that we use.
        $extfile = PHORUM_PATH . "/include/db/PhorumMysqlDB/{$ext}.php";
        if (!file_exists($extfile)) trigger_error(
           "The Phorum MySQL database layer is unable to find the extension " .
           "file $extfile on the system. Check if all Phorum files are " .
           "uploaded and if you did specify the correct " .
           "\"mysql_php_extension\" in the file include/config/database.php " .
           "(valid options are \"mysql\", \"mysqli\" and " .
           "\"mysqli_replication\").",
           E_USER_ERROR
        );

        include $extfile;

        $ext_class = "PhorumMysqlDB_{$ext}";
        $this->extension = new $ext_class;
    }

    /**
     * The interact() method is relayed to the MySQL layer extension object.
     */
    public function interact(
        $return, $sql = NULL, $keyfield = NULL, $flags = 0)
    {
        return $this->extension->interact($return, $sql, $keyfield, $flags);
    }

    /**
     * The fetch_row() method is relayed to the MySQL layer extension object.
     */
    public function fetch_row($res, $type)
    {
        return $this->extension->fetch_row($res, $type);
    }
}

?>
