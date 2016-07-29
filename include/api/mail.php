<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

/**
 * This script implements the Phorum mail API.
 *
 * @package    PhorumAPI
 * @subpackage Mail
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

/**
 * The wraplength for Quoted-Printable encoded data. The RFC defines
 * a maximum line length of 76 characters. We use a few characters less
 * here, to make wrapped mailheaders better fit 80 column displays
 * (pathetic, I know).
 */
define('RFC2045_WRAPLEN', 70);

// {{{ Function: phorum_api_mail()
/**
 * Send an mail message to one or more mail addresses.
 *
 * This function takes an array of mail addresses and a data array as
 * its arguments. The fields that can be used in the data array are:
 *
 * <ul>
 *   <li><b>from_address</b> (optional)<br/>
 *       Used as the From: header for the mail. If this
 *       field is absent or empty, then a From: header is constructed
 *       based on Phorum's mail settings. Note: when there are
 *       special (non-ASCII) characters in the from address, then
 *       the calling code has to take care of correctly escaping the
 *       contents (see also {@link phorum_api_mail_encode_header}).</li>
 *   <li><b>mailsubject</b> (mandatory)<br/>
 *       Used as the Subject: header for the mail.</li>
 *   <li><b>mailmessage</b> (mandatory)<br/>
 *       The body for the mail.
 *   <li><b>msgid</b> (optional)<br/>
 *       Used as the Message-ID: header for the mail. A Message-ID header
 *       value looks like "msgid@hostname". If the "@hostname" part is
 *       not available in the provided msgid, then that part is added by this
 *       function automatically. If this field is absent, then a random
 *       Message-ID will automatically be generated.</li>
 *   <li><b>custom_headers</b> (optional)<br/>
 *       This is a string, containing extra mail headers for the mail
 *       message. Multiple headers must be separated by a single
 *       newline ("\n") character. The string must not end with a newline.</li>
 * </ul>
 *
 * Some extra fields that are filled by Phorum code for
 * use by the <literal>mail_prepare</literal> hook:
 * <ul>
 *   <li><b>mailmessagetpl</b> (optional)<br/>
 *     The name of the language string that was used as a template
 *     for the "mailmessage" field.</li>
 *   <li><b>mailsubjecttpl</b> (optional)<br/>
 *     The name of the language string that was used as a template
 *     for the "mailsubject" field.</li>
 *   <li><b>language</b> (optional)<br/>
 *       The name of the language that is used for the mail.
 *       This information is provided, because different users might
 *       be using the forums in a different language.</li>
 * </ul>
 *
 * Any other fields that are in the data are used for doing text
 * replacements in the mail subject and mail message body. For each
 * key/value pair in the array, a global replacement of "%key%" with
 * "value" is performed on the subject and body. What exact key/value
 * pairs are available depends on the calling code.
 *
 * <b>Example call:</b>
 *
 * <code>
 * phorum_api_mail(
 *     'john.doe@example.com',
 *     array(
 *         'from_address' => 'jane.doe@example.com',
 *         'mailsubject'  => 'Get home early today!',
 *         'mailmessage'  => 'I am baking cookies, so do not let me wait.'
 *     )
 * );
 * </code>
 *
 * @param string|array $addresses
 *     A single recipient mail address or an array containing the
 *     recipient mail addresses.
 *
 * @param array $data
 *     An array containing data for the mail message. See the description
 *     of the {@link phorum_api_mail()} function for a description of
 *     the contents of this array.
 *
 * @return integer
 *     The number of recipients to which the message was sent.
 */
