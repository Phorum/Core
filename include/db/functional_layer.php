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
 * @package    PhorumDBLayer
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// ----------------------------------------------------------------------
// Table name variables
//
// These are the table names that are used by the database system.
// The PhorumDB layer uses class properties for the table names.
// We keep these here for backward compatibility with the functional layer.
// ----------------------------------------------------------------------

$prefix = $PHORUM['DB']->prefix;

$PHORUM['message_table']              = $prefix . '_messages';
$PHORUM['user_newflags_table']        = $prefix . '_user_newflags';
$PHORUM['user_newflags_min_id_table'] = $prefix . '_user_min_id';
$PHORUM['subscribers_table']          = $prefix . '_subscribers';
$PHORUM['files_table']                = $prefix . '_files';
$PHORUM['search_table']               = $prefix . '_search';
$PHORUM['settings_table']             = $prefix . '_settings';
$PHORUM['forums_table']               = $prefix . '_forums';
$PHORUM['user_table']                 = $prefix . '_users';
$PHORUM['user_permissions_table']     = $prefix . '_user_permissions';
$PHORUM['groups_table']               = $prefix . '_groups';
$PHORUM['forum_group_xref_table']     = $prefix . '_forum_group_xref';
$PHORUM['user_group_xref_table']      = $prefix . '_user_group_xref';
$PHORUM['custom_fields_table']        = $prefix . '_custom_fields';
$PHORUM['banlist_table']              = $prefix . '_banlists';
$PHORUM['pm_messages_table']          = $prefix . '_pm_messages';
$PHORUM['pm_folders_table']           = $prefix . '_pm_folders';
$PHORUM['pm_xref_table']              = $prefix . '_pm_xref';
$PHORUM['pm_buddies_table']           = $prefix . '_pm_buddies';
$PHORUM['message_tracking_table']     = $prefix . '_messages_edittrack';

// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

function phorum_db_mysql_connect() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'mysql_connect'),
        func_get_args()
    );
}

function phorum_db_sanitize_mixed() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'sanitize_mixed'),
        func_get_args()
    );
}

function phorum_db_validate_field() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'validate_field'),
        func_get_args()
    );
}

function phorum_db_interact() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'interact'),
        func_get_args()
    );
}

function phorum_db_get_forums() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_forums'),
        func_get_args()
    );
}

function phorum_db_get_custom_fields() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_custom_fields'),
        func_get_args()
    );
}

function phorum_db_check_connection() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'check_connection'),
        func_get_args()
    );
}

function phorum_db_close_connection() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'close_connection'),
        func_get_args()
    );
}

function phorum_db_run_queries() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'run_queries'),
        func_get_args()
    );
}

function phorum_db_load_settings() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'load_settings'),
        func_get_args()
    );
}

function phorum_db_update_settings() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_settings'),
        func_get_args()
    );
}

function phorum_db_get_thread_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_thread_list'),
        func_get_args()
    );
}

function phorum_db_get_Recent_messages() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_Recent_messages'),
        func_get_args()
    );
}

function phorum_db_get_unapproved_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_unapproved_list'),
        func_get_args()
    );
}

function phorum_db_post_message() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'post_message'),
        func_get_args()
    );
}

function phorum_db_update_message() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_message'),
        func_get_args()
    );
}

function phorum_db_delete_message() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_message'),
        func_get_args()
    );
}

function phorum_db_get_messagetree() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_messagetree'),
        func_get_args()
    );
}

function phorum_db_get_message() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message'),
        func_get_args()
    );
}

function phorum_db_get_messages() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_messages'),
        func_get_args()
    );
}

function phorum_db_get_message_index() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_index'),
        func_get_args()
    );
}

function phorum_db_search() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'search'),
        func_get_args()
    );
}

function phorum_db_get_neighbour_thread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_neighbour_thread'),
        func_get_args()
    );
}

function phorum_db_update_forum_stats() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_forum_stats'),
        func_get_args()
    );
}

function phorum_db_move_thread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'move_thread'),
        func_get_args()
    );
}

