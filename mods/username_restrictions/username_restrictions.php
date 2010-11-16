<?php

require_once "./mods/username_restrictions/defaults.php";

function phorum_mod_username_restrictions_before_register($data)
{
    // If another module returned an error already, then we won't run
    // our checks right now.
    if (isset($data["error"])) return $data;

    $PHORUM   = $GLOBALS['PHORUM'];
    $settings = $PHORUM['mod_username_restrictions'];
    $lang     = $PHORUM['DATA']['LANG']['mod_username_restrictions'];
    $username = $data['username'];

    // Trim the username to be sure that we're handling a clean string.
    $username = trim($username);

    // ----------------------------------------------------------------------
    // Check minimum username length.
    // ----------------------------------------------------------------------

    if (!empty($settings['min_length'])) {
        if (strlen($username) < $settings['min_length']) {
            $str = $lang['min_length'];
            $str = str_replace('%length%', $settings['min_length'], $str);
            $data['error'] = $str; 
        }
    }

    // ----------------------------------------------------------------------
    // Check maximum username length.
    // ----------------------------------------------------------------------

    if (!isset($data['error']) && !empty($settings['max_length'])) {
        if (strlen($username) > $settings['max_length']) {
            $str = $lang['max_length'];
            $str = str_replace('%length%', $settings['max_length'], $str);
            $data['error'] = $str; 
        }
    }

    // ----------------------------------------------------------------------
    // Check valid characters.
    // ----------------------------------------------------------------------

    if (!isset($data['error']) && strlen($settings['valid_chars']))
    {
        // Generate regular expression for stripping unwanted characters
        // and a description of the allowed characters in case we need
        // to show an error message.
        $strip = '/[^';
        $allowed = array();
        for ($i=0; $i<strlen($settings['valid_chars']); $i++)
        {
            $c = $settings['valid_chars'][$i];

            $allowed[] = $lang["allowed_$c"];

            switch ($c) {
                case 'l':
                  $strip .= 'a-z';
                  break;
                case 'n':
                  $strip .= '0-9';
                  break;
                case 'd';
                  $strip .= '\.';
                  break;
                case 'h';
                  $strip .= '\-';
                  break;
                case 'u';
                  $strip .= '_';
                  break;
                case 's';
                  $strip .= ' ';
                  break;
            }
        }
        $strip .= ']+/i';

        // Strip the username.
        $stripped_username = preg_replace($strip, '', $username);

        // If the username changed, then unwanted characters were used.
        // Notify the user and update the username with the stripped 
        // version of the username.
        if ($stripped_username != $username)
        {
            if (count($allowed) == 1) {
                $valid_chars = $allowed[0];
            } else {
                $last = array_pop($allowed);
                $valid_chars = implode(', ', $allowed);
                $valid_chars .= ' ' . $lang['and'] . ' ' . $last;
            }

            $str = $lang['valid_chars'];
            $str = str_replace('%username%', $username , $str);
            $str = str_replace('%valid_chars%', $valid_chars , $str);

            $data['error'] = $str;

            $username = $stripped_username;
        }
    }

    // ----------------------------------------------------------------------
    // Check lower case username.
    // ----------------------------------------------------------------------

    if (!isset($data['error']) && !empty($settings['only_lowercase']))
    {
        $lowercase_username = strtolower($username);

        // If the username changed, then upper case characters were used.
        // Notify the user and update the username with the lower case 
        // version of the username.
        if ($lowercase_username != $username)
        {
            $str = $lang['only_lowercase'];
            $str = str_replace('%username%', $username , $str);

            $data['error'] = $str;

            $username = $lowercase_username;
        }
    }


    // Put back the username in the data (we might have changed it
    // during the checks).
    $data["username"] = $_POST["username"] = $username;

    return $data;
}


?>
