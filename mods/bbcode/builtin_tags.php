<?php
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
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

/**
 * The description for all built-in BBcode tags.
 */
$GLOBALS['PHORUM']['MOD_BBCODE']['BUILTIN'] = array
(
    'b' => array(
        BBCODE_INFO_DESCRIPTION   => '[b]bold text[/b]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<b>',
        BBCODE_INFO_REPLACECLOSE  => '</b>'
    ),

    'i' => array(
        BBCODE_INFO_DESCRIPTION   => '[i]italic text[/i]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<i>',
        BBCODE_INFO_REPLACECLOSE  => '</i>'
    ),

    'u' => array(
        BBCODE_INFO_DESCRIPTION   => '[u]underlined text[/u]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<u>',
        BBCODE_INFO_REPLACECLOSE  => '</u>'
    ),

    's' => array(
        BBCODE_INFO_DESCRIPTION   => '[s]strike through text[/s]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<s>',
        BBCODE_INFO_REPLACECLOSE  => '</s>'
    ),

    'sub' => array(
        BBCODE_INFO_DESCRIPTION   => '[sub]subscripted text[/sub]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<sub>',
        BBCODE_INFO_REPLACECLOSE  => '</sub>'
    ),

    'sup' => array(
        BBCODE_INFO_DESCRIPTION   => '[sup]superscripted text[/sup]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<sup>',
        BBCODE_INFO_REPLACECLOSE  => '</sup>'
    ),

    'color' => array(
        BBCODE_INFO_DESCRIPTION   => '[color=#123456]colored text[/color]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('color' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_color_handler'
    ),

    'size' => array(
        BBCODE_INFO_DESCRIPTION   => '[size=20px]text of a different size[/size]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('size' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_size_handler'
    ),

    'small' => array(
        BBCODE_INFO_DESCRIPTION   => '[small]small text[/small]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 1,
        BBCODE_INFO_REPLACEOPEN   => '<small>',
        BBCODE_INFO_REPLACECLOSE  => '</small>'
    ),

    'large' => array(
        BBCODE_INFO_DESCRIPTION   => '[large]large text[/large]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 1,
        BBCODE_INFO_REPLACEOPEN   => '<span style="font-size: large">',
        BBCODE_INFO_REPLACECLOSE  => '</span>'
    ),

    'url' => array(
        BBCODE_INFO_DESCRIPTION   =>
            '[url=http://example.com]cool site![/url]<br/>' .
            '[url]http://example.com[/url]<br/>' .
            'For adding website links.',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('url' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_url_handler'
    ),

    'img' => array(
        BBCODE_INFO_DESCRIPTION   =>'[img]http://example.com/image.jpg[/img]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('img' => '', 'size' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_img_handler'
    ),

    'email' => array(
        BBCODE_INFO_DESCRIPTION   =>
            '[email subject="website mail!"]johndoe@example.com[/email]<br/>' .
            'For adding links to email addresses',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('email' => '', 'subject' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_email_handler'
    ),

    'hr' => array(
        BBCODE_INFO_DESCRIPTION   => '[hr] a horizontal line',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_OPENONLY      => TRUE,
        BBCODE_INFO_REPLACEOPEN   => '<hr class="bbcode"/>',
        BBCODE_INFO_STRIPBREAK    => TRUE
    ),

    'list' => array(
        BBCODE_INFO_DESCRIPTION   =>
            '[list]<br/>[*]Item one<br/>[*]Item two<br/>[/list]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_ARGS          => array('list' => 'b'),
        BBCODE_INFO_CALLBACK      => 'bbcode_list_handler',
        BBCODE_INFO_STRIPBREAK    => TRUE
    ),

    // No description defined. This one is covered by the list item.
    // By not defining a description, we hide this one from the
    // module settings interface. The dependency will make this tag
    // active when the "list" tag is enabled.
    '*' => array(
        BBCODE_INFO_PARENTS       => array('list'),
        BBCODE_INFO_REPLACEOPEN   => '<li>',
        BBCODE_INFO_REPLACECLOSE  => '</li>'
    ),

    'quote' => array(
        BBCODE_INFO_DESCRIPTION   =>
            '[quote]quoted text[/quote]<br/>' .
            '[quote John Doe]quoted text[/quote]<br/>' .
            '[quote=John Doe]quoted text[/quote]<br/>' .
            'For adding quoted text.',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_VALUEONLY     => TRUE,
        BBCODE_INFO_ARGS          => array('quote' => ''),
        BBCODE_INFO_CALLBACK      => 'bbcode_quote_handler',
        // This one not enabled now, because this is also taken care of
        // in include/format_functions.php.
        //BBCODE_INFO_STRIPBREAK    => TRUE

    ),

    'code' => array(
        BBCODE_INFO_DESCRIPTION   =>
            '[code]<br/>' .
            ' preformatted<br/>' .
            '&nbsp;&nbsp;&nbsp;text<br/>' .
            '[/code]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<pre class="bbcode">',
        BBCODE_INFO_REPLACECLOSE  => '</pre>',
        // This one not enabled now, because this is also taken care of
        // in include/format_functions.php.
        //BBCODE_INFO_STRIPBREAK    => TRUE
    ),
    
    'left' => array(
        BBCODE_INFO_DESCRIPTION   =>'[left]left aligned content[/left]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<div style="text-align: left;" class="bbcode">',
        BBCODE_INFO_REPLACECLOSE  => '</div>'
    ),
    
    'center' => array(
        BBCODE_INFO_DESCRIPTION   =>'[center]centered content[/center]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<center class="bbcode">',
        BBCODE_INFO_REPLACECLOSE  => '</center>'
    ),

    'right' => array(
        BBCODE_INFO_DESCRIPTION   =>'[right]right aligned content[/right]',
        BBCODE_INFO_HASEDITORTOOL => TRUE,
        BBCODE_INFO_DEFAULTSTATE  => 2,
        BBCODE_INFO_REPLACEOPEN   => '<div style="text-align: right;" class="bbcode">',
        BBCODE_INFO_REPLACECLOSE  => '</div>'
    )    
);


?>
