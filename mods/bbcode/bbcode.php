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

if (!defined("PHORUM")) return;

require_once('./mods/bbcode/defaults.php');

// Message formatting hook, for translating BBcode tags into HTML.
function phorum_mod_bbcode_format($data)
{
    $PHORUM   = $GLOBALS["PHORUM"];
    $settings = $PHORUM['mod_bbcode'];
    $enabled  = $settings['enabled'];

    // ----------------------------------------------------------------------
    // Prepare the bbcode replacements
    // ----------------------------------------------------------------------

    // Extra properties to add to <a href> tags.
    $extra_href_properties = '';
    if (!empty($settings['links_in_new_window'])) {
        $extra_href_properties.= 'target="_blank" ';
    }
    if (!empty($settings['rel_no_follow'])) {
        $extra_href_properties.= 'rel="nofollow" ';
    }

    // Build search / replace arrays.
    $search  = array();
    $replace = array();

    // Add 'bold' support.
    if (!empty($enabled['bold'])) {
        $search[]  = "/\[b\](.+?)\[\/b\]/is";
        $replace[] = "<strong class=\"bbcode\">$1</strong>";
    }

    // Add 'italic' support.
    if (!empty($enabled['italic'])) {
        $search[]  = "/\[i\](.+?)\[\/i\]/is";
        $replace[] = "<i class=\"bbcode\">$1</i>";
    }

    // Add 'underline' support.
    if (!empty($enabled['underline'])) {
        $search[]  = "/\[u\](.+?)\[\/u\]/is";
        $replace[] = "<u class=\"bbcode\">$1</u>";
    }

    // Add 'strike' support.
    if (!empty($enabled['strike'])) {
        $search[]  = "/\[s\](.+?)\[\/s\]/is";
        $replace[] = "<s class=\"bbcode\">$1</s>";
    }

    // Add 'sub' support.
    if (!empty($enabled['subscript'])) {
        $search[]  = "/\[sub\](.+?)\[\/sub\]/is";
        $replace[] = "<sub class=\"bbcode\">$1</sub>";
    }

    // Add 'sup' support.
    if (!empty($enabled['superscript'])) {
        $search[]  = "/\[sup\](.+?)\[\/sup\]/is";
        $replace[] = "<sup class=\"bbcode\">$1</sup>";
    }

    // Add 'color' support.
    if (!empty($enabled['color'])) {
        $search[]  = "/\[color=([\#a-z0-9]+?)\](.+?)\[\/color\]/is";
        $replace[] = "<span style=\"color: $1\">$2</span>";
    }

    // Add 'size' support.
    if (!empty($enabled['size'])) {
        $search[]  = "/\[size=([+\-\da-z]+?)\](.+?)\[\/size\]/is";
        $replace[] = "<span style=\"font-size: $1\">$2</span>";
    }

    // Add 'small' support.
    if (!empty($enabled['small'])) {
        $search[]  = "/\[small\](.+?)\[\/small\]/is";
        $replace[] = "<small>$1</small>";
    }

    // Add 'large' support.
    if (!empty($enabled['large'])) {
        $search[]  = "/\[large\](.+?)\[\/large\]/is";
        $replace[] = "<span style=\"font-size: large\">$1</span>";
    }

    // Add 'code' support.
    if (!empty($enabled['code'])) {
        $search[]  = "/\[code\](.+?)\[\/code\]/is";
        $replace[] = "<pre class=\"bbcode\">$1</pre>";
    }

    // Add 'center' support.
    if (!empty($enabled['center'])) {
        $search[]  = "/\[center\](.+?)\[\/center\]/is";
        $replace[] = "<center class=\"bbcode\">$1</center>";
    }

    // Add 'hr' support.
    if (!empty($enabled['hr'])) {
        $search[]  = "/\[(hr|hline)\]/i";
        $replace[] = "<hr class=\"bbcode\" />";
    }

    // Add 'img' support.
    if (!empty($enabled['image'])) {
        $search[]  = "/\[img\]((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%# ]+?)\[\/img\]/is";
        $replace[] = "<img src=\"$1\" class=\"bbcode\" alt=\"$1\" />";
    }

    // Add 'url' support.
    if (!empty($enabled['url']))
    {
        $search[]  = "/\[url\]((http:\/\/|https:\/\/|ftp:\/\/|mailto:)([a-z0-9\.\-@:]+)[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),\#%~ ]*?)\[\/url\]/is";
        if (empty($settings['show_full_urls'])) {
            $replace[] = "[<a $extra_href_properties href=\"$1\">$3</a>]";
        } else {
            $replace[] = "<a $extra_href_properties href=\"$1\">$1</a>";
        }

        $search[]  = "/\[url=((http:\/\/|https:\/\/|ftp:\/\/|mailto:)[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%# ]+?)\](.+?)\[\/url\]/is";
        $replace[] = "<a $extra_href_properties href=\"$1\">$3</a>";
    }

    // Add 'email' support.
    if (!empty($enabled['email'])) {
        $search[]  = "/\[email\]([a-z0-9\-_\.\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+?)\[\/email\]/ies";
        $replace[] = "'<a $extra_href_properties href=\"'.phorum_html_encode('mailto:$1').'\">'.phorum_html_encode('$1').'</a>'";
    }

    // Note: 'quote' is special and will be handled later on in the code.

    // ----------------------------------------------------------------------
    // Format the messages.
    // ----------------------------------------------------------------------

    foreach ($data as $message_id => $message)
    {
        // No formatting needed if the message does not contain a body.
        if (!isset($message['body'])) continue;

        // Check for disabled formatting.
        if (!empty($PHORUM["mod_bbcode"]["allow_disable_per_post"]) &&
            !empty($message['meta']['disable_bbcode'])) {
            continue;
        }

        $body = $message["body"];

        // Convert bare URLs into bbcode tags, unless [url] and/or
        // bare URL processing are disabled.
        if (!empty($enabled['url']) &&
            !empty($PHORUM["mod_bbcode"]["process_bare_urls"]))
        {
            // A magic marker, to tag our URL conversion.
            $marker = 'BBCODEMARKER'.substr(md5(microtime()), 0, 8);

            $body = preg_replace("/([^='\"(\[url\]|\[img\])])((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%#]+)/i", "$1:$marker:$2:/$marker:", " $body");
            if (preg_match_all("!:$marker:(.+?):/$marker:!i", $body, $match))
            {
                $urls = array_unique($match[1]);

                foreach ($urls as $key => $url)
                {
                    // Strip punctuation from URL.
                    if (preg_match("|[^a-z0-9=&/\+_]+$|i", $url, $match))
                    {
                        $extra = $match[0];
                        $true_url = substr($url, 0, -1 * (strlen($match[0])));
                        $body = str_replace("$url:/$marker:", "$true_url:/$marker:$extra", $body);
                        $url = $true_url;
                    }

                    // Generate bbcode tag.
                    $body = str_replace(":$marker:$url:/$marker:", "[url]{$url}[/url]", $body);
                }
            }
        }

        // Convert bare email addresses into bbcode tags, unless [email]
        // and/or bare email address processing are disabled.
        if (!empty($enabled['email']) &&
            !empty($PHORUM["mod_bbcode"]["process_bare_email"]))
        {
            $body = preg_replace("/(^|[\s])([a-z0-9][a-z0-9\-_\.\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[a-z0-9])([\?\!\.,;:\s]|<phorum break>|$)/i", "$1[email]$2[/email]$3", $body);
        }

        // It makes no sense to do any of the following code if there is no
        // "[" character in the body by now.
        if (strstr($body, "["))
        {
            // Fiddle with white space around quote and code tags.
            $body = preg_replace("/\s*(\[\/?(code|quote)\])\s*/", "$1", $body);

            // Run the regular expression replacements for the standard tags.
            $body = preg_replace($search, $replace, $body);

            // The [quote] tags have to be handled differently, because these
            // can be embedded within each other. Here, we make sure that
            // we only run quote replacement if we have matching start and
            // end tags.
            if (!empty($enabled['quote']) &&
                preg_match_all('/\[quote([\s=][^\]\[]+?)?\]/', $body, $m) &&
                count($m[0]) == substr_count($body, "[/quote]"))
            {
                $quotestart = '<blockquote class="bbcode">'.
                              $PHORUM["DATA"]["LANG"]["mod_bbcode"]["quote_title"] .
                              ':<div>';

                $body = preg_replace(array(
                    "/\[quote\]/is",
                    "/\[quote ([^\]\[]+?)\]/is",
                    "/\[quote=([^\]\[]+?)\]/is",
                    "/\[\/quote\]/is"
                ), array(
                    "$quotestart",
                    "$quotestart<strong>$1</strong><br/>",
                    "$quotestart<strong>$1</strong><br/>",
                    "<br/></div></blockquote>"
                ), $body);
            }
        }

        $data[$message_id]["body"] = $body;
    }

    return $data;
}

