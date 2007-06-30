<?php
# Handle a user forum login

if (!defined('PHORUM')) return;

require_once("./include/api/base.php");
require_once("./include/api/user.php");

// Check the username and password.
$user_id = phorum_api_user_authenticate(
    PHORUM_FORUM_SESSION,     // for a standard front end forum session
    "username",               // the username to check
    "password"                // the password to check
);
if (!$user_id) die("Username or password incorrect!\n");

// Make the authenticated user the active user for Phorum. This is all
// that is needed to tell Phorum that this user is logged in.
$set_active = phorum_api_user_set_active_user(
    PHORUM_FORUM_SESSION,     // for a standard front end forum session
    $user_id,                 // the user_id that has to be the active user
    PHORUM_FLAG_SESSION_ST    // jumpstart the short term session
);
if (!$set_active) die("Setting user_id $user_id as the active user failed!\n");

// Create a session for the active user, so the user will be remembered
// on subsequent requests.
phorum_api_user_session_create(
    PHORUM_FORUM_SESSION,     // for a standard front end forum session
    PHORUM_SESSID_RESET_LOGIN // reset session ids for which that is
);                            // appropriate at login time

?>
