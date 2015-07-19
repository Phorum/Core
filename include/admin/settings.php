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
////////////////////////////////////////////////////////////////////////////////

if ( !defined( "PHORUM_ADMIN" ) ) return;

$error = "";

if ( count( $_POST ) )
{
    // Keep track if we need to run display name updates.
    $need_display_name_updates = FALSE;

    // set the defaults
    foreach( $_POST as $field => $value ) {
        switch ( $field ) {
            case "title":

                if ( empty( $value ) ) {
                    $_POST[$field] = "Phorum 5";
                }

                break;

            case "http_path":

                if ( empty( $value ) ) {
                    $_POST[$field] = dirname( $_SERVER["HTTP_REFERER"] );
                } elseif ( !preg_match( "/^(http|https):\/\/(([a-z0-9][a-z0-9_-]*)(\.[a-z0-9][a-z0-9_-]*)+)(:(\d+))?/i", $value ) && !preg_match( "/^(http|https):\/\/[a-z0-9][a-z0-9_-]*(:\d+)?/i", $value ) ) {
                    $error = "The provided HTTP Path is not a valid URL.";
                }

                break;

            case "session_timeout":

                $_POST[$field] = (int)$_POST[$field];

                break;

            case "short_session_timeout":

                $_POST[$field] = (int)$_POST[$field];

                // impose a 5 minute minimum on this field for sanity reasons
                if($_POST[$field]<5) $_POST[$field];

                break;

            case "session_path":

                if ( empty( $value ) ) {
                    $_POST[$field] = "/";
                } elseif ( $value[0] != "/" ) {
                    $error = "Session Path must start with a /";
                }

                break;

            case "session_domain":

                if ( !empty( $value ) && !stristr( $_POST["http_path"], $value ) ) {
                    $error = "Session Domain must be part of the domain in HTTP Path or empty.";
                }

                break;

            case "system_email_from_address":

                if ( empty( $value ) ) {
                    $error = "You must supply an email address for system emails to use as a from address.";
                }

                break;

            case "max_file_size":

                settype( $_POST[$field], "int" );

                break;

            case "file_space_quota":

                settype( $_POST[$field], "int" );

                break;

            case "file_types":

                $_POST[$field] = strtolower( $value );

                break;

            case "private_key":

                $private_key = trim($value);
                if (strlen($private_key) < 30) {
                    $error = "Use at least 30 characters for the secret private key.";
                }
                $_POST[$field] = $private_key;
                break;

            case "display_name_source":

                if ($_POST[$field] != $PHORUM["display_name_source"]) {
                    $need_display_name_updates = TRUE;
                }
                break;
        }

        if ( $error ) break;
    }

    if ( empty( $error ) ) {
        unset($_POST["module"] );
        unset($_POST["phorum_admin_token"]);

        if ( phorum_db_update_settings( $_POST ) ) {
            $redir = phorum_admin_build_url(array('module=settings','message=success'), TRUE);
            if ($need_display_name_updates) {
                $redir = phorum_admin_build_url(array('module=update_display_names'), TRUE);
            }
            phorum_redirect_by_url($redir);
            exit();
        } else {
            $error = "Database error while updating settings.";
        }
    }
}

if ( $error ) {
    phorum_admin_error( $error );
} elseif( isset($_GET['message']) && $_GET['message'] == 'success' ) {
	$okmsg = "Settings updated";
    phorum_admin_okmsg ( $okmsg);
}
// create the time zone drop down array
for( $x = -23;$x <= 23;$x++ ) {
    $tz_range[$x] = $x;
}

include_once "./include/admin/PhorumInputForm.php";

$frm = new PhorumInputForm ( "", "post" );

$frm->addbreak( "Phorum General Settings" );

$frm->hidden( "module", "settings" );

$row=$frm->addrow( "Phorum Title", $frm->text_box( "title", $PHORUM["title"], 50 ) );

$row=$frm->addrow( "Phorum Description", $frm->textarea( "description", $PHORUM["description"], 30, 5, "style='width: 100%'" ) );