// Quote hook, for overriding the default Phorum message quoting method.
function phorum_mod_bbcode_quote ($data)
{
    // Some other hook already formatted the quote.
    if (!is_array($data)) return $data;

    if (!empty($GLOBALS["PHORUM"]["mod_bbcode"]["quote_hook"]))
    {
        // Replace characters that collide with bbcode safe characters,
        // otherwise, the [quote] will not be parsed correctly.
        $author = str_replace(array('[',']'), array('(',')'),$data[0]);

        return "[quote $author]$data[1][/quote]";
    } else {
        return $data;
    }
}

// Add the "Disable BBcode" option to the template. Note that the template
// should contain the code {HOOK "tpl_editor_disable_bbcode"} at an
// appropriate place for this to work.
function phorum_mod_bbcode_tpl_editor_disable_bbcode()
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_bbcode"]["allow_disable_per_post"]))
        return;

    include(phorum_get_template('bbcode::disable_option'));
}

// Process "Disable BBcode" option from the message form.
function phorum_mod_bbcode_posting_custom_action($message)
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_bbcode"]["allow_disable_per_post"])) {
        unset($message['meta']['disable_bbcode']);
        return $message;
    }

    if (count($_POST)) {
        if (empty($_POST['disable_bbcode'])) {
            unset($message['meta']['disable_bbcode']);
        } else {
            $message['meta']['disable_bbcode'] = 1;
        }
    }

    return $message;
}

