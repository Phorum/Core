<?php

if (!defined('PHORUM')) return;

$PHORUM['DBCONFIG'] = array(
    'type'               => 'mysql',
    'name'               => 'phorum',
    'server'             => 'db',
    'user'               => 'phorum',
    'password'           => 'phorum',
    'table_prefix'       => 'phorum',
    'port'               => '3306',
    'socket'             => NULL,
    'mysql_php_extension' => 'mysqli',
    'mysql_use_ft'       => '1',
    'empty_search_table' => '0',
    'charset'            => 'utf8',
    'slaves'             => array(),
);
