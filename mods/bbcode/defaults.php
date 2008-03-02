<?php
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2008  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

if (!defined("PHORUM") && !defined('PHORUM_ADMIN')) return;

// Value array contain the following fields:
// [0] A description of the BBcode tool
// [1] TRUE = editor tools button available, FALSE = not available
// [2] Default value for the tag configuration
$GLOBALS["bbcode_features"] = array
(
    'bold' => array(
        '[b]bold text[/b]',
        TRUE, 2
    ),
    'italic' => array(
        '[i]italic text[/i]',
        TRUE, 2
    ),
    'underline' => array(
        '[u]underlined text[/u]',
        TRUE, 2
    ),
    'strike' => array(
        '[s]strike through text[/s]',
        TRUE, 2
    ),
    'subscript' => array(
        '[sub]subscripted text[/sub]',
        TRUE, 2
    ),
    'superscript' => array(
        '[sup]superscripted text[/sup]',
        TRUE, 2
    ),
    'color' => array(
        '[color=#123456]colored text[/color]',
        TRUE, 2
    ),
    'size' => array(
        '[size=20px]text of a different size[/size]',
        TRUE, 2
    ),
    'small' => array(
        '[small]small text[/small]',
        TRUE, 1
    ),
    'large' => array(
        '[large]large text[/large]',
        TRUE, 1
    ),
    'code' => array(
        '[code]<br/>' .
        '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;formatted<br/>' .
        '&nbsp;&nbsp;text<br/>' .
        '&nbsp;&nbsp;&nbsp;&nbsp;e.g. programming code<br/>' .
        '[/code]',
        TRUE, 2
    ),
    'center' => array(
        '[center]centered content[/center]',
        TRUE, 2
    ),
    'hr' => array(
        '[hr] or [hline] for a horizontal line',
        TRUE, 2
    ),
    'image' => array(
        '[img]http://example.com/image.jpg[/img]<br/>',
        TRUE, 2
    ),
    'url' => array(
        '[url=http://example.com]cool site![/url]<br/>' .
        '[url]http://example.com[/url]<br/>' .
        'For adding website links. This will also enable automatic<br/>' .
        'detection of URLs in messages and make them clickable.',
        TRUE, 2
    ),
    'email' => array(
        '[email]johndoe@example.com[/email]<br/>' .
        'For adding email links. This will also enable<br/>' .
        'automatic detection of email addresses in messages<br/>' .
        'and make them clickable.',
        TRUE, 2
    ),
    'quote' => array(
        '[quote]quoted text[/quote]<br/>' .
        '[quote John Doe]quoted text[/quote]<br/>' .
        '[quote=John Doe]quoted text[/quote]<br/>' .
        'For adding quoted text.',
        TRUE, 2
    ),
);


if (! isset($GLOBALS["PHORUM"]["mod_bbcode"]) ||
    ! is_array($GLOBALS["PHORUM"]["mod_bbcode"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"] = array();
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["links_in_new_window"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["links_in_new_window"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["rel_no_follow"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["rel_no_follow"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["quote_hook"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["quote_hook"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["show_full_urls"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["show_full_urls"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_urls"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_urls"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_email"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_email"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["enabled"]) ||
    !is_array($GLOBALS["PHORUM"]["mod_bbcode"]["enabled"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["enabled"] = array();
}

foreach ($GLOBALS["bbcode_features"] as $id => $feature) {
    if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["enabled"][$id])) {
        $GLOBALS["PHORUM"]["mod_bbcode"]["enabled"][$id] = $feature[2];
    }
}

?>
