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
////////////////////////////////////////////////////////////////////////////////

// Redirect to another page. This is used for working around an MSIE bug
// where redirecting to an anchored URL loses the anchor if redirected
// directly from a script that acts on POST input coming from an
// enctype="multipart/mixed" form... *sigh*.

define('phorum_page', 'redirect');

require_once("./common.php");

$redirect_to = isset($PHORUM["args"]["phorum_redirect_to"]) ? $PHORUM["args"]["phorum_redirect_to"] : '';
$prefix      = $PHORUM['http_path'];
$prefix_len  = strlen($prefix);
// Require the URL to start with http_path and the next char to be '/' or
// end-of-string to prevent subdomain bypass (e.g. example.com.evil.com).
if ($redirect_to !== '' &&
    strpos($redirect_to, $prefix) === 0 &&
    (strlen($redirect_to) === $prefix_len || $redirect_to[$prefix_len] === '/')) {
    phorum_redirect_by_url(urldecode($redirect_to));
} else {
    header("Location: index.php");
}

?>
