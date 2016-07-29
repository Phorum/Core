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

/**
 * This script implements the MySQL database layer for Phorum.
 */

/**
 * The PhorumMysqlDB class, which implements the MySQL database
 * layer for Phorum.
 */
class PhorumMysqlDB extends PhorumDB
{
    // {{{ Properties

    /**
     * Whether or not the database system supports "USE INDEX" in a SELECT
     * @var boolean
     */
    protected $_can_USE_INDEX = TRUE;

    /**
     * Whether or not the database system supports "INSERT DELAYED".
     * @var boolean
     */
    protected $_can_INSERT_DELAYED = TRUE;

    /**
     * Whether or not the database system supports "UPDATE IGNORE".
     * @var boolean
     */
    protected $_can_UPDATE_IGNORE = TRUE;

    /**
     * Whether or not the database system supports "INSERT IGNORE".
     * @var boolean
     */
    protected $_can_INSERT_IGNORE = TRUE;

    /**
     * Whether or not the database system supports "TRUNCATE".
     * @var boolean
     */
    protected $_can_TRUNCATE = TRUE;

    /**
     * Whether or not the database system supports multiple inserts
     * in one command like INSERT INTO .. VALUES (set 1), (set 2), .., (set n).
     * @var boolean
     */
    protected $_can_insert_multiple = TRUE;

    /**
     * The method to use for string concatenation.
     * Either "pipes" (PostgreSQL style) or "concat" (MySQL style, using
     * the concat() function).
     * @var string
     */
    protected $_concat_method = 'pipes';

