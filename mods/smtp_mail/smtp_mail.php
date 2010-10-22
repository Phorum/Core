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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

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

			require_once("./mods/smtp_mail/phpmailer/class.phpmailer.php");  
			  
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
            // in phpmailer as a public var
            $mail->MessageID=$data['messageid'];
            
            // add custom headers if defined
            if(!empty($data['custom_headers'])) {
                // custom headers in phpmailer are added one by one
            	$custom_headers = explode("\n",$data['custom_headers']);
            	foreach($custom_headers as $cheader) {
            		$mail->AddCustomHeader($cheader);
            	}
            }
            
            // add attachments if provided
            if(isset($data['attachments']) && count($data['attachments'])) {
            	/*
            	 * Expected input is an array of
            	 * 
            	 * array(
            	 * 'filename'=>'name of the file including extension',
            	 * 'filedata'=>'plain (not encoded) content of the file',
            	 * 'mimetype'=>'mime type of the file', (optional)
            	 * )
            	 * 
            	 */
            	
            	foreach($data['attachments'] as $att_id => $attachment) {
            		$att_type = (!empty($attachment['mimetype']))?$attachment['mimetype']:'application/octet-stream';
            		$mail->AddStringAttachment($attachment['filedata'],$attachment['filename'],'base64',$att_type);
            		
            		// try to unset it in the original array to save memory
            		unset($data['attachments'][$att_id]);
            	}
            	
            }
            
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
            		    $error_msg  = "There was an error sending the message.";
            		    $detail_msg = "Error returned was: ".$mail->ErrorInfo;
            		   
                        if (function_exists('event_logging_writelog')) {
                            event_logging_writelog(array(
                               "source"    => "smtp_mail",
                               "message"   => $error_msg,
                               "details"   => $detail_msg,
							   "loglevel"  => EVENTLOG_LVL_ERROR,
                               "category"  => EVENTLOG_CAT_MODULE
                            ));
                        }            		    
                        if(!isset($settings['show_errors']) || !empty($settings['show_errors'])) {
                		    echo $error_msg."\n";
                		    echo $detail_msg;         
                        }
            		} elseif(!empty($settings['log_successful'])) {
            		      if (function_exists('event_logging_writelog')) {
                            event_logging_writelog(array(
                               "source"    => "smtp_mail",
                               "message"   => "Email successfully sent",
                               "details"   => "An email has been sent:\nTo:$address\nSubject: $subject\nBody: $message\n" ,
							   "loglevel"  => EVENTLOG_LVL_INFO,
                               "category"  => EVENTLOG_CAT_MODULE
                          ));
                        }     
            		}   
				    // Clear all addresses  for next loop  
					$mail->ClearAddresses(); 
            	}
            }
            
            // bcc needs just one send call
            if($bcc) {
            		if(!$mail->Send()) {
            		   $error_msg  = "There was an error sending the bcc message.";
            		   $detail_msg = "Error returned was: ".$mail->ErrorInfo;
            		   
                       if (function_exists('event_logging_writelog')) {
                            event_logging_writelog(array(
                               "source"    => "smtp_mail",
                               "message"   => $error_msg,
                               "details"   => $detail_msg,
							   "loglevel"  => EVENTLOG_LVL_ERROR,
                               "category"  => EVENTLOG_CAT_MODULE
                            ));
                       }            	
                       if(!isset($settings['show_errors']) || !empty($settings['show_errors'])) {	    
                		   echo $error_msg."\n";
                		   echo $detail_msg;
                       }
            		} elseif(!empty($settings['log_successful'])) {
            		      if (function_exists('event_logging_writelog')) {
            		        $address_join = implode(",",$addresses);
            		          
                            event_logging_writelog(array(
                               "source"    => "smtp_mail",
                               "message"   => "BCC-Email successfully sent",
                               "details"   => "An email (bcc-mode) has been sent:\nBCC:$address_join\nSubject: $subject\nBody: $message\n" ,
							   "loglevel"  => EVENTLOG_LVL_INFO,
                               "category"  => EVENTLOG_CAT_MODULE
                          ));
                        }     
            		}
            }
            
            // we have to close the connection with pipelining
            // which is only used in non-bcc mode
            if(!$bcc) {
            	$mail->SmtpClose();
            }
            
            
        } catch (Exception $e) {
            $error_msg  = "There was a problem communicating with SMTP";
            $detail_msg = "The error returned was: ".$e->getMessage();
            
            if (function_exists('event_logging_writelog')) {
                event_logging_writelog(array(
                      "source"    => "smtp_mail",
                      "message"   => $error_msg,
                      "details"   => $detail_msg,
    				  "loglevel"  => EVENTLOG_LVL_ERROR,
                      "category"  => EVENTLOG_CAT_MODULE
                ));
            }
            if(!isset($settings['show_errors']) || !empty($settings['show_errors'])) {
                echo $error_msg."\n";
                echo $detail_msg;            
            }
            exit();
        } 
    }

    unset($message);
    unset($mail);

    // make sure that the internal mail-facility doesn't kick in
    return 0;
}

?>
