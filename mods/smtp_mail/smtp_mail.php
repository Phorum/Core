<?php
/*
* SMTP-Mail-Module v0.8
* made by Thomas Seifert
* email: thomas (at) phorum.org
*
*/

if(!defined("PHORUM")) return;

function phorum_smtp_send_messages ($data)
{
    $PHORUM=$GLOBALS["PHORUM"];

    $from      = $data['from'];
    $addresses = $data['addresses'];
    $subject   = $data['subject'];
    $message   = $data['body'];
  	$num_addresses = count($addresses);

    $settings  = $PHORUM['smtp_mail'];
    $settings['auth'] = empty($settings['auth'])?false:true;

	if($num_addresses > 0){

	    // include the phpmailer-class
	    include_once('./mods/smtp_mail/phpmailer/class.phpmailer.php');

	    $mail = new PHPMailer();
	    // set the plugin-dir so it finds its other classes
	    $mail->PluginDir="./mods/smtp_mail/phpmailer/";

	    $mail->IsSMTP(); // telling the class to use SMTP
	    $mail->Host = $settings['host']; // SMTP server
	    $mail->Port = $settings['port'];

	    if($settings['auth']) {
	        $mail->SMTPAuth = true;
	        $mail->Username = $settings['username'];
	        $mail->Password = $settings['password'];
	    }
	    $mail->From = $PHORUM["system_email_from_address"];
	    $mail->FromName = $PHORUM["system_email_from_name"];
        $mail->CharSet = $PHORUM["DATA"]["CHARSET"];
        $mail->Encoding = $PHORUM["DATA"]["MAILENCODING"];
        $mail->SetLanguage("en",'./mods/smtp_mail/phpmailer/language/');

	    $mail->Subject = $subject;
	    $mail->Body = $message;


		if(isset($PHORUM['use_bcc']) && $PHORUM['use_bcc'] && $num_addresses > 3){
		    foreach($addresses as $address) {
		        $mail->AddBCC($address);
		    }

            // send the actual message
            if(!$mail->Send()) {
                echo "Error while sending mails in bcc-mode... ";
                echo "Error was: ".$mail->ErrorInfo;
            }
            // remove the bccs for safety
            $mail->ClearBCCs();

		} else {
			foreach($addresses as $address) {

			    // add the recipient
                $mail->AddAddress($address);

                // send the message
                if(!$mail->Send()) {
                    echo "Error while sending mails in one-at-a-time mode ... ";
                    echo "Error was: ".$mail->ErrorInfo;
                }

                // remove the address from above for the next round
                $mail->ClearAddresses();
			}
		}
	}

	unset($mail);

    // make sure that the internal mail-facility doesn't kick in
    return 0;
}

?>
