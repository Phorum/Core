<?php

if ( !defined( "PHORUM_ADMIN" ) ) return;

$error = "";

if ( count( $_POST ) ) {
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
                } elseif ( !preg_match( "/^(http|https):\/\/(([a-z0-9][a-z0-9_-]*)(\.[a-z0-9][a-z0-9_-]*)+)(:(\d+))?/i", $value ) && !preg_match( "/^(http|https):\/\/[a-z0-9][a-z0-9_-]*(:\d+)?\//i", $value ) ) {
                    $error = "The provided HTTP Path is not a valid URL.";
                } 

                break;

            case "cache":

                if ( empty( $value ) ) {
                    $_POST[$field] = "/tmp";
                } elseif ( !file_exists( $value ) ) {
                    $error = "This cache directory does not exist.  Please create it with the proper permissions.";
                } 

                break;

            case "session_timeout":

                $_POST[$field] = (int)$_POST[$field];

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
        } 

        if ( $error ) break;
    } 

    if ( empty( $error ) ) {
        unset( $_POST["module"] );

        if ( phorum_db_update_settings( $_POST ) ) {
            phorum_redirect_by_url($_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Database error while updating settings.";
        } 
    } 
} 

if ( $error ) {
    phorum_admin_error( $error );
} 
// create the time zone drop down array
for( $x = -23;$x <= 23;$x++ ) {
    $tz_range[$x] = $x;
} 

include_once "./include/admin/PhorumInputForm.php";

$frm = &new PhorumInputForm ( "", "post" );

$frm->addbreak( "Phorum General Settings" );

$frm->hidden( "module", "settings" );

$row=$frm->addrow( "Phorum Title", $frm->text_box( "title", $PHORUM["title"], 50 ) );

$row=$frm->addrow( "DNS Lookups", $frm->select_tag( "dns_lookup", array( "No", "Yes" ), $PHORUM["dns_lookup"] ) );

$row=$frm->addrow( "Use Cookies", $frm->select_tag( "use_cookies", array( "No", "Yes" ), $PHORUM["use_cookies"] ) );

$row=$frm->addrow( "Hide Forums", $frm->select_tag( "hide_forums", array( "No", "Yes" ), $PHORUM["hide_forums"] ) );
$frm->addhelp($row, "Hide Forums", "By setting this to Yes, forums that users are not allowed to read will be hidden from them in the forums list." );

$row=$frm->addrow( "Show New Count in Forum List", $frm->select_tag( "show_new_on_index", array( "No", "Yes" ), $PHORUM["show_new_on_index"] ) );

$row=$frm->addrow( "Enable Private Messaging", $frm->select_tag( "enable_pm", array( "No", "Yes" ), $PHORUM["enable_pm"] ) );

$row=$frm->addrow( "Count New Private Messages", $frm->select_tag( "enable_new_pm_count", array( "No", "Yes" ), $PHORUM["enable_new_pm_count"] ) );
$frm->addhelp($row, "Count New Private Messages", "By setting this to Yes, Phorum will check if a user has new private messages, and display an indicator. On a Phorum with a lot of users and private messages, this may hurt performance. This option has no effect if Private Messaging is disabled." );

$row=$frm->addrow( "Enable Drop-down User List", $frm->select_tag( "enable_dropdown_userlist", array( "No", "Yes" ), $PHORUM["enable_dropdown_userlist"] ) );
$frm->addhelp($row, "Enable Drop-down User List", "By setting this to Yes, Phorum will display a drop-down list of users instead of an empty text box on pages where you can select a user. Two examples of such pages are when sending a private message, and when adding users to a group in the group moderation page. This option should be disabled if you have a large number of users, as a list of thousands of users will slow performance dramatically." );

$row=$frm->addrow( "Enable Moderator Notifications", $frm->select_tag( "enable_moderator_notifications", array( "No", "Yes" ), $PHORUM["enable_moderator_notifications"] ) );
$frm->addhelp($row, "Enable Moderator Notifications", "By setting this to Yes, Phorum will display notice to the various kinds of moderators when they have a new item that requires their attention. For example, message moderators will see a notice whenever there is an unapproved message." );

$row=$frm->addrow( "User Post Edit Time Limit (minutes)", $frm->text_box( "user_edit_timelimit", $PHORUM["user_edit_timelimit"], 10) );
$frm->addhelp($row, "User Post Edit Time Limit (minutes)", "If set to a value larger then 0, this acts as a time limit for post editing. Users will only be able to edit their own posts within this time limit. This only applies if a user has the necessary permissions to edit their post, and does not affect moderators." );

$row=$frm->addrow( "After posting goto", $frm->select_tag( "redirect_after_post", array( "list"=>"Message List Page", "read"=>"Message Read Page" ), $PHORUM["redirect_after_post"] ) );

$frm->addbreak( "HTML Settings" );

$row=$frm->addrow( "Phorum HTML Title", $frm->text_box( "html_title", $PHORUM["html_title"], 50 ) );

$row=$frm->addrow( "Phorum Head Tags", $frm->textarea( "head_tags", $PHORUM["head_tags"], 30, 5, "style='width: 100%'" ) );

$frm->addbreak( "File/Path Settings" );

$row=$frm->addrow( "HTTP Path", $frm->text_box( "http_path", $PHORUM["http_path"], 30 ) );
$frm->addhelp($row, "HTTP Path", "This is the base url of your Phorum." );

