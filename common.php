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

/**
 * This script bootstraps the Phorum web environment. It will load the
 * Phorum API and handle tasks that are required for initializing and
 * handling the request.
 */

// Check that this file is not loaded directly.
if (basename(__FILE__) == basename($_SERVER["PHP_SELF"])) exit();

require_once dirname(__FILE__).'/include/api.php';

phorum_api_request_parse();

/*
 * [hook]
 *     common_pre
 *
 * [description]
 *     This hook can be used for overriding settings that were loaded and
 *     setup at the start of the <filename>common.php</filename> script.
 *     If you want to dynamically assign and tweak certain settings, then
 *     this is the designated hook to use for that.<sbr/>
 *     <sbr/>
 *     Because the hook was put after the request parsing phase, you can
 *     make use of the request data that is stored in the global variables
 *     <literal>$PHORUM['forum_id']</literal> and
 *     <literal>$PHORUM['ref_thread_id']</literal> and
 *     <literal>$PHORUM['ref_message_id']</literal> and
 *     <literal>$PHORUM['args']</literal>.
 *
 * [category]
 *     Request initialization
 *
 * [when]
 *     Right after loading the settings from the database and parsing the
 *     request, but before making descisions on user, language and template.
 *
 * [input]
 *     No input.
 *
 * [output]
 *     No output.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_common_pre()
 *     {
 *         global $PHORUM;
 *
 *         // If we are in the forum with id = 10, we set the administrator
 *         // email information to a different value than the one configured
 *         // in the general settings.
 *         if ($PHORUM["forum_id"] == 10)
 *         {
 *             $PHORUM["system_email_from_name"] = "John Doe";
 *             $PHORUM["system_email_from_address"] = "John.Doe@example.com";
 *         }
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["common_pre"])) {
    phorum_api_hook("common_pre", "");
}

// ----------------------------------------------------------------------
// Setup data for standard (not admin) pages
// ----------------------------------------------------------------------

if (!defined( "PHORUM_ADMIN" ))
{
    $PHORUM["DATA"]["TITLE"] =
        isset($PHORUM["title"]) ? $PHORUM["title"] : "";

    $PHORUM["DATA"]["DESCRIPTION"] =
        isset( $PHORUM["description"]) ? $PHORUM["description"] : "";

    $PHORUM["DATA"]["HTML_TITLE"] = !empty($PHORUM["html_title"])
        ? $PHORUM["html_title"] : $PHORUM["DATA"]["TITLE"];

    $PHORUM["DATA"]["HEAD_TAGS"] = isset($PHORUM["head_tags"])
        ? $PHORUM["head_tags"] : "";

    $PHORUM["DATA"]["FORUM_ID"] = $PHORUM["forum_id"];

    // If the Phorum is disabled, display a message.
    if (isset($PHORUM["status"]) &&
        $PHORUM["status"] == PHORUM_MASTER_STATUS_DISABLED) {

        if (!empty($PHORUM["disabled_url"])) {
            phorum_api_redirect($PHORUM['disabled_url']);
        } else {
            echo "This Phorum is currently administratively disabled. Please " .
                 "contact the web site owner at ".
                 htmlspecialchars($PHORUM['system_email_from_address'])." " .
                 "for more information.";
            exit();
        }
    }

    if(!empty($PHORUM["forum_id"])){

        // Load the settings for the currently active forum.
        $forum_settings = phorum_api_forums_get(
        $PHORUM["forum_id"],null,null,null,PHORUM_FLAG_INCLUDE_INACTIVE);

        if ($forum_settings === NULL)
        {
            /*
             * [hook]
             *     common_no_forum
             *
             * [description]
             *     This hook is called in case a forum_id is requested for
             *     an unknown or inaccessible forum. It can be used for
             *     doing things like logging the bad requests or fully
             *     overriding Phorum's default behavior for these cases
             *     (which is redirecting the user back to the index page).
             *
             * [category]
             *     Request initialization
             *
             * [when]
             *     In <filename>common.php</filename>, right after detecting
             *     that a requested forum does not exist or is inaccessible
             *     and right before redirecting the user back to the Phorum
             *     index page.
             *
             * [input]
             *     No input.
             *
             * [output]
             *     No output.
             *
             * [example]
             *     <hookcode>
             *     function phorum_mod_foo_common_no_forum()
             *     {
             *         // Return a 404 Not found error instead of redirecting
             *         // the user back to the index.
             *         header("HTTP/1.0 404 Not Found");
             *         print "<html><head>\n";
             *         print "  <title>404 - Not Found</title>\n";
             *         print "</head><body>";
             *         print "  <h1>404 - Forum Not Found</h1>";
             *         print "</body></html>";
             *         exit();
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM["hooks"]["common_no_forum"])) {
                phorum_api_hook("common_no_forum", "");
            }

            phorum_api_redirect(PHORUM_INDEX_URL);
        }

        $PHORUM = array_merge($PHORUM, $forum_settings);

    } elseif(isset($PHORUM["forum_id"]) && $PHORUM["forum_id"]==0){

        $PHORUM = array_merge( $PHORUM, $PHORUM["default_forum_options"] );

        // some hard settings are needed if we are looking at forum_id 0
        $PHORUM['vroot']         = 0;
        $PHORUM['parent_id']     = 0;
        $PHORUM['active']        = 1;
        $PHORUM['folder_flag']   = 1;
        $PHORUM['cache_version'] = 0;
    }

    // handling vroots
    if (!empty($PHORUM['vroot']))
    {
        $vroot_folders = $PHORUM['DB']->get_forums($PHORUM['vroot']);

        $PHORUM["title"] = $vroot_folders[$PHORUM['vroot']]['name'];
        $PHORUM["DATA"]["TITLE"] = $PHORUM["title"];
        $PHORUM["DATA"]["HTML_TITLE"] = $PHORUM["title"];

        if ($PHORUM['vroot'] == $PHORUM['forum_id']) {
            // Unset the forum-name if we are in the vroot-index.
            // Otherwise, the NAME and TITLE would be the same and still
            // shown twice.
            unset($PHORUM['name']);
        }
    }

    // Stick some stuff from the settings into the template DATA.
    $PHORUM["DATA"]["NAME"] = isset($PHORUM["name"]) ? $PHORUM["name"] : "";
    $PHORUM["DATA"]["HTML_DESCRIPTION"] = isset( $PHORUM["description"]) ? preg_replace("!\s+!", " ", $PHORUM["description"]) : "";
    // Clean up for getting the description without html in it, so we
    // can use it inside the HTML meta description element.
    $PHORUM["DATA"]["DESCRIPTION"] = str_replace(
        array('\'', '"'), array('', ''),
        strip_tags($PHORUM["DATA"]["HTML_DESCRIPTION"])
    );
    $PHORUM["DATA"]["ENABLE_PM"] = isset( $PHORUM["enable_pm"]) ? $PHORUM["enable_pm"] : '';
    if (!empty($PHORUM["DATA"]["HTML_TITLE"]) && !empty($PHORUM["DATA"]["NAME"])) {
        $PHORUM["DATA"]["HTML_TITLE"] .= PHORUM_SEPARATOR;
    }
    $PHORUM["DATA"]["HTML_TITLE"] .= $PHORUM["DATA"]["NAME"];

    // Try to restore a user session.
    if (phorum_api_user_session_restore(PHORUM_FORUM_SESSION))
    {
        // If the user has overridden thread settings, change them here.
        $modes = phorum_api_forums_get_display_modes($PHORUM);
        $PHORUM["threaded_list"] = $modes['list'];
        $PHORUM["threaded_read"] = $modes['read'];

        // Provide the number of new private messages for the user in the
        // "new_private_messages" field.
        if (!empty($PHORUM["enable_pm"])) {
            $PHORUM['user']['new_private_messages'] =
                $PHORUM['user']['pm_new_count'];
        }
    }

    /*
     * [hook]
     *     common_post_user
     *
     * [description]
     *     This hook gives modules a chance to override Phorum variables
     *     and settings, after the active user has been loaded. The settings
     *     for the active forum are also loaded before this hook is called,
     *     therefore this hook can be used for overriding general settings,
     *     forum settings and user settings.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     Right after loading the data for the active user in
     *     <filename>common.php</filename>, but before deciding on the
     *     language and template to use.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_common_post_user()
     *     {
     *         global $PHORUM;
     *
     *         // Switch the read mode for admin users to threaded.
     *         if ($PHORUM['user']['user_id'] && $PHORUM['user']['admin']) {
     *             $PHORUM['threaded_read'] = PHORUM_THREADED_ON;
     *         }
     *
     *         // Disable "float_to_top" for anonymous users.
     *         if (!$PHORUM['user']['user_id']) {
     *             $PHORUM['float_to_top'] = 0;
     *         }
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["common_post_user"])) {
         phorum_api_hook("common_post_user", "");
    }

    // Some code that only has to be run if the forum isn't set to fixed view.
    if (empty($PHORUM['display_fixed']))
    {
        // User template override.
        if (!empty($PHORUM['user']['user_template']) &&
            (!isset($PHORUM["user_template"]) ||
             !empty($PHORUM['user_template']))) {
            $PHORUM['template'] = $PHORUM['user']['user_template'];
        }

        // Check for a template that is passed on through request parameters.
        // Only use valid template names.
        $template = NULL;
        if (!empty($PHORUM["args"]["template"])) {
            $template = basename($PHORUM["args"]["template"]);
        } elseif (!empty($_POST['template'])) {
            $template = basename($_POST['template']);
        }
        if ($template !== NULL && $template != '..') {
            $PHORUM['template'] = $template;
            $PHORUM['DATA']['GET_VARS'][] = "template=".urlencode($template);
            $PHORUM['DATA']['POST_VARS'] .= "<input type=\"hidden\" name=\"template\" value=\"".htmlspecialchars($template)."\" />\n";
        }

        // User language override, when allowed by the general Phorum settings.
        if (!empty($PHORUM['user_language']) &&
            !empty($PHORUM['user']['user_language'])) {
            $PHORUM['language'] = $PHORUM['user']['user_language'];
        }
    }

    // If no language is set by now or if the language file for the
    // configured language does not exist, then fallback to the
    // language that is configured in the default forum settings.
    if (empty($PHORUM["language"]) ||
        !file_exists(PHORUM_PATH."/include/lang/$PHORUM[language].php")) {
        $PHORUM['language'] = $PHORUM['default_forum_options']['language'];

        // If the language file for the default forum settings language
        // cannot be found, then fallback to the hard-coded default.
        if (!file_exists(PHORUM_PATH."/include/lang/$PHORUM[language].php")) {
            $PHORUM['language'] = PHORUM_DEFAULT_LANGUAGE;
        }
    }

    // If the requested template does not exist, then fallback to the
    // template that is configured in the default forum settings.
    if (!file_exists(PHORUM_PATH."/templates/$PHORUM[template]/info.php")) {
        $PHORUM['template'] = $PHORUM['default_forum_options']['template'];

        // If the template directory for the default forum settings template
        // cannot be found, then fallback to the hard-coded default.
        if (!file_exists(PHORUM_PATH."/templates/$PHORUM[template]/info.php")) {
            $PHORUM['template'] = PHORUM_DEFAULT_TEMPLATE;
        }
    }

    // Use output buffering so we don't get header errors if there's
    // some additional output in the upcoming included files (e.g. UTF-8
    // byte order markers or whitespace outside the php tags).
    ob_start();

    // User output buffering so we don't get header errors.
    // Not loaded if we are running an external or scheduled script.
    if (!defined('PHORUM_SCRIPT'))
    {
        include phorum_api_template('settings');
        $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
        $PHORUM["DATA"]["URL"]["TEMPLATE"] = htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");
        $PHORUM["DATA"]["URL"]["CSS"] = phorum_api_url(PHORUM_CSS_URL, "css");
        $PHORUM["DATA"]["URL"]["CSS_PRINT"] = phorum_api_url(PHORUM_CSS_URL, "css_print");
        $PHORUM["DATA"]["URL"]["JAVASCRIPT"] = phorum_api_url(PHORUM_JAVASCRIPT_URL);
        $PHORUM["DATA"]["URL"]["AJAX"] = phorum_api_url(PHORUM_AJAX_URL);
    }

    // Language names that modules might be using to reference the same
    // language. Before Phorum 5.3, the language filename format was
    // not standardized, so some other formats might still be in use.
    // The included language file can fill this array with appropriate
    // language names when needed.
    $PHORUM['compat_languages'] = array();

    // Load the main language file.
    $PHORUM['language'] = basename($PHORUM['language']);
    if (file_exists(PHORUM_PATH."/include/lang/$PHORUM[language].php")) {
        require_once PHORUM_PATH."/include/lang/$PHORUM[language].php";
    } else trigger_error(
        "Language file include/lang/$PHORUM[language].php not found",
        E_USER_ERROR
    );

    // Add the active language and the default language to compat_languages,
    // so we can simply use the language array to scan for language files.
    $PHORUM['compat_languages'][$PHORUM['language']] = $PHORUM['language'];
    $PHORUM['compat_languages'] = array_reverse($PHORUM['compat_languages']);
    if (!isset($PHORUM['compat_languages'][PHORUM_DEFAULT_LANGUAGE])) {
        $PHORUM['compat_languages'][PHORUM_DEFAULT_LANGUAGE] =
            PHORUM_DEFAULT_LANGUAGE;
    }
    foreach (explode(',', PHORUM_DEFAULT_LANGUAGE_COMPAT) as $fallback) {
        if (!isset($PHORUM['compat_languages'][$fallback])) {
            $PHORUM['compat_languages'][$fallback] = $fallback;
        }
    }

    // Load language file(s) for localized modules.
    if (!empty($PHORUM['hooks']['lang']['mods'])) {
        foreach($PHORUM['hooks']['lang']['mods'] as $mod) {
            $mod = basename($mod);
            $loaded = FALSE;
            foreach ($PHORUM['compat_languages'] as $language) {
                $language_file = PHORUM_PATH."/mods/$mod/lang/$language.php";
                if (file_exists($language_file)) {
                    require_once $language_file;
                    $loaded = TRUE;
                    break;
                }
            }
            if (!$loaded) trigger_error(
                "No language file found for module $mod", E_USER_ERROR
            );
        }
    }

    // Clean up the output buffer.
    ob_end_clean();

    // Load the locale from the language file into the template vars.
    $PHORUM["DATA"]["LOCALE"] = isset($PHORUM['locale']) ? $PHORUM['locale'] : "";

    // If there is no HCHARSET (used by the htmlspecialchars() calls), then
    // use the CHARSET for it instead. The HCHARSET is implemented to work
    // around the limitation of PHP that it does not support all charsets
    // for the htmlspecialchars() call. For example iso-8859-9 (Turkish)
    // is not supported, in which case the combination CHARSET=iso-8859-9
    // with HCHARSET=iso-8859-1 can be used to prevent PHP warnings.
    if (empty($PHORUM["DATA"]["HCHARSET"])) {
        $PHORUM["DATA"]["HCHARSET"] = $PHORUM["DATA"]["CHARSET"];
    }

    // Set the internal encoding for mbstring functions.
    mb_internal_encoding($PHORUM['DATA']['CHARSET']);

    // HTML titles can't contain HTML code, so we strip HTML tags
    // and HTML escape the title.
    $PHORUM["DATA"]["HTML_TITLE"] = htmlspecialchars(strip_tags($PHORUM["DATA"]["HTML_TITLE"]), ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

    // For non-admin users, check if the forum is set to
    // read-only or administrator-only mode.
    if (empty($PHORUM["user"]["admin"]) && isset($PHORUM['status']))
    {
        if ($PHORUM["status"] == PHORUM_MASTER_STATUS_ADMIN_ONLY &&
            phorum_page != 'css' &&
            phorum_page != 'javascript' &&
            phorum_page != 'login') {

            phorum_build_common_urls();
            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["AdminOnlyMessage"];
            phorum_api_user_set_active_user(PHORUM_FORUM_SESSION, NULL);

            /**
             * @todo Not compatible with portable / embedded Phorum setups.
             */
            phorum_api_output('message');
            exit();

        } elseif ($PHORUM['status'] == PHORUM_MASTER_STATUS_READ_ONLY) {
            $PHORUM['DATA']['GLOBAL_ERROR'] = $PHORUM['DATA']['LANG']['ReadOnlyMessage'];
            phorum_api_user_set_active_user(PHORUM_FORUM_SESSION, NULL);
        }
    }

    // If moderator notifications are on and the person is a mod,
    // lets find out if anything needs attention.

    $PHORUM["user"]["NOTICE"]["MESSAGES"] = FALSE;
    $PHORUM["user"]["NOTICE"]["USERS"] = FALSE;
    $PHORUM["user"]["NOTICE"]["GROUPS"] = FALSE;

    if ($PHORUM["DATA"]["LOGGEDIN"])
    {
        // By default, only bug the user on the list, index and cc pages.
        // The template can override this behaviour by setting a comma
        // separated list of phorum_page names in a template define statement
        // like this: {DEFINE show_notify_for_pages "page 1,page 2,..,page n"}
        if (isset($PHORUM["TMP"]["show_notify_for_pages"])) {
            $show_notify_for_pages = explode(",", $PHORUM["TMP"]["show_notify_for_pages"]);
        } else {
            $show_notify_for_pages = array('index','list','cc');
        }

        // Check for moderator notifications that have to be shown.
        if (in_array(phorum_page, $show_notify_for_pages) &&
            !empty($PHORUM['enable_moderator_notifications'])) {

            $forummodlist = phorum_api_user_check_access(
                PHORUM_USER_ALLOW_MODERATE_MESSAGES, PHORUM_ACCESS_LIST
            );
            if (count($forummodlist) > 0 ) {
                $PHORUM["user"]["NOTICE"]["MESSAGES"] = ($PHORUM['DB']->get_unapproved_list($forummodlist, TRUE, 0, TRUE) > 0);
                $PHORUM["DATA"]["URL"]["NOTICE"]["MESSAGES"] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
            }
            if (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS)) {
                $PHORUM["user"]["NOTICE"]["USERS"] = (count($PHORUM['DB']->user_get_unapproved()) > 0);
                $PHORUM["DATA"]["URL"]["NOTICE"]["USERS"] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
            }
            $groups = phorum_api_user_check_group_access(PHORUM_USER_GROUP_MODERATOR, PHORUM_ACCESS_LIST);
            if (count($groups) > 0) {
                $PHORUM["user"]["NOTICE"]["GROUPS"] = count($PHORUM['DB']->get_group_members(array_keys($groups), PHORUM_USER_GROUP_UNAPPROVED));
                $PHORUM["DATA"]["URL"]["NOTICE"]["GROUPS"] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION);
            }
        }

        // A quick template variable for deciding whether or not to show
        // moderator notification.
        $PHORUM["user"]["NOTICE"]["SHOW"] =
            $PHORUM["user"]["NOTICE"]["MESSAGES"] ||
            $PHORUM["user"]["NOTICE"]["USERS"] ||
            $PHORUM["user"]["NOTICE"]["GROUPS"];
    }

    /*
     * [hook]
     *     common
     *
     * [description]
     *     This hook gives modules a chance to override Phorum variables
     *     and settings near the end of the <filename>common.php</filename>
     *     script. This can be used to override the Phorum (settings)
     *     variables that are setup during this script.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     At the end of <filename>common.php</filename>.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_common()
     *     {
     *         global $PHORUM;
     *
     *         // Override the admin email address.
     *         $PHORUM["system_email_from_name"] = "John Doe";
     *         $PHORUM["system_email_from_address"] = "John.Doe@example.com";
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["common"])) {
        phorum_api_hook("common", "");
    }

    /*
     * [hook]
     *     page_<phorum_page>
     *
     * [availability]
     *     Phorum 5 >= 5.2.7
     *
     * [description]
     *     This hook gives modules a chance to run hook code for a specific
     *     Phorum page near the end of the the <filename>common.php</filename>
     *     script.<sbr/>
     *     <sbr/>
     *     It gives modules a chance to override Phorum variables
     *     and settings near the end of the <filename>common.php</filename>
     *     script. This can be used to override the Phorum (settings)
     *     variables that are setup during this script.
     *     <sbr/>
     *     The <literal>phorum_page</literal> definition that is set
     *     for each script is used to construct the name of the hook that will
     *     be called. For example the <filename>index.php</filename> script
     *     uses phorum_page <literal>index</literal>, which means that the
     *     called hook will be <literal>page_index</literal>.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     At the end of <filename>common.php</filename>, right after the
     *     <hook>common</hook> hook is called.<sbr/>
     *     <sbr/>
     *     You can look at this as if the hook is called at the start of the
     *     called script, since including <filename>common.php</filename>
     *     is about the first thing that a Phorum script does.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_page_list()
     *     {
     *         global $PHORUM;
     *
     *         // Set the type of list page to use, based on a cookie.
     *         if (empty($_COOKIE['list_style'])) {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_DEFAULT;
     *         } elseif ($_COOKIE['list_style'] == 'threaded') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_ON;
     *         } elseif ($_COOKIE['list_style'] == 'flat') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_OFF;
     *         } elseif ($_COOKIE['list_style'] == 'hybrid') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_HYBRID;
     *         }
     *     }
     *     </hookcode>
     */
    $page_hook = 'page_'.phorum_page;
    if (isset($PHORUM["hooks"][$page_hook])) {
        phorum_api_hook($page_hook, "");
    }

    $formatted = phorum_api_format_users(array($PHORUM['user']));
    $PHORUM['DATA']['USER'] = $formatted[0];
    $PHORUM['DATA']['PHORUM_PAGE'] = phorum_page;
    $PHORUM['DATA']['USERTRACK'] = $PHORUM['track_user_activity'];
    $PHORUM['DATA']['VROOT'] = $PHORUM['vroot'];
    $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"forum_id\" value=\"{$PHORUM["forum_id"]}\" />\n";
    if (!empty($PHORUM['ref_thread_id'])) {
        $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"ref_thread_id\" value=\"{$PHORUM["ref_thread_id"]}\" />\n";
    }
    if (!empty($PHORUM['ref_message_id'])) {
        $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"ref_message_id\" value=\"{$PHORUM["ref_message_id"]}\" />\n";
    }

    if (!empty($PHORUM['use_rss'])) {
        if($PHORUM["default_feed"] == "rss"){
            $PHORUM["DATA"]["FEED"] = $PHORUM["DATA"]["LANG"]["RSS"];
            $PHORUM["DATA"]["FEED_CONTENT_TYPE"] = "application/rss+xml";
        } else {
            $PHORUM["DATA"]["FEED"] = $PHORUM["DATA"]["LANG"]["ATOM"];
            $PHORUM["DATA"]["FEED_CONTENT_TYPE"] = "application/atom+xml";
        }
    }

    $PHORUM['DATA']['BREADCRUMBS'] = array();

    // Add the current forum path to the breadcrumbs.
    $index_page_url_template = phorum_api_url(PHORUM_INDEX_URL, '%forum_id%');
    if (!empty($PHORUM['forum_path']) && !is_array($PHORUM['forum_path'])) {
        $PHORUM['forum_path'] = unserialize($PHORUM['forum_path']);
    }
    if (empty($PHORUM['forum_path']))
    {
        $id = $PHORUM['forum_id'];
        $url = empty($id)
             ? phorum_api_url(PHORUM_INDEX_URL)
             : str_replace('%forum_id%',$id,$index_page_url_template);

        $PHORUM['DATA']['BREADCRUMBS'][] = array(
            'URL'  => $url,
            'TEXT' => $PHORUM['DATA']['LANG']['Home'],
            'ID'   => $id,
            'TYPE' => 'root'
        );
    }
    else
    {
        $track = NULL;
        foreach ($PHORUM['forum_path'] as $id => $name)
        {
            if ($track === NULL) {
                $name = $PHORUM['DATA']['LANG']['Home'];
                $type = 'root';
                $first = FALSE;
            } else {
                $type = 'folder';
            }

            if(empty($id)) {
                $url = phorum_api_url(PHORUM_INDEX_URL);
            } else {
                $url = str_replace('%forum_id%',$id,$index_page_url_template);
            }

            // Note: $id key is not required in general. Only used for
            // fixing up the last entry's TYPE.
            $PHORUM['DATA']['BREADCRUMBS'][$id]=array(
                'URL'  => $url,
                'TEXT' => strip_tags($name),
                'ID'   => $id,
                'TYPE' => $type
            );
            $track = $id;
        }

        if (!$PHORUM['folder_flag']) {
            $PHORUM['DATA']['BREADCRUMBS'][$track]['TYPE'] = 'forum';
            $PHORUM['DATA']['BREADCRUMBS'][$track]['URL'] = phorum_api_url(PHORUM_LIST_URL, $track);
        }

        if (!empty($PHORUM['ref_thread_id'])) {
            $PHORUM['DATA']['BREADCRUMBS'][] = array(
                'URL'  => phorum_api_url(
                    PHORUM_READ_URL,
                    $PHORUM['ref_thread_id'],
                    $PHORUM['ref_message_id']
                ),
                'TEXT' => $PHORUM['DATA']['LANG']['Thread'],
                'ID'   => $PHORUM['ref_message_id'],
                'TYPE' => 'message'
            );

        }
    }
}