function phorum_db_close_thread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'close_thread'),
        func_get_args()
    );
}

function phorum_db_reopen_thread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'reopen_thread'),
        func_get_args()
    );
}

function phorum_db_add_forum() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_forum'),
        func_get_args()
    );
}

function phorum_db_update_forum() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_forum'),
        func_get_args()
    );
}

function phorum_db_drop_forum() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'drop_forum'),
        func_get_args()
    );
}

function phorum_db_drop_folder() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'drop_folder'),
        func_get_args()
    );
}

function phorum_db_add_message_edit() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_message_edit'),
        func_get_args()
    );
}

function phorum_db_get_message_edits() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_edits'),
        func_get_args()
    );
}

function phorum_db_get_groups() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_groups'),
        func_get_args()
    );
}

function phorum_db_get_group_members() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_group_members'),
        func_get_args()
    );
}

function phorum_db_add_group() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'add_group'),
        func_get_args()
    );
}

function phorum_db_update_group() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'update_group'),
        func_get_args()
    );
}

function phorum_db_delete_group() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_group'),
        func_get_args()
    );
}

function phorum_db_user_get_moderators() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_moderators'),
        func_get_args()
    );
}

function phorum_db_user_count() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_count'),
        func_get_args()
    );
}

function phorum_db_user_get_all() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_all'),
        func_get_args()
    );
}

function phorum_db_user_get() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get'),
        func_get_args()
    );
}

function phorum_db_user_get_fields() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_fields'),
        func_get_args()
    );
}

function phorum_db_user_get_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_list'),
        func_get_args()
    );
}

function phorum_db_user_check_login() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_check_login'),
        func_get_args()
    );
}

function phorum_db_user_search() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_search'),
        func_get_args()
    );
}

function phorum_db_user_add() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_add'),
        func_get_args()
    );
}

function phorum_db_user_save() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_save'),
        func_get_args()
    );
}

function phorum_db_save_custom_fields() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'save_custom_fields'),
        func_get_args()
    );
}

function phorum_db_user_display_name_updates() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_display_name_updates'),
        func_get_args()
    );
}

function phorum_db_user_save_groups() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_save_groups'),
        func_get_args()
    );
}

function phorum_db_user_subscribe() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_subscribe'),
        func_get_args()
    );
}

function phorum_db_user_unsubscribe() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_unsubscribe'),
        func_get_args()
    );
}

function phorum_db_user_increment_posts() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_increment_posts'),
        func_get_args()
    );
}

function phorum_db_user_get_groups() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_groups'),
        func_get_args()
    );
}

function phorum_db_user_get_unapproved() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_unapproved'),
        func_get_args()
    );
}

function phorum_db_user_delete() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_delete'),
        func_get_args()
    );
}

function phorum_db_delete_custom_fields() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'delete_custom_fields'),
        func_get_args()
    );
}

function phorum_db_get_file_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_file_list'),
        func_get_args()
    );
}

function phorum_db_get_user_file_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_user_file_list'),
        func_get_args()
    );
}

function phorum_db_get_message_file_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_message_file_list'),
        func_get_args()
    );
}

function phorum_db_file_get() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_get'),
        func_get_args()
    );
}

function phorum_db_file_save() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_save'),
        func_get_args()
    );
}

function phorum_db_file_delete() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_delete'),
        func_get_args()
    );
}

function phorum_db_file_link() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'file_link'),
        func_get_args()
    );
}

function phorum_db_get_user_filesize_total() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_user_filesize_total'),
        func_get_args()
    );
}

function phorum_db_list_stale_files() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'list_stale_files'),
        func_get_args()
    );
}

function phorum_db_newflag_allread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_allread'),
        func_get_args()
    );
}

function phorum_db_newflag_get_flags() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_flags'),
        func_get_args()
    );
}

function phorum_db_newflag_check() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_check'),
        func_get_args()
    );
}

function phorum_db_newflag_count() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_count'),
        func_get_args()
    );
}

function phorum_db_newflag_get_unread_count() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_unread_count'),
        func_get_args()
    );
}

