<?php

    if(!defined("PHORUM")) return;

    ////////////////////////////////////////////////////////////////////////
    //
    // This function sorts $rows and fills $threads.  It assumes that $rows
    // is an array that is sorted by thread, then id.  This is critical as
    // it ensures that a child is not encountered before a parent.
    // It could be made more complicated to implement the tree graphics
    // as Phorum 3 did.  However, this is much faster and less complicated
    // If someone just has to have the tree graphics, it can be done.
    //

    function phorum_sort_threads($rows)
    {
        foreach($rows as $row){
            $rows[$row["parent_id"]]["children"][]=$row["message_id"];
        }

        $sorted_rows=array(0=>array());

        _phorum_recursive_sort($rows, $sorted_rows);

        unset($sorted_rows[0]);

        return $sorted_rows;
    }


    // not to be called directly.  Call phorum_sort_threads

    function _phorum_recursive_sort($rows, &$threads, $seed=0, $indent=0)
    {
        global $PHORUM;

        if($seed>0){
            $threads[$rows[$seed]["message_id"]]=$rows[$seed];
            $threads[$rows[$seed]["message_id"]]["indent"]=str_repeat($PHORUM['TMP']['indentstring'], $indent);
            $threads[$rows[$seed]["message_id"]]["indent_cnt"]=$indent;
            
            if(!empty($indent)){
                $threads[$rows[$seed]["message_id"]]["indent"].=$PHORUM['TMP']['marker'];
            }
            $indent++;

        }
        if(isset($rows[$seed]["children"])){
            foreach($rows[$seed]["children"] as $child){
                _phorum_recursive_sort($rows, $threads, $child, $indent);
            }
        }
    }




?>