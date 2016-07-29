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

// Check if a minimal distro is installed.

$phorum_check = "Check if all required Phorum files are available";

// A list of files which we consider to be the minimal set of
// Phorum files to get a working Phorum. Some files are checked
// through code later on, because the rules are somewhat more
// complex than only checking for availability of a file (e.g.
// the database layers, where at least one layer should be
// available).
$GLOBALS["PHORUM"]["minimal_distro"] = array
(
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
    'include/admin/functions.php',
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

    'include/ajax/call.checkpm.php',
    'include/ajax/examples.php',
    'include/ajax/call.markread.php',
    'include/ajax/call.helloworld.php',

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

    'include/db/functional_layer.php',
    'include/db/PhorumDB.php',

    'include/index/directory.php',
    'include/index/flat.php',
    'include/javascript/phorum-javascript-library.php',
    'include/javascript/jquery-1.6.2.min.js',
    'include/version_functions.php',

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

    'include/api/template.php',
    'include/api/output.php',
    'include/api/write_file.php',
    'include/api/deprecated.php',
    'include/api/custom_field.php',
    'include/api/redirect.php',
    'include/api/file.php',
    'include/api/error/backtrace.php',
    'include/api/error/database.php',
    'include/api/error.php',
    'include/api/ban.php',
    'include/api/buffer.php',
    'include/api/feed/atom.php',
    'include/api/feed/html.php',
    'include/api/feed/rss.php',
    'include/api/feed/js.php',
    'include/api/forums.php',
    'include/api/newflags.php',
    'include/api/format.php',
    'include/api/image.php',
    'include/api/sign.php',
    'include/api/url.php',
    'include/api/examples/file_store.php',
    'include/api/examples/user_login.php',
    'include/api/examples/file_delete.php',
    'include/api/examples/user_auth_module.php',
    'include/api/diff.php',
    'include/api/constants.php',
    'include/api/message.php',
    'include/api/template/compile.php',
    'include/api/modules.php',
    'include/api/mail.php',
    'include/api/mail/pm_notify.php',
    'include/api/mail/message_notify.php',
    'include/api/mail/message_moderate.php',
    'include/api/user.php',
    'include/api/tree.php',
    'include/api/feed.php',
    'include/api/json.php',
    'include/api/dev.php',
    'include/api/charset.php',
    'include/api/format/forums.php',
    'include/api/format/messages.php',
    'include/api/format/censor.php',
    'include/api/format/users.php',
    'include/api/lang.php',
    'include/api/generate.php',
    'include/api/read_file.php',
    'include/api/request.php',
    'include/api/system.php',
    'include/api/http_get.php',

    'admin.'.PHORUM_FILE_EXTENSION,
    'ajax.'.PHORUM_FILE_EXTENSION,
    'changes.'.PHORUM_FILE_EXTENSION,
    'common.'.PHORUM_FILE_EXTENSION,
    'control.'.PHORUM_FILE_EXTENSION,
    'css.'.PHORUM_FILE_EXTENSION,
    'feed.'.PHORUM_FILE_EXTENSION,
    'file.'.PHORUM_FILE_EXTENSION,
    'follow.'.PHORUM_FILE_EXTENSION,
    'index.'.PHORUM_FILE_EXTENSION,
    'javascript.'.PHORUM_FILE_EXTENSION,
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
    'script.'.PHORUM_FILE_EXTENSION,
    'feed.'.PHORUM_FILE_EXTENSION,
    'search.'.PHORUM_FILE_EXTENSION,
    'versioncheck.php',
);

// A list of files that were removed from the distro. For these files,
// we check if they are removed from the Phorum directory as well.
// If not (which might happen after an upgrade path), then a fatal
// sanity check error is generated to notice that these files must
// be removed.
$GLOBALS["PHORUM"]["deprecated_distro"] = array
(
    'critical' => array
    (
      'post.'.PHORUM_FILE_EXTENSION,   // deprecated by the posting.php script
    ),
    'warning' => array
    (
      'include/api/file_storage.php',  // renamed to include/api/file.php
      'include/api/custom_profile_fields.php', // Moved to CustomField API
      'include/phorum_get_url.php',    // moved to URL API
      'include/forum_functions.php',   // moved to Forums API
      'include/format_functions.php',  // moved to Message and Format API
      'include/index_flat.php',        // renamed to include/index/flat.php
      'include/index_classic.php',     // renamed to include/index/flat.php
      'include/index_directory.php',   // renamed to include/index/directory.php
      'include/timing.php',            // moved to Profiler API
      'include/feed_functions.php',    // moved to Feed API
      'include/profile_functions.php', // moved to Ban API
      'include/templates.php',         // moved to Template API
      'include/thread_info.php',       // moved to Thread API
      'include/thread_sort.php',       // moved to Thread API
      'include/email_functions.php',   // moved to Mail API
      'include/admin_functions.php',   // moved to include/admin/functions.php
    )
);

// A list of database layer files that ship with Phorum.
$GLOBALS["PHORUM"]["distro_dblayers"] = array(
    "PhorumMysqlDB.php",
    "PhorumPostgresqlDB.php"
);

// A list of templates that ship with Phorum.
$GLOBALS["PHORUM"]["distro_templates"] = array(
    "emerald",
    "classic",
    "lightweight"
);

// A list of language files that ship with Phorum.
$GLOBALS["PHORUM"]["distro_languages"] = array(
    "en_US.UTF-8.php",
);

function phorum_check_distro()
{
    global $PHORUM;

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
    // Database layers have the format "Phorum<Layer>DB.php".
    $ok = false;
    while ($entry = readdir($dir)) {
        if (preg_match('/^Phorum\w+DB\.php$/', $entry)) {
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

    // ------------------------------------------------------------------
    // Check if there are deprecated files in the Phorum tree.
    // ------------------------------------------------------------------

    $warning  = 0;
    $critical = 0;
    foreach ($PHORUM['deprecated_distro'] as $type => $files) {
        foreach ($files as $file)
        {
            if (file_exists($file))
            {
                if ($type == 'critical') {
                    $critical ++;
                    $color = 'red';
                } else {
                    $warning ++;
                    $color = 'darkorange';
                }
                $errors[] = '<span style="color:'.$color.'">' .
                            $file .
                            '</span>';
            }
        }
    }

    if (count($errors)) return array(
        $critical ? PHORUM_SANITY_CRIT : PHORUM_SANITY_WARN,
        "One or more files in your Phorum tree are no longer part of
         the Phorum distribution. Please, remove the following file(s)
         from your Phorum installation" .
        ($warning && $critical
         ? ' (the ones in red are highly recommended for removal)' : '') .
        ":<br/>" .
         "<ul><li>" . implode("</li>\n<li>", $errors) . "</li></ul>",
        "This could happen after installing a newer version of Phorum
         on top of an existing Phorum installation. In this newer version,
         some files might no longer be in use. To prevent conflicts,
         it is best to remove these files from your Phorum installation
         directory."
    );


    // All checks were OK.
    return array (PHORUM_SANITY_OK, NULL, NULL);
}
?>
