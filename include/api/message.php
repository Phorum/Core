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
 * This script implements the Phorum message API.
 *
 * The message API is used for managing messages and user related data.
 *
 * @package    PhorumAPI
 * @subpackage Message
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Variable definitions

global $PHORUM;

/**
 * The MFLD_* definitions indicate the position of the configation
 * options in the message field definitions.
 */
define('MFLD_TR',      0);
define('MFLD_TYPE',    1);
define('MFLD_DEFAULT', 2);

/**
 * This array describes message data fields. It is mainly used internally
 * for configuring how to handle the fields and for doing checks on them.
 * Value format: <t|b>:<type>[:default]
 * t = field that is only used for the thread starter
 * b = field that is used for both thread starters and replies
 */
$PHORUM['API']['message_fields'] = array
(
    // Message ID, both in numerical and string format. The
    // string formatted msgid field can be used in mail messages
    // for the Message-ID mail header.
    'message_id'        => 'b:int',
    'msgid'             => 'b:string',

    // The date and time at which the message was posted.
    'datestamp'         => 'b:int',

    // The position of the message in the forum.
    'forum_id'          => 'b:int',    // in which forum
    'thread'            => 'b:int',    // in which thread
    'parent_id'         => 'b:int:0',  // below which parent message

    // Special message and thread flags.
    'status'            => 'b:int:' . PHORUM_STATUS_APPROVED,
    'sort'              => 'b:int:' . PHORUM_SORT_DEFAULT, // for sticky msgs
    'moved'             => 't:bool:0', // this message is a move notification
    'closed'            => 't:bool:0', // thread is closed for posting

    // Information about the message author.
    'user_id'           => 'b:int:0',
    'author'            => 'b:string',
    'email'             => 'b:string',
    'moderator_post'    => 'b:bool:0', // user was a moderator when posting
    'ip'                => 'b:string',

    // The message contents.
    'subject'           => 'b:string',
    'body'              => 'b:string',

    // Aribitrary meta data storage.
    'meta'              => 'b:array',

    // Counters.
    'thread_count'      => 't:int',    // number of messages in a thread
    'viewcount'         => 'b:int',    // how often the message was viewed
    'threadviewcount'   => 't:int',    // how often the thread was viewed

    // Information about the last update to the thread.
    'modifystamp'       => 't:int',    // when the last message was posted
    'recent_message_id' => 't:int',    // message_id of most recent message
    'recent_user_id'    => 't:int',    // user_id of most recent message author
    'recent_author'     => 't:string', // name of most recent message author
);

// }}}

// ----------------------------------------------------------------------
// Handling message data.
// ----------------------------------------------------------------------

// TODO

?>
