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
 * This script implements a backward compatibility database layer.
 *
 * In previous versions of Phorum, the database layer was functional and
 * not object oriented, which was a design choice driven by speed optimization.
 * In recent versions of PHP, speed issues have been resolved, so in Phorum
 * 5.3 we started using OO, to make it easier to implement new database
 * layers by extending from a base layer class.
 *
 * To support modules that use phorum_db_* calls, we provide this
 * compatibility layer, which relays all database calls transparently to the
 * new OO layer.
 *
 * Note: new methods that are added to the OO layer, do not have to be added
 * to this functional layer. This layer is only here for backward compatibility.
 *
 * @package    PhorumDBLayer
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// ----------------------------------------------------------------------
// Table name variables
//
// These are the table names that are used by the database system.
// The PhorumDB layer uses class properties for the table names.
// We keep these here for backward compatibility with the functional layer.
// ----------------------------------------------------------------------

$PHORUM['message_table']              = $PHORUM['DB']->message_table;
$PHORUM['user_newflags_table']        = $PHORUM['DB']->user_newflags_table;
$PHORUM['user_newflags_min_id_table'] = $PHORUM['DB']->user_newflags_min_id_table;
$PHORUM['subscribers_table']          = $PHORUM['DB']->subscribers_table;
$PHORUM['files_table']                = $PHORUM['DB']->files_table;
$PHORUM['search_table']               = $PHORUM['DB']->search_table;
$PHORUM['settings_table']             = $PHORUM['DB']->settings_table;
$PHORUM['forums_table']               = $PHORUM['DB']->forums_table;
$PHORUM['user_table']                 = $PHORUM['DB']->user_table;
$PHORUM['user_permissions_table']     = $PHORUM['DB']->user_permissions_table;
$PHORUM['groups_table']               = $PHORUM['DB']->groups_table;
$PHORUM['forum_group_xref_table']     = $PHORUM['DB']->forum_group_xref_table;
$PHORUM['user_group_xref_table']      = $PHORUM['DB']->user_group_xref_table;
$PHORUM['custom_fields_config_table'] = $PHORUM['DB']->custom_fields_config_table;
$PHORUM['custom_fields_table']        = $PHORUM['DB']->custom_fields_table;
$PHORUM['banlist_table']              = $PHORUM['DB']->banlist_table;
$PHORUM['pm_messages_table']          = $PHORUM['DB']->pm_messages_table;
$PHORUM['pm_folders_table']           = $PHORUM['DB']->pm_folders_table;
$PHORUM['pm_xref_table']              = $PHORUM['DB']->pm_xref_table;
$PHORUM['pm_buddies_table']           = $PHORUM['DB']->pm_buddies_table;
$PHORUM['message_tracking_table']     = $PHORUM['DB']->message_tracking_table;

// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

function phorum_db_mysql_connect() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'mysql_connect'), $args
    );
}

function phorum_db_sanitize_mixed() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'sanitize_mixed'), $args
    );
}

function phorum_db_validate_field() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'validate_field'), $args
    );
}

function phorum_db_interact() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'interact'), $args
    );
}

function phorum_db_fetch_row() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'fetch_row'), $args
    );
}

function phorum_db_get_forums() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_forums'), $args
    );
}

function phorum_db_get_custom_fields() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_custom_fields'), $args
    );
}

function phorum_db_check_connection() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'check_connection'), $args
    );
}

function phorum_db_close_connection() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'close_connection'), $args
    );
}

function phorum_db_run_queries() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'run_queries'), $args
    );
}

function phorum_db_load_settings() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'load_settings'), $args
    );
}

function phorum_db_update_settings() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_settings'), $args
    );
}

function phorum_db_get_thread_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_thread_list'), $args
    );
}

function phorum_db_get_recent_messages() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_recent_messages'), $args
    );
}

function phorum_db_get_unapproved_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_unapproved_list'), $args
    );
}

function phorum_db_post_message() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'post_message'), $args
    );
}

function phorum_db_update_message() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_message'), $args
    );
}

function phorum_db_delete_message() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_message'), $args
    );
}

function phorum_db_get_messagetree() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_messagetree'), $args
    );
}

function phorum_db_get_message() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message'), $args
    );
}

function phorum_db_get_messages() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_messages'), $args
    );
}

function phorum_db_get_message_index() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_index'), $args
    );
}

function phorum_db_search() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'search'), $args
    );
}

function phorum_db_get_neighbour_thread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_neighbour_thread'), $args
    );
}

function phorum_db_update_forum_stats() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_forum_stats'), $args
    );
}

function phorum_db_move_thread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'move_thread'), $args
    );
}

function phorum_db_close_thread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'close_thread'), $args
    );
}

function phorum_db_reopen_thread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'reopen_thread'), $args
    );
}

function phorum_db_add_forum() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_forum'), $args
    );
}

function phorum_db_update_forum() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_forum'), $args
    );
}

function phorum_db_drop_forum() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'drop_forum'), $args
    );
}

function phorum_db_drop_folder() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'drop_folder'), $args
    );
}

function phorum_db_add_message_edit() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_message_edit'), $args
    );
}

function phorum_db_get_message_edits() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_edits'), $args
    );
}

function phorum_db_get_groups() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_groups'), $args
    );
}

function phorum_db_get_group_members() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_group_members'), $args
    );
}

function phorum_db_add_group() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_group'), $args
    );
}

function phorum_db_update_group() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_group'), $args
    );
}

function phorum_db_delete_group() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_group'), $args
    );
}

function phorum_db_user_get_moderators() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_moderators'), $args
    );
}

