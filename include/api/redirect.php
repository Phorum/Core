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
 * This script implements functionality for redirecting the browser
 * to a different URL.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_redirect()
/**
 * Redirect the browser to a different page.
 *
 * @param string|integer $url
 *
 *     The URL to redirect to, in case $url is a string.
 *
 *     In case an integer is used, then this is treated as one of
 *     the PHORUM_*_URL constants for building a Phorum URL. The API
 *     call phorum_api_url() will be called to build the URL using
 *     the $url value and any other arguments that are provided to
 *     the function call. After building the URL, the browser is
 *     redirected to that URL.
 */
function phorum_api_redirect($url)
{
    // Handle building of a Phorum URL in case an integer value was
    // provided as the URL.
    if (is_integer($url)) {
        $argv = func_get_args();
        $url = call_user_func_array('phorum_api_url', $argv);
    }

    // Some browsers strip the anchor from the URL in case we redirect
    // from a POSTed page :-/. So here we wrap the redirect,
    // to work around that problem. Instead of redirecting directly,
    // we do a GET request to Phorum's redirect.php script, which we
    // send the URL to redirect to in a request parameter. Then, the
    // redirect.php script will handle the actual redirect.
    if (count($_POST) && strstr($url, "#")) {
        $url = phorum_api_url(
            PHORUM_REDIRECT_URL,
            'phorum_redirect_to=' . urlencode($url)
        );
    }

    // Check for response splitting and valid http(s) URLs.
    if (preg_match("/\s/", $url) || !preg_match("!^https?://!i", $url)) {
        $url = phorum_api_url(PHORUM_INDEX_URL);
    }

    // An ugly IIS-hack to avoid crashing IIS servers.
    if (isset($_SERVER['SERVER_SOFTWARE']) &&
        stristr($_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS")) {
        $qurl = htmlspecialchars($url);
        $jurl = addslashes($url);
        print "<html><head><title>Redirecting ...</title>
               <script type=\"text/javascript\">
               //<![CDATA[
               document.location.href='$jurl'
               //]]>
               </script>
               <meta http-equiv=\"refresh\"
                     content=\"0; URL=$qurl\">
               </head>
               <body><a href=\"$qurl\">Redirecting ...</a></body>
               </html>";
    }
    // Standard browser redirection.
    else {
        header("Location: $url");
    }

    exit(0);
}
// }}}

?>