    // }}}

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
        parent::__construct();

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
        $return, $sql = NULL, $keyfield = NULL, $flags = 0,
        $limit = 0, $offset = 0)
    {
        global $PHORUM;

        // When the 'disable_sql_cache' option is enabled in the database
        // configuration, then rewrite MySQL SELECT queries to make use
        // of the additive SQL_NO_CACHE. This will tell the MySQL database
        // server to run the query, without using the query cache.
        if ($sql !== NULL && !empty($PHORUM['DBCONFIG']['disable_sql_cache'])) {
            $sql = preg_replace(
                '/^\s*select\s/i', 'SELECT SQL_NO_CACHE ', $sql);
        }

        return $this->extension->interact(
            $return, $sql, $keyfield, $flags, $limit, $offset);
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

    // {{{ Method: create_tables()
    /**
     * Create the tables that are needed in the database. This function will
     * only be called at install time. After installation, changes in the
     * database schema will be handled by the database upgrade system.
     *
     * @return mixed
     *     NULL on success or an error message on failure.
     */
    public function create_tables()
    {
        global $PHORUM;

        $lang = PHORUM_DEFAULT_LANGUAGE;

        $charset = empty($PHORUM['DBCONFIG']['charset'])
                 ? ''
                 : "DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";

        $create_table_queries = array(

          "CREATE TABLE {$this->forums_table} (
               forum_id                 int unsigned   NOT NULL auto_increment,
               name                     varchar(50)    NOT NULL default '',
               active                   tinyint(1)     NOT NULL default 0,
               description              text           NOT NULL,
               template                 varchar(50)    NOT NULL default '',
               folder_flag              tinyint(1)     NOT NULL default 0,
               parent_id                int unsigned   NOT NULL default 0,
               list_length_flat         int unsigned   NOT NULL default 0,
               list_length_threaded     int unsigned   NOT NULL default 0,
               moderation               int unsigned   NOT NULL default 0,
               threaded_list            tinyint(1)     NOT NULL default 0,
               threaded_read            tinyint(1)     NOT NULL default 0,
               float_to_top             tinyint(1)     NOT NULL default 0,
               check_duplicate          tinyint(1)     NOT NULL default 0,
               allow_attachment_types   varchar(100)   NOT NULL default '',
               max_attachment_size      int unsigned   NOT NULL default 0,
               max_totalattachment_size int unsigned   NOT NULL default 0,
               max_attachments          int unsigned   NOT NULL default 0,
               pub_perms                int unsigned   NOT NULL default 0,
               reg_perms                int unsigned   NOT NULL default 0,
               display_ip_address       tinyint(1)     NOT NULL default 1,
               allow_email_notify       tinyint(1)     NOT NULL default 1,
               language                 varchar(100)   NOT NULL default '$lang',
               email_moderators         tinyint(1)     NOT NULL default 0,
               message_count            int unsigned   NOT NULL default 0,
               sticky_count             int unsigned   NOT NULL default 0,
               thread_count             int unsigned   NOT NULL default 0,
               last_post_time           int unsigned   NOT NULL default 0,
               display_order            int unsigned   NOT NULL default 0,
               read_length              int unsigned   NOT NULL default 0,
               vroot                    int unsigned   NOT NULL default 0,
               forum_path               text           NOT NULL,
               count_views              tinyint(1)     NOT NULL default 0,
               count_views_per_thread   tinyint(1)     NOT NULL default 0,
               display_fixed            tinyint(1)     NOT NULL default 0,
               reverse_threading        tinyint(1)     NOT NULL default 0,
               inherit_id               int unsigned       NULL default NULL,
               cache_version            int unsigned   NOT NULL default 0,

               PRIMARY KEY (forum_id),
               KEY name (name),
               KEY folder_index (parent_id, vroot, active, folder_flag)
           ) $charset",

          "CREATE TABLE {$this->message_table} (
               message_id               int unsigned   NOT NULL auto_increment,
               forum_id                 int unsigned   NOT NULL default 0,
               thread                   int unsigned   NOT NULL default 0,
               parent_id                int unsigned   NOT NULL default 0,
               user_id                  int unsigned   NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(255)   NOT NULL default '',
               body                     text           NOT NULL,
               email                    varchar(100)   NOT NULL default '',
               ip                       varchar(255)   NOT NULL default '',
               status                   tinyint(4)     NOT NULL default 2,
               msgid                    varchar(100)   NOT NULL default '',
               modifystamp              int unsigned   NOT NULL default 0,
               thread_count             int unsigned   NOT NULL default 0,
               moderator_post           tinyint(1)     NOT NULL default 0,
               sort                     tinyint(4)     NOT NULL default 2,
               datestamp                int unsigned   NOT NULL default 0,
               meta                     mediumtext         NULL,
               viewcount                int unsigned   NOT NULL default 0,
               threadviewcount          int unsigned   NOT NULL default 0,
               closed                   tinyint(1)     NOT NULL default 0,
               recent_message_id        int unsigned   NOT NULL default 0,
               recent_user_id           int unsigned   NOT NULL default 0,
               recent_author            varchar(255)   NOT NULL default '',
               moved                    tinyint(1)     NOT NULL default 0,
               hide_period              int unsigned   NOT NULL default 0,

               PRIMARY KEY (message_id),
               KEY special_threads (sort,forum_id),
               KEY last_post_time (forum_id,status,modifystamp),
               KEY dup_check (forum_id,author(50),subject,datestamp),
               KEY recent_user_id (recent_user_id),
               KEY user_messages (user_id,message_id),
               KEY updated_threads (status,parent_id,modifystamp),
               KEY list_page_flat (forum_id,status,parent_id,datestamp),
               KEY thread_date (thread,datestamp),
               KEY list_page_float (forum_id,status,parent_id,modifystamp),
               KEY forum_recent_messages (forum_id,status,datestamp),
               KEY recent_threads (status,parent_id,datestamp),
               KEY recent_messages (status,datestamp),
               KEY forum_thread_count(forum_id,parent_id,status,moved,message_id),
               KEY forum_message_count(forum_id,status,moved,message_id)
           ) $charset",

          "CREATE TABLE {$this->settings_table} (
               name                     varchar(255)   NOT NULL default '',
               type                     enum('V','S')  NOT NULL default 'V',
               data                     text           NOT NULL,

               PRIMARY KEY (name)
           ) $charset",

          "CREATE TABLE {$this->subscribers_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               sub_type                 tinyint(4)     NOT NULL default 0,
               thread                   int unsigned   NOT NULL default 0,

               PRIMARY KEY (user_id,forum_id,thread),
               KEY forum_id (forum_id,thread,sub_type)
           ) $charset",

          "CREATE TABLE {$this->user_permissions_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               permission               int unsigned   NOT NULL default 0,

               PRIMARY KEY  (user_id,forum_id),
               KEY forum_id (forum_id,permission)
           ) $charset",

          // When creating extra fields, then mind to update the file
          // include/api/custom_field.php script too (it contains a
          // list of reserved names for custom profile fields).
          "CREATE TABLE {$this->user_table} (
               user_id                  int unsigned   NOT NULL auto_increment,
               username                 varchar(50)    NOT NULL default '',
               real_name                varchar(255)   NOT NULL default '',
               display_name             varchar(255)   NOT NULL default '',
               password                 varchar(50)    NOT NULL default '',
               password_temp            varchar(50)    NOT NULL default '',
               sessid_lt                varchar(50)    NOT NULL default '',
               sessid_st                varchar(50)    NOT NULL default '',
               sessid_st_timeout        int unsigned   NOT NULL default 0,
               email                    varchar(100)   NOT NULL default '',
               email_temp               varchar(110)   NOT NULL default '',
               hide_email               tinyint(1)     NOT NULL default 1,
               active                   tinyint(1)     NOT NULL default 0,
               signature                text           NOT NULL,
               threaded_list            tinyint(1)     NOT NULL default 0,
               posts                    int(10)        NOT NULL default 0,
               admin                    tinyint(1)     NOT NULL default 0,
               threaded_read            tinyint(1)     NOT NULL default 0,
               date_added               int unsigned   NOT NULL default 0,
               date_last_active         int unsigned   NOT NULL default 0,
               last_active_forum        int unsigned   NOT NULL default 0,
               hide_activity            tinyint(1)     NOT NULL default 0,
               show_signature           tinyint(1)     NOT NULL default 0,
               email_notify             tinyint(1)     NOT NULL default 0,
               pm_email_notify          tinyint(1)     NOT NULL default 1,
               pm_new_count             int unsigned   NOT NULL default 0,
               tz_offset                float(4,2)     NOT NULL default '-99.00',
               is_dst                   tinyint(1)     NOT NULL default 0,
               user_language            varchar(100)   NOT NULL default '',
               user_template            varchar(100)   NOT NULL default '',
               moderation_email         tinyint(1)     NOT NULL default 1,
               settings_data            mediumtext     NOT NULL,

               PRIMARY KEY (user_id),
               UNIQUE KEY username (username),
               KEY active (active),
               KEY userpass (username,password),
               KEY sessid_st (sessid_st),
               KEY sessid_lt (sessid_lt),
               KEY activity (date_last_active,hide_activity,last_active_forum),
               KEY date_added (date_added),
               KEY email_temp (email_temp)
           ) $charset",

          "CREATE TABLE {$this->user_newflags_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               message_id               int unsigned   NOT NULL default 0,

               PRIMARY KEY  (user_id,forum_id,message_id),
               KEY move (message_id, forum_id)
           ) $charset",

          "CREATE TABLE {$this->groups_table} (
               group_id                 int unsigned   NOT NULL auto_increment,
               name                     varchar(255)   NOT NULL default '',
               open                     tinyint(1)     NOT NULL default '0',

               PRIMARY KEY  (group_id)
           ) $charset",

          "CREATE TABLE {$this->forum_group_xref_table} (
               forum_id                 int unsigned   NOT NULL default 0,
               group_id                 int unsigned   NOT NULL default 0,
               permission               int unsigned   NOT NULL default 0,

               PRIMARY KEY  (forum_id,group_id),
               KEY group_id (group_id)
           ) $charset",

          "CREATE TABLE {$this->user_group_xref_table} (
               user_id                  int unsigned   NOT NULL default 0,
               group_id                 int unsigned   NOT NULL default 0,
               status                   tinyint(4)     NOT NULL default 1,

               PRIMARY KEY  (user_id,group_id)
           ) $charset",

          "CREATE TABLE {$this->files_table} (
               file_id                  int unsigned   NOT NULL auto_increment,
               user_id                  int unsigned   NOT NULL default 0,
               filename                 varchar(255)   NOT NULL default '',
               filesize                 int unsigned   NOT NULL default 0,
               file_data                mediumtext     NOT NULL,
               add_datetime             int unsigned   NOT NULL default 0,
               message_id               int unsigned   NOT NULL default 0,
               link                     varchar(10)    NOT NULL default '',

               PRIMARY KEY (file_id),
               KEY add_datetime (add_datetime),
               KEY message_id_link (message_id,link),
               KEY user_id_link (user_id,link)
           ) $charset",

          "CREATE TABLE {$this->banlist_table} (
               id                       int unsigned   NOT NULL auto_increment,
               forum_id                 int unsigned   NOT NULL default 0,
               type                     tinyint(4)     NOT NULL default 0,
               pcre                     tinyint(1)     NOT NULL default 0,
               string                   varchar(255)   NOT NULL default '',
               comments                 text           NOT NULL,

               PRIMARY KEY (id),
               KEY forum_id (forum_id)
           ) $charset",

          "CREATE TABLE {$this->search_table} (
               message_id               int unsigned   NOT NULL default '0',
               forum_id                 int unsigned   NOT NULL default '0',
               search_text              mediumtext     NOT NULL,

               PRIMARY KEY (message_id),
               KEY forum_id (forum_id),
               FULLTEXT KEY search_text (search_text)
           ) ENGINE=MyISAM $charset",

          "CREATE TABLE {$this->custom_fields_config_table} (
               id                       int unsigned   NOT NULL auto_increment,
               field_type               tinyint(1)     NOT NULL default 1,
               name                     varchar(50)    NOT NULL default '',
               length                   mediumint      NOT NULL default 255,
               html_disabled            tinyint(1)     NOT NULL default 1,
               show_in_admin            tinyint(1)     NOT NULL default 0,
               deleted                  tinyint(1)     NOT NULL default 0,

               PRIMARY KEY (id),
               UNIQUE KEY field_type_name (field_type, name)
          ) $charset",

          "CREATE TABLE {$this->custom_fields_table} (
               relation_id              int unsigned   NOT NULL default 0,
               field_type               tinyint(1)     NOT NULL default 1,
               type                     int unsigned   NOT NULL default 0,
               data                     text           NOT NULL,

               PRIMARY KEY (relation_id, field_type, type)
           ) $charset",

          "CREATE TABLE {$this->pm_messages_table} (
               pm_message_id            int unsigned   NOT NULL auto_increment,
               user_id                  int unsigned   NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(100)   NOT NULL default '',
               message                  text           NOT NULL,
               datestamp                int unsigned   NOT NULL default 0,
               meta                     mediumtext     NOT NULL,

               PRIMARY KEY (pm_message_id),
               KEY user_id (user_id)
           ) $charset",

          "CREATE TABLE {$this->pm_folders_table} (
               pm_folder_id             int unsigned   NOT NULL auto_increment,
               user_id                  int unsigned   NOT NULL default 0,
               foldername               varchar(20)    NOT NULL default '',

               PRIMARY KEY (pm_folder_id)
           ) $charset",

          "CREATE TABLE {$this->pm_xref_table} (
               pm_xref_id               int unsigned   NOT NULL auto_increment,
               user_id                  int unsigned   NOT NULL default 0,
               pm_folder_id             int unsigned   NOT NULL default 0,
               special_folder           varchar(10)        NULL default NULL,
               pm_message_id            int unsigned   NOT NULL default 0,
               read_flag                tinyint(1)     NOT NULL default 0,
               reply_flag               tinyint(1)     NOT NULL default 0,

               PRIMARY KEY (pm_xref_id),
               KEY xref (user_id,pm_folder_id,pm_message_id),
               KEY read_flag (read_flag)
           ) $charset",

          "CREATE TABLE {$this->pm_buddies_table} (
               pm_buddy_id              int unsigned   NOT NULL auto_increment,
               user_id                  int unsigned   NOT NULL default 0,
               buddy_user_id            int unsigned   NOT NULL default 0,

               PRIMARY KEY pm_buddy_id (pm_buddy_id),
               UNIQUE KEY userids (user_id, buddy_user_id),
               KEY buddy_user_id (buddy_user_id)
           ) $charset",

          "CREATE TABLE {$this->message_tracking_table} (
               track_id                 int unsigned   NOT NULL auto_increment,
               message_id               int unsigned   NOT NULL default 0,
               user_id                  int unsigned   NOT NULL default 0,
               time                     int unsigned   NOT NULL default 0,
               diff_body                text               NULL,
               diff_subject             text               NULL,

               PRIMARY KEY track_id (track_id),
               KEY message_id (message_id)
           ) $charset",

           "CREATE TABLE {$this->user_newflags_min_id_table} (
               user_id                  int unsigned   NOT NULL default '0',
               forum_id                 int unsigned   NOT NULL default '0',
               min_id                   int unsigned   NOT NULL default '0',
               PRIMARY KEY (user_id, forum_id)
            ) $charset",
        );

        foreach ($create_table_queries as $sql) {
            $error = $this->interact(
                DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY);
            if ($error !== NULL) {
                return $error;
            }
        }

        return NULL;
    }
    // }}}

    // {{{ Method: search()
    /**
     * Search the database using the provided search criteria and return
     * an array containing the total count of matches and the visible
     * messages based on the page $offset and $length.
     *
     * @param string $search
     *     The query to search on in messages and subjects.
     *
     * @param mixed $author
     *     The query to search on in the message authors or a numerical user_id
     *     if searching for all messages for a certain user_id.
     *
     * @param boolean $return_threads
     *     Whether to return the results as threads (TRUE) or messages (FALSE).
     *     When searching for a user ($match_type = USER_ID), then only the
     *     thread starter messages that were posted by the user are returned.
     *
     * @param integer $page
     *     The result page offset starting with 0.
     *
     * @param integer $limit
     *     The result page limit (nr. of results per page).
     *
     * @param string $match_type
     *     The type of search. This can be one of:
     *     - ALL:     search on all of the words (uses $search)
     *     - ANY:     search on any of the words (uses $search)
     *     - PHRASE:  search for an exact phrase (uses $search)
     *     - USER_ID: search for an author id (uses $author)
     *
     * @param integer $days
     *     The number of days to go back in the database for searching
     *     (last [x] days) or zero to search within all dates.
     *
     * @param string $match_forum
     *     The forum restriction. This can be either the string "ALL" to search
     *     in any of the readable forums or a comma separated list of forum_ids.
     *
     * @return array
     *     An array containing two fields:
     *     - "count" contains the total number of matching messages.
     *     - "rows" contains the messages that are visible, based on the page
     *       $page and page $limit. The messages are indexed by message_id.
     */
    public function search(
        $search, $author, $return_threads, $page, $limit,
        $match_type, $days, $match_forum)
    {
        global $PHORUM;

        $fulltext_mode = isset($PHORUM['DBCONFIG']['mysql_use_ft']) &&
                         $PHORUM['DBCONFIG']['mysql_use_ft'];

        $search = trim($search);
        $author = trim($author);
        settype($return_threads, 'bool');
        settype($page, 'int');
        settype($limit, 'int');
        settype($days, 'int');

        // For spreading search results over multiple pages.
        $offset = $page * $limit;

        // Initialize the return data.
        $return = array('count' => 0, 'rows' => array());

        // Return immediately if the search queries are empty.
        if ($search == '' && $author == '') return $return;

        // Check what forums the active Phorum user can read.
        $allowed_forums = phorum_api_user_check_access(
            PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST
        );

        // If the user is not allowed to search any forum or the current
        // active forum, then return the emtpy search results array.
        if (empty($allowed_forums) ||
            ($PHORUM['forum_id']>0 &&
             !in_array($PHORUM['forum_id'], $allowed_forums))) return $return;

        // Prepare forum_id restriction.
        $match_forum_arr = explode(",", $match_forum);
        $search_forums = array();
        foreach ($match_forum_arr as $forum_id) {
            if ($forum_id == "ALL") {
                $search_forums = $allowed_forums;
                break;
            }
            if (isset($allowed_forums[$forum_id])) {
                $search_forums[] = $forum_id;
            }
        }
        if (count($search_forums)){
            $forum_where = "AND forum_id in (".implode(",", $search_forums).")";
        } else {
            // Hack attempt or error. Return empty search results.
            return $return;
        }

        // Prepare day limit restriction.
        if ($days > 0) {
            $ts = time() - 86400*$days;
            $datestamp_where = "AND datestamp >= $ts";
        } else {
            $datestamp_where = '';
        }

        // We make use of temporary tables for storing intermediate search
        // results. These tables are stored in $tables during processing.
        $tables = array();

        // -------------------------------------------------------------------
        // Handle search for user_id only.
        // -------------------------------------------------------------------

        if ($search == '' && $author != '' && $match_type == 'USER_ID')
        {
            $user_id = (int) $author;
            if (empty($user_id)) return $return;

            // Search for messages.
            $where = "user_id = $user_id AND
                      status=".PHORUM_STATUS_APPROVED." AND
                      moved=0";
            if ($return_threads) $where .= " AND parent_id = 0";
            $sql = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM   {$this->message_table} " .
                    ($this->_can_USE_INDEX ? "USE INDEX (user_messages)" : "") .
                   "WHERE  $where $forum_where
                    ORDER  BY datestamp DESC";

            // Retrieve the message rows.
            $rows = $this->interact(
                DB_RETURN_ASSOCS, $sql,
                "message_id", NULL, $limit, $offset
            );

            // Retrieve the number of found messages.
            $count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT found_rows()"
            );

            // Fill the return data.
            $return = array("count" => $count, "rows"  => $rows);

            return $return;
        }

        // -------------------------------------------------------------------
        // Handle search for message and subject.
        // -------------------------------------------------------------------

        if ($search != '')
        {
            $match_str = '';
            $tokens = array();

            if ($match_type == "PHRASE")
            {
                $search = str_replace('"', '', $search);
                $match_str = '"'.$this->interact(DB_RETURN_QUOTED, $search).'"';
            }
            else
            {
                // Surround with spaces so matching is easier.
                $search = " $search ";

                // Pull out all grouped terms, e.g. (nano mini).
                $paren_terms = array();
                if (strstr($search, '(')) {
                    preg_match_all('/ ([+\-~]*\(.+?\)) /', $search, $m);
                    $search = preg_replace('/ [+\-~]*\(.+?\) /', ' ', $search);
                    $paren_terms = $m[1];
                }

                // Pull out all the double quoted strings,
                // e.g. '"iMac DV" or -"iMac DV".
                $quoted_terms = array();
                if (strstr( $search, '"')) {
                    preg_match_all('/ ([+\-~]*".+?") /', $search, $m);
                    $search = preg_replace('/ [+\-~]*".+?" /', ' ', $search);
                    $quoted_terms = $m[1];
                }

                // Finally, pull out the rest words in the string.
                $norm_terms = preg_split(
                    "/\s+/", $search, 0, PREG_SPLIT_NO_EMPTY);

                // Merge all search terms together.
                $tokens =  array_merge(
                    $quoted_terms, $paren_terms, $norm_terms);
            }

            // Handle full text message / subject search.
            if ($fulltext_mode)
            {
                // Create a match string based on the parsed query tokens.
                if (count($tokens))
                {
                    $match_str = '';

                    foreach ($tokens as $term)
                    {
                        if (!strstr("+-~", substr($term, 0, 1)))
                        {
                            if (strstr($term, ".") &&
                                !preg_match('!^".+"$!', $term) &&
                                substr($term, -1) != "*") {
                                $term = "\"$term\"";
                            }
                            if ($match_type == "ALL") {
                                $term = "+".$term;
                            }
                        }

                        $match_str .= "$term ";
                    }

                    $match_str = trim($match_str);
                    $match_str = $this->interact(DB_RETURN_QUOTED, $match_str);
                }

                $table_name = $this->search_table . "_ft_" . md5(microtime());

                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $table_name (
                         KEY (message_id)
                     ) ENGINE=HEAP
                       SELECT message_id
                       FROM   {$this->search_table}
                       WHERE  MATCH (search_text)
                              AGAINST ('$match_str' IN BOOLEAN MODE)"
                );

                $tables[] = $table_name;
            }
            // Handle standard message / subject search.
            else
            {
                if (count($tokens))
                {
                    $condition = ($match_type == "ALL") ? "AND" : "OR";

                    foreach($tokens as $tid => $token) {
                        $tokens[$tid] =
                            $this->interact(DB_RETURN_QUOTED, $token);
                    }

                    $match_str = "('%".implode("%' $condition '%", $tokens)."%')";
                }

                $table_name = $this->search_table . "_like_" . md5(microtime());

                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $table_name (
                         KEY (message_id)
                     ) ENGINE=HEAP
                       SELECT message_id
                       FROM   {$this->search_table}
                       WHERE search_text LIKE $match_str"
                );

                $tables[] = $table_name;
            }
        }

        // -------------------------------------------------------------------
        // Handle search for author.
        // -------------------------------------------------------------------

        if ($author != '')
        {
            $table_name = $this->search_table . "_author_" . md5(microtime());

            // Search either by user_id or by username.
            if ($match_type == "USER_ID") {
                $author = (int) $author;
                $author_where = "user_id = $author";
            } else {
                $author = $this->interact(DB_RETURN_QUOTED, $author);
                $author_where = "author = '$author'";
            }

            $this->interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table_name (
                   KEY (message_id)
                 ) ENGINE=HEAP
                   SELECT message_id
                   FROM   {$this->message_table}
                   WHERE  $author_where"
            );

            $tables[] = $table_name;
        }

        // -------------------------------------------------------------------
        // Gather the results.
        // -------------------------------------------------------------------

        if (count($tables))
        {
            // If we only have one temporary table, we can use it directly.
            if (count($tables) == 1) {
                $table = array_shift($tables);
            }
            // In case we have multiple temporary tables, we join them together
            // in a new temporary table for retrieving the results.
            else
            {
                $table = $this->search_table . "_final_" . md5(microtime());

                $joined_tables = "";
                $main_table = array_shift($tables);
                foreach ($tables as $tbl) {
                    $joined_tables.= "INNER JOIN $tbl USING (message_id)";
                }

                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $table (
                       KEY (message_id)
                     ) ENGINE=HEAP
                       SELECT m.message_id
                       FROM   $main_table m $joined_tables"
                );
            }

            // When only threads need to be returned, then join the results
            // that we have so far with the message table into a result set
            // that only contains the threads for the results.
            if ($return_threads)
            {
                $threads_table = $this->search_table .
                                 "_final_threads_" . md5(microtime());
                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $threads_table (
                       KEY (message_id)
                     ) ENGINE=HEAP
                       SELECT distinct thread AS message_id
                       FROM   {$this->message_table}
                              INNER JOIN $table
                              USING (message_id)"
                );

                $table = $threads_table;
            }

            // Retrieve the found messages.
            $rows = $this->interact(
                DB_RETURN_ASSOCS,
                "SELECT SQL_CALC_FOUND_ROWS *
                 FROM   {$this->message_table}
                        INNER JOIN $table USING (message_id)
                 WHERE  status=".PHORUM_STATUS_APPROVED."
                        $forum_where
                        $datestamp_where
                 ORDER  BY datestamp DESC",
                 "message_id", NULL, $limit, $offset
            );

            // Retrieve the number of found messages.
            $count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT found_rows()"
            );

            // Fill the return data.
            $return = array("count" => $count, "rows"  => $rows);
        }

        return $return;
    }
    // }}}

    // {{{ Method: newflag_update_forum()
    /**
     * Update the forum_id for the newflags. The newflags are updated by
     * setting their forum_ids to the forum_ids of the referenced message
     * table records.
     *
     * @param array $message_ids
     *     An array of message_ids which should be updated.
     */
    public function newflag_update_forum($message_ids)
    {
        $this->sanitize_mixed($message_ids, 'int');
        $ids_str = implode(', ', $message_ids);

        return $this->interact(
            DB_RETURN_RES,
            "UPDATE IGNORE
                    {$GLOBALS['PHORUM']['user_newflags_table']} AS flags,
                    {$GLOBALS['PHORUM']['message_table']}       AS msg
             SET    flags.forum_id   = msg.forum_id
             WHERE  flags.message_id = msg.message_id AND
                    flags.message_id IN ($ids_str)",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}
}

?>
