<?php

if (!defined('PHORUM')) return;

if (!function_exists('random_int') || !function_exists('random_bytes'))
{
    require_once(dirname(__FILE__).'/random_compat-2.0.2/lib/random.php');
}

?>
