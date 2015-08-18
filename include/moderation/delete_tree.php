<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => NULL,
    'TEXT' => $PHORUM['DATA']['LANG']['Moderate'] . ': ' .
              $PHORUM['DATA']['LANG']['DelMessReplies'],
    'TYPE' => 'delete'
);

if (count($_GET) && empty($_POST["thread"]))
{
    $args = array(
        "mod_step" => PHORUM_DELETE_TREE,
        "thread"   => $msgthd_id
    );

    foreach($PHORUM["args"] as $k=>$v){
        if(!is_numeric($k)){
            $args[$k] = $v;
        }
    }

    phorum_show_confirmation_form(
        $PHORUM["DATA"]["LANG"]["ConfirmDeleteThread"],
        phorum_api_url(PHORUM_MODERATION_ACTION_URL),
        $args
    );
}

$message = $PHORUM['DB']->get_message($msgthd_id);

$nummsgs = 0;

// A hook to allow modules to implement extra or different
// delete functionality.
$delete_handled = 0;
if (isset($PHORUM["hooks"]["before_delete"]))
    list($delete_handled,$msg_ids,$msgthd_id,$message,$delete_mode) = phorum_api_hook("before_delete", array(0,array(),$msgthd_id,$message,PHORUM_DELETE_TREE));

if(!$delete_handled) {

    // Delete the message and all its replies.
    $msg_ids = $PHORUM['DB']->delete_message($msgthd_id, PHORUM_DELETE_TREE);

    // Cleanup the attachments for all deleted messages.
    require_once PHORUM_PATH . '/include/api/file.php';
    foreach($msg_ids as $id){
        $files=$PHORUM['DB']->get_message_file_list($id);
        foreach($files as $file_id=>$data){
            phorum_api_file_delete($file_id);
        }
    }

    // Check if we have moved threads to delete.
    // We unset the forum id, so $PHORUM['DB']->get_messages()
    // will return messages with the same thread id in
    // other forums as well (those are the move notifications).
    $forum_id = $PHORUM["forum_id"];
    $PHORUM["forum_id"] = 0;
    $moved = $PHORUM['DB']->get_messages($msgthd_id);

    foreach ($moved as $id => $data) {
        if (!empty($data["moved"])) {
            $PHORUM["forum_id"] = $data['forum_id'];
            $PHORUM['DB']->delete_message($id, PHORUM_DELETE_MESSAGE);
        }
    }
    $PHORUM["forum_id"] = $forum_id;

}

$nummsgs = count($msg_ids);

// Run a hook for performing custom actions after cleanup.
if (isset($PHORUM["hooks"]["delete"])) {
    phorum_api_hook("delete", $msg_ids);
}

$PHORUM['DATA']['OKMSG']=$nummsgs." ".$PHORUM["DATA"]["LANG"]['MsgDeletedOk'];
if(isset($PHORUM['args']['old_forum']) && !empty($PHORUM['args']['old_forum'])) {
    $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
}
if (isset($PHORUM['args']["prepost"])) {
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_api_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
} else {
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
}

?>
