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

/**
 * This script implements utility functions for retrieving data
 * using HTTP requests.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Function: phorum_api_http_get()
/**
 * This function can be used to retrieve data from a URL using
 * an HTTP GET request.
 *
 * The way in which data can be retrieved for URLs, depends a lot on
 * the features and the configuration for PHP on the system. This
 * function will try to autodetect a way that will work for the
 * running system automatically.
 *
 * @param string $url
 *     The URL to retrieve.
 *
 * @param string $method
 *     The method to use for retrieving the data. By default, this function
 *     will try to autodetect a working method. Providing a $method parameter
 *     is mostly useful for debugging purposes. Available methods (in the
 *     order in which they are probed in the code) are:
 *     - curl: using the curl library (requires extension "curl")
 *     - socket: using PHP socket programming (requires extension "sockets")
 *     - file: using fopen() (requires option "allow_url_fopen" to be enabled)
 *
 * @return string $data
 *     The data that was loaded from the URL or NULL if an error occurred.
 *     The function {@link phorum_api_strerror()} can be used to retrieve
 *     information about the error which occurred.
 */
function phorum_api_http_get($url, $method = NULL)
{
    // Reset error storage.
    $GLOBALS['PHORUM']['API']['errno'] = NULL;
    $GLOBALS['PHORUM']['API']['error'] = NULL;

    // For keeping track of errors in this function.
    $error = NULL;
    $fatal = FALSE;

    // -----------------------------------------------------------------
    // Try to use the CURL library tools
    // -----------------------------------------------------------------

    if (($method === NULL || $method == 'curl') &&
        extension_loaded('curl'))
    {
        $method = NULL;

        $curl = @curl_init();
        if ($curl === FALSE) {
            $error = 'Failed to initialize curl request';
        }
        else
        {
            // We don't care a lot about certificates for retrieving data.
            @curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            @curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            // Return the header and content when doing a request.
            @curl_setopt($curl, CURLOPT_HEADER,         1);
            @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            // We do not let the curl library follow Location: redirects.
            // Because (at the time of writing) PHP in safe mode disables
            // redirect following (to prevent redirection to file://),
            // we implement our own redirect handling here.
            @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

            // The maximum number of redirects that we want to follow.
            // This has to be limited to prevent looping.
            $max_redirects = 10;

            // Also, track URLs that we have already seen as an extra
            // anti looping system.
            $seen = array();

            $work_url = $url;
            for(;;)
            {
                // Only HTTP allowed.
                if (!preg_match('!^https?://!i', $work_url)) {
                    $error = "Denying non-HTTP URL: $work_url";
                    $fatal = TRUE;
                    break;
                }

                // Looping prevention.
                if ($max_redirects-- == 0) {
                    $error = "Bailed out after too many page redirects.";
                    $fatal = TRUE;
                    break;
                }
                if (isset($seen[$work_url])) {
                    $error = "Following the URL results in a loop.";
                    $fatal = TRUE;
                    break;
                }
                $seen[$work_url] = 1;

                // Retrieve the page.
                @curl_setopt($curl, CURLOPT_URL, $work_url);
                $data = @curl_exec($curl);
                if ($data == FALSE) {
                    $error = "The curl HTTP request failed.";
                    break;
                }
                list($header, $body) = explode("\r\n\r\n", $data, 2);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $requested_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

                // If we got the data, we can return it.
                if ($code == 200) {
                    return $body;
                }

                // Analyze the result data to see what we should to next.
                $res = phorum_api_http_get_analyze(
                    $requested_url, $code, $header
                );

                // An error was returned. Bail out.
                if (isset($res['error'])) {
                    $fatal = empty($res['fatal']) ? 0 : 1;
                    $error = $res['error'];
                    break;
                }

                // A redirect was returned.
                if (isset($res['redirect'])) {
                    $work_url = $res['redirect'];
                    continue;
                }

                // Shouldn't get here.
                trigger_error(
                    'phorum_api_http_get_nalyze() returned an ' .
                    'unexpected result.',
                    E_USER_ERROR
                );
            }
        }
    }

    // Return fatal errors. For non fatal errors, we fall through
    // to the next method.
    if ($error !== NULL && $fatal) {
        return phorum_api_error_set(PHORUM_ERRNO_ERROR, $error);
    }

    // -----------------------------------------------------------------
    // Try to use a direct fsockopen call.
    // -----------------------------------------------------------------

    if (($method === NULL || $method == 'socket') &&
        extension_loaded('sockets'))
    {
        $method = NULL;

        // The maximum number of redirects that we want to follow.
        // This has to be limited to prevent looping.
        $max_redirects = 10;

        // Also, track URLs that we have already seen as an extra
        // anti looping system.
        $seen = array();

        $work_url = $url;
        for(;;)
        {
            // Only HTTP allowed.
            if (!preg_match('!^https?://!i', $work_url)) {
                $error = "Denying non-HTTP URL: $work_url";
                $fatal = TRUE;
                break;
            }

            // Looping prevention.
            if ($max_redirects-- == 0) {
                $error = "Bailed out after too many page redirects.";
                $fatal = TRUE;
                break;
            }
            if (isset($seen[$work_url])) {
                $error = "Following the URL results in a loop.";
                $fatal = TRUE;
                break;
            }
            $seen[$work_url] = 1;

            // Format the HTTP request for retrieving the URL.
            $parsed = @parse_url(strtoupper($work_url));
            if (!isset($parsed['host'])) {
                $error = "Cannot parse URL";
                $fatal = TRUE;
                break;
            }
            $uri = preg_replace('!^\w+://[^/]+!', '', $work_url);
            if ($uri == '') $uri = '/';
            $req = "GET $uri HTTP/1.1\r\n" .
                   "Connection: close\r\n" .
                   "Host: ".strtolower($parsed['host']) . "\r\n" .
                   "\r\n";

            // Determine protocol and port for the request.
            $port  = NULL;
            $proto = '';
            if (!empty($parsed['port'])) {
                $port = (int)$parsed['port'];
            }
            if (!empty($parsed['scheme'])) {
                $s = strtolower($parsed['scheme']);
                if ($s == 'http') {
                    $proto = '';
                    if ($port === NULL) $port = 80;
                }
                elseif ($s == 'https') {
                    if (!extension_loaded('openssl')) {
                        $error = "PHP lacks SSL support";
                        $fatal = TRUE;
                        break;
                    }
                    $proto = 'ssl://';
                    if ($port === NULL) $port = 443;
                }
            }
            if ($port === NULL) $port = 80;

            // Connect to the webserver.
            $fp = @fsockopen($proto.$parsed['host'],$port,$errno,$errstr,10);
            if (!$fp) {
                $error = "Connection to server failed ($errstr)";
                $fatal = TRUE;
                break;
            }

            // Send the HTTP request.
            fwrite($fp, $req);

            // Read the HTTP response.
            $response = '';
            while (is_resource($fp) && !feof($fp)) {
                $response .= fread($fp, 1024);
            }
            fclose($fp);
            if ($response == '') {
                $error = "No data retrieved from server";
                $fatal = TRUE;
                break;
            }

            // Parse the response.
            list ($header, $body) = explode("\r\n\r\n", $response, 2);
            list ($status, $header) = explode("\r\n", $header, 2);
            if (preg_match('!^HTTP/\d+\.\d+\s+(\d+)\s!', $status, $m)) {
                $code = $m[1];
            } else {
                $error = "Unexpected status from server ($status)";
                $fatal = TRUE;
                break;
            }

            // If we got the data, we can return it.
            if ($code == 200)
            {
                // Check if we need to handle chunked transfers
                // (see RFC 2616, section 3.6.1: Chunked Transfer Coding).
                if (preg_match('/^Transfer-Encoding:\s*chunked\s*$/m',$header))
                {
                    $unchunked = '';
                    for(;;)
                    {
                        // Check if there is another chunk.
                        // There should be, but let's protect against
                        // bad chunked data.
                        if (strstr($body, "\r\n") === FALSE) break;

                        // Get the size of the next chunk.
                        list ($sz,$rest) = explode("\r\n", $body, 2);
                        $sz = preg_replace('/;.*$/', '', $sz);
                        $sz = hexdec($sz);

                        // Size 0 indicates end of body data.
                        if ($sz == 0) break;

                        // Add the chunk to the unchunked body data.
                        $unchunked .= substr($rest, 0, $sz);
                        $body = substr($rest, $sz + 2); // +2 = skip \r\n
                    }

                    // Return the unchunked body content.
                    return $unchunked;
                }

                // Return the body content.
                return $body;
            }

            // Analyze the result data to see what we should to next.
            $res = phorum_api_http_get_analyze(
                $work_url, $code, $header
            );

            // An error was returned. Bail out.
            if (isset($res['error'])) {
                $fatal = empty($res['fatal']) ? 0 : 1;
                $error = $res['error'];
                break;
            }

            // A redirect was returned.
            if (isset($res['redirect'])) {
                $work_url = $res['redirect'];
                continue;
            }

            // Shouldn't get here.
            trigger_error(
                'phorum_api_http_get_analyze() returned an ' .
                'unexpected result.',
                E_USER_ERROR
            );
        }
    }

    // Return fatal errors. For non fatal errors, we fall through
    // to the next method.
    if ($error !== NULL && $fatal) {
        return phorum_api_error_set(PHORUM_ERRNO_ERROR, $error);
    }

    // -----------------------------------------------------------------
    // Try to use file_get_contents
    // -----------------------------------------------------------------

    if (($method === NULL || $method == 'fopen') &&
        ini_get('allow_url_fopen'))
    {
        $method = NULL;

        $track = ini_get('track_errors');
        ini_set('track_errors', TRUE);
        $php_errormsg = '';
        $contents = @file_get_contents($url);
        ini_set('track_errors', $track);

        if ($contents === FALSE || $php_errormsg != '') {
            $error = preg_replace('/(^.*?\:\s+|[\r\n])/', '', $php_errormsg);
            $error = "[$error]";
        } else {
            return $contents;
        }
    }

    // Return errors.
    if ($error !== NULL) {
        return phorum_api_error_set(PHORUM_ERRNO_ERROR, $error);
    }

    // Catch illegal methods
    if ($method !== NULL) {
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            'Illegal method: ' . $method
        );
    }

    return phorum_api_error_set(
        PHORUM_ERRNO_ERROR,
        'No working HTTP request method found'
    );
}

