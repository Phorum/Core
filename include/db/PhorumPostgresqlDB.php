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
 * This script implements the PostgreSQL database layer for Phorum.
 */

/**
 * The PhorumPostgresqlDB class, which implements the PostgreSQL database
 * layer for Phorum.
 */
class PhorumPostgresqlDB extends PhorumDB
{
    // {{{ Properties

    /**
     * Whether or not the database system supports "TRUNCATE".
     * @var boolean
     */
    protected $_can_TRUNCATE = TRUE;

    // }}}

    // {{{ Method: interact()
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
     * @param $limit    - The maximum number of rows to return.
     * @param $offset   - The number of rows to skip in the result set,
     *                    before returning rows to the caller.
     *
     * @return $res     - The result of the query, based on the $return
     *                    parameter.
     */
    public function interact(
        $return, $sql = NULL, $keyfield = NULL, $flags = 0,
        $limit = 0, $offset = 0)
    {
        global $PHORUM;

        static $conn;

        // Close the database connection.
        if ($return == DB_CLOSE_CONN)
        {
            if (!empty($conn))
            {
                pg_close($conn);
                $conn = null;
            }
            return;
        }

        // Setup a database connection if no database connection is
        // available yet.
        if (empty($conn))
        {
            // Format the connection string for pg_connect.
            $conn_string = '';
            if ($PHORUM['DBCONFIG']['server'])
                $conn_string .= ' host=' . $PHORUM['DBCONFIG']['server'];
            if ($PHORUM['DBCONFIG']['user'])
                $conn_string .= ' user=' . $PHORUM['DBCONFIG']['user'];
            if ($PHORUM['DBCONFIG']['password'])
                $conn_string .= ' password=' . $PHORUM['DBCONFIG']['password'];
            if ($PHORUM['DBCONFIG']['name'])
                $conn_string .= ' dbname=' . $PHORUM['DBCONFIG']['name'];

            // Try to setup a connection to the database.
            $conn = @pg_connect($conn_string, PGSQL_CONNECT_FORCE_NEW);

            if ($conn === FALSE) {
                if ($flags & DB_NOCONNECTOK) return FALSE;
                phorum_api_error(
                    PHORUM_ERRNO_DATABASE,
                    'Failed to connect to the database.'
                );
                exit;
            }
            if (!empty($PHORUM['DBCONFIG']['charset'])) {
                $charset = $PHORUM['DBCONFIG']['charset'];
                pg_query($conn, "SET CLIENT_ENCODING TO '$charset'");
            }
        }

        // RETURN: quoted parameter.
        if ($return === DB_RETURN_QUOTED) {
            return pg_escape_string($conn, $sql);
        }

        // RETURN: database connection handle.
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
        if (!@pg_send_query($conn, $sql)) trigger_error(
            __METHOD__ . ': Internal error: ' .
            'pg_send_query() failed!', E_USER_ERROR
        );

        // Check if an error occurred.
        $res = pg_get_result($conn);
        $errno = pg_result_error_field($res, PGSQL_DIAG_SQLSTATE);
        if ($errno != 0)
        {
            // See if the $flags tell us to ignore the error.
            $ignore_error = FALSE;
            switch ($errno)
            {
                // Table does not exist.
                case '42P01':
                  if ($flags & DB_MISSINGTABLEOK) $ignore_error = TRUE;
                  break;

                // Table already exists or duplicate key name.
                // These two cases use the same error code.
                case '42P07':
                  if ($flags & DB_TABLEEXISTSOK) $ignore_error = TRUE;
                  if ($flags & DB_DUPKEYNAMEOK)  $ignore_error = TRUE;
                  break;

                // Duplicate column name.
                case '42701':
                  if ($flags & DB_DUPFIELDNAMEOK) $ignore_error = TRUE;
                  break;

                // Duplicate entry for key.
                case '23505':
                  if ($flags & DB_DUPKEYOK) {
                      $ignore_error = TRUE;

                      # the code expects res to have no value upon error
                      $res = NULL;
                  }
                  break;
            }

            // Handle this error if it's not to be ignored.
            if (! $ignore_error)
            {
                $errmsg = pg_result_error($res);

                // RETURN: error message
                if ($return === DB_RETURN_ERROR) return $errmsg;

                // Trigger an error.
                phorum_api_error(
                    PHORUM_ERRNO_DATABASE,
                    "$errmsg ($errno): $sql"
                );
                exit;
            }
        }

        // RETURN: NULL (no error)
        if ($return === DB_RETURN_ERROR) {
            return NULL;
        }

        // RETURN: query resource handle
        if ($return === DB_RETURN_RES) {
            return $res;
        }

        // RETURN: number of rows
        if ($return === DB_RETURN_ROWCOUNT) {
            return $res ? pg_num_rows($res) : 0;
        }

        // RETURN: array rows or single value
        if ($return === DB_RETURN_ROW  ||
            $return === DB_RETURN_ROWS ||
            $return === DB_RETURN_VALUE)
        {
            // Keyfields are only valid for DB_RETURN_ROWS.
            if ($return !== DB_RETURN_ROWS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = pg_fetch_row($res)) {
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
        if ($return === DB_RETURN_ASSOC ||
            $return === DB_RETURN_ASSOCS)
        {
            // Keyfields are only valid for DB_RETURN_ASSOCS.
            if ($return !== DB_RETURN_ASSOCS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = pg_fetch_assoc($res)) {
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
        if ($return === DB_RETURN_NEWID)
        {
            $res = pg_exec($conn, "SELECT lastval()");
            if ($res === FALSE) {
                phorum_api_error(
                    PHORUM_ERRNO_DATABASE,
                    'Failed to get a lastval() result.'
                );
            }
            $row = pg_fetch_row($res);
            if ($row === FALSE) {
                phorum_api_error(
                    PHORUM_ERRNO_DATABASE,
                    'No rows returned from LASTVAL().'
                );
            }
            return $row[0];
        }

        trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal return type specified!', E_USER_ERROR
        );
    }
    // }}}

    // {{{ Method: fetch_row()
    /**
     * Return a single row from a query result. This method can be used
     * if a lot of rows have to be processed one by one, in which case the
     * DB_RETURN_ROWS and DB_RETURN_ASSOCS return types for the
     * {@link PhorumDBLayer::interact()} method might consume lots of memory.
     *
     * @param resource $res
     *     The result set resource handle. This is the return value of the
     *     method {@link PhorumDBLayer::interact()}, when running a query
     *     with the DB_RETURN_RES return type.
     *
     * @param integer $type
     *     A flag, which indicates what type of row has to be returned.
     *     One of {@link DB_RETURN_ASSOC} or {@link DB_RETURN_ROW}, which
     *     will let this method return respectively an associative array
     *     (field name -> value) or an array (field index -> value).
     *
     * @return mixed
     *     This method returns either an array containing a single row
     *     or NULL if there are no more rows to retrieve.
     */
    public function fetch_row($res, $type)
    {
        if ($type === DB_RETURN_ASSOC) {
            $row = pg_fetch_assoc($res);
        } elseif ($type === DB_RETURN_ROW) {
            $row = pg_fetch_row($res);
        } else trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal \$type parameter used', E_USER_ERROR
        );

        return $row ? $row : NULL;
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

        $create_table_queries = array(

          "CREATE TABLE {$this->forums_table} (
               forum_id                 bigserial      NOT NULL,
               name                     varchar(50)    NOT NULL default '',
               active                   smallint       NOT NULL default 0,
               description              text           NOT NULL,
               template                 varchar(50)    NOT NULL default '',
               folder_flag              smallint       NOT NULL default 0,
               parent_id                bigint         NOT NULL default 0,
               list_length_flat         bigint         NOT NULL default 0,
               list_length_threaded     bigint         NOT NULL default 0,
               moderation               bigint         NOT NULL default 0,
               threaded_list            smallint       NOT NULL default 0,
               threaded_read            smallint       NOT NULL default 0,
               float_to_top             smallint       NOT NULL default 0,
               check_duplicate          smallint       NOT NULL default 0,
               allow_attachment_types   varchar(100)   NOT NULL default '',
               max_attachment_size      bigint         NOT NULL default 0,
               max_totalattachment_size bigint         NOT NULL default 0,
               max_attachments          bigint         NOT NULL default 0,
               pub_perms                bigint         NOT NULL default 0,
               reg_perms                bigint         NOT NULL default 0,
               display_ip_address       smallint       NOT NULL default 1,
               allow_email_notify       smallint       NOT NULL default 1,
               language                 varchar(100)   NOT NULL default '$lang',
               email_moderators         smallint       NOT NULL default 0,
               message_count            bigint         NOT NULL default 0,
               sticky_count             bigint         NOT NULL default 0,
               thread_count             bigint         NOT NULL default 0,
               last_post_time           bigint         NOT NULL default 0,
               display_order            bigint         NOT NULL default 0,
               read_length              bigint         NOT NULL default 0,
               vroot                    bigint         NOT NULL default 0,
               forum_path               text           NOT NULL,
               count_views              smallint       NOT NULL default 0,
               count_views_per_thread   smallint       NOT NULL default 0,
               display_fixed            smallint       NOT NULL default 0,
               reverse_threading        smallint       NOT NULL default 0,
               inherit_id               bigint             NULL default NULL,
               cache_version            bigint         NOT NULL default 0,

               PRIMARY KEY (forum_id)
           )",

          "CREATE INDEX {$this->forums_table}_name
           ON {$this->forums_table} (name)",

          "CREATE INDEX {$this->forums_table}_active
           ON {$this->forums_table} (active, parent_id)",

          "CREATE INDEX {$this->forums_table}_folder_index
           ON {$this->forums_table} (parent_id, vroot, active, folder_flag)",

          "CREATE TABLE {$this->message_table} (
               message_id               bigserial      NOT NULL,
               forum_id                 bigint         NOT NULL default 0,
               thread                   bigint         NOT NULL default 0,
               parent_id                bigint         NOT NULL default 0,
               user_id                  bigint         NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(255)   NOT NULL default '',
               body                     text           NOT NULL,
               email                    varchar(100)   NOT NULL default '',
               ip                       varchar(255)   NOT NULL default '',
               status                   smallint       NOT NULL default 2,
               msgid                    varchar(100)   NOT NULL default '',
               modifystamp              bigint         NOT NULL default 0,
               thread_count             bigint         NOT NULL default 0,
               moderator_post           smallint       NOT NULL default 0,
               sort                     smallint       NOT NULL default 2,
               datestamp                bigint         NOT NULL default 0,
               meta                     text               NULL,
               viewcount                bigint         NOT NULL default 0,
               threadviewcount          bigint         NOT NULL default 0,
               closed                   smallint       NOT NULL default 0,
               recent_message_id        bigint         NOT NULL default 0,
               recent_user_id           bigint         NOT NULL default 0,
               recent_author            varchar(255)   NOT NULL default '',
               moved                    smallint       NOT NULL default 0,
               hide_period              bigint         NOT NULL default 0,

               PRIMARY KEY (message_id)
           )",

          "CREATE INDEX {$this->message_table}_special_threads
           ON {$this->message_table} (sort, forum_id)",

          "CREATE INDEX {$this->message_table}_last_post_time
           ON {$this->message_table} (forum_id, status, modifystamp)",

          "CREATE INDEX {$this->message_table}_dup_check
           ON {$this->message_table} (forum_id, author, subject, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_user_id
           ON {$this->message_table} (recent_user_id)",

          "CREATE INDEX {$this->message_table}_user_messages
           ON {$this->message_table} (user_id, message_id)",

          "CREATE INDEX {$this->message_table}_updated_threads
           ON {$this->message_table} (status, parent_id, modifystamp)",

          "CREATE INDEX {$this->message_table}_list_page_flat
           ON {$this->message_table} (forum_id, status, parent_id, datestamp)",

          "CREATE INDEX {$this->message_table}_thread_date
           ON {$this->message_table} (thread, datestamp)",

          "CREATE INDEX {$this->message_table}_list_page_float
           ON {$this->message_table}
           (forum_id, status, parent_id, modifystamp)",

          "CREATE INDEX {$this->message_table}_forum_recent_messages
           ON {$this->message_table} (forum_id, status, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_threads
           ON {$this->message_table} (status, parent_id, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_messages
           ON {$this->message_table} (status, datestamp)",

          "CREATE INDEX {$this->message_table}_forum_thread_count
           ON {$this->message_table}
           (forum_id, parent_id, status, moved, message_id)",

          "CREATE INDEX {$this->message_table}_forum_message_count
           ON {$this->message_table} (forum_id, status, moved, message_id)",

          "CREATE TABLE {$this->settings_table} (
               name                     varchar(255)   NOT NULL default '',
               type                     char(1)        NOT NULL default 'V',
                                        CHECK (type IN ('V', 'S')),
               data                     text           NOT NULL,

               PRIMARY KEY (name)
           )",

          "CREATE TABLE {$this->subscribers_table} (
               user_id                  bigint         NOT NULL default 0,
               forum_id                 bigint         NOT NULL default 0,
               sub_type                 smallint       NOT NULL default 0,
               thread                   bigint         NOT NULL default 0,

               PRIMARY KEY (user_id, forum_id, thread)
           )",

          "CREATE INDEX {$this->message_table}_forum_id
           ON {$this->subscribers_table} (forum_id, thread, sub_type)",

          "CREATE TABLE {$this->user_permissions_table} (
               user_id                  bigint         NOT NULL default 0,
               forum_id                 bigint         NOT NULL default 0,
               permission               bigint         NOT NULL default 0,

               PRIMARY KEY (user_id,forum_id)
           )",

          "CREATE INDEX {$this->user_permissions_table}_forum_id
           ON {$this->user_permissions_table} (forum_id, permission)",

          // When creating extra fields, then mind to update the file
          // include/api/custom_field.php script too (it contains a
          // list of reserved names for custom profile fields).
          "CREATE TABLE {$this->user_table} (
               user_id                  bigserial      NOT NULL,
               username                 varchar(50)    NOT NULL default '',
               real_name                varchar(255)   NOT NULL default '',
               display_name             varchar(255)   NOT NULL default '',
               password                 varchar(50)    NOT NULL default '',
               password_temp            varchar(50)    NOT NULL default '',
               sessid_lt                varchar(50)    NOT NULL default '',
               sessid_st                varchar(50)    NOT NULL default '',
               sessid_st_timeout        bigint         NOT NULL default 0,
               email                    varchar(100)   NOT NULL default '',
               email_temp               varchar(110)   NOT NULL default '',
               hide_email               smallint       NOT NULL default 1,
               active                   smallint       NOT NULL default 0,
               signature                text           NOT NULL,
               threaded_list            smallint       NOT NULL default 0,
               posts                    bigint         NOT NULL default 0,
               admin                    smallint       NOT NULL default 0,
               threaded_read            smallint       NOT NULL default 0,
               date_added               bigint         NOT NULL default 0,
               date_last_active         bigint         NOT NULL default 0,
               last_active_forum        bigint         NOT NULL default 0,
               hide_activity            smallint       NOT NULL default 0,
               show_signature           smallint       NOT NULL default 0,
               email_notify             smallint       NOT NULL default 0,
               pm_email_notify          smallint       NOT NULL default 1,
               pm_new_count             bigint         NOT NULL default 0,
               tz_offset                numeric(4,2)   NOT NULL default -99.00,
               is_dst                   smallint       NOT NULL default 0,
               user_language            varchar(100)   NOT NULL default '',
               user_template            varchar(100)   NOT NULL default '',
               moderation_email         smallint       NOT NULL default 1,
               settings_data            text           NOT NULL,

               PRIMARY KEY (user_id)
          )",

          "CREATE UNIQUE INDEX {$this->user_table}_username
           ON {$this->user_table} (username)",

          "CREATE INDEX {$this->user_table}_active
           ON {$this->user_table} (active)",

          "CREATE INDEX {$this->user_table}_userpass
           ON {$this->user_table} (username, password)",

          "CREATE INDEX {$this->user_table}_sessid_st
           ON {$this->user_table} (sessid_st)",

          "CREATE INDEX {$this->user_table}_sessid_lt
           ON {$this->user_table} (sessid_lt)",

          "CREATE INDEX {$this->user_table}_activity
           ON {$this->user_table}
           (date_last_active, hide_activity, last_active_forum)",

          "CREATE INDEX {$this->user_table}_date_added
           ON {$this->user_table} (date_added)",

          "CREATE INDEX {$this->user_table}_email_temp
           ON {$this->user_table} (email_temp)",

          "CREATE TABLE {$this->user_newflags_table} (
               user_id                  bigint         NOT NULL default 0,
               forum_id                 bigint         NOT NULL default 0,
               message_id               bigint         NOT NULL default 0,

               PRIMARY KEY (user_id, forum_id, message_id)
           )",

