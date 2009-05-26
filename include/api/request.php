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
 * This script implements functions for processing page requests.
 *
 * @package    PhorumAPI
 * @subpackage Request
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Function: phorum_api_request_check_token()
/**
 * Setup and check posting tokens for form POST requests.
 *
 * For protecting forms against CSRF attacks, a signed posting token
 * is utilized. This posting token must be included in the POST request.
 * Without the token, Phorum will not accept the POST data. 
 *
 * This function will check whether we are handling a POST request.
 * If yes, then check if an anti-CSRF token is provided in the POST data.
 * If no token is available or if the token does not match the expected
 * token, then the POST request is rejected.
 *
 * As a side effect, the required token is added to the {POST_VARS}
 * template variable. This facilitates protecting scripts. As
 * long as the template variable is added to the <form> for the
 * script, it will be automatically protected.
 *
 * @param string $target_page
 *     The page for which to check a posting token. When no target
 *     page is provided, then the constant "phorum_page" is used instead.
 *
 * @return string
 *     The expected posting token.
 */
function phorum_api_request_check_token($target_page = NULL)
{
    global $PHORUM;

    if ($target_page === NULL) $target_page = phorum_page;

    // Generate the posting token.
    $posting_token = md5(
        ($target_page !== NULL ? $target_page : phorum_page) . '/' .
        (
          $PHORUM['user']['user_id']
          ? $PHORUM['user']['password'].'/'.$PHORUM['user']['sessid_lt']
          : (
              isset($_SERVER['HTTP_USER_AGENT'])
              ? $_SERVER['HTTP_USER_AGENT']
              : 'unknown'
            )
        ) . '/' .
        $PHORUM['private_key']
    );

    // Add the posting token to the {POST_VARS}.
    $PHORUM['DATA']['POST_VARS'] .=
        "<input type=\"hidden\" name=\"posting_token:$target_page\" " .
        "value=\"$posting_token\"/>\n";

    // Check the posting token if a form post is done.
    if (!empty($_POST))
    {
        if (!isset($_POST["posting_token:$target_page"]) ||
            $_POST["posting_token:$target_page"] != $posting_token) {
            $PHORUM['DATA']['ERROR'] =
                'Possible hack attempt detected. ' .
                'The posted form data was rejected.';
            phorum_build_common_urls();
            phorum_output("message");
            exit();
        }
    }

    return $posting_token;
}
// }}}

?>
