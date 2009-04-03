<?php
// For now, we implement mb_substr simply as substr().
// We need to think about adding a check for broken multi-byte
// characters at the end of the resulting substring, about a
// fully compatible pure PHP mb_substr solution.

if (!defined('PHORUM')) return;

if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = NULL, $encoding = NULL)
    {
        if ($length) {
            return substr($str, $start, $length);
        } else {
            return substr($str, $start);
        }
    }
}
?>
