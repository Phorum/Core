<?php

if (!defined('PHORUM')) return;

if (!function_exists('json_decode'))
{
    function json_decode($content, $assoc=false)
    {
        require_once('./mods/compat_json/json-pear.php');

        static $json_a;
        static $json_b;

        if ($assoc) {
            if (!$json_a) {
                $json_a = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
            }
            $json = $json_a;
        } else {
            if (!$json_b) {
                $json_b = new Services_JSON;
            }
            $json = $json_b;
        }

        return $json->decode($content);
    }
}

if (!function_exists('json_encode'))
{
    function json_encode($content)
    {
        require_once('./mods/compat_json/json-pear.php');

        static $json;
        if (!$json) {
            $json = new Services_JSON;
        }
        return $json->encode($content);
    }
}

?>
