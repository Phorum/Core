<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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

// Check if the files for all configured languages
// are available in the installation.

$phorum_check = "Language support";

function phorum_check_language($is_install = false) {
    global $PHORUM;

    $checked = array();

    // Check for the default language file.
    if (! file_exists("./include/lang/{$PHORUM["default_forum_options"]["language"]}.php")) return array(
        PHORUM_SANITY_WARN,
        "Your default language is set to
         \"".htmlspecialchars($PHORUM["default_forum_options"]["language"])."\",
         but the language file \"include/lang/".
         htmlspecialchars($PHORUM["default_forum_options"]["language"].".php")."\" is
         not available on your system (anymore?).",
        "Install the specified language file to make this default
         language work or change the Default Language setting
         under General Settings."
    );
    $checked[$PHORUM["default_forum_options"]["language"]] = true;

    // If this check is run at install time, we're done.
    if ($is_install) return array(PHORUM_SANITY_OK, NULL, NULL);

    // Check for the forum specific language file(s).
    require_once './include/api/forums.php';
    $forums = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INCLUDE_INACTIVE
    );
    foreach ($forums as $id => $forum) {
        if (!empty($forum["language"]) && !$checked[$forum["language"]] &&
            !file_exists("./include/lang/{$forum["language"]}.php")) {
            $type = $forum['folder_flag'] ? 'folder' : 'forum';
            return array(
              PHORUM_SANITY_WARN,
              "The language for $type \"".
               htmlspecialchars($forum["name"])."\" is set to
               \"".htmlspecialchars($forum["language"])."\",
               but the language file \"include/lang/".
               htmlspecialchars($forum["language"].".php")."\" is
               not available on your system (anymore?).",
              "Install the specified language file to make this language
               work or change the language setting for the forum."
            );
        }
        $checked[$forum["language"]] = true;
    }

    // All checks are OK.
    return array(PHORUM_SANITY_OK, NULL, NULL);
}
?>
