<?php

    if(!defined("PHORUM_ADMIN")) return;

    class PhorumAdminMenu
    {
        var $_title;
        var $_columns;
        var $_width;
        var $_links;

        function PhorumAdminMenu ($title="", $width=0, $columns=1)
        {
            $this->reset($title, $width, $columns);
        }

        function reset($title="", $width=0, $columns=1)
        {
            $this->_title = $title;
            $this->_width = $width;
            $this->_columns = $columns;
            $this->_links=array();
        }

        function add($title, $module, $description, $column=1)
        {
            $this->_links[]=array("title"=>$title, "module"=>$module, "description"=>$description, "column"=>$column);
        }


        function show()
        {
            foreach($this->_links as $link){
                if(empty($cols[$link["column"]])) $cols[$link["column"]]="";
                $desc=$link["description"];
                $html ="<a onMouseOver=\"window.status='$desc'; return true;\" onMouseOut=\"window.status=''; return true;\" href=\"$_SERVER[PHP_SELF]";
                if(!empty($link["module"])) $html.="?module=$link[module]";
                $html.="\">$link[title]</a>&nbsp;";
                $cols[$link["column"]][]=$html;
            }

            ksort($cols);

            echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" class=\"PhorumAdminMenu\"";
            if(!empty($this->_width)) echo " width=\"$this->_width\"";
            echo ">\n";
            echo "<tr>\n";
            echo "    <th class=\"PhorumAdminMenuTitle\" colspan=\"$this->_columns\">$this->_title</th>\n";
            echo "</tr>\n";
            echo "<tr>\n";

            foreach($cols as $links){
                echo "    <td class=\"PhorumAdminMenuLinks\">".implode("<br />", $links)."</td>\n";
            }

            echo "</tr>\n";
            echo "</table>\n";


        }

    }

?>