$row=$frm->addrow( "DNS Lookups", $frm->select_tag( "dns_lookup", array( "No", "Yes" ), $PHORUM["dns_lookup"] ) );
$frm->addhelp($row, "DNS Lookups",
    "DNS is the system that (amongsts other things) is responsible for
     translating host names into IP addresses and vice versa.
     If DNS lookups are enabled, phorum will use DNS lookups for:
     <ul>
     <li>logging the hostname from which a message was posted, instead of
         the plain IP address;</li>
     <li>being able to create IP ban items based on host and domain names;</li>
     <li>checking the email address that is for user registrations.</li>
     </ul>
     If Phorum keeps reporting illegal email addresses during signup or
     if you are suffering from massive delays during posting messages,
     then there might be DNS problems on your server. In that case, disable
     DNS lookups as a work around.");

$row=$frm->addrow( "Hide inaccessible Forums on the Index Page", $frm->select_tag( "hide_forums", array( "No", "Yes" ), $PHORUM["hide_forums"] ) );
$frm->addhelp($row, "Hide inaccessible Forums on the Index Page", "By setting this to Yes, forums that users are not allowed to read will be hidden from them in the forums list." );

$row=$frm->addrow( "Show New Counts for Forums on the Index Page", $frm->select_tag( "show_new_on_index", array( "No", "Yes", "No count, just indicator" ), $PHORUM["show_new_on_index"] ) );
$frm->addhelp($row, "Show New Counts for Forums on the Index Page", "This feature will show registered users how many new messages and threads there are in each forum on the forum list page.  If you have a large number of posts, a large number of forums, a large number of users or some combination of the three, this setting could cause some performance issues.  If you see performance issues, try setting it \"No count, indicator only\" or \"No\"" );

$row=$frm->addrow( "How to Display Forums and Folders on the Index Page", $frm->select_tag( "use_new_folder_style", array( "Directory Structure", "Flat Structure" ), $PHORUM["use_new_folder_style"] ) );
$frm->addhelp($row, "How to Display Forums and Folders on the Index Page",
    "Forum has multiple displaying styles available for the index page:
     <ul>
     <li><b>Directory Structure:</b><br/>
         <br/>
         <em>This style resembles the way in which you normally would browse a
         filesystem directory structure, hence the name.</em><br/>
         <br/>
         When using this style, the index page will show a list of folders
         and a list of forums that are available in the folder for which the
         index page is shown. The user can either go to a forum or traverse
         the folder tree by going to one of the deeper folders.<br/><br/></li>
     <li><b>Flat Structure</b><br/>
         <br/>
         <em>This style will present the user a flat list of category sections
         with forums (and possibly folders) in them. This is the style
         that most forums use nowadays.</em><br/>
         <br/>
         When using this style, the root index page will show category
         sections with forums and folders in them. Each section is a folder
         that is directly below the root. The folders and forums that are
         in those folders are shown in the corresponding sections.<br/>
         Forums that are placed directly in the root folder, will be
         shown in a generic \"Forums\" section.<br/>
         <br/>
         When visiting a subfolder, then the folders and forums inside that
         subfolder are shown. For a subfolder, No sections are displayed.
         This means that browsing subfolders in the flat structure works
         a bit like browsing them in the directory structure.</li>
     </ul>"
);

$row=$frm->addrow( "Enable Moderator Notifications", $frm->select_tag( "enable_moderator_notifications", array( "No", "Yes" ), $PHORUM["enable_moderator_notifications"] ) );
$frm->addhelp($row, "Enable Moderator Notifications", "By setting this to Yes, Phorum will display notice to the various kinds of moderators when they have a new item that requires their attention. For example, message moderators will see a notice whenever there is an unapproved message." );

$row=$frm->addrow( "User Post Edit Time Limit (minutes)", $frm->text_box( "user_edit_timelimit", $PHORUM["user_edit_timelimit"], 10) );
$frm->addhelp($row, "User Post Edit Time Limit (minutes)", "If set to a value larger then 0, this acts as a time limit for post editing. Users will only be able to edit their own posts within this time limit. This only applies if a user has the necessary permissions to edit their post, and does not affect moderators." );

$row=$frm->addrow( "Track Edit Changes", $frm->select_tag( "track_edits", array( "No", "Yes", "Yes, Moderator Only" ), $PHORUM["track_edits"] ) );
$frm->addhelp($row, "Track Edit Changes", "When enabled, the changes made to a message will be stored and viewed by users.  It can optionaly only be viewed by moderators." );

$row=$frm->addrow( "Reply form appears", $frm->select_tag( "reply_on_read_page", array( "1"=>"On the read page", "0"=>"On a separate page" ), $PHORUM["reply_on_read_page"] ) );

$row=$frm->addrow( "After posting go to", $frm->select_tag( "redirect_after_post", array( "list"=>"Message List Page", "read"=>"Message Read Page" ), $PHORUM["redirect_after_post"] ) );

$row=$frm->addrow( "After submitting a search query", $frm->select_tag( "skip_intermediate_search_page", array( 0=>"show an intermediate page (\"search is running\")", 1=>"directly go to the search results" ), $PHORUM["skip_intermediate_search_page"] ) );
$frm->addhelp($row, "After search action", "On large forums or slow servers, searching for messages might take a little while. To prevent users from submitting the same search query over and over again (in case they think the search didn't work, because they didn't get their results fast enough), you can show an intermediate page, telling the user that the search is running. If your system can deliver search results quickly enough, then you can skip the intermediate page and go directly to the search results page.");

$row=$frm->addrow( "Database error handling", $frm->select_tag( "error_logging", array( "screen"=>"Errors will be shown on the screen", "file"=>"Errors will go to a logfile (".$PHORUM['cache']."/phorum-sql-errors.log)", "mail"=> "Errors will be emailed to the system email address"), $PHORUM["error_logging"] ) );

$row=$frm->addrow( "Secret private key for signing data", $frm->text_box("private_key", $PHORUM["private_key"], 50) );
$frm->addhelp($row, "Secret key for signing data", "On several occasions, data is transferred from the Phorum system to the user's system and back again. To be sure that there was no tampering with this data on the way, it is signed by Phorum using this secret key. If you do not understand what this is for, then it is safe to simply keep the pre-configured value.<br/><br/><b>Warning:</b> if you change this key, users who are active right now might experience problems.");

$row=$frm->addrow( "Allow Linking To Uploaded Files", $frm->select_tag( "file_offsite", array( PHORUM_OFFSITE_FORUMONLY => "Only from the forum", PHORUM_OFFSITE_THISSITE => "From this web site", PHORUM_OFFSITE_ANYSITE => "From any web site" ), $PHORUM["file_offsite"] ) );
$frm->addhelp($row, "Allow Off Site Links", "You may not want to allow other web sites to link to files that users have uploaded to your forums. If not, then set this option to \"Only from the forum\". If you want to use links on other parts of your web site, then use \"From this web site\". If you want to allow other websites to link to your forum file uploads, then select \"From any web site\".<br/><br/>If your needs are more specific than this (e.g. if you want to allow access from a specific group of web sites), you will need to use your web server's security features to accomplish this. Apache users can reference <i>Prevent \"Image Theft\"</i> at http://httpd.apache.org/docs/env.html#examples." );

$row=$frm->addrow( "Put file name in pathinfo for file download URLs", $frm->select_tag("file_url_uses_pathinfo", array( "No", "Yes"), $PHORUM["file_url_uses_pathinfo"]) );
$frm->addhelp($row, "Use pathinfo for file URLs", "All Phorum file downloads (for user files and forum message attachments) run through the file.php script. As a result, users who right-click a file URL and choose \"Save link as ..\" will end up in their browser with file.php as the default file name. With this option enabled however, Phorum will try to give the browser a real file name instead. This is done by putting the file name in the URL as pathinfo (which makes the download link look like /file.php/downloadfile.ext?1,2,file=3).<br/><br/>The webserver needs to support the use of pathinfo for this feature to work. So if you are unable to download files after enabling this option, your webserver probably lacks pathinfo support and you cannot use this feature.");

$row=$frm->addrow( "Use the PHP fileinfo extension for mime-type detection", $frm->select_tag("file_fileinfo_ext", array( "No", "Yes"), (isset($PHORUM["file_fileinfo_ext"]))?$PHORUM["file_fileinfo_ext"]:1 ) );
$frm->addhelp($row, "Use the PHP fileinfo extension for mime-type detection", "Fileinfo is a php-extension which was added by default in PHP-5.3.0 and is a <a href=\"http://pecl.php.net/package/Fileinfo\">PECL extension</a> for manual install in previous php-versions. If this setting is enabled, the fileinfo extension will be used to return the mime-type of uploaded files to make sure that the mime-type matches the file contents and isn't done purely based on the data on upload or the file extension.");

$row=$frm->addrow( "Mime Magic file for the PHP fileinfo extension", $frm->text_box("mime_magic_file", $PHORUM["mime_magic_file"], 75 ) );
$frm->addhelp($row, "Mime Magic file for the PHP fileinfo extension", 'If no system-wide mime magic file is installed, the fileinfo extension can use a manually defined one. You could download one (it actually consists of 4 files) manually and put the path to the mime magic file to use in this field, including the file itself without the extension.<br /><br />For example, if you installed the mime magic files in <pre>C:\Programme\xampp\share\mime</pre> you will have to enter <pre>C:\Programme\xampp\share\mime\magic</pre> in this field.');

$row=$frm->addrow( "Allow Login-Redirection to the following URLs", $frm->text_box("login_redir_urls", $PHORUM["login_redir_urls"], 75 ));
$frm->addhelp($row, "Allow Login-Redirection to the following URLs", "The login.php script can be called with a \"redir=&lt;url&gt;\" parameter, to let it redirect to an URL of choice after logging in. For security reasons, only redirects to localhost and to the Phorum URL are allowed. I you want to allow Phorum to redirect to a different URL, then enter that URL here. Multiple URLs can be provided as a comma separated list. Redirects to URLs not starting with the URLs listed here, besides the Phorum URL and localhost, are ignored.<br /><br />A full URL should be like http://www.domainname.com/path or http://www.domain.com ... ");

$frm->addbreak( "HTML Settings" );

$row=$frm->addrow( "Phorum HTML Title", $frm->text_box( "html_title", $PHORUM["html_title"], 50 ) );

$row=$frm->addrow( "Phorum Head Tags", $frm->textarea( "head_tags", $PHORUM["head_tags"], 30, 5, "style='width: 100%'" ) );
$frm->addhelp($row, "Phorum Head Tags", "This option can be used to provide additional HTML code that will be added to the &lt;head&gt; section of the pages. This could for example be used for adding meta keywords:<br/>
<pre style=\"font-size: x-small\">&lt;meta name=\"KEYWORDS\" content=\"...\"\ /&gt;</pre>");

$row=$frm->addrow( "Show and allow feed links", $frm->select_tag( "use_rss", array( "No", "Yes" ), $PHORUM["use_rss"] ) );
$frm->addhelp($row, "Show and allow feed links",
    "A feed is a standardized format for providing information about new
     content on a web site. Various programs can be used to process and
     read feed information (e.g. specialized \"feed readers\", Firefox,
     Google's on line feed reader, Thunderbird mail).<br />
     <br />
     If you enable this feature, then your visitors will be able to
     subscribe to feeds for your forums.<br />
     <br />
     Note that feed readers will automatically poll for feed updates using
     a client side defined time interval, so with a lot of active feed
     subscriptions, the number of requests to your web server will rise.
     Phorum uses server side caching of the feed data to keep the
     server load that is required for supporting feeds to a minimum.");

$row=$frm->addrow( "Default feed type", $frm->select_tag( "default_feed", array( "rss"=>"RSS", "atom"=>"Atom" ), $PHORUM["default_feed"] ) );
$frm->addhelp($row, "Default feed type",
    "There are multiple standards for providing content feeds.
     Phorum supports RSS and ATOM, which are the most widely spread XML
     based feed formats.<br />
     <br />
     If you are unsure what to use, then select \"RSS\".");

$frm->addbreak( "File/Path Settings" );

$row=$frm->addrow( "HTTP Path", $frm->text_box( "http_path", $PHORUM["http_path"], 30 ) );
$frm->addhelp($row, "HTTP Path", "This is the base url of your Phorum." );

$row=$frm->addrow( "Disabled URL", $frm->text_box( "disabled_url", $PHORUM["disabled_url"], 50 ) );
$frm->addhelp($row, "Disabled URL", "This url will be redirected to when the Phorum status is disabled.  If no URL is given, a message in English will be displayed." );

$frm->addbreak( "Date Options" );

$row=$frm->addrow( "Time Zone Offset", $frm->select_tag( "tz_offset", $tz_range, $PHORUM["tz_offset"] ) );
$frm->addhelp($row, "Time Zone Offset", "If you and/or your users are in a different time zone than the server, you can have the default displayed time adjusted by using this option." );

$frm->addbreak( "Cookie/Session Settings" );

$row=$frm->addrow( "Use Cookies", $frm->select_tag( "use_cookies", array( "Use no cookies", "Allow cookies", "Require cookies" ), $PHORUM["use_cookies"] ) );
$frm->addhelp($row, "Use Cookies", "Phorum can track logged in users by using cookies or session information on URLs.<br/><br/><b>Use no cookies</b>: The session information will always be included on the URL.<br/><br/><b>Allow cookies</b>: The session information will be stored in cookies, if the user's browser supports it. Otherwise the information is included on the URL.<br/><br/><b>Require cookies</b>: Session information is only stored in cookies. If the user's browser does not support cookies, the user will not be able to login.");

$row=$frm->addrow( "Main Session Timeout (days)", $frm->text_box( "session_timeout", $PHORUM["session_timeout"], 10 ) );
$frm->addhelp($row, "Session Timeout", "When users log in to your Phorum, they are issued a cookie.  You can set this timeout to the number of days that you want the cookie to stay on the users computer.  If you set it to 0, the cookie will only last as long as the user has the browser open." );

$row=$frm->addrow( "Session Path (start with /)", $frm->text_box( "session_path", $PHORUM["session_path"], 30 ) );
$frm->addhelp($row, "Session Path", "When cookies are sent to client's browser, part of the cookie determines the path (url) for which the cookies are valid.  For example, if the url is http://example.com/phorum, you could set the path to /phorum.  Then, the users browser would only send the cookie information when the user accessed the Phorum.  You could also use simply / and the cookie info will be sent for any page on your site.  This could be useful if you want to use Phorum's login system for other parts of your site." );

$row=$frm->addrow( "Session Domain", $frm->text_box( "session_domain", $PHORUM["session_domain"], 30 ) );
$frm->addhelp($row, "Session Domain", "Most likely, you can leave this blank.  If you know you need to use a different domain (like you use forums.example.com, you may want to just use example.com as the domain), you may enter it here." );

$row=$frm->addrow( "Track User Usage", $frm->select_tag( "track_user_activity", array( 0=>"Never", 86400=>"Once per day", 3600=>"Once per hour", 1800=>"Once per half hour", 900=>"Once per 15 minutes", 600=>"Once per 10 minutes", 300=>"Once per 5 minutes", 60=>"Once per minute", 1=>"Constantly" ), $PHORUM["track_user_activity"] ) );
$frm->addhelp($row, "Track User Usage", "When set, the last time a user accessed the Phorum will be recorded as often as you have decided upon.  This will require constant updates to your database.  If you have a busy forum on weak equipment, it may be bad to use a short update interval." );

$frm->addbreak( "Tighter Security" );

$row=$frm->addrow( "Enable Tighter Security", $frm->select_tag( "tight_security", array( "No", "Yes" ), $PHORUM["tight_security"] ) );
$frm->addhelp($row, "Enable Tighter Security", "Tight security in Phorum will require that users confirm their login information from time to time before posting messages, accessing private messages or using their Control Center.  The length of time is determined by Short Session Timeout." );

$row=$frm->addrow( "Short Session Timeout (minutes)", $frm->text_box( "short_session_timeout", $PHORUM["short_session_timeout"], 10 ) );
$frm->addhelp($row, "Short Session Timeout", "When tight security is enabled, the users will be issued a second cookie when the type in their login information.  If the user does not use the site for the period of time you set here, they will have to re-enter their login information before posting messages, accessing private messages or using their Control Center.  They will still be allowed to read the Phorum as long as their Main Session is still good.  The time is minutes.  The minimum is 5 minutes.  Otherwise, your users will be very angry at you.<br /><br />P.S. 1 day = 1440 minutes" );

$frm->addbreak( "User Settings" );

$row=$frm->addrow( "Allow Time Zone Selection", $frm->select_tag( "user_time_zone", array( "No", "Yes" ), $PHORUM["user_time_zone"] ) );

$row=$frm->addrow( "Allow Template Selection", $frm->select_tag( "user_template", array( "No", "Yes" ), $PHORUM["user_template"] ) );
$frm->addhelp($row, "Allow Template Selection",
    "If enabled, the user will find an option in his control center to
     select the template to use.<br />
     <br />
     Note: The user selected template will only be used for forums,
     which do not have \"Fixed Display-Settings\" enabled in the
     forum settings.");

$reg_con_arr = array(

    PHORUM_REGISTER_INSTANT_ACCESS => "None needed",

    PHORUM_REGISTER_VERIFY_EMAIL => "Verify via email",

    PHORUM_REGISTER_VERIFY_MODERATOR => "Verified by a moderator",

    PHORUM_REGISTER_VERIFY_BOTH => "Verified by a moderator and via email"

    );

$row=$frm->addrow( "Registration Verification", $frm->select_tag( "registration_control", $reg_con_arr, $PHORUM["registration_control"] ) );

$row=$frm->addrow( "Enable Drop-down User List", $frm->select_tag( "enable_dropdown_userlist", array( "No", "Yes" ), $PHORUM["enable_dropdown_userlist"] ) );

$frm->addhelp($row, "Enable Drop-down User List", "By setting this to Yes, Phorum will display a drop-down list of users instead of an empty text box on pages where you can select a user. Two examples of such pages are when sending a private message, and when adding users to a group in the group moderation page. This option should be disabled if you have a large number of users, as a list of thousands of users will slow performance dramatically." );

$row = $frm->addrow( "What to use as the display name", $frm->select_tag("display_name_source", array("username" => "User's username", "real_name" => "User's real name"), isset($PHORUM["display_name_source"]) ? $PHORUM["display_name_source"] : "username") );
$frm->addhelp($row, "What to use as the display name", "You can choose to use either the user's username or the real name (which can be edited by the user from the control center) as the name by which the user is referenced throughout all Phorum pages.<br/><br/>This is not an option that you normally would want to change on a live system that has been running for a while. One reason is that all stored names will have to be updated in the database (e.g. the posting authors), which can take quite a while on a big forum (it <i>will</i> work though). More impor tant is that you might confuse your users by changing the display names.");

$row=$frm->addrow( "Force hiding of email addresses", $frm->select_tag( "hide_email_addr", array( "No", "Yes" ), $PHORUM["hide_email_addr"] ) );
$frm->addhelp($row, "Force hiding of email addresses", "If set to \"No\", then registered users can choose themselves whether they want their email addresses displayed to other users or not. If set to \"Yes\", then all email addresses will be hidden, including those of anonymous users. Also, the option \"Allow other users to see my email address\" will be removed from the user control center.");

$upload_arr = array(

    PHORUM_UPLOADS_SELECT => "Off",

    PHORUM_UPLOADS_REG => "On",

    );

$row=$frm->addrow( "File Uploads:", $frm->select_tag( "file_uploads", $upload_arr, $PHORUM["file_uploads"] ) );
$frm->addhelp($row, "File Uploads", "These settings apply to personal file uploads that user can do in their control center. The users can link to these files by copying and pasting the file URLs. Some modules (like the avatar module) also make use of personal files (for example for storing the images that the user can use as the avatar to show).<br/><br/>These settings do <i>not</i> control uploading of forum message attachments. For changing message attachment settings, you have to edit the settings of the forum for which you want to enable attachments.");

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;File Types (eg. gif;jpg)", $frm->text_box( "file_types", $PHORUM["file_types"], 30 ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Max File Size (KB)", $frm->text_box( "max_file_size", $PHORUM["max_file_size"], 30 ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;File Space Quota (KB)", $frm->text_box( "file_space_quota", $PHORUM["file_space_quota"], 30 ) );

$row=$frm->addrow( "Private Messaging (PM):", $frm->select_tag( "enable_pm", array( "Off", "On" ), $PHORUM["enable_pm"] ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Check For New PM", $frm->select_tag( "enable_new_pm_count", array( "No", "Yes" ), $PHORUM["enable_new_pm_count"] ) );
$frm->addhelp($row, "Check For Private Messages", "By setting this to Yes, Phorum will check if a user has new private messages, and display an indicator. On a Phorum with a lot of users and private messages, this may hurt performance. This option has no effect if Private Messaging is disabled." );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Max number of stored messages", $frm->text_box( "max_pm_messagecount", $PHORUM["max_pm_messagecount"], 30 ) );
$frm->addhelp($row, "Max number of stored messages", "This is the maximum number of private messages that a user may store on the server. The number of private messages is the total of all messages in all PM folders together. Setting this value to zero will allow for unlimited messages.");

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Allow notification of new PM by email", $frm->select_tag("allow_pm_email_notify", array("No", "Yes"), $PHORUM["allow_pm_email_notify"]) );
$frm->addhelp($row, "Allow notification of new PM by email",
    "If this option is enabled, Phorum will send a notification email to
     users that receive a new private message. The user will find an option
     in their control center which can be used to disable the notification
     email.");

$frm->addbreak( "System Email Settings" );

$row=$frm->addrow( "System Emails From Name", $frm->text_box( "system_email_from_name", $PHORUM["system_email_from_name"], 30 ) );

$row=$frm->addrow( "System Emails From Address", $frm->text_box( "system_email_from_address", $PHORUM["system_email_from_address"], 30 ) );

$row=$frm->addrow( "Use BCC in sending mails:", $frm->select_tag( "use_bcc", array( "No", "Yes" ), $PHORUM["use_bcc"] ) );

$row=$frm->addrow( "Ignore Admin for moderator-emails:", $frm->select_tag( "email_ignore_admin", array( "No", "Yes" ), $PHORUM["email_ignore_admin"] ) );
$frm->addhelp($row, "Ignore Admin for moderator-emails", "If you select yes for this option, then the moderator-notifications and report-message emails will not be sent to the admininistrator, only to moderators" );

/*
 * [hook]
 *     admin_general
 *
 * [description]
 *     This hook can be used for adding items to the form on the
 *     "General Settings" page of the admin interface.
 *
 * [category]
 *     Admin interface
 *
 * [when]
 *     Right before the <literal>PhorumInputForm</literal> object is shown.
 *
 * [input]
 *     The <literal>PhorumInputForm</literal> object.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_admin_general ($frm) 
 *     {
 *         // Add a section for the foo settings
 *         $frm->addbreak( "Foo Module Settings" );
 *
 *         // Add the option to cache the bar
 *         $row=$frm->addrow( "Enable Bar Caching:", $frm->select_tag( "mod_foo[enable_bar_caching]", array( "No", "Yes" ), $PHORUM["mod_foo"]["enable_bar_caching"] ) );
 *         $frm->addhelp($row, "Enable Bar Caching", "If you select yes for this option, then the bar will be cached." );
 *
 *         // Return the modified PhorumInputForm
 *         return $frm;
 *
 *     }
 *     </hookcode>
 */
$frm=phorum_hook("admin_general", $frm);

$frm->show();

?>

