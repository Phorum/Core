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
 * This script implements backward compatible functions for
 * functions that have been deprecated in Phorum. We do provide these
 * backward compatibility functions for module compatibility. Because
 * there are third party modules, we cannot control whether or not
 * they are brought up to speed with the Phorum API.
 *
 * These deprecated functions might be removed from future versions
 * of Phorum.
 *
 * @package PhorumAPI
 * @subpackage DeprecatedFunctions
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */


/**
 * @deprecated Replaced by {@link phorum_api_error()}.
 */
function phorum_api_error_set($errno, $error = NULL) {
    return phorum_api_error($errno, $error);
}

/**
 * @deprecated Replaced by {@link phorum_api_error->code()}.
 */
function phorum_api_errno() {
    return phorum_api_error_code();
}

/**
 * @deprecated Replaced by {@link phorum_api_error->message()}.
 */
function phorum_api_strerror() {
    return phorum_api_error_message();
}

/**
 * @deprecated Replaced by {@link phorum_api_url()}.
 */
function phorum_get_url() {
    $argv = func_get_args();
    return call_user_func_array('phorum_api_url', $argv);
}

/**
 * @deprecated Replaced by {@link phorum_api_url_current()}.
 */
function phorum_get_current_url($include_query_string = TRUE) {
    return phorum_api_url_current($include_query_string);
}

/**
 * @deprecated Replaced by {@link phorum_api_redirect()}.
 */
function phorum_redirect_by_url($url) {
    return phorum_api_redirect($url);
}

/**
 * @deprecated Replaced by {@link phorum_api_hook()}.
 */
function phorum_hook() {
    $argv = func_get_args();
    return call_user_func_array('phorum_api_hook', $argv);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_date()}.
 */
function phorum_date($picture, $ts) {
    return phorum_api_format_date($picture, $ts);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_relative_date()}.
 */
function phorum_relative_date($ts) {
    return phorum_api_format_relative_date($ts);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_filesize()}.
 */
function phorum_filesize($sz) {
    return phorum_api_format_filesize($sz);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_strip()}.
 */
function phorum_strip_body($body) {
    return phorum_api_format_strip($body);
}

/**
 * @deprecated Replaced by {@link phorum_api_buffer_clear()}.
 */
function phorum_ob_clean() {
    return phorum_api_buffer_clear();
}

/**
 * @deprecated Replaced by {@link phorum_api_write_file()}.
 */
function phorum_write_file($file, $data) {
    require_once PHORUM_PATH.'/include/api/write_file.php';
    return phorum_api_write_file($file, $data);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_messages()}.
 */
function phorum_format_messages($messages, $author_spec = NULL) {
    require_once PHORUM_PATH.'/include/api/format/messages.php';
    return phorum_api_format_messages($messages, $author_spec);
}

/**
 * @deprecated Replaced by {@link phorum_api_mail_check_address()}.
 */
function phorum_valid_email($address) {
    require_once PHORUM_PATH.'/include/api/mail.php';
    return phorum_api_mail_check_address($address);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_configure()}.
 */
function phorum_api_custom_profile_field_configure($field) {
    require_once PHORUM_PATH.'/include/api/custom_field.php';
    $field['field_type'] = PHORUM_CUSTOM_FIELD_USER;
    return phorum_api_custom_field_configure($field);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_byname()}.
 */
function phorum_api_custom_profile_field_byname($name) {
    require_once PHORUM_PATH.'/include/api/custom_field.php';
    return phorum_api_custom_field_byname($name, PHORUM_CUSTOM_FIELD_USER);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_delete()}.
 */
function phorum_api_custom_profile_field_delete($id, $hard_delete = FALSE) {
    require_once PHORUM_PATH.'/include/api/custom_field.php';
    return phorum_api_custom_field_delete($id, $hard_delete);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_restore()}.
 */
function phorum_api_custom_profile_field_restore($id) {
    require_once PHORUM_PATH.'/include/api/custom_field.php';
    return phorum_api_custom_field_restore($id);
}

/**
 * @deprecated Replaced by {@link phorum_api_error_backtrace()}.
 */
function phorum_generate_backtrace($skip = 0, $hidepath = "{path to Phorum}") {
    require_once PHORUM_PATH.'/include/api/error/backtrace.php';
    return phorum_api_error_backtrace($skip, $hidepath);
}

/**
 * @deprecated Replaced by {@link phorum_api_dev_dump()}.
 */
