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
 * This script implements functions related to Phorum output.
 *
 * @package    PhorumAPI
 * @subpackage Output
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

/**
 * The date format to use in HTTP headers.
 */
define('HTTPDATE', 'D, d M Y H:i:s \G\M\T');

// {{{ Function: phorum_api_output()
/**
 * Wrapper function to handle most common output scenarios.
 *
 * @param string|array $templates
 *     If a string, then that template is included.
 *     If an array, then all templates are included in the order of the array.
 */
function phorum_api_output($templates)
{
    $phorum = Phorum::API();

    if (!is_array($templates)) {
        $templates = array($templates);
    }

    /*
     * [hook]
     *     start_output
     *
     * [description]
     *     This hook gives modules a chance to apply some last minute
     *     changes to the Phorum data. You can also use this hook to
     *     call <phpfunc>ob_start</phpfunc> if you need to buffer Phorum's
     *     full output (e.g. to do some post processing on the data
     *     from the <hook>end_output</hook> hook.<sbr/>
     *     <sbr/>
     *     Note: this hook is only called for standard pages (the ones
     *     that are constructed using a header, body and footer) and not
     *     for output from scripts that do raw output like
     *     <filename>file.php</filename>, <filename>javascript.php</filename>,
     *     <filename>css.php</filename> and <filename>rss.php</filename>.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After setting up all Phorum data, right before sending the
     *     page header template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_start_output()
     *     {
     *         global $PHORUM;
     *
     *         // Add some custom data to the page title.
     *         $title = $PHORUM['DATA']['HTML_TITLE'];
     *         $PHORUM['DATA']['HTML_TITLE'] = "-=| Phorum Rocks! |=- $title";
     *     }
     *     </hookcode>
     */
    if (isset($GLOBALS['PHORUM']['hooks']['start_output'])) {
        $phorum->modules->hook('start_output');
    }

    // Copy only what we need into the current scope. We do this at
    // this point and not earlier, so the start_output hook can be
    // used for changing values in the $PHORUM data.
    $PHORUM = array(
        'DATA'   => $GLOBALS['PHORUM']['DATA'],
        'locale' => $GLOBALS['PHORUM']['locale'],
        'hooks'  => $GLOBALS['PHORUM']['hooks']
    );

    include $phorum->template('header');

    /*
     * [hook]
     *     after_header
     *
     * [description]
     *     This hook can be used for adding content to the pages that is
     *     displayed after the page header template, but before the main
     *     page content.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page header template, but before sending the
     *     main page content.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_header()
     *     {
     *         // Only add data after the header for the index and list pages.
     *         if (phorum_page != 'index' && phorum_page != 'list') return;
     *
     *         // Add some static notification after the header.
     *         print '<div style="border:1px solid orange; padding: 1em">';
     *         print 'Welcome to our forums!';
     *         print '</div>';
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['after_header'])) {
        $phorum->modules->hook('after_header');
    }

    foreach($templates as $template){
        include $phorum->template($template);
    }

    /*
     * [hook]
     *     before_footer
     *
     * [description]
     *     This hook can be used for adding content to the pages that is
     *     displayed after the main page content, but before the page footer.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the main page content, but before sending the
     *     page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_before_footer()
     *     {
     *         // Add some static notification before the footer.
     *         print '<div style="font-size: 90%">';
     *         print '  For technical support, please send a mail to ';
     *         print '  <a href="mailto:tech@example.com">the webmaster</a>.';
     *         print '</div>';
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['before_footer'])) {
        $phorum->modules->hook('before_footer');
    }

    include $phorum->template('footer');

    /*
     * [hook]
     *     end_output
     *
     * [description]
     *     This hook can be used for performing post output tasks.
     *     One of the things that you could use this for, is for
     *     reading in buffered output using <phpfunc>ob_get_contents</phpfunc>
     *     in case you started buffering using <phpfunc>ob_start</phpfunc>
     *     from the <hook>start_output</hook> hook.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_end_output()
     *     {
     *         // Some made up call to some fake statistics package.
     *         include "/usr/share/lib/footracker.php";
     *         footracker_register_request();
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['end_output'])) {
        $phorum->modules->hook('end_output');
    }
}
// }}}

// {{{ Function: phorum_api_output_last_modify_time()
/**
 * Check if an If-Modified-Since header is in the request. If yes, then
 * check if the provided content modification time lies before the time
 * from that header.
 *
 * If yes, then the content did not change and we return
 * a HTTP 304 status (Not Modified) to notify the browser about this.
 * The browser can then use the cached content.
 *
 * If no, then a Last-Modified header is sent.
 *
 * @param int $last_modified
 *     Epoch timestamp for the last time that the content changed.
 */
function phorum_api_output_last_modify_time($last_modified)
{
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) 
    {
        $header = preg_replace('/;.*$/','',$_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $modified_since = strtotime($header);
        
        if ($modified_since >= $last_modified)
        {
            $proto = empty($_SERVER['SERVER_PROTOCOL'])
                   ? 'HTTP/1.0' : $_SERVER['SERVER_PROTOCOL'];
            header("$proto 304 Not Modified");
            header('Status: 304');
            exit(0);
        }
    }

    // Set the Last-Modified header, so the browser can use that
    // on the next request to bootstrap this client side caching mechanism.
    header("Last-Modified: " . date("r", $last_modified));
}
// }}}

// {{{ Function: phorum_api_output_cache_max_age()
/**
 * Send headers to tell the browser that the output can be cached
 * and for how long.
 *
 * @param integer $max_age
 *     The number of seconds that the content may be cached by the browser.
 */
function phorum_api_output_cache_max_age($max_age)
{
    settype($max_age, 'int');    

    header('Cache-Control: max-age='.$max_age);
    header('Expires: ' . gmdate(HTTPDATE, time()+$max_age));
}
// }}}

// {{{ Function: phorum_api_output_cache_disable()
/**
 * Send headers to tell the browser that the output should not be cached.
 */
function phorum_api_output_cache_disable()
{
    // Set an expire date in the past.
    header('Expires: ' . gmdate(HTTPDATE, time() - 99999));

    // Always modified by now.
    header('Last-Modified: ' . gmdate(HTTPDATE, time()));

    // HTTP/1.1
    header('cache-Control: no-store, no-cache, must-revalidate');
    header('cache-Control: post-check=0, pre-check=0', FALSE);

    // HTTP/1.0
    header('Pragma: no-cache');
}
// }}}

?>
