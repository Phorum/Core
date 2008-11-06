<?php
/*
* SMTP-Mail-Module
* made by Thomas Seifert
* email: thomas (at) phorum.org
*
*/

if(!defined("PHORUM")) return;

function phorum_smtp_send_messages ($data)
{
    $PHORUM=$GLOBALS["PHORUM"];

    $addresses = $data['addresses'];
    $subject   = $data['subject'];
    $message   = $data['body'];
    $num_addresses = count($addresses);

    $settings  = $PHORUM['smtp_mail'];
    $settings['auth'] = empty($settings['auth'])?false:true;

    if($num_addresses > 0){

        try {

			require("./mods/smtp_mail/phpmailer/class.phpmailer.php");  
			  
			$mail = new PHPMailer();  
			$mail->PluginDir = "./mods/smtp_mail/phpmailer/";
			  
            $mail->CharSet  = $PHORUM["DATA"]["CHARSET"];
            $mail->Encoding = $PHORUM["DATA"]["MAILENCODING"];
			$mail->Mailer   = "smtp";  
			$mail->IsHTML(false);
			
		    $mail->From     = $PHORUM['system_email_from_address'];
		    $mail->Sender   = $PHORUM['system_email_from_address'];
		    $mail->FromName = $PHORUM['system_email_from_name'];			

            if(!isset($settings['host']) || empty($settings['host'])) {
                $settings['host'] = 'localhost';
            }

            if(!isset($settings['port']) || empty($settings['port'])) {
                $settings['port'] = '25';
            }

			$mail->Host     = $settings['host'];  
			$mail->Port     = $settings['port'];
			
            // set the connection type
            if($settings['conn'] == 'ssl') {
                $mail->SMTPSecure   = "ssl";
            } elseif($settings['conn'] == 'tls') {
                $mail->SMTPSecure   = "tls";
            }
            
            // smtp-authentication
            if($settings['auth'] && !empty($settings['username'])) {
            	$mail->SMTPAuth=true;
            	$mail->Username = $settings['username'];
            	$mail->Password = $settings['password'];
            }
            
            $mail->Body    = $message;
            $mail->Subject = $subject;
            
            // add the newly created message-id
            $mail->HeaderLine("Message-ID", $data['messageid']);
            
            
            if(!empty($settings['bcc']) && $num_addresses > 3){
            	$bcc = 1;
            	$mail->AddAddress("undisclosed-recipients:;");
            } else {
            	$bcc = 0;
            	// lets keep the connection alive - it could be multiple mails
            	$mail->SMTPKeepAlive = true;
            }
            
            foreach ($addresses as $address) {
            	if($bcc){
            		$mail->addBCC($address);
            	} else {
            		$mail->AddAddress($address);
            		if(!$mail->Send()) {
            			echo "There was an error sending the message.\n";
            			echo "Error returned was ".$mail->ErrorInfo;
            		}
   
				    // Clear all addresses  for next loop  
					$mail->ClearAddresses(); 
            	}
            }
            
            // bcc needs just one send call
            if($bcc) {
            		if(!$mail->Send()) {
            			echo "There was an error sending the bcc message.\n";
            			echo "Error returned was ".$mail->ErrorInfo;
            		}
            }
            
            // we have to close the connection with pipelining
            // which is only used in non-bcc mode
            if(!$bcc) {
            	$mail->SmtpClose();
            }
            
            
        } catch (Exception $e) {
            echo "There was a problem communicating with SMTP: " . $e->getMessage();            exit();
        } 
    }

    unset($message);
    unset($mail);

    // make sure that the internal mail-facility doesn't kick in
    return 0;
}

?>
