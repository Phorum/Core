<?php
/*
FUNCTION

    checkpm - check for availability of new unread private messages.

ARGUMENTS

    [user_id]

        The user_id of the user to check for. If this user_id is
        not provided, the user_id of the logged in user will be
        used instead. If no user is logged in, the call will
        return zero by default.

EXAMPLE JSON REQUESTS

    { "call": "checkpm" }

    { "call": "checkpm",
      "user_id": 1234 }

RETURN VALUE

    The call will return zero if there are no unread private
    messages or if no user_id is known. The call will return
    one if there are unread private messages.

ERRORS

    The call will return an error if the user_id is not in the
    right format.

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (! defined('PHORUM')) return;

$user_id = phorum_ajax_getarg('user_id', 'int>0', 0);
if ($user_id == 0 && isset($PHORUM["user"]["user_id"])) {
    $user_id = $PHORUM["user"]["user_id"];
}

$hasnew = $user_id == 0
        ? 0
        : phorum_db_pm_checknew($user_id) ? 1 : 0;

phorum_ajax_return($hasnew);
?>