          "CREATE INDEX {$this->user_newflags_table}_move
           ON {$this->user_newflags_table} (message_id, forum_id)",

          "CREATE TABLE {$this->groups_table} (
               group_id                 bigserial      NOT NULL,
               name                     varchar(255)   NOT NULL default '',
               open                     smallint       NOT NULL default 0,

               PRIMARY KEY (group_id)
           )",

          "CREATE TABLE {$this->forum_group_xref_table} (
               forum_id                 bigint         NOT NULL default 0,
               group_id                 bigint         NOT NULL default 0,
               permission               bigint         NOT NULL default 0,

               PRIMARY KEY (forum_id, group_id)
           )",

          "CREATE INDEX {$this->forum_group_xref_table}_group_id
           ON {$this->forum_group_xref_table} (group_id)",

          "CREATE TABLE {$this->user_group_xref_table} (
               user_id                  bigint         NOT NULL default 0,
               group_id                 bigint         NOT NULL default 0,
               status                   smallint       NOT NULL default 1,

               PRIMARY KEY (user_id, group_id)
           )",

          "CREATE TABLE {$this->files_table} (
               file_id                  bigserial      NOT NULL,
               user_id                  bigint         NOT NULL default 0,
               filename                 varchar(255)   NOT NULL default '',
               filesize                 bigint         NOT NULL default 0,
               file_data                text           NOT NULL,
               add_datetime             bigint         NOT NULL default 0,
               message_id               bigint         NOT NULL default 0,
               link                     varchar(10)    NOT NULL default '',

               PRIMARY KEY (file_id)
           )",

          "CREATE INDEX {$this->files_table}_add_datetime
           ON {$this->files_table} (add_datetime)",

          "CREATE INDEX {$this->files_table}_message_id_link
           ON {$this->files_table} (message_id, link)",

          "CREATE INDEX {$this->files_table}_user_id_link
           ON {$this->files_table} (user_id, link)",

          "CREATE TABLE {$this->banlist_table} (
               id                       bigserial      NOT NULL,
               forum_id                 bigint         NOT NULL default 0,
               type                     smallint       NOT NULL default 0,
               pcre                     smallint       NOT NULL default 0,
               string                   varchar(255)   NOT NULL default '',
               comments                 text           NOT NULL,

               PRIMARY KEY (id)
           )",

          "CREATE INDEX {$this->banlist_table}_forum_id
           ON {$this->banlist_table} (forum_id)",

          "CREATE TABLE {$this->search_table} (
               message_id               bigint         NOT NULL default 0,
               forum_id                 bigint         NOT NULL default 0,
               search_text              text           NOT NULL,

               PRIMARY KEY (message_id)
           )",

          "CREATE INDEX {$this->search_table}_forum_id
           ON {$this->search_table} (forum_id)",

          "CREATE TABLE {$this->custom_fields_config_table} (
               id                       bigint         NOT NULL auto_increment,
               field_type               tinyint(1)     NOT NULL default 1,
               name                     varchar(50)    NOT NULL,
               length                   mediumint      NOT NULL default 255,
               html_disabled            smallint       NOT NULL default 1,
               show_in_admin            smallint       NOT NULL default 0,
               deleted                  smallint       NOT NULL default 0,

               PRIMARY KEY (id)
          )",

          "CREATE UNIQUE INDEX
              {$this->custom_fields_config_table}_field_type_name
           ON {$this->custom_fields_config_table} (field_type, name)",

          "CREATE TABLE {$this->custom_fields_table} (
               relation_id              bigint         NOT NULL default 0,
               field_type               smallint       NOT NULL default 1,
               type                     bigint         NOT NULL default 0,
               data                     text           NOT NULL,

               PRIMARY KEY (relation_id, field_type, type)
           )",

          "CREATE TABLE {$this->pm_messages_table} (
               pm_message_id            bigserial      NOT NULL,
               user_id                  bigint         NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(100)   NOT NULL default '',
               message                  text           NOT NULL,
               datestamp                bigint         NOT NULL default 0,
               meta                     text           NOT NULL,

               PRIMARY KEY (pm_message_id)
           )",

          "CREATE INDEX {$this->pm_messages_table}_user_id
           ON {$this->pm_messages_table} (user_id)",

          "CREATE TABLE {$this->pm_folders_table} (
               pm_folder_id             bigserial      NOT NULL,
               user_id                  bigint         NOT NULL default 0,
               foldername               varchar(20)    NOT NULL default '',

               PRIMARY KEY (pm_folder_id)
           )",

          "CREATE TABLE {$this->pm_xref_table} (
               pm_xref_id               bigserial      NOT NULL,
               user_id                  bigint         NOT NULL default 0,
               pm_folder_id             bigint         NOT NULL default 0,
               special_folder           varchar(10)        NULL default NULL,
               pm_message_id            bigint         NOT NULL default 0,
               read_flag                smallint       NOT NULL default 0,
               reply_flag               smallint       NOT NULL default 0,

               PRIMARY KEY (pm_xref_id)
           )",

          "CREATE INDEX {$this->pm_xref_table}_xref
           ON {$this->pm_xref_table} (user_id, pm_folder_id, pm_message_id)",

          "CREATE INDEX {$this->pm_xref_table}_read_flag
           ON {$this->pm_xref_table} (read_flag)",

          "CREATE TABLE {$this->pm_buddies_table} (
               pm_buddy_id              bigserial      NOT NULL,
               user_id                  bigint         NOT NULL default 0,
               buddy_user_id            bigint         NOT NULL default 0,

               PRIMARY KEY (pm_buddy_id)
           )",

          "CREATE UNIQUE INDEX {$this->pm_buddies_table}_userids
           ON {$this->pm_buddies_table} (user_id, buddy_user_id)",

          "CREATE INDEX {$this->pm_buddies_table}_buddy_user_id
           ON {$this->pm_buddies_table} (buddy_user_id)",

          "CREATE TABLE {$this->message_tracking_table} (
               track_id                 bigserial      NOT NULL,
               message_id               bigint         NOT NULL default 0,
               user_id                  bigint         NOT NULL default 0,
               time                     bigint         NOT NULL default 0,
               diff_body                text               NULL,
               diff_subject             text               NULL,

               PRIMARY KEY (track_id)
           )",

          "CREATE INDEX {$this->message_tracking_table}_message_kd
           ON {$this->message_tracking_table} (message_id)",

          "CREATE TABLE {$this->user_newflags_min_id_table} (
              user_id                  bigint         NOT NULL default 0,
              forum_id                 bigint         NOT NULL default 0,
              min_id                   bigint         NOT NULL default 0,

              PRIMARY KEY (user_id, forum_id)
           )"
        );

        foreach ($create_table_queries as $sql)
        {
            $error = $this->interact(
                DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY);
            if ($error !== NULL) {
                return $error;
            }
        }

        return NULL;
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

        // Some cunning queries are needed for making moving the newflags
        // failsafe. A race condition that can happen otherwise, is that a
        // message is already updated to its new forum_id, but that the
        // newflags have not yet been updated. A user who already has a
        // newflag for a specific message and views that message before this
        // function is finished, will end up with two newflag records:
        // one for the old forum and one for the new forum.
        //
        // When we would simply update the newflags, we could end up with
        // unique index errors because of this.

        // Step 1: Make sure that there is max 1 record that has
        // a forum_id different from the target forum_id. This is done
        // in order to make the second step work. That step runs
        // into a unique index error if there are more than 1 records
        // that have a forum_id different from the target forum_id.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->user_newflags_table} AS f1
             USING  {$this->user_newflags_table} f2,
                    {$this->message_table} m
             WHERE  m.message_id IN ($ids_str)    AND
                    f1.message_id = m.message_id  AND
                    f1.forum_id  != m.forum_id    AND
                    f1.message_id = f2.message_id AND
                    f1.user_id    = f2.user_id    AND
                    f1.forum_id   < f2.forum_id",
            NULL,
            DB_MASTERQUERY
        );

        // Step 2: Update the forum_ids for the newflags, unless
        // a record already exists for the target forum_id.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->user_newflags_table} f1
             SET    forum_id = m.forum_id
             FROM   {$this->message_table} m
             WHERE  m.message_id IN($ids_str)    AND
                    f1.message_id = m.message_id AND
                    NOT EXISTS(
                        SELECT *
                        FROM   {$this->user_newflags_table} f2
                        WHERE  f2.user_id    = f1.user_id    AND
                               f2.message_id = f1.message_id AND
                               f2.forum_id   = m.forum_id
                    )",
            NULL,
            DB_MASTERQUERY
        );

        // Step 3: Cleanup for cases where a newflag record already
        // existed for the target forum_id in the previous query.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->user_newflags_table} f1
             USING {$this->message_table} m
             WHERE m.message_id IN ($ids_str)   AND
                   f1.message_id = m.message_id AND
                   f1.forum_id  != m.forum_id",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}
}

?>
