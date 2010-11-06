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

if (!defined("PHORUM_ADMIN")) return;

// Load constants.
require_once('./mods/bbcode/api.php');

// Load tag descriptions.
require_once('./mods/bbcode/builtin_tags.php');

// Load default settings.
require_once('./mods/bbcode/defaults.php');

// Available option sets for the bbcode tag dropdown menus.
$options_without_editor_tools = array(
    0  => 'Disabled',
    1  => 'Enabled',
);
$options_with_editor_tools = $options_without_editor_tools;
$options_with_editor_tools[2] = 'Enabled + editor tools button';

// Save settings.
if (count($_POST))
{
    // Add options to the settings array.
    $PHORUM["mod_bbcode"] = array(
      "links_in_new_window"    => empty($_POST["links_in_new_window"])    ?0:1,
      "rel_no_follow"          => empty($_POST["rel_no_follow"])          ?0:$_POST["rel_no_follow"],
      "follow_urls"            => empty($_POST["follow_urls"])          ?'':$_POST["follow_urls"],
      "quote_hook"             => empty($_POST["quote_hook"])             ?0:1,
      "show_full_urls"         => empty($_POST["show_full_urls"])         ?0:1,
      "process_bare_urls"      => empty($_POST["process_bare_urls"])      ?0:1,
      "process_bare_email"     => empty($_POST["process_bare_email"])     ?0:1,
      "allow_disable_per_post" => empty($_POST["allow_disable_per_post"]) ?0:1,
      "enable_bbcode_escape"   => empty($_POST["enable_bbcode_escape"])   ?0:1
    );

    // Add enable / disable status for individual tags.
    $nr_of_enabled_tags = 0;
    $PHORUM['mod_bbcode']['enabled'] = array();
    foreach ($GLOBALS['PHORUM']['MOD_BBCODE']['BUILTIN'] as $tagname => $tag) {
        if (isset($_POST['enabled'][$tagname])) {
            $value = (int) $_POST['enabled'][$tagname];
            $PHORUM['mod_bbcode']['enabled'][$tagname] = $value;
            if ($value == 2) $nr_of_enabled_tags ++;
        }
    }

    // Store the new settings array.
    phorum_db_update_settings(array(
        'mod_bbcode' => $PHORUM['mod_bbcode']
    ));

    phorum_admin_okmsg("The settings were successfully saved.");

    if ($nr_of_enabled_tags > 0 && empty($PHORUM['mods']['editor_tools'])) {
        phorum_admin_error("<b>Notice:</b> You have configured one or more BBcode tags to add a button to the editor tool bar. However, you have not enabled the Editor Tools module. If you want to use the tool buttons, then remember to activate the Editor Tools module.");
    }
}

require_once('./include/admin/PhorumInputForm.php');
$frm = new PhorumInputForm ("", "post", "Save settings");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "bbcode");

$frm->addbreak("General settings for the BBcode module");

$row = $frm->addrow("Open links in new window", $frm->checkbox("links_in_new_window", "1", "Yes", $PHORUM["mod_bbcode"]["links_in_new_window"]));
$frm->addhelp($row, "Open links in new window", "When users post links on your forum, you can choose whether to open these in a new window or not.");

$row = $frm->addrow("Turn bare URLs into clickable links", $frm->checkbox("process_bare_urls", "1", "Yes", $PHORUM["mod_bbcode"]["process_bare_urls"]));
$frm->addhelp($row, "Turn bare URLs into clickable links", "If you enable this option, then the BBcode module will try to detect bare URLs in the message (URLs that are not surrounded by [url]...[/url] BBcode tags) and turn those into clickable links (as if they were surrounded by [url]...[/url]).");

$row = $frm->addrow("Turn bare email addresses into clickable links", $frm->checkbox("process_bare_email", "1", "Yes", $PHORUM["mod_bbcode"]["process_bare_email"]));
$frm->addhelp($row, "Turn bare email addresses into clickable links", "If you enable this option, then the BBcode module will try to detect bare email addresses in the message (addresses that are not surrounded by [email]...[/email] BBcode tags) and turn those into clickable links (as if they were surrounded by [email]...[/email]).");

$row = $frm->addrow("Show full URLs", $frm->checkbox("show_full_urls", "1", "Yes", $PHORUM["mod_bbcode"]["show_full_urls"]));
$frm->addhelp($row, "Show full URLs", "By default, URLs are truncated by phorum to show only [www.example.com]. This is done to prevent very long URLs from cluttering and distrurbing the web site layout. By enabling this feature, you can suppress the truncation, so full URLs are shown.");

