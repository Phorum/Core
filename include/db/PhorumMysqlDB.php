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
    // {{{ Method:: __construct()
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
    // }}}

    // {{{ Method: interact()
    /**
     * The interact() method is relayed to the MySQL layer extension object.
     */
    public function interact(
        $return, $sql = NULL, $keyfield = NULL, $flags = 0)
    {
        return $this->extension->interact($return, $sql, $keyfield, $flags);
    }
    // }}}

    // {{{ Method: fetch_row()
    /**
     * The fetch_row() method is relayed to the MySQL layer extension object.
     */
    public function fetch_row($res, $type)
    {
        return $this->extension->fetch_row($res, $type);
    }
    // }}}

    // {{{ Method: maxpacketsize()
    /**
     * This function is used by the sanity checking system in the admin
     * interface to determine how much data can be transferred in one query.
     * This is used to detect problems with uploads that are larger than the
     * database server can handle. The function returns the size in bytes.
     *
     * @return integer
     *     The maximum packet size in bytes or NULL if there is no limit.
     */
    public function maxpacketsize()
    {
        $maxsize = $this->interact(
            DB_RETURN_VALUE,
            'SELECT @@global.max_allowed_packet',
            NULL,
            DB_MASTERQUERY
        );

        return $maxsize;
    }
    // }}}

    // {{{ Method: sanitychecks()
    /**
     * This function is used by the sanity checking system to let the
     * database layer do sanity checks of its own. This function can
     * be used by every database layer to implement specific checks.
     *
     * The return value for this function should be exactly the same
     * as the return value expected for regular sanity checking
     * function (see include/admin/sanity_checks.php for information).
     *
     * There's no need to load the sanity_check.php file for the needed
     * constants, because this function should only be called from the
     * sanity checking system.
     *
     * @return array
     *     A return value as expected by Phorum's sanity checking system.
     */
    public function sanitychecks()
    {
        global $PHORUM;

        // For Phorum 5.2+, we need the "charset" option to be set
        // in the include/config/database.php.
        if (!isset($PHORUM['DBCONFIG']['charset'])) return array(
            PHORUM_SANITY_CRIT,
            "Database configuration parameter \"charset\" missing.",
            "The option \"charset\" is missing in your database configuration.
             This might indicate that you are using an
             include/config/database.php from an older Phorum version, which does
             not yet contain this option. Please, copy
             include/config/database.php.sample to
             include/config/database.php and edit this new database.php. Read
             Phorum's install.txt for installation instructions."
        );

        // Retrieve the MySQL server version.
        $version = $this->interact(
            DB_RETURN_VALUE,
            'SELECT @@global.version',
            NULL,
            DB_MASTERQUERY
        );
        if (!$version) return array(
            PHORUM_SANITY_WARN,
            "The database layer could not retrieve the version of the
             running MySQL server",
            "This probably means that you are running a really old MySQL
             server, which does not support \"SELECT @@global.version\"
             as a SQL command. If you are not running a MySQL server
             with version 4.0.18 or higher, then please upgrade your
             MySQL server. Else, contact the Phorum developers to see
             where this warning is coming from"
        );

        // See if we recognize the version numbering.
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $ver)) return array(
            PHORUM_SANITY_WARN,
            "The database layer was unable to recognize the MySQL server's
             version number \"" . htmlspecialchars($version) . "\". Therefore,
             checking if the right version of MySQL is used is not possible.",
            "Contact the Phorum developers and report this specific
             version number, so the checking scripts can be updated."
        );

        // MySQL before version 4.
        if ($ver[1] < 5) return array(
            PHORUM_SANITY_CRIT,
            "The MySQL database server that is used is too old. The
             running version is \"" . htmlspecialchars($version) . "\",
             while MySQL version 5.0.x or higher is required.",
            "Upgrade your MySQL server to a newer version. If your
             website is hosted with a service provider, please contact
             the service provider to upgrade your MySQL database."
        );

        // All checks are okay.
        return array (PHORUM_SANITY_OK, NULL);
    }
    // }}}
}

?>
