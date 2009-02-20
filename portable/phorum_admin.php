<?php

/**
 * This is pretty straight forward.  We just load the settings to get the
 * actual Phorum base dir and then load the admin from there.  The admin
 * is very flexible and will work from most anywhere.
 *
 * It is probably best to not wrap the Phorum admin inside another application
 * unless you really put in a lot of work to make that happen.
 *
 */

require_once('./phorum_settings.php');

if(isset($PHORUM_DIR)){
    chdir($PHORUM_DIR);
} else {
    trigger_error("\$PHORUM_DIR not set.  Can't start Phorum", E_USER_ERROR);
}

require_once('./admin.php');

?>
