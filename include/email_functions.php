<?php

if(!defined("PHORUM")) return;

function phorum_valid_email($email){
	$PHORUM = $GLOBALS["PHORUM"];

	$ret = false;

	$email = trim($email);

	if(preg_match('/^([a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+(\.[a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+)*)@(((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\]))\.)*((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\]))$/i', $email)){
		if(!$PHORUM["dns_lookup"]){ 
			// format is valid
			// don't look up mail server
			$ret = true;
		}else{ 
			// try to contact the mail server.
			$fulldomain = substr(strstr($email, "@"), 1).".";
			// $domain = $fulldomain; 
			// we cycle through each part of the domain looking for an MX record. ... WHY?
			// while(strstr($domain, ".") && $ret == false){
				if(function_exists('checkdnsrr') && checkdnsrr($fulldomain, "MX")){
					$ret = true;
				}
                                
                                /* else{
					$domain = substr(strstr($domain, "."), 1);
				}
                                */
			// } 
			// some hosts don't have an MX record, but accept mail themselves
			if(!$ret){
				if(@fsockopen($fulldomain, 25)){
					$ret = true;
				}
			}
		}
	}

	return $ret;
}

/**
 * function for sending email to users, gets addresses-array and data-array
 */
function phorum_email_user($addresses, $data)
{
	$PHORUM = $GLOBALS['PHORUM'];

	$mailmessage = $data['mailmessage'];
	unset($data['mailmessage']);
	$mailsubject = $data['mailsubject'];
	unset($data['mailsubject']);

	if(is_array($data) && count($data)) {
		foreach(array_keys($data) as $key){
			$mailmessage = str_replace("%$key%", $data[$key], $mailmessage);
			$mailsubject = str_replace("%$key%", $data[$key], $mailsubject);
		}
	}

	$num_addresses = count($addresses);
    $from_address = "\"".$PHORUM['system_email_from_name']."\" <".$PHORUM['system_email_from_address'].">";

    $hook_data=array(
          'addresses'  => $addresses,
          'from'       => $from_address,
          'subject'    => $mailsubject,
          'body'       => $mailmessage,          
          'bcc'        => $PHORUM['use_bcc']
    );
    
    $send_messages = phorum_hook("send_mail", $hook_data);
    
    if(isset($data["msgid"])){
        $msgid="\nMessage-ID: $data[msgid]";
    } else {
        $msgid="";
    }

	if($send_messages != 0 && $num_addresses > 0){
		if(isset($PHORUM['use_bcc']) && $PHORUM['use_bcc'] && $num_addresses > 3){
			mail(" ", $mailsubject, $mailmessage, "X-Mailer: Phorum5$msgid\nFrom: $from_address\nBCC: " . implode(",", $addresses));
		} else {
			foreach($addresses as $address){
				mail($address, $mailsubject, $mailmessage, "X-Mailer: Phorum5$msgid\nFrom: $from_address");
			}
		}
	}
}

function phorum_email_notice($message)
{
    $PHORUM=$GLOBALS["PHORUM"];

    $mail_users = phorum_db_get_subscribed_users($PHORUM['forum_id'], $message['thread'], PHORUM_SUBSCRIPTION_MESSAGE);
    
    if (count($mail_users)) {
        $mail_data = array("mailmessage" => $PHORUM["DATA"]["LANG"]['NewReplyMessage'],
            "mailsubject" => $PHORUM["DATA"]["LANG"]['NewReplySubject'],
            "forumname" => $PHORUM["DATA"]["NAME"],
            "author"  => $message['author'],
            "subject" => $message['subject'],
            "read_url" => phorum_get_url(PHORUM_READ_URL, $message['thread'], $message['message_id']),
            "remove_url" => phorum_get_url(PHORUM_FOLLOW_URL, $message['thread'], "remove=1"),
            "noemail_url" => phorum_get_url(PHORUM_FOLLOW_URL, $message['thread'], "noemail=1"),
            "followed_threads_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS)
            );
        if (isset($_POST[PHORUM_SESSION])) {
            // strip any auth info from the read url
            $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["read_url"]);
            $mail_data["remove_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["remove_url"]);
            $mail_data["noemail_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["noemail_url"]);
            $mail_data["followed_threads_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["followed_threads_url"]);

        } 
        phorum_email_user($mail_users, $mail_data);
    } 
}

function phorum_email_moderators($message)
{
    $PHORUM=$GLOBALS["PHORUM"];

    $mail_users = phorum_user_get_moderators($PHORUM['forum_id']);

    if (count($mail_users)) {
        if($message["status"] > 0) { // just notification of a new message
            $mailtext = $PHORUM["DATA"]["LANG"]['NewUnModeratedMessage'];
        } else { // posts needing approval
            $mailtext = $PHORUM["DATA"]["LANG"]['NewModeratedMessage'];
        }
        $mail_data = array("mailmessage" => $mailtext,
                           "mailsubject" => $PHORUM["DATA"]["LANG"]['NewModeratedSubject'],
                           "forumname" => $PHORUM["DATA"]["NAME"],
                           "author"  => $message['author'],
                           "subject" => $message['subject'],
                           "approve_url" => phorum_get_url(PHORUM_PREPOST_URL),
                           "read_url" => phorum_get_url(PHORUM_READ_URL, $message['thread'], $message['message_id'])
        );
        if (isset($_POST[PHORUM_SESSION])) {
            // strip any auth info from the read url
            $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["read_url"]);
            $mail_data["approve_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION . "=" . urlencode($_POST[PHORUM_SESSION]) . "!", "", $mail_data["approve_url"]);

        }         
        phorum_email_user($mail_users, $mail_data);
    }
}

?>
