<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
if ( basename( __FILE__ ) == basename( $_SERVER["PHP_SELF"] ) ) exit();

// Load and instantiate the Phorum API.
require_once dirname(__FILE__).'/include/api.php';
$phorum = Phorum::API();

$phorum->request->parse();

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
    $phorum->modules->hook("common_pre", "");
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

    // Do not try to restore a session for CSS and JavaScript.
    // We do not need user authentication for those.
    $skipsession = FALSE;
    if(phorum_page == 'css' || phorum_page == 'javascript') {
        $skipsession = TRUE;
    }

    // If the Phorum is disabled, display a message.
    if (isset($PHORUM["status"]) &&
        $PHORUM["status"] == PHORUM_MASTER_STATUS_DISABLED) {

        if (!empty($PHORUM["disabled_url"])) {
            $phorum->redirect($PHORUM['disabled_url']);
        } else {
            echo "This Phorum is currently administratively disabled. Please " .
                 "contact the web site owner at ".
                 htmlspecialchars($PHORUM['system_email_from_address'])." " .
                 "for more information.";
            exit();
        }
    }

    // Check for upgrade or new install.
    if (!isset( $PHORUM['internal_version']))
    {
        echo "<html><head><title>Phorum error</title></head><body>
              No Phorum settings were found. Either this is a brand new
              installation of Phorum or there is a problem with your
              database server. If this is a new install, please
              <a href=\"admin.php\">go to the admin page</a> to complete
              the installation. If not, check your database server.
              </body></html>";
        exit();
    } elseif ($PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
              !isset($PHORUM['internal_patchlevel']) ||
              $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL) {
        if(isset($PHORUM["DBCONFIG"]["upgrade_page"])){
            $phorum->redirect($PHORUM["DBCONFIG"]["upgrade_page"]);
        } else {
            echo "<html><head><title>Upgrade notification</title></head><body>
                  It looks like you have installed a new version of
                  Phorum.<br/>Please visit the admin page to complete
                  the upgrade!
                  </body></html>";
            exit();
        }
    }

    // Load the settings for the currently active forum.
    $forum_settings = $phorum->forums->get(
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
            $phorum->modules->hook("common_no_forum", "");
        }

        $phorum->redirect(PHORUM_INDEX_URL);
    }

    $PHORUM = array_merge($PHORUM, $forum_settings);

    // handling vroots
    if (!empty($PHORUM['vroot']))
    {
        $vroot_folders = $phorum->db->get_forums($PHORUM['vroot']);

        $PHORUM["title"] = $vroot_folders[$PHORUM['vroot']]['name'];
        $PHORUM["DATA"]["TITLE"] = $PHORUM["title"];
        $PHORUM["DATA"]["HTML_TITLE"] = $PHORUM["title"];

        if($PHORUM['vroot'] == $PHORUM['forum_id']) {
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
    if (!$skipsession && $phorum->user->session_restore(PHORUM_FORUM_SESSION))
    {
        // If the user has overridden thread settings, change it here.
        if (!isset($PHORUM['display_fixed']) || !$PHORUM['display_fixed'])
        {
            if ($PHORUM["user"]["threaded_list"] == PHORUM_THREADED_ON) {
                $PHORUM["threaded_list"] = TRUE;
            } elseif ($PHORUM["user"]["threaded_list"] == PHORUM_THREADED_OFF) {
                $PHORUM["threaded_list"] = FALSE;
            }
            if ($PHORUM["user"]["threaded_read"] == PHORUM_THREADED_ON) {
                $PHORUM["threaded_read"] = 1;
            } elseif ($PHORUM["user"]["threaded_read"] == PHORUM_THREADED_OFF) {
                $PHORUM["threaded_read"] = 0;
            } elseif ($PHORUM["user"]["threaded_read"] == PHORUM_THREADED_HYBRID) {
                $PHORUM["threaded_read"] = 2;
            }
        }

        // check if the user has new private messages
        if (!empty($PHORUM["enable_new_pm_count"]) &&
            !empty($PHORUM["enable_pm"])) {
            $PHORUM['user']['new_private_messages'] =
                $phorum->db->pm_checknew($PHORUM['user']['user_id']);
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
         $phorum->modules->hook("common_post_user", "");
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

        // User language override.
        if (!empty($PHORUM['user']['user_language'])) {
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
        require_once phorum_get_template('settings');
        $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
        $PHORUM["DATA"]["URL"]["TEMPLATE"] = htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");
        $PHORUM["DATA"]["URL"]["CSS"] = $phorum->url(PHORUM_CSS_URL, "css");
        $PHORUM["DATA"]["URL"]["CSS_PRINT"] = $phorum->url(PHORUM_CSS_URL, "css_print");
        $PHORUM["DATA"]["URL"]["JAVASCRIPT"] = $phorum->url(PHORUM_JAVASCRIPT_URL);
        $PHORUM["DATA"]["URL"]["AJAX"] = $phorum->url(PHORUM_AJAX_URL);
    }

    // Load the main language file.
    $PHORUM['language'] = basename($PHORUM['language']);
    if (file_exists(PHORUM_PATH."/include/lang/$PHORUM[language].php")) {
        require_once PHORUM_PATH."/include/lang/$PHORUM[language].php";
    }

    // Load language file(s) for localized modules.
    if (!empty($PHORUM['hooks']['lang']['mods'])) {
        foreach($PHORUM['hooks']['lang']['mods'] as $mod) {
            $mod = basename($mod);
            if (file_exists(PHORUM_PATH."/mods/$mod/lang/$PHORUM[language].php")) {
                require_once PHORUM_PATH."/mods/$mod/lang/$PHORUM[language].php";
            } elseif (file_exists(PHORUM_PATH."/mods/$mod/lang/".PHORUM_DEFAULT_LANGUAGE.".php")) {
                require_once PHORUM_PATH."/mods/$mod/lang/".PHORUM_DEFAULT_LANGUAGE.".php";
            }
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
            $phorum->user->set_active_user(PHORUM_FORUM_SESSION, NULL);

            /**
             * @todo Not compatible with portable / embedded Phorum setups.
             */
            $phorum->output('message');
            exit();

        } elseif ($PHORUM['status'] == PHORUM_MASTER_STATUS_READ_ONLY) {
            $PHORUM['DATA']['GLOBAL_ERROR'] = $PHORUM['DATA']['LANG']['ReadOnlyMessage'];
            $phorum->user->set_active_user(PHORUM_FORUM_SESSION, NULL);
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

            $forummodlist = $phorum->user->check_access(
                PHORUM_USER_ALLOW_MODERATE_MESSAGES, PHORUM_ACCESS_LIST
            );
            if (count($forummodlist) > 0 ) {
                $PHORUM["user"]["NOTICE"]["MESSAGES"] = ($phorum->db->get_unapproved_list($forummodlist, TRUE, 0, TRUE) > 0);
                $PHORUM["DATA"]["URL"]["NOTICE"]["MESSAGES"] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
            }
            if ($phorum->user->check_access(PHORUM_USER_ALLOW_MODERATE_USERS)) {
                $PHORUM["user"]["NOTICE"]["USERS"] = (count($phorum->db->user_get_unapproved()) > 0);
                $PHORUM["DATA"]["URL"]["NOTICE"]["USERS"] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
            }
            $groups = $phorum->user->check_group_access(PHORUM_USER_GROUP_MODERATOR, PHORUM_ACCESS_LIST);
            if (count($groups) > 0) {
                $PHORUM["user"]["NOTICE"]["GROUPS"] = count($phorum->db->get_group_members(array_keys($groups), PHORUM_USER_GROUP_UNAPPROVED));
                $PHORUM["DATA"]["URL"]["NOTICE"]["GROUPS"] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION);
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
        $phorum->modules->hook("common", "");
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
        $phorum->modules->hook($page_hook, "");
    }

    $formatted = $phorum->format->users(array($PHORUM['user']));
    $PHORUM['DATA']['USER'] = $formatted[0];
    $PHORUM['DATA']['PHORUM_PAGE'] = phorum_page;
    $PHORUM['DATA']['USERTRACK'] = $PHORUM['track_user_activity'];
    $PHORUM['DATA']['VROOT'] = $PHORUM['vroot'];
    $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"forum_id\" value=\"{$PHORUM["forum_id"]}\" />\n";

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
    $index_page_url_template = $phorum->url(PHORUM_INDEX_URL, '%forum_id%');
    if (!empty($PHORUM['forum_path']) && !is_array($PHORUM['forum_path'])) {
        $PHORUM['forum_path'] = unserialize($PHORUM['forum_path']);
    }
    if (empty($PHORUM['forum_path']))
    {
        $id = $PHORUM['forum_id'];
        $url = empty($id)
             ? $phorum->url(PHORUM_INDEX_URL)
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
                $url = $phorum->url(PHORUM_INDEX_URL);
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
            $PHORUM['DATA']['BREADCRUMBS'][$track]['URL'] = $phorum->url(PHORUM_LIST_URL, $track);
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
    $phorum = Phorum::API();

    $retval = TRUE;

    if ($PHORUM["forum_id"] > 0 &&
        !$PHORUM["folder_flag"] &&
        !$phorum->user->check_access(PHORUM_USER_ALLOW_READ)) {

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

        $phorum->output("message");

        $retval = FALSE;
    }

    return $retval;
}

/**
 * Find out what input and output files to use for a template.
 *
 * @param string $page
 *     The template name (e.g. "header", "css", "foobar::frontpage", etc.).
 *
 * @return array
 *     This function returns an array, containing two elements:
 *     - The PHP file to include for the template base name.
 *     - The file to use as template input. In case there's no
 *       .tpl file to pre-process, the value will be NULL. In that
 *       case, the $phpfile return value can be included directly.
 */
function phorum_get_template_file( $page )
{
    global $PHORUM;
    $phorum = Phorum::API();

    $page = basename($page);

    /*
     * [hook]
     *     get_template_file
     *
     * [availability]
     *     Phorum 5 >= 5.2.11
     *
     * [description]
     *     Allow modules to have influence on the results of the
     *     phorum_get_template_file() function. This function translates
     *     a page name (e.g. <literal>list</literal>) into a filename
     *     to use as the template source for that page (e.g.
     *      <filename>/path/to/phorum/templates/emerald/list.tpl</filename>).
     *
     * [category]
     *     Page output
     *
     * [when]
     *     At the start of the phorum_get_template_file() function
     *     from <filename>common.php</filename>.
     *
     * [input]
     *     An array containing two elements:
     *     <ul>
     *       <li>page:
     *           The page that was requested.</li>
     *       <li>source:
     *           The file that has to be used as the source for the page.
     *           This one is initialized as NULL.</li>
     *     </ul>
     *
     * [output]
     *     Same as input. Modules can override either or both of the array
     *     elements. When the "source" element is set after running the
     *     hook, then the file named in this element is directly used as
     *     the template source. It must end in either ".php" or ".tpl" to
     *     be accepted as a template source. Phorum does not do any additional
     *     checking on this source file name. It is the module's duty to
     *     provide a correct source file name.<sbr/>
     *     Otherwise, the template source file is determined based on
     *     the value of the "page" element, following the standard
     *     Phorum template resolving rules.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_get_template_file($data)
     *     {
     *         // Override the "index_new" template with a custom
     *         // template from the "foo" module.
     *         if ($data['page'] == 'index_new') {
     *             $data['page'] = 'foo::index_new';
     *         }
     *
     *         // Point the "pm" template directly at a custom PHP script.
     *         if ($data['page'] == 'pm') {
     *             $data['source'] = './mods/foo/pm_output_handler.php';
     *         }
     *
     *         return $data;
     *     }
     *     </hookcode>
     */
    $tplbase = NULL;
    $template = NULL;
    if (isset($GLOBALS["PHORUM"]["hooks"]["get_template_file"])) {
        $res = $phorum->modules->hook("get_template_file", array(
            'page'   => $page,
            'source' => NULL
        ));
        if ($res['source'] !== NULL && strlen($res['source']) > 4)
        {
            // PHP source can be returned right away. These will be included
            // directly by the template handling code.
            if (substr($res['source'], -4, 4) == '.php') {
                return array($res['source'], NULL);
            }
            // For .tpl files, we continue running this function, because
            // a cache file name has to be compiled for storing the
            // compiled template data.
            if (substr($res['source'], -4, 4) == '.tpl') {
                $tplbase = substr($res['source'], 0, -4);
            }
        }
        $page = basename($res['page']);
        $template = 'set_from_module';
    }

    // No template source set by a module? Then continue by finding
    // a template based on the provided template page name.
    if ($tplbase === NULL)
    {
        // Check for a module reference in the page name.
        $fullpage = $page;
        $module = NULL;
        if (($pos = strpos($fullpage, "::", 1)) !== FALSE) {
            $module = substr($fullpage, 0, $pos);
            $page = substr($fullpage, $pos+2);
        }

        if ($module === NULL) {
            $prefix = $PHORUM['template_path'];
            // The postfix is used for checking if the template directory
            // contains at least the mandatory info.php file. Otherwise, it
            // could be an incomplete or empty template.
            $postfix = '/info.php';
        } else {
            $prefix = PHORUM_PATH.'/mods/'.basename($module).'/templates';
            $postfix = '';
        }

        // If no user template is set or if the template cannot be found,
        // fallback to the configured default template. If that one can also
        // not be found, then fallback to the hard-coded default template.
        if (empty($PHORUM["template"]) ||
            !file_exists("$prefix/{$PHORUM['template']}$postfix"))
        {
            $template = $PHORUM["default_forum_options"]["template"];
            if ($template != PHORUM_DEFAULT_TEMPLATE &&
                !file_exists("$prefix/$template$postfix")) {
                $template = PHORUM_DEFAULT_TEMPLATE;
            }

            // If we're not handling a module template, then we can change the
            // global template to remember the fallback template and to make
            // sure that {URL->TEMPLATE} and {TEMPLATE} aren't pointing to a
            // non-existent template in the end..
            if ($module === NULL) { $PHORUM["template"] = $template; }
        } else {
            $template = $PHORUM['template'];
        }

        $tplbase = "$prefix/$template/$page";

        // check for straight PHP file
        if (file_exists("$tplbase.php")) {
            return array("$tplbase.php", NULL);
        }
    }

    // Build the compiled template and template input file names.
    $tplfile = "$tplbase.tpl";
    $safetemplate = str_replace(array("-",":"), array("_","_"), $template);
    if (isset($module)) $page = "$module::$page";
    $safepage = str_replace(array("-",":"), array("_","_"), $page);
    $phpfile = "{$PHORUM["cache"]}/tpl-$safetemplate-$safepage-" .
           md5(dirname(__FILE__) . $tplfile) . ".php";

    return array($phpfile, $tplfile);
}

/**
 * Returns the PHP file to include for a template file. This function will
 * automatically compile .tpl files if no compiled template is available.
 *
 * If the format for the template file is <module>::<template>, then
 * the template is loaded from the module's directory. The directory
 * structure for storing module templates is the same as for the
 * main templates directory, only it is stored within a module's
 * directory:
 *
 * <phorum_dir>/mods/templates/<template name>/<page>.tpl
 *
 * @param $page - The template base name (e.g. "header", "css", etc.).
 * @return $phpfile - The PHP file to include for showing the template.
 */
function phorum_get_template( $page )
{
    // This might for example happen if a template contains code like
    // {INCLUDE template} instead of {INCLUDE "template"}.
    if ($page === NULL || $page == "") {
        print "<h1>Phorum Template Error</h1>";
        print "phorum_get_template() was called with an empty page name.<br/>";
        print "This might indicate a template problem.<br/>";
        if (function_exists('debug_print_backtrace')) {
            print "Here's a backtrace that might help finding the error:";
            print "<pre>";
            debug_print_backtrace();
            print "</pre>";
        }
        exit(1);
    }

    list ($phpfile, $tplfile) = phorum_get_template_file($page);

    // No template to pre-process.
    if ($tplfile == NULL) return $phpfile;

    // Pre-process template if the output file isn't available.
    if (! file_exists($phpfile)) {
        require_once PHORUM_PATH."/include/templates.php";
        phorum_import_template($page, $tplfile, $phpfile);
    }

    return $phpfile;
}

/**
 * Generate the URLs that are used on most pages.
 */
function phorum_build_common_urls()
{
    global $PHORUM;
    $phorum = Phorum::API();

    $GLOBALS["PHORUM"]["DATA"]["URL"]["BASE"] = $phorum->url(PHORUM_BASE_URL);
    $GLOBALS["PHORUM"]["DATA"]["URL"]["HTTP_PATH"] = $PHORUM['http_path'];

    $GLOBALS["PHORUM"]["DATA"]["URL"]["LIST"] = $phorum->url(PHORUM_LIST_URL);

    // These links are only needed in forums, not in folders.
    if (isset($PHORUM['folder_flag']) && !$PHORUM['folder_flag']) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["POST"] = $phorum->url(PHORUM_POSTING_URL);
        $GLOBALS["PHORUM"]["DATA"]["URL"]["SUBSCRIBE"] = $phorum->url(PHORUM_SUBSCRIBE_URL);
    }

    $GLOBALS["PHORUM"]["DATA"]["URL"]["SEARCH"] = $phorum->url(PHORUM_SEARCH_URL);

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
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = $phorum->url(PHORUM_INDEX_URL, $index_id);
        else
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = $phorum->url(PHORUM_INDEX_URL);
    }

    // these urls depend on the login-status of a user
    if ($GLOBALS["PHORUM"]["DATA"]["LOGGEDIN"]) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = $phorum->url( PHORUM_LOGIN_URL, "logout=1" );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = $phorum->url( PHORUM_CONTROLCENTER_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["PM"] = $phorum->url( PHORUM_PM_URL );
    } else {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = $phorum->url( PHORUM_LOGIN_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = $phorum->url( PHORUM_REGISTER_URL );
    }
}

?>