function phorum_db_newflag_add_min_id() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_add_min_id'),
        func_get_args()
    );
}

function phorum_db_newflag_add_read() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_add_read'),
        func_get_args()
    );
}

function phorum_db_newflag_get_count() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_get_count'),
        func_get_args()
    );
}

function phorum_db_newflag_delete() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_delete'),
        func_get_args()
    );
}

function phorum_db_newflag_update_forum() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'newflag_update_forum'),
        func_get_args()
    );
}

function phorum_db_user_list_subscribers() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_list_subscribers'),
        func_get_args()
    );
}

function phorum_db_user_list_subscriptions() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_list_subscriptions'),
        func_get_args()
    );
}

function phorum_db_user_get_subscription() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_get_subscription'),
        func_get_args()
    );
}

function phorum_db_get_banlists() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_banlists'),
        func_get_args()
    );
}

function phorum_db_get_banitem() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_banitem'),
        func_get_args()
    );
}

function phorum_db_del_banitem() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'del_banitem'),
        func_get_args()
    );
}

function phorum_db_mod_banlists() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'mod_banlists'),
        func_get_args()
    );
}

function phorum_db_pm_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_list'),
        func_get_args()
    );
}

function phorum_db_pm_get() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_get'),
        func_get_args()
    );
}

function phorum_db_pm_create_folder() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_create_folder'),
        func_get_args()
    );
}

function phorum_db_pm_rename_folder() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_rename_folder'),
        func_get_args()
    );
}

function phorum_db_pm_delete_folder() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_delete_folder'),
        func_get_args()
    );
}

function phorum_db_pm_getfolders() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_getfolders'),
        func_get_args()
    );
}

function phorum_db_pm_messagecount() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_messagecount'),
        func_get_args()
    );
}

function phorum_db_pm_checknew() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_checknew'),
        func_get_args()
    );
}

function phorum_db_pm_send() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_send'),
        func_get_args()
    );
}

function phorum_db_pm_setflag() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_setflag'),
        func_get_args()
    );
}

function phorum_db_pm_delete() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_delete'),
        func_get_args()
    );
}

function phorum_db_pm_move() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_move'),
        func_get_args()
    );
}

function phorum_db_pm_update_message_info() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_update_message_info'),
        func_get_args()
    );
}

function phorum_db_pm_is_buddy() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_is_buddy'),
        func_get_args()
    );
}

function phorum_db_pm_buddy_add() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_add'),
        func_get_args()
    );
}

function phorum_db_pm_buddy_delete() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_delete'),
        func_get_args()
    );
}

function phorum_db_pm_buddy_list() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'pm_buddy_list'),
        func_get_args()
    );
}

function phorum_db_split_thread() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'split_thread'),
        func_get_args()
    );
}

function phorum_db_get_max_messageid() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'get_max_messageid'),
        func_get_args()
    );
}

function phorum_db_increment_viewcount() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'increment_viewcount'),
        func_get_args()
    );
}

function phorum_db_rebuild_search_data() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'rebuild_search_data'),
        func_get_args()
    );
}

function phorum_db_rebuild_user_posts() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'rebuild_user_posts'),
        func_get_args()
    );
}

function phorum_db_user_search_custom_profile_field() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'user_search_custom_profile_field'),
        func_get_args()
    );
}

function phorum_db_search_custom_profile_field() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'search_custom_profile_field'),
        func_get_args()
    );
}

function phorum_db_metaquery_compile() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'metaquery_compile'),
        func_get_args()
    );
}

function phorum_db_metaquery_messagesearch() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'metaquery_messagesearch'),
        func_get_args()
    );
}

function phorum_db_create_tables() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'create_tables'),
        func_get_args()
    );
}

function phorum_db_maxpacketsize() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'maxpacketsize'),
        func_get_args()
    );
}

function phorum_db_sanitychecks() {
    return call_user_func_array(
        array($GLOBALS['PHORUM']['DB'], 'sanitychecks'),
        func_get_args()
    );
}

?>
