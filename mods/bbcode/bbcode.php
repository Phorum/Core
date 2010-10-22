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

if (!defined("PHORUM")) return;

require_once('./mods/bbcode/api.php');
require_once('./mods/bbcode/builtin_tags.php');
require_once('./mods/bbcode/defaults.php');

// Initialize the bbcode parser if that has not been done before.
// This will mainly be used at install time to initialize the data.
if (!isset($GLOBALS['PHORUM']['mod_bbcode_parser'])) {
    bbcode_api_initparser();
}

// Message formatting hook, for translating BBcode tags into HTML.
function phorum_mod_bbcode_format($data)
{
    global $PHORUM;
    $settings = $PHORUM['mod_bbcode'];

    // Initialize the BBcode parser.
    static $init = FALSE;
    if ($init == FALSE) {
        bbcode_api_initparser();
    }

    // Format the message bodies.
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
        if (!empty($PHORUM['mod_bbcode_parser']['taginfo']['url']) &&
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
        if (!empty($PHORUM['mod_bbcode_parser']['taginfo']['email']) &&
            !empty($PHORUM["mod_bbcode"]["process_bare_urls"]))
        {
            $body = preg_replace("/(^|[\s])([a-z0-9][a-z0-9\-_\.\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[a-z0-9])([\?\!\.,;:\s]|<phorum break>|$)/i", "$1[email]$2[/email]$3", $body);
        }

        // It makes no sense to do any of the following code if there is no
        // "[" character in the body by now.
        if (strpos($body, '[') !== FALSE)
        {
            // Tokenize the body code.
            $tokens = bbcode_api_tokenize($body);

            // Render the tokens into an HTML page.
            $body = bbcode_api_render($body, $tokens, $data[$message_id]);
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
        // Quote the author name if necessary.
        if (strpos($data[0], ' ') === FALSE &&
            strpos($data[0], '"') === FALSE &&
            strpos($data[0], ']') === FALSE) {
            $author = $data[0];
        } else {
            $author = $data[0];
            $author = str_replace('\\', '\\\\', $author);
            $author = str_replace('"', '\\"', $author);
            $author = '"' . $author . '"';
        }

        return "[quote=$author]\n$data[1][/quote]";
    }
    else  {
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
    global $PHORUM;

    $lang = $PHORUM['DATA']['LANG']['mod_bbcode'];

    $nr_of_enabled_tags = 0;

    $enabled = isset($PHORUM['mod_bbcode']['enabled'])
        ? $PHORUM['mod_bbcode']['enabled'] : array();
    $builtin = $PHORUM['MOD_BBCODE']['BUILTIN'];

    // Register the tool buttons.
    foreach ($PHORUM['mod_bbcode_parser']['taginfo'] as $id => $taginfo)
    {
        // Skip tool if no editor tools button is implemented.
        if (! $taginfo[BBCODE_INFO_HASEDITORTOOL]) continue;

        // Check if the editor tool should be shown. If not, then skip
        // to the next tag. If there are no settings saved yet for the
        // module, then use the settings from the builtin tag list.
        if ((isset($enabled[$id]) && $enabled[$id] != 2) ||
            (!isset($PHORUM['mod_bbcode']['enabled'][$id]) &&
             isset($builtin[$id]) &&
             $builtin[$id][BBCODE_INFO_DEFAULTSTATE] != 2)) {
             continue;
        }

        // Keep track of the number of enabled tags.
        $nr_of_enabled_tags ++;

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
    global $PHORUM;

    $data[] = array(
        "module" => "bbcode",
        "source" => "file(mods/bbcode/bbcode_editor_tools.js)"
    );

    // If the color tool is not enabled, we are done.
    if (empty($PHORUM['mod_bbcode_parser']['taginfo']['color'])) return $data;

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
