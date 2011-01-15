<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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

if (!defined("PHORUM")) return;

/**
 * Outputs a confirmation form.
 *
 * To maintain backwards compatibility with the templates,
 * we generate a form in code and output it using stdblock.
 *
 * The function exits the script after displaying the form.
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
            <input type="hidden" name="confirmation" value="1" />

            <?php foreach ($args as $name => $value){ ?>
                <input type="hidden"
                    name="<?php echo htmlspecialchars($name, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?>"
                    value="<?php echo htmlspecialchars($value, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]); ?>" />
            <?php } ?>

            <?php echo $PHORUM["DATA"]["POST_VARS"]; ?>

            <input type="submit"
                name="confirmation_yes"
                value="<?php echo $PHORUM["DATA"]["LANG"]["Yes"]; ?>" />

            <input type="submit"
                name="confirmation_no"
                value="<?php echo $PHORUM["DATA"]["LANG"]["No"]; ?>" />

        </form>
        <br />
    </div>
    <?php

    $PHORUM["DATA"]["BLOCK_CONTENT"] = ob_get_clean();
    phorum_api_output("stdblock");
    exit();
}

/**
 * A utility function to handle redirecting back from the moderation page.
 * This function will determine a suitable return page on its own.
 */
function phorum_redirect_back_from_moderation()
{
    global $PHORUM;

    // When the parameter "prepost" is available in the request, then
    // the moderation action was initiated from the moderation interface
    // in the user control center.
    if (isset($_POST['prepost']) ||
        isset($_GET['prepost'])  ||
        isset($PHORUM['args']['prepost']))
    {
        phorum_api_redirect(phorum_api_url(
            PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED
        ));
    }

    // Find the id of the thread or message on which the moderation
    // action has been performed.
    if (isset($_POST["thread"])) {
        $msgthd_id = (int)$_POST["thread"];
    } elseif(isset($PHORUM['args'][2])) {
        $msgthd_id = (int)$PHORUM['args'][2];
    } else {
        $msgthd_id = 0;
    }

    // If no id was found, then redirect back to the list page for
    // the active forum or the index page if no active forum is available.
    if (empty($msgthd_id))
    {
        if (empty($PHORUM["forum_id"])) {
            phorum_api_redirect(PHORUM_INDEX_URL);
        } else {
            phorum_api_redirect(PHORUM_LIST_URL);
        }
    }

    // Check if the message still exists. It might be gone after a
    // moderation action. When the message no longer exists, redirect
    // the user back to the list page for the active forum.
    $message = phorum_db_get_message($msgthd_id);
    if (!$message) phorum_return_to_list();

    // Redirect back to the message that we found.
    phorum_api_redirect(phorum_api_url(
       PHORUM_READ_URL, $message['thread'], $message['message_id']
    ));
}

?>
