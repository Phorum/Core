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

define("phorum_page", "feed");
require_once './common.php';

require_once PHORUM_PATH.'/include/api/feed.php';

// Check if feeds are allowed.
if (empty($PHORUM['use_rss'])) {
    exit();
}

// Find out for what entity / entities we have to create the feed.
if (empty($PHORUM['forum_id']) || $PHORUM['forum_id'] == $PHORUM['vroot']) {
    $what  = PHORUM_FEED_VROOT;
    $which = $PHORUM['vroot'];
}
elseif (isset($PHORUM['args'][1])) {
    $what  = PHORUM_FEED_THREAD;
    $which = (int) $PHORUM['args'][1];
}
elseif (!$PHORUM['folder_flag']) {
    $what  = PHORUM_FEED_FORUM;
    $which = $PHORUM['forum_id'];
}
else {
    $what = "";
    $which= "";
    trigger_error(
        "The feed script was called with a folder id as the " .
        "forum_id argument. This is not supported.",
        E_USER_ERROR
    );
}

// The number of items to retrieve.
// If no amount is provided, then 30 is used by default.
$count = empty($PHORUM['args']['count']) ? 30 : $PHORUM['args']['count'];

// Check if reply messages have been requested for the feed.
$replies = empty($PHORUM["args"]["replies"]) ? 0 : 1;

// Check what output adapter to use for delivering the feed.
// If no adapter type is provided, then "rss" is used by default.
$adapter = empty($PHORUM['args']['type']) ? 'rss' : $PHORUM['args']['type'];

// Generate and send the feed.
phorum_api_feed(
    $adapter,    // The output adapter to use
    $what,       // all forums, single forum or single thread
    $which,      // forum or thread id (not used for "all forums")
    $count,      // the number of messages to show
    $replies     // with or without reply messages
);

?>
