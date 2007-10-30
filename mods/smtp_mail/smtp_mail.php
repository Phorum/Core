<?php
/*
* SMTP-Mail-Module v0.8
* made by Thomas Seifert
* email: thomas (at) phorum.org
*
*/

if(!defined("PHORUM")) return;
define('SWIFT_DIRECTORY','./mods/smtp_mail/swiftmailer');

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

        try {

            // include the swiftmailer-class

            require_once SWIFT_DIRECTORY."/Swift.php";
            require_once SWIFT_DIRECTORY."/Swift/Connection/SMTP.php";

            // set the connection type
            if($settings['conn'] == 'plain') {
                $conn_type = Swift_Connection_SMTP::ENC_OFF;
            } elseif($settings['conn'] == 'ssl') {
                $conn_type = Swift_Connection_SMTP::ENC_SSL;
            } elseif($settings['conn'] == 'tls') {
                $conn_type = Swift_Connection_SMTP::ENC_TLS;
            } else {
                $conn_type = Swift_Connection_SMTP::AUTO_DETECT;
            }

            if(!isset($settings['host']) || empty($settings['host'])) {
                $settings['host'] = 'localhost';
            }

            if(!isset($settings['port']) || empty($settings['port'])) {
                $settings['port'] = '25';
            }

            // setup the connection with hostname and port
            $smtp = new Swift_Connection_SMTP($settings['host'], $settings['port'],$conn_type);

            // smtp-authentication
            if($settings['auth'] && !empty($settings['username'])) {
                $smtp->setUsername($settings['username']);
                $smtp->setpassword($settings['password']);
            }

            // construct the swift-mailer
            $swift = new Swift($smtp);

            // construct the message
            $message = new Swift_Message($subject, $message, $type="text/plain", $PHORUM["DATA"]["MAILENCODING"], $PHORUM["DATA"]["CHARSET"]);

            $recipients = new Swift_RecipientList();

            if(isset($settings['bcc']) && $settings['bcc'] && $num_addresses > 3){

                $recipients->addTo("undisclosed-recipients:;");
                $recipients->addBcc($addresses);

            } else {

                $recipients->addTo($addresses);

            }

            $swift->batchSend($message,$recipients,new Swift_Address($PHORUM["system_email_from_address"], $PHORUM["system_email_from_name"]));

        } catch (Swift_Connection_Exception $e) {
            echo "There was a problem communicating with SMTP: " . $e->getMessage();
            exit();
        } catch (Swift_Message_MimeException $e) {
            echo "There was an unexpected problem building the email:" . $e->getMessage();
            exit();
        }
    }

    unset($recipients);
    unset($message);
    unset($swift);
    unset($smtp);

    // make sure that the internal mail-facility doesn't kick in
    return 0;
}

?>