// ----------------------------------------------------------------------
// Setup data for admin pages
// ----------------------------------------------------------------------

else {

    // The admin interface is not localized, but we might need language
    // strings at some point after all, for example if we reset the
    // author name in messages for deleted users to "anonymous".
    $PHORUM["language"] = basename($PHORUM['default_forum_options']['language']);
    if (file_exists(PHORUM_PATH."/include/lang/$PHORUM[language].php")) {
        require_once PHORUM_PATH."/include/lang/$PHORUM[language].php";
    }
}

// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

/**
 * Check if the user has read permission for a forum page.
 *
 * If the user does not have read permission for the currently active
 * forum, then an error message is shown. What message to show depends
 * on the exact case. Possible cases are:
 *
 * - The user is logged in: final missing read permission message;
 * - The user is not logged in, but wouldn't be allowed to read the
 *   forum, even if he were logged in: final missing read permission message;
 * - The user is not logged in, but could be allowed to read the
 *   forum if he were logged in: please login message.
 *
 * @return boolean
 *     TRUE in case the user is allowed to read the forum,
 *     FALSE otherwise.
 */
function phorum_check_read_common()
{
    global $PHORUM;

    $retval = TRUE;

    if ($PHORUM["forum_id"] > 0 &&
        !$PHORUM["folder_flag"] &&
        !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ)) {

        if ( $PHORUM["DATA"]["LOGGEDIN"] ) {
            // if they are logged in and not allowed, they don't have rights
            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoRead"];
        } else {
            // Check if they could read if logged in.
            // If so, let them know to log in.
            if (empty($PHORUM["DATA"]["POST"]["parentid"]) &&
                $PHORUM["reg_perms"] & PHORUM_USER_ALLOW_READ) {
                $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["PleaseLoginRead"];
            } else {
                $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoRead"];
            }
        }

        phorum_build_common_urls();

        phorum_api_output("message");

        $retval = FALSE;
    }

    return $retval;
}

