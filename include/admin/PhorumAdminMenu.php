<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

if (!defined("PHORUM_ADMIN")) return;

class PhorumAdminMenu
{
    var $_title;
    var $_id;
    var $_links;

    function PhorumAdminMenu ($title="", $id="")
    {
        $this->reset($title, $id);
    }

    function reset($title="", $id="")
    {
        $this->_title = $title;
        $this->_id = $id;
        $this->_links=array();
    }

    function add($title, $module, $description, $parameters = NULL)
    {
        if ($parameters !== NULL)
        {
            if (!is_array($parameters)) {
                $parameters = array($parameters);
            }
        }

        $this->_links[] = array(
            "title"       => $title,
            "module"      => $module,
            "description" => $description,
            "parameters"  => $parameters
        );
    }

    function show()
    {
        if($this->_title){
            echo "<div class=\"PhorumAdminMenuTitle\">$this->_title</div>\n";
        }
        echo "<div class=\"PhorumAdminMenu\"";
        if ($this->_id) echo " id=\"$this->_id\"";
        echo ">";

        foreach($this->_links as $link)
        {
            $desc = htmlspecialchars($link["description"]);
            $href = htmlspecialchars($_SERVER["PHP_SELF"]);
            $title = htmlspecialchars($link["title"]);

            $input_args = array();
            if(!empty($link["module"])) $input_args[]="module=$link[module]";
            if (!empty($link["parameters"])) {
                $input_args = array_merge($input_args, $link["parameters"]);
            }
            $url = phorum_admin_build_url($input_args);
            $html ="<a title=\"$desc\" href=\"$url";
            $html .= "\">$title</a><br />";
            echo $html;
        }

        echo "</div>\n";
    }
}

?>
