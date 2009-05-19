<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * The mail API is used for sending mail messages. It is currently under
 * development. Functions from Phorum's include/email_functions.php file
 * will be tranferred to this API layer.
 *
 * @package    PhorumAPI
 * @subpackage Mail
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

/**
 * The wraplength for Quoted-Printable encoded data. The RFC defines
 * a maximum line length of 76 characters. We use a few characters less
 * here, to make wrapped mailheaders better fit 80 column displays
 * (pathetic, I know).
 */
define('RFC2045_WRAPLEN', 70);

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

        // No more characters left? Then we are done.
        if ($len == 0) break;

        // Check how many unsafe chars in a row we can find in the string
        // from the current cursor position on.
        $count = strcspn($string, $safe_chars, $cursor);

        // From the RFC:
        // "(General 8bit representation) Any octet, except a CR or LF that
        //  is part of a CRLF line break of the canonical (standard) form
        //  of the data being encoded, may be represented by an "=" followed
        //  by a two digit hexadecimal representation of the octet's value.
        //  The digits of the hexadecimal alphabet, for this purpose, are
        //  "0123456789ABCDEF".  Uppercase letters must be used; lowercase
        //  letters are not allowed."
        while ($count > 0)
        {
            // From the RFC:
            // "(Line Breaks) A line break in a text body, represented
            //  as a CRLF sequence in the text canonical form, must be
            //  represented by a (RFC 822) line break, which is also a
            //  CRLF sequence"
            if ($string[$cursor] == "\r" &&
                isset($string[$cursor+1]) &&
                $string[$cursor + 1] == "\n") {
                $res .= "\r\n\t";
                $cursor += 2;
                $linecursor = 0;
                $count -= 2;
                $len -= 2;
            }
            // No CRLF break. Handle character escaping.
            else
            {
                // If we are at the end of the line, then wrap around with
                // a soft break. We take 3 characters into account to
                // take care of the "=XX" encoding.
                if (($linecursor + 3) >= RFC2045_WRAPLEN) {
                    $res .= "$postfix\r\n\t$prefix";
                    $linecursor = $prefixlen;
                }

                // Add the escaped character.
                $res .= sprintf('=%02X', ord($string[$cursor]));
                $cursor ++;
                $linecursor += 3;
                $count--;
                $len--;
            }
        }

        // No more characters left? Then we are done.
        if ($len == 0) break;
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
    // Don't even try to read this one. Your head will explode.
    if (preg_match('/
        ^([a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+
        (\.[a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+)*) @
        (((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|
        (2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|
        (2(([0-4][0-9])|(5[0-5]))))\]))\.)*
        ((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|
        (2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|
        (2(([0-4][0-9])|(5[0-5]))))\]))$/xi', $address))
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
