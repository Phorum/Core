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
    $phorum = Phorum::API();
    $field['type'] = PHORUM_CUSTOM_FIELD_USER;
    return $phorum->custom_field->configure($field);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_byname()}.
 */
function phorum_api_custom_profile_field_byname($name) {
    $phorum = Phorum::API();
    return $phorum->custom_field->byname($name,PHORUM_CUSTOM_FIELD_USER);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_delete()}.
 */
function phorum_api_custom_profile_field_delete($id, $hard_delete = FALSE) {
    $phorum = Phorum::API();
    return $phorum->custom_field->delete($id, PHORUM_CUSTOM_FIELD_USER, $hard_delete);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_restore()}.
 */
function phorum_api_custom_profile_field_restore($id) {
    $phorum = Phorum::API();
    return $phorum->custom->field_restore($id, PHORUM_CUSTOM_FIELD_USER);
}

/**
 * @deprecated Replaced by {@link phorum_api_custom_field_checkconfig()}.
 */
function phorum_api_custom_profile_field_checkconfig() {
    $phorum = Phorum::API();
    return $phorum->custom_field->checkconfig();
}

/**
 * @deprecated Replaced by {@link phorum_api_error_backtrace()}.
 */
function phorum_generate_backtrace($skip = 0, $hidepath = "{path to Phorum}") {
    $phorum = Phorum::API();
    return $phorum->error->backtrace($skip, $hidepath);
}


?>
