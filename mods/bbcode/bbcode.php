<?php

if(!defined("PHORUM")) return;

// BB Code Phorum Mod
function phorum_bb_code($data)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $search = array(
        "/\[img\]((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%# ]+?)\[\/img\]/is",
        "/\[url\]((http|https|ftp|mailto):\/\/([a-z0-9\.\-@:]+)[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),\#%~ ]*?)\[\/url\]/is",
        "/\[url=((http|https|ftp|mailto):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%# ]+?)\](.+?)\[\/url\]/is",
        "/\[email\]([a-z0-9\-_\.\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+?)\[\/email\]/ies", 
        "/\[color=([\#a-z0-9]+?)\](.+?)\[\/color\]/is", 
        "/\[size=([+\-\da-z]+?)\](.+?)\[\/size\]/is", 
        "/\[b\](.+?)\[\/b\]/is", 
        "/\[u\](.+?)\[\/u\]/is", 
        "/\[i\](.+?)\[\/i\]/is", 
        "/\[center\](.+?)\[\/center\]/is", 
        "/\[hr\]/i", 
        "/\[code\](.+?)\[\/code\]/is", 
        "/\[quote\](.+?)\[\/quote\]/is", 
    );

    $replace = array(
        "<img src=\"$1\" />",
        "[<a href=\"$1\">$3</a>]",
        "<a href=\"$1\">$3</a>",
        "'<a href=\"'.phorum_html_encode('mailto:$1').'\">'.phorum_html_encode('$1').'</a>'",
        "<span style=\"color: $1\">$2</span>",
        "<span style=\"font-size: $1\">$2</span>",
        "<strong>$1</strong>",
        "<u>$1</u>",
        "<i>$1</i>",
        "<center class=\"bbcode\">$1</center>",
        "<hr class=\"bbcode\" />",
        "<pre class=\"bbcode\">$1</pre>",
        "<blockquote class=\"bbcode\">".$PHORUM["DATA"]["LANG"]["Quote"] . ":<br />$1</blockquote>"
    );

    foreach($data as $message_id => $message){

        if(isset($message["body"])){

            // do BB Code here
            $body = $message["body"];

            $rnd=substr(md5($body.time()), 0, 4);

            // convert bare urls into bbcode tags as best we can
            // the haystack has to have a space in front of it for the preg to work.
            $body = preg_replace("/([^='\"(\[url\]|\[img\])])((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%#]+)/i", "$1:$rnd:$2:/$rnd:", " $body");
            
            // convert bare email addresses into bbcode tags as best we can.
            $body = preg_replace("/([a-z0-9][a-z0-9\-_\.\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[a-z0-9])/i", "[email]$1[/email]", $body);

            // stip puncuation from urls
            if(preg_match_all("!:$rnd:(.+?):/$rnd:!i", $body, $match)){

                $urls = array_unique($match[1]);

                foreach($urls as $key => $url){
                    // stip puncuation from urls
                    if(preg_match("|[^a-z0-9=&/\+_]+$|i", $url, $match)){

                        $extra = $match[0];
                        $true_url = substr($url, 0, -1 * (strlen($match[0])));

                        $body = str_replace("$url:/$rnd:", "$true_url:/$rnd:$extra", $body);
                        
                        $url = $true_url;
                    }

                    $body = str_replace(":$rnd:$url:/$rnd:", "[url]{$url}[/url]", $body);
                }

            }

            // no sense doing any of this if there is no [ in the body
            if(strstr($body, "[")){

                // clean up any BB code we stepped on.
                $body = str_replace("[email][email]", "[email]", $body);
                $body = str_replace("[/email][/email]", "[/email]", $body);

                // fiddle with white space around quote and code tags.
                $body=preg_replace("/\s*(\[\/*(code|quote)\])\s*/", "$1", $body);

                // run the pregs defined above
                $body = preg_replace($search, $replace, $body);

            }

            
            $data[$message_id]["body"] = $body;
        }
    }

    return $data;
}

?>