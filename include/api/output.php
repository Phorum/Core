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
 * This script implements functions related to Phorum output.
 *
 * @package    PhorumAPI
 * @subpackage Output
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

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
    if (!is_array($templates)) {
        $templates = array($templates);
    }

    /*
     * [hook]
     *     output_templates
     *
     * [description]
     *     This hook can be used to alter the list of templates that
     *     will be displayed by the phorum_api_output() call.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     Before sending any output from phorum_api_output().
     *
     * [input]
     *     An array, containing the names of the templates to display
     *     in the page body (between the header and footer template).
     *
     * [output]
     *     Same as input, possibly modified.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_output_templates($templates)
     *     {
     *         // Add some advertisements at the top and bottom of the page.
     *         array_unshift($templates, "foo::top_advertisement);
     *         array_push($templates, "foo::bottom_advertisement);
     *
     *         return $templates;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['output_templates'])) {
        $templates = phorum_api_hook('output_templates', $templates);
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     output_templates_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>output_templates</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     Before sending any output from phorum_api_output().
     *
     * [input]
     *     An array, containing the names of the templates to display
     *     in the page body (between the header and footer template).
     *
     * [output]
     *     Same as input, possibly modified.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['output_templates_' . phorum_page])) {
        $templates = phorum_api_hook(
          'output_templates_' . phorum_page, $templates);
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
        phorum_api_hook('start_output');
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     start_output_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>start_output</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
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
     */
    if (isset($GLOBALS['PHORUM']['hooks']['start_output_' . phorum_page])) {
        phorum_api_hook('start_output_' . phorum_page);
    }

    // Add some information to the breadcrumbs to make it easy for the
    // templates to apply different styling for the first and the
    // last (i.e. the currently active) breadcrumbs item.
    //
    // Reindex the array. It might not be sequential, due to module tinkering.
    $bc =& $GLOBALS['PHORUM']['DATA']['BREADCRUMBS'];
    $bc = array_values($bc);
    // Add a "FIRST" and "LAST" field to the appropriate records.
    $bc[0]['FIRST'] = TRUE;
    $bc[count($bc) - 1]['LAST'] = TRUE;

    // Copy only what we need into the current scope. We do this at
    // this point and not earlier, so the hooks before this code can be
    // used for changing values in the $PHORUM data.
    $PHORUM = array(
        'DATA'   => $GLOBALS['PHORUM']['DATA'],
        'locale' => $GLOBALS['PHORUM']['locale'],
        'hooks'  => $GLOBALS['PHORUM']['hooks']
    );

    include phorum_api_template('header');

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
        phorum_api_hook('after_header');
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     after_header_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>after_header</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
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
     */
    if (isset($GLOBALS['PHORUM']['hooks']['after_header_' . phorum_page])) {
        phorum_api_hook('after_header_' . phorum_page);
    }

    foreach($templates as $template){
        include phorum_api_template($template);
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     before_footer_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>before_footer</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
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
     */
    if (isset($GLOBALS['PHORUM']['hooks']['before_footer_' . phorum_page])) {
        phorum_api_hook('before_footer_' . phorum_page);
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
        phorum_api_hook('before_footer');
    }

    include phorum_api_template('footer');

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
        phorum_api_hook('end_output');
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     end_output_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>end_output</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
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
     */
    if (isset($GLOBALS['PHORUM']['hooks']['end_output_' . phorum_page])) {
        phorum_api_hook('end_output_' . phorum_page);
    }
}
// }}}

// {{{ Function: phorum_api_output_last_modify_time()
/**
 * Check if an If-Modified-Since header is in the request. If yes, then
 * check if the provided content modification equals the time from that
 * header.
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

        if ($modified_since == $last_modified)
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
    header("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', $last_modified));
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
