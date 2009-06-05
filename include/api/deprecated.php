<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

/**
 * @deprecated Replaced by {@link phorum_api_error()}.
 */
function phorum_api_error_set($errno, $error = NULL) {
    return Phorum::API()->error($errno, $error);
}

/**
 * @deprecated Replaced by {@link phorum_api_error->code()}.
 */
function phorum_api_errno() {
    return Phorum::API()->error->code();
}

/**
 * @deprecated Replaced by {@link phorum_api_error->message()}.
 */
function phorum_api_strerror() {
    return Phorum::API()->error->message();
}

/**
 * @deprecated Replaced by {@link phorum_api_url()}.
 */
function phorum_get_url() {
    Phorum::API()->url; // make sure the URL API layer code is loaded.
    $argv = func_get_args();
    return call_user_func_array('phorum_api_url_get', $argv);
}

/**
 * @deprecated Replaced by {@link phorum_api_url_current()}.
 */
function phorum_get_current_url($include_query_string = TRUE) {
    return Phorum::API()->url->current($include_query_string);
}

/**
 * @deprecated Replaced by {@link phorum_api_url_redirect()}.
 */
function phorum_redirect_by_url($url) {
    return Phorum::API()->redirect($url);
}

/**
 * @deprecated Replaced by {@link phorum_api_modules_hook()}.
 */
function phorum_hook() {
    Phorum::API()->modules; // make sure the Modules API layer code is loaded.
    $argv = func_get_args();
    return call_user_func_array('phorum_api_modules_hook', $argv);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_date()}.
 */
function phorum_date($picture, $ts) {
    return Phorum::API()->format->date($picture, $ts);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_relative_date()}.
 */
function phorum_relative_date($ts) {
    return Phorum::API()->format->relative_date($ts);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_filesize()}.
 */
function phorum_filesize($sz) {
    return Phorum::API()->format->filesize($sz);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_strip()}.
 */
function phorum_strip_body($body) {
    return Phorum::API()->format->strip($body);
}

/**
 * @deprecated Replaced by {@link phorum_api_buffer_clear()}.
 */
function phorum_ob_clean() {
    return Phorum::API()->output->clear();
}

/**
 * @deprecated Replaced by {@link phorum_api_write_file()}.
 */
function phorum_write_file($file, $data) {
    return Phorum::API()->write_file($file, $data);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_messages()}.
 */
function phorum_format_messages($messages, $author_spec = NULL) {
    return Phorum::API()->format->messages($messages, $author_spec);
}

/**
 * @deprecated Replaced by {@link phorum_api_mail_check_address()}.
 */
function phorum_valid_email($address) {
    return Phorum::API()->mail->check_address($address);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_configure()}.
 */
function phorum_api_custom_profile_field_configure($field) {
    $field['type'] = PHORUM_CUSTOM_FIELD_USER;
    return Phorum::API()->custom_field->configure($field);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_byname()}.
 */
function phorum_api_custom_profile_field_byname($name) {
    return Phorum::API()->custom_field->byname($name,PHORUM_CUSTOM_FIELD_USER);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_delete()}.
 */
function phorum_api_custom_profile_field_delete($id, $hard_delete = FALSE) {
    return Phorum::API()->custom_field->delete(
        $id, PHORUM_CUSTOM_FIELD_USER, $hard_delete);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_restore()}.
 */
function phorum_api_custom_profile_field_restore($id) {
    return Phorum::API()->custom->field_restore($id, PHORUM_CUSTOM_FIELD_USER);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_checkconfig()}.
 */
function phorum_api_custom_profile_field_checkconfig() {
    return Phorum::API()->custom_field->checkconfig();
}

/**
 * @deprecated Replaced by {@link phorum_api_error_backtrace()}.
 */
function phorum_generate_backtrace($skip = 0, $hidepath = "{path to Phorum}") {
    return Phorum::API()->error->backtrace($skip, $hidepath);
}

/**
 * @deprecated Replaced by {@link phorum_api_dev_dump()}.
 */
function print_var($var, $admin_only = TRUE) {
    return Phorum::API()->dev->dump($var, $admin_only);
}

/**
 * @deprecated Replaced by {@link phorum_api_error_database()}.
 */
function phorum_database_error($error) {
    return Phorum::API()->error->database($error);
}

/**
 * @deprecated Replaced by {@link phorum_api_format_html_encode()}.
 */
function phorum_html_encode($string) {
    return Phorum::API()->format->html_encode($string);
}

/**
 * @deprecated Replaced by {@link phorum_api_output()}.
 */
function phorum_output($templates) {
    return Phorum::API()->output($templates);
}

/**
 * @deprecated Replaced by {@link phorum_api_template_set()}.
 */
function phorum_switch_template($template = NULL, $template_path = NULL, $template_http_path = NULL) {
    return Phorum::API()->template->set(
        $template, $template_path, $template_http_path
    );
}

/**
 * @deprecated Replaced by {@link phorum_api_template()}.
 */
function phorum_get_template($template) {
    return Phorum::API()->template($template);
}

/**
 * @deprecated Replaced by {@link phorum_api_template_resolve()}.
 */
function phorum_get_template_file($template) {
    return Phorum::API()->template->resolve($template);
}

/**
 * @deprecated Replaced by {@link phorum_api_thread_update_metadata()}.
 */
function phorum_update_thread_info($thread_id) {
    return Phorum::API()->thread->update_metadata($thread_id);
}

/**
 * @deprecated Replaced by {@link phorum_api_forums_tree()}.
 */
function phorum_build_forum_list() {
    return Phorum::API()->forums->tree();
}

/**
 * @deprecated Replaced by {@link phorum_api_mail()}.
 */
function phorum_email_user($addresses, $data) {
    return Phorum::API()->mail($addresses, $data);
}

/**
 * @deprecated Replaced by {@link phorum_api_sign()}.
 */
function phorum_generate_data_signature($data) {
    return Phorum::API()->sign($data);
}

/**
 * @deprecated Replaced by {@link phorum_api_sign_check()}.
 */
function phorum_check_data_signature($data, $signature) {
    return Phorum::API()->sign_check($data, $signature);
}

?>