$row=$frm->addrow( "Cache Directory", $frm->text_box( "cache", $PHORUM["cache"], 30 ) );
$frm->addhelp($row, "Cache Directory", "Phorum caches its templates for faster use later.  This setting is the directory where Phorum stores that cache.  Most users will be fine using their servers temp directory.  If your server uses PHP Safe Mode, you will need to create a directory under your Phorum directory and make it writable by the web server." );

$frm->addbreak( "Date Options" );

$row=$frm->addrow( "Time Zone Offset", $frm->select_tag( "tz_offset", $tz_range, $PHORUM["tz_offset"] ) );
$frm->addhelp($row, "Time Zone Offset", "If you and/or your users are in a different time zone than the server, you can have the default displayed time adjusted by using this option." );

$frm->addbreak( "Cookie/Session Settings" );

$row=$frm->addrow( "Session Timeout (days)", $frm->text_box( "session_timeout", $PHORUM["session_timeout"], 10 ) );
$frm->addhelp($row, "Session Timeout", "When users log in to your Phorum, they are issued a cookie.  You can set this timeout to the number of days that you want the cookie to stay on the users computer.  If you set it to 0, the cookie will only last as long as the user has the browser open." );

$row=$frm->addrow( "Session Path (start with /)", $frm->text_box( "session_path", $PHORUM["session_path"], 30 ) );
$frm->addhelp($row, "Session Path", "When cookies are sent to client's browser, part of the cookie determines the path (url) for which the cookies are valid.  For example, if the url is http://example.com/phorum, you could set the path to /phorum.  Then, the users browser would only send the cookie information when the user accessed the Phorum.  You could also use simply / and the cookie info will be sent for any page on your site.  This could be useful if you want to use Phorum's login system for other parts of your site." );

$row=$frm->addrow( "Session Domain", $frm->text_box( "session_domain", $PHORUM["session_domain"], 30 ) );
$frm->addhelp($row, "Session Domain", "Most likely, you can leave this blank.  If you know you need to use a different domain (like you use forums.example.com, you may want to just use example.com as the domain), you may enter it here." );

$row=$frm->addrow( "Track User Usage", $frm->select_tag( "track_user_activity", array( 0=>"Never", 86400=>"Once per day", 3600=>"Once per hour", 600=>"Once per 5 minutes", 1=>"Constantly" ), $PHORUM["track_user_activity"] ) );
$frm->addhelp($row, "Track User Usage", "When set the last time a user accessed the Phorum will be recorded as often as you have decided upon.  This will require constant updates to your database.  If you have a busy forum on weak equipment, this may be bad thing to set to low." );

$frm->addbreak( "User Settings" );

$row=$frm->addrow( "Allow Time Zone Selection", $frm->select_tag( "user_time_zone", array( "No", "Yes" ), $PHORUM["user_time_zone"] ) );

$row=$frm->addrow( "Allow Template Selection", $frm->select_tag( "user_template", array( "No", "Yes" ), $PHORUM["user_template"] ) );

$reg_con_arr = array( 

    PHORUM_REGISTER_INSTANT_ACCESS => "None needed",

    PHORUM_REGISTER_VERIFY_EMAIL => "Verify via email",

    PHORUM_REGISTER_VERIFY_MODERATOR => "Verified by a moderator",

    PHORUM_REGISTER_VERIFY_BOTH => "Verified by a moderator and via email" 

    );

$row=$frm->addrow( "Registration Verification", $frm->select_tag( "registration_control", $reg_con_arr, $PHORUM["registration_control"] ) );

$upload_arr = array( 

    PHORUM_UPLOADS_SELECT => "Off",

    PHORUM_UPLOADS_REG => "On", 

    );

$row=$frm->addrow( "File Uploads:", $frm->select_tag( "file_uploads", $upload_arr, $PHORUM["file_uploads"] ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;File Types (eg. gif;jpg)", $frm->text_box( "file_types", $PHORUM["file_types"], 30 ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Max File Size (KB)", $frm->text_box( "max_file_size", $PHORUM["max_file_size"], 30 ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;File Space Quota (KB)", $frm->text_box( "file_space_quota", $PHORUM["file_space_quota"], 30 ) );

$row=$frm->addrow( "&nbsp;&nbsp;&nbsp;Allow Off Site Links", $frm->select_tag( "file_offsite", array( "No", "Yes" ), $PHORUM["file_offsite"] ) );
$frm->addhelp($row, "&nbsp;&nbsp;&nbsp;Allow Off Site Links", "You may not want to allow other web sites to link to files that users upload to your forums.  If not, set this to No.  If you want to use links on other parts of your web site or only specific web sites, you will need to use your web server's security features to accomplish this.  For Apache users, you can reference <i>Prevent \"Image Theft\"</i> at http://httpd.apache.org/docs/env.html#examples." );

$frm->addbreak( "General Defaults" );

$row=$frm->addrow( "Default Template", $frm->select_tag( "default_template", phorum_get_template_info(), $PHORUM["default_template"] ) );

$row=$frm->addrow( "Default Language", $frm->select_tag( "default_language", phorum_get_language_info(), $PHORUM["default_language"] ) );

$frm->addbreak( "System Email Settings" );

$row=$frm->addrow( "System Emails From Name", $frm->text_box( "system_email_from_name", $PHORUM["system_email_from_name"], 30 ) );

$row=$frm->addrow( "System Emails From Address", $frm->text_box( "system_email_from_address", $PHORUM["system_email_from_address"], 30 ) );

$row=$frm->addrow( "Use BCC in sending mails:", $frm->select_tag( "use_bcc", array( "No", "Yes" ), $PHORUM["use_bcc"] ) );

// calling mods
$frm=phorum_hook("admin_general", $frm);

$frm->show();

?>

