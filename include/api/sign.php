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
 * This script implements functions for signing data using Phorum's
 * private key. This private key is stored in the Phorum settings variable
 * $PHORUM['private_key'].
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_sign()
/**
 * Generates an MD5 signature for a piece of data using Phorum's secret
 * private key. This can be used to sign data that travels an unsafe path
 * (for example data that is sent to a user's browser and then back to
 * Phorum) and for which tampering should be prevented.
 *
 * @param string $data
 *     The data to sign.
 *
 * @return string
 *     The signature for the data.
 */
function phorum_api_sign($data)
{
   $signature = md5($data . $GLOBALS["PHORUM"]["private_key"]);
   return $signature;
}
// }}}

// {{{ Function: phorum_api_sign_check()
/**
 * Checks whether the signature for a piece of data is valid.
 *
 * @param $data The signed data.
 * @param $signature The signature for the data.
 * @return TRUE in case the signature is okay, FALSE otherwise.
 */
function phorum_api_sign_check($data, $signature)
{
    return md5($data . $GLOBALS["PHORUM"]["private_key"]) == $signature;
}
// }}}

?>
