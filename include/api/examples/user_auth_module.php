<?php

/* phorum module info
hook:  user_authenticate|test_user_authenticate
hook:  user_session_create|test_user_session_create
hook:  user_session_restore|test_user_session_restore
hook:  user_session_destroy|test_user_session_destroy
title: User API hook demo
desc:  This module is a demo for demonstrating the user API hooks, which can be used for implementing external user authentication and session.
author: Phorum Dev Team
url: http://www.phorum.org/
*/

// Let's presume that this is an external session.
session_start();

// We can authenticate the user against our own user database.
// This demo hook will authenticate the user with username "foo"
// and password "bar" as the Phorum user with user_id = 1.
function test_user_authenticate($data)
{
    // Only do this for the forum session. We do not touch the admin session.
    if ($data['type'] == PHORUM_FORUM_SESSION) {
        if ($data['username'] == 'foo' && $data['password'] == 'bar') {
            $data['user_id'] = 1;
        } else {
            $data['user_id'] = FALSE;
        }
    }

    return $data;
}

// This hook overrides creating a Phorum user session. Instead of running
// a Phorum session, we use the PHP session system to track the logged
// in user. We do this by storing the active user_id in the $_SESSION
// variable.
function test_user_session_create($type)
{
    // Only do this for the forum session. We do not touch the admin session.
    if ($type == PHORUM_FORUM_SESSION) {
        $_SESSION['loggedin_user'] = $GLOBALS["PHORUM"]["user"]["user_id"];
        return NULL;
    } else {
        return $type;
    }
}

// This hook overrides the Phorum user session restore process. We use
// the user id that we stored in the PHP $_SESSION variable as the
// active Phorum user.
function test_user_session_restore($data)
{
    if ($_SESSION['loggedin_user']) {
        $user_id = $_SESSION['loggedin_user'];
        $data[PHORUM_SESSION_LONG_TERM]  = $user_id;
        $data[PHORUM_SESSION_SHORT_TERM] = $user_id;
    } else {
        $data[PHORUM_SESSION_LONG_TERM]  = FALSE;
        $data[PHORUM_SESSION_SHORT_TERM] = FALSE;
    }

    return $data;
}

// This hook overrides destroying a Phorum user session. Instead of destroying
// a Phorum session, we clear the user_id that is stored in the $_SESSION
// variable.
function test_user_session_destroy($type)
{
    // Only do this for the forum session. We do not touch the admin session.
    if ($type == PHORUM_FORUM_SESSION) {
        $_SESSION['loggedin_user'] = FALSE;        
        return NULL;
    } else {
        return $type;
    }
}

?>