function phorum_db_user_count() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_count'), $args
    );
}

function phorum_db_user_get_all() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_all'), $args
    );
}

function phorum_db_user_get() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get'), $args
    );
}

function phorum_db_user_get_fields() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_fields'), $args
    );
}

function phorum_db_user_get_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_list'), $args
    );
}

function phorum_db_user_check_login() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_check_login'), $args
    );
}

function phorum_db_user_search() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_search'), $args
    );
}

function phorum_db_user_add() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_add'), $args
    );
}

function phorum_db_user_save() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_save'), $args
    );
}

function phorum_db_save_custom_fields() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'save_custom_fields'), $args
    );
}

function phorum_db_user_display_name_updates() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_display_name_updates'), $args
    );
}

function phorum_db_user_save_groups() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_save_groups'), $args
    );
}

function phorum_db_user_subscribe() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_subscribe'), $args
    );
}

function phorum_db_user_unsubscribe() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_unsubscribe'), $args
    );
}

function phorum_db_user_increment_posts() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_increment_posts'), $args
    );
}

function phorum_db_user_get_groups() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_groups'), $args
    );
}

function phorum_db_user_get_unapproved() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_unapproved'), $args
    );
}

function phorum_db_user_delete() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_delete'), $args
    );
}

function phorum_db_delete_custom_fields() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_custom_fields'), $args
    );
}

function phorum_db_get_file_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_file_list'), $args
    );
}

function phorum_db_get_user_file_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_user_file_list'), $args
    );
}

function phorum_db_get_message_file_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_file_list'), $args
    );
}

function phorum_db_file_get() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_get'), $args
    );
}

function phorum_db_file_save() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_save'), $args
    );
}

function phorum_db_file_delete() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_delete'), $args
    );
}

function phorum_db_file_link() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_link'), $args
    );
}

function phorum_db_get_user_filesize_total() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_user_filesize_total'), $args
    );
}

function phorum_db_list_stale_files() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'list_stale_files'), $args
    );
}

function phorum_db_newflag_allread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_allread'), $args
    );
}

function phorum_db_newflag_get_flags() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_flags'), $args
    );
}

function phorum_db_newflag_check() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_check'), $args
    );
}

function phorum_db_newflag_count() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_count'), $args
    );
}

function phorum_db_newflag_get_unread_count() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_unread_count'), $args
    );
}

function phorum_db_newflag_add_min_id() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_add_min_id'), $args
    );
}

function phorum_db_newflag_add_read() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_add_read'), $args
    );
}

function phorum_db_newflag_get_count() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_count'), $args
    );
}

function phorum_db_newflag_delete() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_delete'), $args
    );
}

function phorum_db_newflag_update_forum() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_update_forum'), $args
    );
}

function phorum_db_user_list_subscribers() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_list_subscribers'), $args
    );
}

function phorum_db_user_list_subscriptions() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_list_subscriptions'), $args
    );
}

function phorum_db_user_get_subscription() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_subscription'), $args
    );
}

function phorum_db_get_banlists() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_banlists'), $args
    );
}

function phorum_db_get_banitem() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_banitem'), $args
    );
}

function phorum_db_del_banitem() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'del_banitem'), $args
    );
}

function phorum_db_mod_banlists() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'mod_banlists'), $args
    );
}

function phorum_db_pm_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_list'), $args
    );
}

function phorum_db_pm_get() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_get'), $args
    );
}

function phorum_db_pm_create_folder() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_create_folder'), $args
    );
}

function phorum_db_pm_rename_folder() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_rename_folder'), $args
    );
}

function phorum_db_pm_delete_folder() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_delete_folder'), $args
    );
}

function phorum_db_pm_getfolders() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_getfolders'), $args
    );
}

function phorum_db_pm_messagecount() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_messagecount'), $args
    );
}

function phorum_db_pm_checknew() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_checknew'), $args
    );
}

function phorum_db_pm_send() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_send'), $args
    );
}

function phorum_db_pm_setflag() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_setflag'), $args
    );
}

function phorum_db_pm_delete() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_delete'), $args
    );
}

function phorum_db_pm_move() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_move'), $args
    );
}

function phorum_db_pm_update_message_info() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_update_message_info'), $args
    );
}

function phorum_db_pm_is_buddy() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_is_buddy'), $args
    );
}

function phorum_db_pm_buddy_add() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_add'), $args
    );
}

function phorum_db_pm_buddy_delete() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_delete'), $args
    );
}

function phorum_db_pm_buddy_list() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_list'), $args
    );
}

function phorum_db_split_thread() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'split_thread'), $args
    );
}

function phorum_db_get_max_messageid() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_max_messageid'), $args
    );
}

function phorum_db_increment_viewcount() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'increment_viewcount'), $args
    );
}

function phorum_db_rebuild_search_data() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'rebuild_search_data'), $args
    );
}

function phorum_db_rebuild_user_posts() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'rebuild_user_posts'), $args
    );
}

function phorum_db_user_search_custom_profile_field() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_search_custom_profile_field'), $args
    );
}

function phorum_db_search_custom_profile_field() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'search_custom_profile_field'), $args
    );
}

function phorum_db_metaquery_compile() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'metaquery_compile'), $args
    );
}

function phorum_db_metaquery_messagesearch() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'metaquery_messagesearch'), $args
    );
}

function phorum_db_create_tables() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'create_tables'), $args
    );
}

function phorum_db_maxpacketsize() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'maxpacketsize'), $args
    );
}

function phorum_db_sanitychecks() {
    $args = func_get_args();
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'sanitychecks'), $args
    );
}

?>
