<?php
/*

This file holds the configuration for letting Phorum connect to your
database server. If you are running your site with a hosting provider
and you do not know what to fill in here, please contact your hosting
provider for advice.

*/

if (!defined('PHORUM')) return;

$PHORUM['DBCONFIG']=array(

    // Database connection. See the end of this file for a detailed
    // description of the configuration options.
    'type'          => 'mysql',
    'name'          => 'phorum5',
    'server'        => 'localhost',
    'user'          => 'phorum5',
    'password'      => 'phorum5',
    'table_prefix'  => 'phorum',
    'port'          => '3306',
    'socket'        => NULL,

    // An optional URL to redirect the user to when the database is down.
    // If you want to use this option, then remove the "//" in front of it.
    //'down_page'     => 'http://www.example.com/phorum/down.html',

    // An optional URL to redirect the user to when the database has to be
    // upgraded. If you want to use this option, then remove the "//"
    // in front of it.
    //'upgrade_page'  => 'http://www.example.com/phorum/upgrade.html',


    // Specific options for the "mysql" database layer type.
    // -----------------------------------------------------------------

    // Use which MySQL PHP extension? Either NULL, "mysql", "mysqli" or
    // "mysqli_replication" for master/slave setups.
    // If NULL, Phorum will autodetect the extension to use.
    // See the end of this file for a detailed description of this option.
    'mysql_php_extension' =>  NULL,

    // Full text searching? 1=enabled, 0=disabled
    // This option determines whether Phorum will use MySQL's full text
    // algorithm for searching postings. If enabled, searching for postings
    // will be much faster. You will have to disable this feature in case
    // you are running a database version prior to MySQL 4.0.18.
    'mysql_use_ft'  =>  '1',

    // Don't populate the search table for mysql fulltext search
    // (useful if you use a alternate search backend which doesn't use
    // the search-table).
    'empty_search_table' => '0',

    // Specifies the charset used for the "CREATE TABLE" statements and
    // the connection later on. For a list of valid MySQL charsets, see
    // http://dev.mysql.com/doc/refman/5.0/en/charset-charsets.html
    // or run the MySQL query "SHOW CHARACTER SET".
    // Beware: the charset names are different from the ones that are
    // used for specifying web site charsets. For example "utf-8" is not
    // a valid charset.
    'charset' => 'utf8',

    // For master/slave setup (if the "mysql_php_extension" option is set to
    // "mysqli_replication") you need to specify the slave servers to use.
    // If you do not do this, all queries will go to the master server anyway.
    // See the end of this file for a detailed description of this option.
    'slaves' => array(),

);

/*

DETAILED CONFIGURATION FIELD DESCRIPTION
----------------------------------------

* type:

  The type of database. Typically 'mysql' (the only database type which
  is officially supported by the Phorum distribution). If your PHP version
  supports the "mysqli" extension, then do not change this field to "mysqli".
  Instead use the "mysql_php_extension" option.

* name:

  The name of the database.

* server:

  The hostname or IP-address of the database server. You only need to
  change this if the database server is running on a different system.

* user:

  The username which is used for accessing the database server. The
  user must have full access rights to the database, for creating and
  maintaining the needed tables.

* password:

  The password for the database user.

* table_prefix:

  This table prefix will be at the front of all table names that are
  created and used by Phorum. You only need to change this in case you
  are using the same database for multiple Phorum installations.
  By changing the table prefix you can prevent the tables from the
  installations from colliding. E.g. set the table prefix for one
  installation to "phorum1" and the other to "phorum2".
  Important: never change the table prefix for a running system.

* port:

  The mysql (network) port to use. 3306 is the MySQL default.

* socket

  The UNIX socket file path to use for the connection. If this one
  is set to a value other than NULL and the server is set to
  "localhost", then Phorum will connect to the MySQL server
  through the provided socket path.

* mysql_php_extension (MySQL-only option):

  The MySQL database layer has multiple extesions for supporting
  various MySQL connection types. Options are:
  NULL     : Automatically detect the extension to use.
  "mysql"  : Use the PHP mysql extension (php.ini needs a line like
             "extension=mysql.so" for this to work).
  "mysqli" : Use the PHP mysqli extension (php.ini needs a line like
             "extension=mysqli.so" for this to work).
  "mysqli_replication" : Use the PHP mysqli extension and enable
             master/slave functionality (see below with "slaves")

* slaves (for the mysql_php_extension option "mysqli_replication-only"):

  This array can hold a number of slave-servers to use in master/slave mode.
  All write queries will go to the default server above, which is the master-
  server in this setup and read-load will be split randomly over the slave-
  servers specified in this array. If you want your master-server to get read-
  load as well you need to specify it here too.
  Some read-queries will go to the master-server anyway in case they are
  vulnerable to replication-lag.

  Example:
    'slaves'=>array(
        array(    // Database connection 1.
        'name'          =>  'phorum5',
        'server'        =>  '192.168.1.5',
        'user'          =>  'phorum5',
        'password'      =>  'phorum5',
        'port'          =>  '3306',
        'socket'        =>  NULL),
        array(    // Database connection 2.
        'name'          =>  'phorum5',
        'server'        =>  '192.168.1.6',
        'user'          =>  'phorum5',
        'password'      =>  'phorum5',
        'port'          =>  '3306',
        'socket'        =>  NULL),
        array(    // Database connection 3.
        'name'          =>  'phorum5',
        'server'        =>  '192.168.1.7',
        'user'          =>  'phorum5',
        'password'      =>  'phorum5',
        'port'          =>  '3306',
        'socket'        =>  NULL)
    )

*/
?>
