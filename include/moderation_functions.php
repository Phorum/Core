<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM")) return;

include_once("./include/thread_info.php");

/**
 * just returns to the list and exits the program
 */
function phorum_return_to_list()
{
    $PHORUM=$GLOBALS["PHORUM"];
    if(!empty($PHORUM["forum_id"])){
        phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
    }else{
        phorum_redirect_by_url(phorum_get_url(PHORUM_INDEX_URL));
    }
    exit();
}

/* A function to get moderator_data from the user's profile.
 * Without an argument, all moderator_data is returned. With a key as
 * argument, the data for that key is returned or NULL in case the
 * key does not exist.
 */
function phorum_moderator_data_get($key = null)
{
    $PHORUM = $GLOBALS['PHORUM'];

    $user_data = phorum_api_user_get($PHORUM['DATA']['USER']['user_id']);
    if( $user_data['moderator_data'] ) {
        $moderator_data =unserialize($user_data['moderator_data']);
    } else {
        $moderator_data =array();
    }
    if (is_null($key)) {
        return $moderator_data;
    } else {
        return isset($moderator_data[$key]) ? $moderator_data[$key] : NULL;
    }
}

/* A function to save moderator_data in the user's profile. */
function phorum_moderator_data_save($moderator_data)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Clear value in case no data is left in $moderator_data.
    $value = count($moderator_data) ? serialize($moderator_data) : '';

    phorum_api_user_save_raw(array(
        "user_id" => $PHORUM['user']['user_id'],
        "moderator_data" => $value,
    ));
}

/* A function to place a key/value pair in the moderator_data. */
function phorum_moderator_data_put($key, $val)
{
    $moderator_data = phorum_moderator_data_get();
    $moderator_data[$key] = $val;
    phorum_moderator_data_save($moderator_data);
}

/* A function to remove a key/value pair from the moderator_data. */
function phorum_moderator_data_remove($key)
{
    $moderator_data = phorum_moderator_data_get();
    unset($moderator_data[$key]);
    phorum_moderator_data_save($moderator_data);
}

/**
 * Outputs a confirmation form.  To maintain backwards compatibility with
 * the templates, we generate a form in code and output it using stdblock
 *
 * The function exits the script after displaying the form
 *
 * @param   string    $message  Message to display to users
 * @param   string    $action   The URI to post the form to
 * @param   array     $args     The hidden form values to be used in the form
 * @return  void
 *
 */
function phorum_show_confirmation_form($message, $action, $args)
{
    global $PHORUM;

    ob_start();

    ?>
    <div style="text-align: center;">
        <strong><?php echo htmlspecialchars($message, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?></strong>
        <br />
        <br />
        <form
            action="<?php echo htmlspecialchars($action, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?>"
            method="post">

            <input type="hidden"
                name="forum_id" value="<?php echo $PHORUM["forum_id"]; ?>" />

            <?php foreach($args as $name=>$value){ ?>
                <input type="hidden"
                    name="<?php echo htmlspecialchars($name, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?>"
                    value="<?php echo htmlspecialchars($value, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?>" />
            <?php } ?>

            <?php echo $PHORUM["DATA"]["POST_VARS"]; ?>

            <input type="submit"
                name="confirmation"
                value="<?php echo $PHORUM["DATA"]["LANG"]["Yes"]; ?>" />

            <input type="submit"
                name="confirmation"
                value="<?php echo $PHORUM["DATA"]["LANG"]["No"]; ?>" />

        </form>
        <br />
    </div>
    <?php

    $PHORUM["DATA"]["BLOCK_CONTENT"] = ob_get_clean();
    phorum_output("stdblock");
}

?>
