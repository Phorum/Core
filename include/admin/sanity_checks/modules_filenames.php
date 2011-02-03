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

    // Check for possible collisions between modules.

    $phorum_check = "Modules (file name / directory name consistency)";

    function phorum_check_modules_filenames($is_install = false) {
        $PHORUM = $GLOBALS["PHORUM"];

        if ($is_install) {
            return array(PHORUM_SANITY_SKIP, NULL, NULL);
        }


        $d = dir("./mods");
        while (false !== ($entry = $d->read()))
        {
        	// Some entries which we skip by default.
        	if ($entry == '.' || $entry == '..' ||
        	$entry == '.svn' || $entry == 'ATTIC' ||
        	$entry == '.htaccess') continue;
        	
        	// Read in the module information.
        	$lines = array();
        	if (file_exists("./mods/$entry/info.txt")) {
        		$lines = file("./mods/$entry/info.txt");
        	} 
        	
        	if(is_file("./mods/$entry") && substr($entry, -4)==".php") {
        		// one file module, skip it
        	} else {
        		if(!file_exists("./mods/$entry/info.txt")) {
        			return array(
                    PHORUM_SANITY_WARN,
                    "Your module &quot;$entry&quot; doesn't have an info.txt file in its directory. Either its not a module or the installation of that module is broken.", 
                    "You should remove all files or directories which are not modules from the mods-directory and fix broken module installations."
                    );
        		} elseif (!file_exists("./mods/$entry/$entry.php")) {
        			return array(
                    PHORUM_SANITY_WARN,
                    "Your module &quot;$entry&quot; doesn't have an corresponding .php file in its directory. 
                     Each module needs a .php-file with the same name as the directory to work. Either that directory isn't for a module or the installation is broken and either the filename or the directoryname needs corrected.", 
                    "You should remove all files or directories which are not modules from the mods-directory and fix broken module installations."
                    );
        			
        		}
        	}
        }


        // All checks are OK.
        return array(PHORUM_SANITY_OK, NULL, NULL);
    }
?>
