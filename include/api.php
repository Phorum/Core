<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * This script implements a one-object-wraps-all class, which provides
 * access to all Phorum API functionality, by means of autoloading the
 * required implementation files and calling the API functions.
 *
 * @package    PhorumAPI
 * @subpackage Core
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 *
 * @todo Check all trigger_error calls to see if we escaped all
 *       variables using htmlspecialchars().
 *
 * @todo Extend the Phorum::API() singleton documentation, to explain
 *       that this singleton is the API entry point.
 */

/**
 * The Phorum API call router.
 */
class Phorum
{
    private $phorum_path;
    private $func_prefix;
    private $node_file;
    private $node_path;

    private static $instance;

    /**
     * Generate a full file system path to a Phorum file.
     *
     * @param string $file
     *     The file path, relative to the Phorum root.
     *
     * @param string
     *     The absolute file system path to the file.
     */
    public function getPath($file = '')
    {
        // The Phorum installation path.
        // We cannot used the PHORUM_PATH constant from constants.php,
        // because include/api/constants.php might not be loaded yet.
        if (empty($this->phorum_path)) {
            $this->phorum_path = realpath(dirname(__FILE__).'/../');
        }

        return $this->phorum_path . ($file == '' ? '' : '/') . $file;
    }

    /**
     * The Phorum contructor.
     *
     * Creates a node in the Phorum API routing tree.
     *
     * This method is defined as a private method, to enforce the
     * singleton design pattern. To grab an instance of the Phorum
     * class, one should call the {@link Phorum::API()} static method.
     *
     * @param array $node_path
     *     The fileystem path for the constructed API node.
     *     There is no need to call this parameter directly.
     *     It is used internally by the Phorum object to create subnodes.
     *
     * @param array $func_prefix
     *     The prefix that is used for functions below this node.
     *     There is no need to call this parameter directly.
     *     It is used internally by the Phorum object to create subnodes.
     */
    private function __construct($node_path = NULL, $func_prefix = NULL)
    {
        // The filesystem path for the constructed API node.
        if ($node_path === NULL) $node_path = 'include/api';
        $this->node_path = $node_path;

        // The prefix that is used for functions below this node.
        if ($func_prefix === NULL) $func_prefix = 'phorum_api_';
        $this->func_prefix = $func_prefix;

        // Determine the file in which the functions for this node are
        // defined. For the root level API node, we load a special
        // bootstrap script which sets up the required environment.
        if ($func_prefix == 'phorum_api_') {
            $file = $this->getPath('include/api/bootstrap.php');
        } else {
            $file = $this->getPath($node_path.'.php'); 
        }

        // Load the API layer file.
        if (file_exists($file)) {
            global $PHORUM;
            $phorum = $this; // So we can reference $phorum from included code
            require_once $file;
            $this->node_file = $file;
        } else trigger_error(
            "Phorum API layer file \"$file\" not available for " .
            "loading the \"{$this->func_prefix}*\" functions",
            E_USER_ERROR
        );
    }

    /**
     * This method is defined as private to prevent cloning of the
     * Phorum API object.
     */
    private function __clone() { }

    /**
     * Magic method for automatically initializing Phorum API nodes
     * when they are accessed for the first time.
     *
     * @param string $what
     *     The name of the API node (e.g. "user", "file").
     *
     * @return Phorum $node
     *     The Phorum node object for the requested node name.
     */
    public function __get($what)
    {
        $what = basename($what);
        return $this->$what = new Phorum(
            $this->node_path . '/' . $what,
            $this->func_prefix . $what . '_'
        );
    }

    /**
     * Magic method for calling Phorum API functions.
     *
     * @param string $what
     *     The name of the function to call.
     *
     * @param array
     *     An array of arguments for the function call.
     * 
     * @return mixed
     *     The return value of the function call.
     */
    public function __call($what, $args)
    {
        $function = $this->func_prefix.$what;
        if (!function_exists($function))
        {
            // Check for an API layer, named $what.
            // Check if the function prefix{$what}() exists.
            // If yes, then we'll redirect to that function.
            // E.g. $phorum->url() will be handled by phorum_api_url()
            // from the include/api/url.php API library file.
            $this->$what; // forces loading the layer.
            $function = $this->func_prefix.$what;

            // Out of luck.
            if (!function_exists($function)) trigger_error(
                "Phorum API file \"{$this->node_file}\" does not implement " .
                "API function $function()",
                E_USER_ERROR
            );
        }

        return call_user_func_array($function, $args);
    }

    /**
     * Singleton implementation.
     *
     * This will instantiate one instance of the Phorum class and return
     * it. On subsequent calls, the same instance is returned.
     *
     * Usage: $phorum = Phorum::API();
     *
     * @return Phorum
     */
    public function API ()
    {
        if (!isset(Phorum::$instance)) Phorum::$instance = new Phorum();
        return Phorum::$instance;
    }
}

?>