// Add tool buttons to the Editor Tools module's tool bar.
function phorum_mod_bbcode_editor_tool_plugin()
{
    $PHORUM = $GLOBALS['PHORUM'];
    $lang   = $PHORUM['DATA']['LANG']['mod_bbcode'];

    $nr_of_enabled_tags = 0;

    // Register the tool buttons.
    foreach ($GLOBALS["bbcode_features"] as $id => $feature)
    {
        // Keep track of the number of enabled tags.
        if (!empty($PHORUM["mod_bbcode"]["enabled"][$id])) {
            $nr_of_enabled_tags ++;
        }

        // Skip feature if no editor tools button is implemented.
        if (! $feature[1]) continue;

        // Skip feature, unless the editor tool button is enabled.
        if ($PHORUM["mod_bbcode"]["enabled"][$id] != 2) continue;

        // Determine the description to use for the tool. If we can find
        // a description in the language strings, then we use that one.
        // Otherwise, we simply fall back to the less descriptive feature id.
        $description = isset($lang[$id]) ? $lang[$id] : $id;

        // Register the tool button with the Editor Tools module.
        editor_tools_register_tool(
            $id,                           // Tool id
            $description,                  // Tool description
            "./mods/bbcode/icons/$id.gif", // Tool button icon
            "editor_tools_handle_$id()"    // Javascript action on click
        );
    }

    // Register the bbcode help page, unless no tags were enabled at all.
    if ($nr_of_enabled_tags > 0)
    {
        $description = isset($lang['bbcode help'])
                     ? $lang['bbcode help'] : 'BBcode help';

        editor_tools_register_help(
            $description,
            phorum_get_url(PHORUM_ADDON_URL, 'module=bbcode', 'action=help')
        );
    }

    // Make language strings available for the editor tools javascript code.
    editor_tools_register_translations($lang);
}

// Register the additional CSS code for this module.
function phorum_mod_bbcode_css_register($data)
{
    // We only want to add data to the standard screen stylesheet.
    if ($data['css'] != 'css') return $data;

    // For the "color" tool, we need to load the color picker CSS stylesheet.
    $data['register'][] = array(
        "module" => "bbcode",
        "where"  => "after",
        "source" => "file(mods/bbcode/colorpicker/js_color_picker_v2.css)"
    );
    return $data;
}

// Register the additional JavaScript code for this module.
function phorum_mod_bbcode_javascript_register($data)
{
    $data[] = array(
        "module" => "bbcode",
        "source" => "file(mods/bbcode/bbcode_editor_tools.js)"
    );

    // If the color tool is not enabled, we are done.
    if (empty($GLOBALS['bbcode_features']['color'])) return $data;

    // Add libraries for the color tool.
    $data[] = array(
        "module" => "bbcode",
        "source" => "file(mods/bbcode/colorpicker/js_color_picker_v2.js.php)"
    );
    $data[] = array(
        "module" => "bbcode",
        "source" => "file(mods/bbcode/colorpicker/color_functions.js)"
    );

    return $data;
}

// The addon hook is used for displaying a help info screen.
function phorum_mod_bbcode_addon()
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($PHORUM["args"]["action"])) trigger_error(
        'Missing "action" argument for bbcode module addon call',
        E_USER_ERROR
    );

    if ($PHORUM["args"]["action"] == 'help')
    {
        $lang = $GLOBALS['PHORUM']['language'];
        if (!file_exists("./mods/bbcode/help/$lang/bbcode.php")) {
            $lang = 'english';
        }
        include("./mods/bbcode/help/$lang/bbcode.php");
        exit(0);
    }

    trigger_error(
        'Illegal "action" argument ' .
        '"' . htmlspecialchars($PHORUM['args']['action']) . '"' .
        'for bbcode module addon call',
        E_USER_ERROR
    );
}

?>