function phorum_api_mail($addresses, $data)
{
    global $PHORUM;

    // Turn a single $address into an array.
    if (!is_array($addresses)) {
        $addresses = array($addresses);
    }

    // Check mandatory field.
    if (!isset($data['mailsubject'])) trigger_error(
        'phorum_api_mail(): The mail function was called without a ' .
        'mail subject in the "mailsubject" field.',
        E_USER_ERROR
    );
    if (!isset($data['mailmessage'])) trigger_error(
        'phorum_api_mail(): The mail function was called without a ' .
        'mail message in the "mailmailmessage" field.',
        E_USER_ERROR
    );

    /*
     * [hook]
     *     mail_prepare
     *
     * [description]
     *     This hook is run at the very beginning of the function
     *     <literal>phorum_api_mail()</literal> and is therefore called for
     *     <emphasis>every</emphasis> mail that is sent from Phorum.
     *     Modules can fully change the list of mail addresses and the
     *     message data. All changes will propagate to the workings of
     *     the <literal>phorum_api_mail()</literal> function.
     *
     * [category]
     *     Mail
     *
     * [when]
     *     In the file <filename>include/api/mail.php</filename> at the
     *     start of <literal>phorum_api_mail()</literal>.
     *
     * [input]
     *     An array containing:
     *     <ul>
     *     <li>An array of addresses.</li>
     *     <li>An array containing the data for the message.</li>
     *     </ul>
     *
     * [output]
     *     Same as input, possibly modified.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_mail_prepare ($addresses, $data)
     *     {
     *         global $PHORUM;
     *
     *         // Add a disclaimer to the end of every mail message.
     *         $data["mailmessage"] .= $PHORUM["mod_foo"]["mail_disclaimer"];
     *
     *         return array($addresses, $data);
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['mail_prepare'])) {
        list ($addresses,$data) = phorum_api_hook(
            'mail_prepare', array($addresses,$data)
        );
    }

    // Clear some variables that are only meant as information for
    // the mail_prepare hook.
    unset($data['mailmessagetpl']);
    unset($data['mailsubjecttpl']);
    unset($data['language']);

    // ----------------------------------------------------------------------
    // Generate an RFC compliant Message-ID.
    // ----------------------------------------------------------------------

    # Try to find a useful hostname to use in the Message-ID.
    $host = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else if (function_exists('posix_uname')) {
        $sysinfo = @posix_uname();
        if (!empty($sysinfo['nodename'])) {
            $host .= $sysinfo['nodename'];
        }
        if (!empty($sysinfo['domainname'])) {
            $host .= $sysinfo['domainname'];
        }
    } else if (function_exists('php_uname')) {
        $host = @php_uname('n');
    } else if (($envhost = getenv('HOSTNAME')) !== false) {
        $host = $envhost;
    }
    if (empty($host)) {
        $host = 'webserver';
    }

    // Use a provided message id.
    if (isset($data['msgid']))
    {
        $messageid = "<{$data['msgid']}@$host>";
        unset($data['msgid']);
    }
    // Generate a random message id.
    else
    {
        $l = localtime(time());
        $l[4]++; $l[5]+=1900;
        $stamp = sprintf(
            "%d%02d%02d%02d%02d",
            $l[5], $l[4], $l[3], $l[2], $l[1]
        );
        $rand = substr(md5(microtime()), 0, 14);
        $messageid = "<$stamp.$rand@$host>";
    }
    $messageid_header="Message-ID: $messageid";

    // ----------------------------------------------------------------------
    // Determine the From: header for the mail.
    // ----------------------------------------------------------------------

    // The "from_address" data field can be used to provide a specific
    // From: header value. If this field is absent or empty, then the
    // header value is constructed based on the system_email_* settings.
    if (!isset($data['from_address']) || trim($data['from_address']) == '')
    {
        $from_name = trim($PHORUM['system_email_from_name']);
        if ($from_name != '')
        {
            // Handle (Quoted-Printable) encoding of the from name.
            // Mail headers can not contain 8-bit data as per RFC821.
            $from_name = phorum_api_mail_encode_header($from_name, "\t");

            $prefix  = $from_name.' <';
            $postfix = '>';
        } else {
            $prefix = $postfix = '';
        }

        $data['from_address'] =
            $prefix . $PHORUM['system_email_from_address'] . $postfix;
    }

    $from_address = $data['from_address'];
    unset($data['from_address']);

    // ----------------------------------------------------------------------
    // Determine the Subject: header and mail body for the mail.
    // ----------------------------------------------------------------------

    $mailsubject = $data['mailsubject'];
    unset($data['mailsubject']);

    $mailmessage = $data['mailmessage'];
    unset($data['mailmessage']);

    // Replace template variables in the subject and message body.
    if (is_array($data) && count($data)) {
        foreach ($data as $key => $val) {
            if ($val === NULL || is_array($val)) continue;
            $mailmessage = str_replace("%$key%", $val, $mailmessage);
            $mailsubject = str_replace("%$key%", $val, $mailsubject);
        }
    }

    // Handle (Quoted-Printable) encoding of the Subject: header.
    // Mail headers can not contain 8-bit data as per RFC821.
    $mailsubject = phorum_api_mail_encode_header($mailsubject, "\t");

    // ----------------------------------------------------------------------
    // Send the mail message.
    // ----------------------------------------------------------------------

    /*
     * [hook]
     *     mail_send
     *
     * [description]
     *     This hook can be used for implementing an alternative mail sending
     *     system. The hook should return TRUE if Phorum should still run
     *     its own mail sending code. If you want to prevent Phorum from
     *     sending mail, then return FALSE.<sbr/>
     *     <sbr/>
     *     The SMTP module is a good example of using this hook to replace
     *     Phorum's default mail sending system.<sbr/>
     *     <sbr/>
     *     Note that due to the fact that a hook function can return
     *     TRUE or FALSE instead of the original input data, this hook
     *     is not really feasible for letting multiple modules handle
     *     mail delivery (the moment that one module returns TRUE or
     *     FALSE, the following module will get TRUE or FALSE as its
     *     input, instead of the original message data array).
     *     When implementing this hook in a module, it might be a
     *     good idea to beware of this.
     *
     * [category]
     *     Mail
     *
     * [when]
     *     In the file <filename>include/api/mail.php</filename> in
     *     <literal>phorum_api_mail()</literal>, right before the
     *     mail message is sent using <phpfunc>mail</phpfunc>.
     *
     * [input]
     *     An array with mail data (read-only) containing:
     *     <ul>
     *       <li><literal>addresses</literal>:
     *            an array of recpient mail addresses</li>
     *       <li><literal>from</literal>: the sender's mail address</li>
     *       <li><literal>subject</literal>: the mail subject</li>
     *       <li><literal>body</literal>: the mail body</li>
     *       <li><literal>bcc</literal>:
     *           whether or not to use Bcc: for mailing
     *           multiple recipients</li>
     *     </ul>
     *
     * [output]
     *     TRUE or FALSE - see description.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_mail_send ($addresses, $data)
     *     {
     *         global $PHORUM;
     *
     *         // In case another module already handled mail sending.
     *         // (it's recommended to include this check for "mail_send").
     *         if (!is_array($addresses)) return $addresses;
     *
     *         // ... custom code for
     *         // ... sending the mail
     *         // ... goes here
     *
     *         // Tell Phorum not to run its own mail code.
     *         return FALSE;
     *     }
     *     </hookcode>
     */
    $send_messages = TRUE;
    if (isset($PHORUM['hooks']['mail_send']))
    {
        $hook_data = array(
            'addresses'  => $addresses,
            'from'       => $from_address,
            'subject'    => $mailsubject,
            'body'       => $mailmessage,
            'bcc'        => $PHORUM['use_bcc'],
            'messageid'  => $messageid
        );
        if(isset($data['attachments'])) {
            $hook_data['attachments'] = $data['attachments'];
        }


        $send_messages = phorum_api_hook('mail_send', $hook_data);
    }

    // Check if we have any recipients and if a module told us to
    // not run our own mail sending code.
    if (!$send_messages) return count($addresses);
    if (empty($addresses)) return 0;

    // Build the message headers.
    $phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
    $mailer   = 'Phorum' . $phorum_major_version;
    $encoding = empty($PHORUM['DATA']['MAILENCODING'])
              ? '8bit' : $PHORUM['DATA']['MAILENCODING'];
    $charset  = empty($PHORUM['DATA']['CHARSET'])
              ? 'UTF-8' : $PHORUM['DATA']['CHARSET'];
    $mailheader =
        "Content-Type: text/plain; charset=$charset\n" .
        "Content-Transfer-Encoding: $encoding\n" .
        "X-Mailer: $mailer\n" .
        "$messageid_header\n";

    // Add custom headers if defined in the mail data.
    if (!empty($data['custom_headers'])) {
        $mailheader .= $data['custom_headers']."\n";
    }

    // Send the mail using Bcc:
    if (!empty($PHORUM['use_bcc']) && count($addresses) > 3)
    {
        mail(
            " ", $mailsubject, $mailmessage,
            $mailheader .
            "From: $from_address\n" .
            "BCC: " . implode(",", $addresses)
        );
    }
    // Sending mail without Bcc:
    // In this case, a single mail is sent for each recipient.
    else
    {
        foreach ($addresses as $address) {
            mail(
                $address, $mailsubject, $mailmessage,
                $mailheader .
                "From: $from_address"
            );
        }
    }

    return count($addresses);
}
// }}}

