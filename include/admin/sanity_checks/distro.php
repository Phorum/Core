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

    // Check if a minimal distro is installed.

    $phorum_check = "Check if all required Phorum files are available";

    // A list of files which we consider to be the minimal set of
    // Phorum files to get a working Phorum. Some files are checked
    // through code later on, because the rules are somewhat more
    // complex than only checking for availability of a file (e.g.
    // the database layers, where at least one layer should be
    // available).
    $GLOBALS["PHORUM"]["minimal_distro"] = array (
        'admin.'.PHORUM_FILE_EXTENSION,
        'common.'.PHORUM_FILE_EXTENSION,
        'control.'.PHORUM_FILE_EXTENSION,
        'feed.'.PHORUM_FILE_EXTENSION,
        'file.'.PHORUM_FILE_EXTENSION,
        'follow.'.PHORUM_FILE_EXTENSION,
        'include/admin/PhorumAdminMenu.php',
        'include/admin/PhorumInputForm.php',
        'include/admin/badwords.php',
        'include/admin/banlist.php',
        'include/admin/cache.php',
        'include/admin/cache_purge.php',
        'include/admin/customprofile.php',
        'include/admin/default.php',
        'include/admin/deletefolder.php',
        'include/admin/deleteforum.php',
        'include/admin/editfolder.php',
        'include/admin/editforum.php',
        'include/admin/file_purge.php',
        'include/admin/footer.php',
        'include/admin/forum_defaults.php',
        'include/admin/groups.php',
        'include/admin/header.php',
        'include/admin/index.php',
        'include/admin/install.php',
        'include/admin/login.php',
        'include/admin/logout.php',
        'include/admin/manage_languages.php',
        'include/admin/message_prune.php',
        'include/admin/mods.php',
        'include/admin/modsettings.php',
        'include/admin/newfolder.php',
        'include/admin/newforum.php',
        'include/admin/sanity_checks/cache.php',
        'include/admin/sanity_checks/database.php',
        'include/admin/sanity_checks/language.php',
        'include/admin/sanity_checks/modules.php',
        'include/admin/sanity_checks/php_safety.php',
        'include/admin/sanity_checks/upload_limits.php',
        'include/admin/sanity_checks.php',
        'include/admin/settings.php',
        'include/admin/status.php',
        'include/admin/upgrade.php',
        'include/admin/version.php',
        'include/api/base.php',
        'include/api/user.php',
        'include/api/file_storage.php',
        'include/api/custom_profile_fields.php',
        'include/constants.php',
        'include/controlcenter/email.php',
        'include/controlcenter/files.php',
        'include/controlcenter/forum.php',
        'include/controlcenter/groupmod.php',
        'include/controlcenter/groups.php',
        'include/controlcenter/messages.php',
        'include/controlcenter/password.php',
        'include/controlcenter/privacy.php',
        'include/controlcenter/sig.php',
        'include/controlcenter/subthreads.php',
        'include/controlcenter/summary.php',
        'include/controlcenter/user.php',
        'include/controlcenter/users.php',
        'include/email_functions.php',
        'include/feed_functions.php',
        'include/format_functions.php',
        'include/index_classic.php',
        'include/index_new.php',
        'include/moderation_functions.php',
        'include/posting/action_attachments.php',
        'include/posting/action_cancel.php',
        'include/posting/action_edit.php',
        'include/posting/action_post.php',
        'include/posting/action_preview.php',
        'include/posting/check_banlist.php',
        'include/posting/check_integrity.php',
        'include/posting/check_permissions.php',
        'include/posting/request_first.php',
        'include/posting/request_followup.php',
        'include/profile_functions.php',
        'include/templates.php',
        'include/thread_info.php',
        'include/thread_sort.php',
        'include/upload_functions.php',
        'include/version_functions.php',
        'include/api/modules.php',
        'include/api/custom_profile_fields.php',
        'include/api/file_storage.php',
        'include/api/base.php',
        'include/api/user.php',
        'index.'.PHORUM_FILE_EXTENSION,
        'list.'.PHORUM_FILE_EXTENSION,
        'login.'.PHORUM_FILE_EXTENSION,
        'moderation.'.PHORUM_FILE_EXTENSION,
        'pm.'.PHORUM_FILE_EXTENSION,
        'posting.'.PHORUM_FILE_EXTENSION,
        'profile.'.PHORUM_FILE_EXTENSION,
        'read.'.PHORUM_FILE_EXTENSION,
        'redirect.'.PHORUM_FILE_EXTENSION,
        'register.'.PHORUM_FILE_EXTENSION,
        'report.'.PHORUM_FILE_EXTENSION,
        'css.'.PHORUM_FILE_EXTENSION,
        'rss.'.PHORUM_FILE_EXTENSION,
        'feed.'.PHORUM_FILE_EXTENSION,
        'search.'.PHORUM_FILE_EXTENSION,
        'versioncheck.php',
    );

    // A list of database layer files that ship with Phorum.
    $GLOBALS["PHORUM"]["distro_dblayers"] = array(
        "mysql.php",
        # "postgresql.php", needs Porting to Phorum 5.2
    );

    // A list of templates that ship with Phorum.
    $GLOBALS["PHORUM"]["distro_templates"] = array(
        "emerald",
        "classic",
        "lightweight"
    );

    // A list of language files that ship with Phorum.
    $GLOBALS["PHORUM"]["distro_languages"] = array(
        "english.php",
    );

    function phorum_check_distro()
    {
        $PHORUM = $GLOBALS["PHORUM"];

        $errors = array();

        // ------------------------------------------------------------------
        // Check if all files from the minimal distro list are available.
        // ------------------------------------------------------------------

        foreach ($PHORUM["minimal_distro"] as $file)
        {
            // Check availability.
            if (! file_exists($file)) {
                if (strtolower($file) != $file) {
                    $errors[] = "missing (maybe due to case mismatch): $file";
                } else {
                    $errors[] = "missing: $file";
                }
                continue;
            }

            // Check readability.
            if (!($fp = @fopen($file, "r"))) {
                $errors[] = "unreadable: $file";
                continue;
            }
            fclose($fp);
        }

        if (count($errors)) return array(
            PHORUM_SANITY_CRIT,
            "Not all files that are required for running Phorum seem to
             be installed correctly on your server. Below is a list of
             problems:<br/><ul><li>" .
             implode("</li>\n<li>", $errors) .
             "</li></ul>",
            "If a file is marked \"<b>missing</b>\", then it's
             probably not uploaded at all. If there's an additional
             \"<b>case mismatch</b>\" notice, then it could be that your
             FTP upload client mangled the case of the uploaded file's
             filename. If a file is marked \"<b>unreadable</b>\" then
             the file is not readable by the webserver. Make sure that
             the webserver has permission for reading the file, by
             using chmod to mode \"644\" (readable and writable for the
             user, readable for the group and others). Never use
             mod \"777\" for Phorum!"
        );

        // ------------------------------------------------------------------
        // Check if we have at least one database layer available.
        // ------------------------------------------------------------------

        $dir = @opendir("include/db");
        if (! $dir) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to open the directory \"include/db/\". This
             directory contains the database layers for connecting
             Phorum to a database.",
            "Check that you have uploaded the directory \"include/db/\"
             and that it is readable for the webserver by using chmod
             to mode \"755\" (all permissions for the user, readable
             and executable for the group and others). Never use mod
             \"777\" for Phorum!"
        );
        $ok = false;
        while ($entry = readdir($dir)) {
            if (substr($entry, -4, 4) == ".php" && $entry != "config.php") {
                $ok = true;
                break;
            }
        }
        closedir($dir);
        if (! $ok) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to find a database layer file in the
             directory \"include/db/\". A database layer is necessary to
             connect Phorum to a database.",
            "Upload at least one database layer file to this directory.
             Which database layer to upload, depends on the database that
             you want to connect to. Phorum is distributed with the
             database layer(s): " . implode(", ", $PHORUM["distro_dblayers"])
        );

        // ------------------------------------------------------------------
        // Check if we have at least one template available.
        // ------------------------------------------------------------------

        $dir = @opendir("templates/");
        if (! $dir) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to open the directory \"templates/\". This
             directory contains the Phorum templates.",
            "Check that you have uploaded the directory \"templates/\"
             and that it is readable for the webserver by using chmod
             to mode \"755\" (all permissions for the user, readable
             and executable for the group and others). Never use mod
             \"777\" for Phorum!"
        );
        $ok = false;
        while ($entry = readdir($dir)) {
            if ($entry != "." && $entry != ".." && $entry != ".svn" && is_dir("templates/$entry")) {
                $ok = true;
                break;
            }
        }
        if (! $ok) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to find a Phorum template in the
             directory \"templates/\".",
            "Upload at least one template directory to this directory.
             Which template(s) to upload, depends on the template(s) that
             you want to use. Phorum is distributed with the
             template(s): " . implode(", ", $PHORUM["distro_templates"])
        );

        // ------------------------------------------------------------------
        // Check if we have at least one language available.
        // ------------------------------------------------------------------

        $dir = @opendir("include/lang/");
        if (! $dir) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to open the directory \"include/lang/\". This
             directory contains the language files for Phorum.",
            "Check that you have uploaded the directory \"include/lang/\"
             and that it is readable for the webserver by using chmod
             to mode \"755\" (all permissions for the user, readable
             and executable for the group and others). Never use mod
             \"777\" for Phorum!"
        );
        $ok = false;
        while ($entry = readdir($dir)) {
            if (substr($entry, -4, 4) == ".php") {
                $ok = true;
                break;
            }
        }
        if (! $ok) return array(
            PHORUM_SANITY_CRIT,
            "Phorum is unable to find a Phorum language file in the
             directory \"include/lang/\".",
            "Upload at least one language file to this directory.
             Which language file(s) to upload, depends on the language(s) that
             you want to use. Phorum is distributed with the
             language file(s): " . implode(", ", $PHORUM["distro_languages"])
        );

        return array (PHORUM_SANITY_OK, NULL, NULL);
    }
?>