/**
 * Generate the URLs that are used on most pages.
 */
function phorum_build_common_urls()
{
    global $PHORUM;

    $GLOBALS["PHORUM"]["DATA"]["URL"]["BASE"] = phorum_api_url(PHORUM_BASE_URL);
    $GLOBALS["PHORUM"]["DATA"]["URL"]["HTTP_PATH"] = $PHORUM['http_path'];

    $GLOBALS["PHORUM"]["DATA"]["URL"]["LIST"] = phorum_api_url(PHORUM_LIST_URL);

    // These links are only needed in forums, not in folders.
    if (isset($PHORUM['folder_flag']) && !$PHORUM['folder_flag']) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["POST"] = phorum_api_url(PHORUM_POSTING_URL);
        $GLOBALS["PHORUM"]["DATA"]["URL"]["SUBSCRIBE"] = phorum_api_url(PHORUM_SUBSCRIBE_URL);
    }

    $GLOBALS["PHORUM"]["DATA"]["URL"]["SEARCH"] = phorum_api_url(PHORUM_SEARCH_URL);

    // Find the id for the index.
    $index_id=-1;

    // A folder where we usually don't show the index-link but on
    // additional pages like search and login it is shown.
    if ($PHORUM['folder_flag'] && phorum_page != 'index' &&
        ($PHORUM['forum_id'] == 0 || $PHORUM['vroot'] == $PHORUM['forum_id'])) {

        $index_id = $PHORUM['forum_id'];

    // Either a folder where the link should be shown (not vroot or root)
    // or an active forum where the link should be shown.
    } elseif (($PHORUM['folder_flag'] &&
              ($PHORUM['forum_id'] != 0 && $PHORUM['vroot'] != $PHORUM['forum_id'])) ||
              (!$PHORUM['folder_flag'] && $PHORUM['active'])) {

        // Go to root or vroot.
        if (isset($PHORUM["index_style"]) && $PHORUM["index_style"] == PHORUM_INDEX_FLAT) {
            // vroot is either 0 (root) or another id
            $index_id = $PHORUM["vroot"];
        // Go to the parent folder.
        } else {
            $index_id=$PHORUM["parent_id"];
        }
    }

    if ($index_id > -1) {
        // check if its the full root, avoid adding an id in this case (SE-optimized ;))
        if (!empty($index_id))
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = phorum_api_url(PHORUM_INDEX_URL, $index_id);
        else
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = phorum_api_url(PHORUM_INDEX_URL);
    }

    // these urls depend on the login-status of a user
    if ($GLOBALS["PHORUM"]["DATA"]["LOGGEDIN"]) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = phorum_api_url( PHORUM_LOGIN_URL, "logout=1" );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = phorum_api_url( PHORUM_CONTROLCENTER_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["VIEWPROFILE"] = phorum_api_url(PHORUM_PROFILE_URL, $PHORUM['user']['user_id']);
        $GLOBALS["PHORUM"]["DATA"]["URL"]["PM"] = phorum_api_url( PHORUM_PM_URL );
    } else {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = phorum_api_url( PHORUM_LOGIN_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = phorum_api_url( PHORUM_REGISTER_URL );
    }
}

?>