/**
 * A helper function for {@link phorum_api_http_get()} to determine
 * what to do with a no "200 OK" response from the web server.
 *
 * @param string $requested_url
 * @param string $code
 * @param string $header
 *
 * @return array
 *     The return array either contains the fields "error" and "fatal" (if
 *     there was some error) or the field "redirect" (if a new URL has to
 *     be requested).
 */
function phorum_api_http_get_analyze($requested_url, $code, $header)
{
    switch ($code)
    {
        // Handle HTTP redirects.
        case 301:
        case 302:
            // Get original URL.
            $orig_url = @parse_url($requested_url);

            // Get redirect URL.
            $header = str_replace("\r", "", $header);
            $lines = explode("\n", $header);
            $location = NULL;
            foreach ($lines as $line) {
                if (substr($line, 0, 9) == 'Location:') {
                    $location = trim(substr($line, 9));
                    break;
                }
            }
            if ($location === NULL) return array(
                'error' => 'Missing Location header in redirect response.',
                'fatal' => TRUE
            );
            $redir_url = @parse_url($location);

            // Merge the original and redirect URL, to build
            // the URL to redirect to.
            if (!isset($redir_url['scheme'])) {
                $redir_url['scheme'] = $orig_url['scheme'];
            }
            if (!isset($redir_url['host'])) {
                $redir_url['host'] = $orig_url['host'];
            }
            if (!isset($redir_url['path'])) {
                $redir_url['path'] = $orig_url['path'];
            }
            if (!isset($redir_url['port']) &&
                isset($orig_url['port'])) {
                $redir_url['port'] = $orig_url['port'];
            }

            // Generate the new URL to retrieve.
            $redirect = $redir_url['scheme'] . '://' .
                        $redir_url['host'] .
                        (!empty($redir_url['port'])
                         ? ':'.$redir_url['port'] : '') .
                        $redir_url['path'] .
                        (!empty($redir_url['query'])
                         ? '?'.$redir_url['query'] : '');

            // Continue with the new request.
            return array('redirect' => $redirect);

        // Handle errors.
        case 401:
            return array(
                'error' => 'The file is password protected.',
                'fatal' => TRUE
            );
        case 403:
            return array(
                'error' => 'Permission denied.',
                'fatal' => TRUE
            );
        case 404:
            return array(
                'error' => 'File not found.',
                'fatal' => TRUE
            );
        case 500:
            return array(
                'error' => 'Remote server return an error.',
                'fatal' => TRUE
            );
        default:
            return array(
                'error' => "HTTP request failed with code $code.",
                'fatal' => TRUE
            );
    }
}
// }}}

// {{{ Function: phorum_api_http_get_supported()
/**
 * Check if platform support is available for retrieving files via HTTP using
 * phorum_api_http_get().
 *
 * @return boolean
 *     TRUE in case files can be retrieving using HTTP, FALSE otherwise.
 */
function phorum_api_http_get_supported()
{
    return extension_loaded('curl')    ||
           extension_loaded('sockets') ||
           ini_get('allow_url_fopen');
}
// }}}

?>
