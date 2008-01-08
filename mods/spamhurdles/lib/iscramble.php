<?php

/******************************************************************************
 * iScramble - Scramble HTML source to make it difficult to read              *
 *                                                                            *
 * Visit the iScramble homepage at http://www.z-host.com/php/iscramble        *
 *                                                                            *
 * Copyright (C) 2003 Ian Willis. All rights reserved.                        *
 *                                                                            *
 * This script is FreeWare.                                                   *
 *                                                                            *
 ******************************************************************************/

/******************************************************************************
 * Modified by Maurice Makaay <maurice@phorum.org> for making this code work  *
 * inside xml/xhtml content as well. This includes some code to make          *
 * javascript code that is in the scrambled data execute (the original        *
 * document.writeln() method will execute this JavaScript code as well).      *
 ******************************************************************************/

$iscramble_version = "1.0-phorum";

// Phorum change: added this function to provide the javascript code
// that is needed to handle javascript in scrambled code.
function iScramble_javascript()
{
    static $already_returned = FALSE;

    if ($already_returned) return '';

    $javascript =
      "<script type=\"text/javascript\">
       //<![CDATA[
       function iscramble_eval_javascript(data)
       {
           var cursor = 0; var start = 1; var end = 1;
           while (cursor < data.length && start > 0 && end > 0) {
               start = data.indexOf('<script', cursor);
               end   = data.indexOf('</script', cursor);
               if (end > start && end > -1) {
                   if (start > -1) {
                       var res = data.substring(start, end);
                       start = res.indexOf('>') + 1;
                       res = res.substring(start);
                       if (res.length != 0) {
                           eval(res);
                       }
                   }
                   cursor = end + 1;
               }
           }
       }
       //]]>
       </script>\n";

    $already_returned = TRUE;

    return $javascript;
}

/* Perform ROT13 encoding on a string */
function iScramble_rot13($str)
{
    $from = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $to = 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM';

    return strtr($str, $from, $to);
}

/* Perform the equivalent of the JavaScript escape function */
function iScramble_escape($plain)
{
    $escaped = "";
    $passChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789*@-_+./";

    for ($i = 0; $i < strlen($plain); $i++)
    {
        $char = $plain{$i};
        if (strpos($passChars, $char) === false)
        {
            // $char is not in the list of $passChars. Encode in hex format
            $escaped .= sprintf("%%%02X", ord($char));
        }
        else
        {
            $escaped .= $char;
        }
    }

    return $escaped;
}


/* Main iScramble function
 *
 * This function takes plain text and scrambles them. It returns some JavaScript
 * that contains the scrambled text and JavaScript to unscramble it.
 *
 * RETURNS:     JavaScript code to display the scrambled message.
 *
 * PARAMETERS:
 *
 *  NAME        TYPE
 *
 *  $plain      String      Plan text to scramble
 *  $longPwd    Boolean     True for better scrambling, using a longer password.
 *                          This produces larger JavaScript code.
 *                          Defaults to False.
 *  $rot13      Boolean     True for better scrambling, using rot13 encoding of
 *                          the plain text. This produces larger JavaScript
 *                          code and takes longer to decode. Not recommended
 *                          for large $plain strings.
 *                          Defaults to False.
 *  $sorry      String      Message displayed if visitor does not have
 *                          JavaScript enabled in their web browser.
 *                          Defaults to "<i>[Please Enable JavaScript]</i>".
 */
function iScramble($plain, $longPwd=False, $rot13=False, $sorry="<i>[Please Enable JavaScript]</i>")
{
    global $iscramble_version;
    static $iscramble_idx = 0;

    $iscramble_idx ++;

    $escaped = iScramble_escape($plain);
    if ($rot13)
    {
        $escaped = iScramble_rot13($escaped);
    }

    $numberOfColumns = 10;
    $numberOfRows = ceil(strlen($escaped) / $numberOfColumns);
    $scrambled = "";

    $escaped = str_pad($escaped, $numberOfColumns * $numberOfRows);

    // Choose a password
    $password = "";
    srand(time());
    for ($j = 0; $j < ($longPwd ? $numberOfRows : 1); $j++)
    {
        $availChars = substr("0123456789", 0, $numberOfColumns);
        for ($i = 0 ; $i < $numberOfColumns; $i++)
        {
            $char = $availChars{ rand(0, strlen($availChars)-1) };
            $password .= $char;
            $availChars = str_replace($char, "", $availChars);
        }
    }

    $scramblePassword = str_repeat($password, $longPwd ? 1 : $numberOfRows);

    // Do the scrambling
    $scrambled = str_repeat(" ", $numberOfColumns * $numberOfRows);
    $k = 0;
    for ($i = 0; $i < $numberOfRows; $i++)
    {
        for($j = 0; $j < $numberOfColumns; $j++ )
        {
            $scrambled{(((int)$scramblePassword{$k}) * $numberOfRows) + $i} = $escaped{$k};
            $k++;
        }
    }

    // Generate the JavaScript
    // Phorum change: make script compliant with w3 checks.
    $javascript = "<span id=\"iscramble_{$iscramble_idx}\"></span>";
    $javascript .= "<script type=\"text/javascript\">\n";
    $javascript .= "//<![CDATA[\n";

    $javascript .= "var box = document.getElementById('iscramble_{$iscramble_idx}');";
    $javascript .= "var a='';var b='$scrambled';var c='$password';";
    if ($rot13)
    {
        $javascript .= "var d='';";
    }
    $javascript .= "for(var i=0;i<$numberOfRows;i++) for(var j=0;j<$numberOfColumns;j++) ";

    if ($rot13)
    {
        $javascript .= "{d=b.charCodeAt(";
    }
    else
    {
        $javascript .= "a+=b.charAt(";
    }

    if ($longPwd)
    {
        $javascript .= "(parseInt(c.charAt(i*$numberOfColumns+j))*$numberOfRows)+i); ";
    }
    else
    {
        $javascript .= "(parseInt(c.charAt(j))*$numberOfRows)+i);";
    }

    if ($rot13)
    {
        $javascript .= "if ((d>=65 && d<78) || (d>=97 && d<110)) d+=13; else if ((d>=78 && d<91) || (d>=110 && d<123)) d-=13;a+=String.fromCharCode(d);}";
    }

    $javascript .= "var unscrambled_data = unescape(a);\n";
    $javascript .= "box.innerHTML = unscrambled_data;\n";
    $javascript .= "iscramble_eval_javascript(unscrambled_data);\n";
    $javascript .= "//]]>\n";
    $javascript .= "</script>\n";
    $javascript .= "<noscript>\n$sorry\n</noscript>\n";

    return $javascript;
}

?>