function print_var($var, $admin_only = TRUE) {
    require_once PHORUM_PATH.'/include/api/dev.php';
    return phorum_api_dev_dump($var, $admin_only);
}

/**
 * @deprecated Replaced by {@link phorum_api_error_database()}.
 */
function phorum_database_error($error) {
    require_once PHORUM_PATH.'/include/api/error/database.php';
    return phorum_api_error_database($error);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_html_encode()}.
 */
function phorum_html_encode($string) {
    return phorum_api_format_html_encode($string);
}

/**
 * @deprecated Replaced by {@link phorum_api_output()}.
 */
function phorum_output($templates) {
    return phorum_api_output($templates);
}

/**
 * @deprecated Replaced by {@link phorum_api_template_set()}.
 */
function phorum_switch_template($template = NULL, $template_path = NULL, $template_http_path = NULL) {
    return phorum_api_template_set(
        $template, $template_path, $template_http_path
    );
}

/**
 * @deprecated Replaced by {@link phorum_api_template()}.
 */
function phorum_get_template($template) {
    return phorum_api_template($template);
}

/**
 * @deprecated Replaced by {@link phorum_api_template_resolve()}.
 */
function phorum_get_template_file($template) {
    return phorum_api_template_resolve($template);
}

/**
 * @deprecated Replaced by {@link phorum_api_thread_update_metadata()}.
 */
function phorum_update_thread_info($thread_id) {
    require_once PHORUM_PATH.'/include/api/thread.php';
    return phorum_api_thread_update_metadata($thread_id);
}

/**
 * @deprecated Replaced by {@link phorum_api_forums_tree()}.
 */
function phorum_build_forum_list() {
    require_once PHORUM_PATH.'/include/api/forums.php';
    return phorum_api_forums_tree();
}

/**
 * @deprecated Replaced by {@link phorum_api_mail()}.
 */
function phorum_email_user($addresses, $data) {
    require_once PHORUM_PATH.'/include/api/mail.php';
    return phorum_api_mail($addresses, $data);
}

/**
 * @deprecated Replaced by {@link phorum_api_sign()}.
 */
function phorum_generate_data_signature($data) {
    require_once PHORUM_PATH.'/include/api/sign.php';
    return phorum_api_sign($data);
}

/**
 * @deprecated Replaced by {@link phorum_api_sign_check()}.
 */
function phorum_check_data_signature($data, $signature) {
    return phorum_api_sign_check($data, $signature);
}

/**
 * @deprecated Replaced by {@link phorum_api_request_check_token()}.
 */
function phorum_check_posting_token($page = NULL) {
    return phorum_api_request_check_token($page);
}

/**
 * @deprecated Replaced by {@link phorum_api_cache_get()}.
 */
function phorum_cache_get($type, $key, $version=NULL) {
    return phorum_api_cache_get($type, $key, $version);
}

/**
 * @deprecated Replaced by {@link phorum_api_cache_put()}.
 */
function phorum_cache_put($type, $key, $data, $ttl = PHORUM_CACHE_DEFAULT_TTL, $version = NULL) {
    return phorum_api_cache_put($type, $key, $data, $ttl, $version);
}

/**
 * @deprecated Replaced by {@link phorum_api_cache_remove()}.
 */
function phorum_cache_remove($type, $key) {
    return phorum_api_cache_remove($type, $key);
}

/**
 * @deprecated Replaced by {@link phorum_api_cache_purge()}.
 */
function phorum_cache_purge($full = false) {
    return phorum_api_cache_purge($full);
}

/**
 * @deprecated Replaced by {@link phorum_api_cache_clear()}.
 */
function phorum_cache_clear() {
    return phorum_api_cache_clear();
}

/**
 * @deprecated Replaced by {@link phorum_api_system_get_max_upload()}.
 */
function phorum_get_system_max_upload() {
    require_once PHORUM_PATH.'/include/api/system.php';
    return phorum_api_system_get_max_upload();
}

/**
 * @deprecated Replaced by {@link phorum_api_newflags_apply_to_messages()}.
 */
function phorum_api_newflags_format_messages($messages, $mode = PHORUM_NEWFLAGS_BY_MESSAGE, $fullcount = FALSE) {
    require_once PHORUM_PATH.'/include/api/newflags.php';
    return phorum_api_newflags_apply_to_messages(
        $messages, $mode,
        $fullcount ? PHORUM_NEWFLAGS_CHECK : PHORUM_NEWFLAGS_COUNT
    );
}

?>