// {{{ Function: phorum_api_mail_encode_header()
/**
 * Handle Quoted-Printable encoding of mail headers, as defined by RFC 2045.
 *
 * @param string $string
 *     The string to encode.
 *
 * @return string
 *     The Quoted-Printable encoded string or the orginal string if the
 *     string does not have to be encoded, because it does not contain
 *     any special characters at all.
 */
function phorum_api_mail_encode_header($string)
{
    global $PHORUM;
    $prefix = '=?'.$PHORUM["DATA"]["CHARSET"].'?Q?';
    $prefixlen = strlen($prefix);
    $postfix = '?=';

    // From the RFC:
    // "Octets with decimal values of 33 through 60 inclusive, and 62
    //  through 126, inclusive, MAY be represented as the US-ASCII
    //  characters which correspond to those octets
    //  [..]
    //  (White Space) Octets with values of 9 and 32 MAY be
    //  represented as US-ASCII TAB (HT) and SPACE characters
    //  respectively, but MUST NOT be so represented at the end
    //  of an encoded line."
    //
    // I removed the question mark from the safe characters list,
    // since those did not get recognized correctly inside the encoded
    // string by mail clients. I also removed space and tab from the
    // allowed characters. That did work, yet SpamAssassin flagged
    // messages containing spaces in the encoded string as spam.
    // These removed characters were moved to the semi safe character
    // list. We still can skip encoding a string if it contains only
    // characters from the semi safe and the safe character list.
    static $safe_chars = NULL;
    static $semi_safe_chars = NULL;
    if ($safe_chars === NULL) {
        $safe_chars = "!\"@#$%&'()*+,-./0123456789:;<>'" .
                      "ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`" .
                      "abcdefghijklmnopqrstuvwxyz{|}~";
        $semi_safe_chars = "? \t";
    }

    // Quick shortcut.
    if ($string == '') return $string;

    // Find out how long the $string is.
    $len = strlen($string);

    // Check for strings that don't need encoding at all.
    $count = strspn($string, $safe_chars . $semi_safe_chars);
    if ($count == $len) return $string;

    // Walk over the $string to encode it.
    $res = $prefix;
    $cursor = 0;
    $linecursor = $prefixlen;
    while ($len > 0)
    {
        // Check how many safe chars in a row we can find in the string
        // from the current cursor position on.
        $count = strspn($string, $safe_chars, $cursor);

        // From the RFC:
        // "The Quoted-Printable encoding REQUIRES that encoded lines be
        //  no more than 76 characters long. If longer lines are to be
        //  encoded with the Quoted-Printable encoding, "soft" line breaks
        //  must be used.  An equal sign as the last character on a encoded
        //  line indicates such a non-significant ("soft") line break in
        //  the encoded text."
        //
        // Here, we add the safe characters in batches to honor this
        // 76 char limit. Note that $count can be zero in case the string
        // starts with a non-safe character.
        while ($count > 0)
        {
            $add = RFC2045_WRAPLEN - $linecursor;
            if ($add > $count) $add = $count;
            $res .= substr($string, $cursor, $add);
            $count -= $add;
            $linecursor += $add;
            $cursor += $add;
            $len -= $add;

            // Characters left? Then add a soft break for the next batch.
            if ($count > 0) {
                $res .= "$postfix\r\n\t$prefix";
                $linecursor = $prefixlen;
            }
        }

        // Encode unsafe chars.
        while ($len > 0 && strcspn($string, $safe_chars, $cursor) > 0)
        {
            // Check how many bytes long the following character is.
            //
            // We found that mail clients do not handle multibyte characters
            // correctly when its bytes are split over wrapped encoding
            // lines. The clients do not put the separated bytes back
            // together, resulting in broken characters in the output.
            $mb_char = mb_substr(substr($string, $cursor), 0, 1);
            $mb_len  = strlen($mb_char);

            // From the RFC:
            // "(General 8bit representation) Any octet, except a CR or LF that
            //  is part of a CRLF line break of the canonical (standard) form
            //  of the data being encoded, may be represented by an "=" followed
            //  by a two digit hexadecimal representation of the octet's value.
            //  The digits of the hexadecimal alphabet, for this purpose, are
            //  "0123456789ABCDEF".  Uppercase letters must be used; lowercase
            //  letters are not allowed."

            // From the RFC:
            // "(Line Breaks) A line break in a text body, represented
            //  as a CRLF sequence in the text canonical form, must be
            //  represented by a (RFC 822) line break, which is also a
            //  CRLF sequence"
            if ($mb_char == "\r" &&
                isset($string[$cursor+1]) &&
                $string[$cursor + 1] == "\n") {
                $res .= "\r\n\t";
                $cursor += 2;
                $linecursor = 0;
                $len -= 2;
            }
            // No CRLF break. Handle character escaping.
            else
            {
                // If we are at the end of the line, then wrap around with
                // a soft break. We take 3 characters into account per byte to
                // take care of the "=XX" encoding.
                if (($linecursor + $mb_len * 3) >= RFC2045_WRAPLEN) {
                    $res .= "$postfix\r\n\t$prefix";
                    $linecursor = $prefixlen;
                }

                // Add the escaped character.
                for ($pos = 0; $pos < $mb_len; $pos++) {
                    $res .= sprintf('=%02X', ord($mb_char[$pos]));
                }

                // Update counters.
                $cursor += $mb_len;
                $linecursor += $mb_len * 3;
                $len -= $mb_len;
            }
        }
    }

    // Add the closing postfix.
    $res .= $postfix;

    return $res;
}
// }}}