$row = $frm->addrow("Add 'rel=nofollow' to links that are posted in your forum", $frm->select_tag('rel_no_follow', array('No','Yes, always','Yes, to external urls and urls not defined below.'),$PHORUM["mod_bbcode"]["rel_no_follow"]) );
$frm->addhelp($row, "Add 'rel=nofollow' to links", 'You can enable Google\'s rel="nofollow" tag for links that are posted in your forums. This tag is used to discourage spamming links to web sites in forums (which can be done to influence search engines by implying that the site is a popular one, because of all the links).<br/><br/>Note that this does not stop spam links from being posted, but it does mean that spammers do not get any credit from Google for that link.');

$row = $frm->addrow("Allowed URLs that won't get the nofollow attribute as defined above", $frm->text_box("follow_urls", $PHORUM["mod_bbcode"]["follow_urls"], 75 ) );
$frm->addhelp($row, "Allowed URLs that won't get the nofollow attribute as defined above", 'If the preceding setting is set to "Yes, to external urls and urls not defined below." this list is checked for allowed URLs which won\'t get the rel=nofollow attribute added. By default only the Phorum URL is allowed. I you want to allow a different URL, then enter that URL here. Multiple URLs can be provided as a comma separated list. All URLs not starting with the URLs listed here, besides the Phorum URL, will get the nofollow attribute added.<br /><br />A full URL should be like http://www.domainname.com/path or http://www.domain.com ... ');


$row = $frm->addrow("Enable BBcode quoting using the [quote] tag", $frm->checkbox("quote_hook", "1", "Yes", $PHORUM["mod_bbcode"]["quote_hook"]));
$frm->addhelp($row, "Enable BBcode [quote]", "If this feature is enabled, then quoting of messages is not done using the standard Phorum method (which resembles email message quoting), but using the BBcode module's quoting method instead. This means that the quoted text is placed within a [quote Author]...[/quote] bbcode block.<br/><br/>Two of the advantages of using this quote method is that the quoted message can be styles though CSS code and that no word wrapping is applied to the text.");

$row = $frm->addrow("Enable posting option \"disable BBcode\"", $frm->checkbox("allow_disable_per_post", "1", "Yes", $PHORUM["mod_bbcode"]["allow_disable_per_post"]));
$frm->addhelp($row, "Enable posting option \"disable BBcode\"", "If this feature is enabled, then your users can get an extra option in the posting editor for disabling the BBcode handling for the posted message. This can be useful if the user wants to post a text about BBcode tags or a text that contains strings that unintentionally match BBcode tags.<br/><br/>To make this option visible, you will have to add the code <b>{HOOK \"tpl_editor_disable_bbcode\"}</b> to the posting.tpl template file at an appropriate spot.");

$row = $frm->addrow("Enable BBcode escape sequence \\[...]", $frm->checkbox("enable_bbcode_escape", "1", "Yes", $PHORUM["mod_bbcode"]["enable_bbcode_escape"]));
$frm->addhelp($row, "Enable BBcode escape sequence", "If this feature is enabled, then your users can prepend BBcode tags with a backslash character in order to let the BBcode module ignore the tag. This is mostly useful for posting BBcode examples in a forum message. In the final rendered message, the backslash character will be hidden. For example, the following code could be added to a message:<br/><pre>\\[b]example for bold text\\[/b]<br/>\\[url=http://www.phorum.org]URL example\\[/url]<br/><br/>These would be showed in the final message as:<br/><pre>[b]example for bold text[/b]<br/>[url=http://www.phorum.org]URL example[/url]");

$row = $frm->addbreak("Activation of BBcode tags");
$frm->addhelp($row, "Activation of BBcode tags", "Using the options below, you can configure which BBcode tags you want to make available to your users.<br/><br/>For most of the tags, you can additionally enable the editor tools button. If you have enabled the Editor Tools module, then doing so will add a button to the editor tool bar for that tag.");

foreach ($GLOBALS['PHORUM']['MOD_BBCODE']['BUILTIN'] as $tagname => $tag)
{
    if (!isset($tag[BBCODE_INFO_DESCRIPTION])) continue;

    $options = $tag[BBCODE_INFO_HASEDITORTOOL]
             ? $options_with_editor_tools
             : $options_without_editor_tools;

    $value   = isset($PHORUM['mod_bbcode']['enabled'][$tagname])
             ? $PHORUM['mod_bbcode']['enabled'][$tagname]
             : $tag[BBCODE_INFO_DEFAULTSTATE];

    $frm->addrow(
        $tag[BBCODE_INFO_DESCRIPTION],
        $frm->select_tag("enabled[$tagname]", $options, $value),
        "top"
    );
}

$frm->show();
?>
