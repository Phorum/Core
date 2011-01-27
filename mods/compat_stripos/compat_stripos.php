<?php

if (!defined('PHORUM')) return;

if (!function_exists('stripos'))
{
    function stripos($haystack, $needle, $offset = 0)
    {
        return strpos(strtolower($haystack), strtolower($needle), $offset);
    }
}

?>
