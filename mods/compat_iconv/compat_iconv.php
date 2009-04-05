<?php

#if (!defined('PHORUM')) return;

if (!function_exists('iconv'))
{
    global $PHORUM;

    // A cache for storing created converter objects.
    $PHORUM['ConvertCharsetCache'] = array();

    require_once(dirname(__FILE__).'/convertcharset-1.1/ConvertCharset.class.php');

    function iconv($in_charset, $out_charset, $str)
    {
        // Get a converter object.
        global $PHORUM;
        $cache_key = strtolower($in_charset.'->'.$out_charset);
        if (isset($PHORUM['ConvertCharsetCache'][$cache_key])) {
            $converter = $PHORUM['ConvertCharsetCache'][$cache_key];
        } else {
            $converter = new ConvertCharset($in_charset, $out_charset);
            $PHORUM['ConvertCharsetCache'][$cache_key] = $converter;
        }

        // Convert and return the string.
        return $converter->Convert($str);
    }
}

?>
