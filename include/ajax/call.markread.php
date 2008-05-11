<?php
/*
FUNCTION

    markread - mark messages read

ARGUMENTS

    [messages]

        An array containing message_ids of messages to mark read.

    [threads]

        An array containing message_ids of threads to mark read.

    [forums]

        An array containing forum_ids of forums to mark read.


EXAMPLE JSON REQUESTS

    Mark a couple of messages read:

        { "call": "markread",
          "messages": [ 2, 12, 65, 987 ] }

    Mark forums 2 and 13 read:

        { "call": "markread",
          "forums": [ 2, 13 ] }

    Mark a couple of threads and messages read:

        { "call": "markread",
          "threads": [ 4, 13, 96 ],
          "messages": [ 1, 55, 321 ] }

RETURN VALUE

    A true value in case marking the items read was successful.

ERRORS

    The call will return an error if the messages / threads
    array is not in the right format.

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (!defined('PHORUM')) return;

// This call only makes sense for logged in users.
// For anonymous users, we'll ignore the call and pretend it was successful.
if (!$PHORUM['DATA']['LOGGEDIN']) ajax_return(TRUE);

// Load the newflags API, which handles marking messages as read.
require_once('./include/api/newflags.php');

// Mark messages, threads and/or forums as read.
foreach (array(
    'messages' => PHORUM_MARKREAD_MESSAGES,
    'threads'  => PHORUM_MARKREAD_THREADS,
    'forums'   => PHORUM_MARKREAD_FORUMS ) as $arg => $mode)
{
    $items = phorum_ajax_getarg($arg, 'array:int>0', array());
    if (!empty($items)) {
        phorum_api_newflags_markread($items, $mode);
    }
}

// We return TRUE (unless some error occured in the previous code).
phorum_ajax_return(TRUE);

?>
