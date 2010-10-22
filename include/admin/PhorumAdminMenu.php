<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
////////////////////////////////////////////////////////////////////////////////

    if(!defined("PHORUM_ADMIN")) return;

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

        function add($title, $module, $description)
        {
            $this->_links[]=array("title"=>$title, "module"=>$module, "description"=>$description);
        }

        /**
         * Adds a custom link to the menu.
         *
         * A custom links URL can link anywhere, not just into Phorum modules
         * 
         * @param mixed  $title       Name of link
         * @param mixed  $url         Destination URL
         * @param double $description Optional, defaults to ''. "title"-Attribute of link
         * @param mixed  $target      Optional, defaults to ''. Target to open in, use "_blank" for just new windows.
         */
        function addCustom($title, $url, $description = '', $target = '')
        {
            $this->_links[]=array("type"=>"custom", "title"=>$title, "url"=>$url, "description"=>$description, "target" => $target);
        }


        /**
         * Return the HTML for this menu.
         *
         * @return string   HTML for rendering the menu
         */
        function getHtml()
        {
            $html = '';
            if($this->_title){
                $html .= "<div class=\"PhorumAdminMenuTitle\">$this->_title</div>\n";
            }
            $html .= "<div class=\"PhorumAdminMenu\"";
            if($this->_id) $html .= " id=\"$this->_id\"";
            $html .= ">";

            foreach($this->_links as $link){
                if (isset($link["type"]) && $link["type"] == "custom") {
                    $desc = htmlspecialchars($link["description"]);
                    $title = htmlspecialchars($link["title"]);
                    $url = htmlspecialchars($link["url"]);
                    $html .="<a title=\"$desc\" href=\"$url\"";
                    if (strlen($link["target"]) > 0) {
                        $html .= " target=\"{$link["target"]}\" ";
                    }
                    $html.="\">$title</a><br />";
                } else {
                    $desc = htmlspecialchars($link["description"]);
                    $title = htmlspecialchars($link["title"]);
                    $input_args = array();
                    if(!empty($link["module"])) $input_args[]="module=$link[module]";
                    $url = phorum_admin_build_url($input_args);
                    $html .="<a title=\"$desc\" href=\"$url";
                    $html.="\">$title</a><br />";
                }
            }

            $html .= "</div>\n";

            return $html;
        }

        function show()
        {
            echo $this->getHtml();
        }

    }

?>
