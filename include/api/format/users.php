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
 * This script implements user formatting.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_format_users()
/**
 * This function handles preparing user data for use in the templates.
 *
 * @param array $users
 *     An array of users that have to be formatted.
 *     Each user is an array on its own, containing the user data.
 *
 * @return array
 *     The same as the $users argument array, with formatting applied.
 */
function phorum_api_format_users($users)
{
    global $PHORUM;

    foreach ($users as $id => $user)
    {
        foreach (array(
            'username', 'real_name', 'display_name',
            'email', 'signature'
        ) as $field) {
            if (isset($user[$field])) {
                if($field == 'display_name' && !empty($PHORUM['custom_display_name'])) {
                      $users[$id][$field] = $user[$field];
                } else {
                    $users[$id][$field] = phorum_api_format_htmlspecialchars($user[$field]);
                }
            }
        }
    }

    return $users;
}
// }}}

?>
