<?php

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


        function show()
        {
            if($this->_title){
                echo "<div class=\"PhorumAdminMenuTitle\">$this->_title</div>\n";
            }
            echo "<div class=\"PhorumAdminMenu\"";
            if($this->_id) echo " id=\"$this->_id\"";
            echo ">";

            foreach($this->_links as $link){
                $desc=$link["description"];
                $html ="<a title='$desc' href=\"$_SERVER[PHP_SELF]";
                if(!empty($link["module"])) $html.="?module=$link[module]";
                $html.="\">$link[title]</a><br />";
                echo $html;
            }

            echo "</div>\n";


        }

    }

?>