<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => NULL,
    'TEXT' => $PHORUM['DATA']['LANG']['Moderate'] . ': ' .
              $PHORUM['DATA']['LANG']['DeleteMessage'],
    'TYPE' => 'delete'
);

if (count($_GET) && empty($_POST["thread"]))
{
    $args = array(
        "mod_step" => PHORUM_DELETE_MESSAGE,
        "thread"   => $msgthd_id
    );

    foreach($PHORUM["args"] as $k=>$v){
        if(!is_numeric($k)){
            $args[$k] = $v;
        }
    }

    phorum_show_confirmation_form(
        $PHORUM["DATA"]["LANG"]["ConfirmDeleteMessage"],
        phorum_api_url(PHORUM_MODERATION_ACTION_URL),
        $args
    );
}

$message = $PHORUM['DB']->get_message($msgthd_id);

/*
 * [hook]
 *     before_delete
 *
 * [description]
 *     This hook allows modules to implement extra or different delete
 *     functionality.<sbr/>
 *     <sbr/>
 *     The primary use of this hook would be for moving the messages
 *     to some archive-area instead of really deleting them.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, just before deleting
 *     the message(s)
 *
 * [input]
 *     An array containing the following 5 parameters:
 *     <ul>
 *     <li><literal>$delete_handled</literal>:
 *         default = <literal>false</literal>, set it to true to avoid
 *         the real delete afterwards</li>
 *     <li><literal>$msg_ids</literal>:
 *         an array containing all deleted message ids</li>
 *     <li><literal>$msgthd_id</literal>:
 *         the msg-id or thread-id to be deleted</li>
 *     <li><literal>$message</literal>:
 *         an array of the data for the message retrieved with
 *         <literal>$msgthd_id</literal></li>
 *     <li><literal>$delete_mode</literal>:
 *         mode of deletion, either
 *         <literal>PHORUM_DELETE_MESSAGE</literal> or
 *         <literal>PHORUM_DELETE_TREE</literal></li>
 *     </ul>
 *
 * [output]
 *     Same as input.<sbr/>
 *     <literal>$delete_handled</literal> and
 *     <literal>$msg_ids</literal> are used as return data for the hook.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_before_delete($data)
 *     {
 *         global $PHORUM;
 *
 *         // Store the message data in the module's settings for
 *         // future use.
 *         $PHORUM["mod_foo"]["deleted_messages"][$msgthd_id] = $message;
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
 *
 *         return $data;
 *     }
 *     </hookcode>
 */
$delete_handled = 0;
if (isset($PHORUM["hooks"]["before_delete"]))
    list($delete_handled,$msg_ids,$msgthd_id,$message,$delete_mode) = phorum_api_hook("before_delete", array(0,array(),$msgthd_id,$message,PHORUM_DELETE_MESSAGE));

// Handle the delete action, unless a module already handled it.
if (!$delete_handled) {

    // Delete the message from the database.
    $PHORUM['DB']->delete_message($msgthd_id, PHORUM_DELETE_MESSAGE);

    // Delete the message attachments from the database.
    require_once PHORUM_PATH . '/include/api/file.php';
    $files=$PHORUM['DB']->get_message_file_list($msgthd_id);
    foreach($files as $file_id=>$data) {
        phorum_api_file_delete($file_id);
    }
}

/*
 * [hook]
 *     delete
 *
 * [description]
 *     This hook can be used for cleaning up anything you may have
 *     created with the post_post hook or any other hook that stored
 *     data tied to messages.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right after deleting a
 *     message from the database.
 *
 * [input]
 *     An array of ids for messages that have been deleted (read-only).
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_delete($msgthd_ids)
 *     {
 *         global $PHORUM;
 *
 *         // Log the deleted message ids
 *         foreach ($msgthd_ids as $msgthd_id) {
 *             $PHORUM["mod_foo"]["deleted_messages"][] = $msgthd_id;
 *         }
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
 *
 *         return $msgthd_ids;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["delete"])) {
    phorum_api_hook("delete", array($msgthd_id));
}

$PHORUM['DATA']['OKMSG']="1 ".$PHORUM["DATA"]['LANG']['MsgDeletedOk'];
if(isset($PHORUM['args']['old_forum']) && !empty($PHORUM['args']['old_forum'])) {
    $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
}

// Determine where to redirect to after the delete operation.
//
// When we're coming from the message moderation interface in
// the control center, then redirect back to there.
if(isset($PHORUM['args']["prepost"]))
{
    $PHORUM['DATA']["URL"]["REDIRECT"] = phorum_api_url(
        PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
}
else
{
    // If we did not delete the thread starter, then redirect
    // back to the parent of the deleted message.
    if ($message['parent_id'])
    {
        $PHORUM['DATA']['URL']['REDIRECT'] = phorum_api_url(
            PHORUM_READ_URL, $message['thread'], $message['parent_id']);
    }
    // Otherwise, redirect back to the message list for the forum.
    else
    {
        $PHORUM['DATA']['URL']['REDIRECT'] =
            $PHORUM['DATA']['URL']['LIST'];
    }
}

?>