// {{{ Function: phorum_api_mail_check_address()
/**
 * Check if an email address is valid.
 *
 * There are three checks available.
 *
 * - A check on the syntax of the email address;
 * - A check to see if an MX DNS record exists for the domain in the address;
 * - A check to see if we can connect to the mailhost on the SMTP port
 *   in case no MX records are available for the domain name.
 *
 * The MX DNS check and the connection test will only be performed
 * when the setting "dns_lookup" is enabled for Phorum.
 *
 * @param string $address
 *     The email address to check.
 *
 * @return bool
 *     FALSE in case the email address is not valid, TRUE otherwise.
 */
function phorum_api_mail_check_address($address)
{
    global $PHORUM;

    $address = trim($address);

    // Do a syntax check on the email address.
    // Don't even try to read this one. Your head will hurt.
    $userblock = '[a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+';
    $hostblock = '((([-a-z0-9]*[a-z0-9])?)|' .
                 '(\#[0-9]+)|(\[((([01]?[0-9]{0,2})|' .
                 '(2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|' .
                 '(2(([0-4][0-9])|(5[0-5]))))\]))';
    if (preg_match(
        "/^{$userblock}(\.$userblock)*@($hostblock\.)*$hostblock$/i", $address
    ))
    {
        // If no DNS lookups are performed, the we are done. The
        // mail address is valid.
        if (empty($PHORUM['dns_lookup'])) return TRUE;

        // If the PHP function checkdnsrr() is not available, then
        // we cannot run the DNS lookup. This might be the case
        // if we are running on a Windows system here.
        if (!function_exists('checkdnsrr')) return TRUE;

        // Grab the domain name from the mail address.
        $domain = preg_replace('/^.*@/', '', $address);

        // Check if a mailserver is configured for the domain.
        // If yes, then this is probably a valid mail domain.
        if (checkdnsrr($domain, "MX")) return TRUE;

        // Some hosts do not have an MX record, but accept mail
        // themselves. We check for such host by trying to setup a
        // network socket connection to the SMTP port on the host.
        ini_set('default_socket_timeout', 10); // default of 60 is too long
        if ($sock = @fsockopen($domain, 25)) {
            fclose($sock);
            return TRUE;
        }
    }

    // If we fall through to here, then the address did not validate.
    return FALSE;
}
// }}}

?>
