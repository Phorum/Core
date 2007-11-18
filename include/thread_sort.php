<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
    $PHORUM = $GLOBALS["PHORUM"];
    
    if(count($rows) == 0) {
        return $rows;
    }

    // Get template defined settings values.
    $indentmultiplier = isset($PHORUM['TMP']['indentmultiplier'])
                      ? $PHORUM['TMP']['indentmultiplier'] : 20;
    $cut_min          = isset($PHORUM['TMP']['subject_cut_min'])
                      ? $PHORUM['TMP']['subject_cut_min'] : 20;
    $cut_max          = isset($PHORUM['TMP']['subject_cut_max'])
                      ? $PHORUM['TMP']['subject_cut_max'] : 60;
    $cut_indentfactor = isset($PHORUM['TMP']['subject_cut_indentfactor'])
                      ? $PHORUM['TMP']['subject_cut_indentfactor'] : 2;

    // ------------------------------------------------------------------
    // Use the Phorum PHP extension if it is available
    // ------------------------------------------------------------------

    if (!empty($PHORUM["php_phorum_extension"]) &&
        function_exists('phorum_ext_treesort')) {

        phorum_ext_treesort(
            $rows, "message_id", "parent_id",
            $indentmultiplier,
            "subject", $cut_min, $cut_max, $cut_indentfactor
        );

        return $rows;
    }

    // ------------------------------------------------------------------
    // PHP extension not available. Revert to the pure PHP solution.
    // ------------------------------------------------------------------

    $missing_parents = array();

    foreach($rows as $row){

        // add row for this message with its parent
        $tmp_rows[$row["message_id"]]["parent_id"] = $row["parent_id"];

        // check if this row was thought to be missing and undo that
        if(isset($missing_parents[$row["message_id"]])){
            unset($missing_parents[$row["message_id"]]);
        }

        // if this row's parent is not yet set, mark it missing
        // when it is encountered, it will be removed
        if($row["parent_id"]!=0 && empty($tmp_rows[$row["parent_id"]])){
            $missing_parents[$row["parent_id"]] = $row["parent_id"];
        }

        // add this row to the parents child list
        $tmp_rows[$row["parent_id"]]["children"][] = $row["message_id"];
    }

    // ------------------------------------------------------------------
    // If there are missing parent, promote their children to the top
    // This should be the exception for broken data
    // ------------------------------------------------------------------
    if(!empty($missing_parents)){
        foreach($missing_parents as $parent_id){
            foreach($tmp_rows[$parent_id]["children"] as $child){
                $tmp_rows[$child]["parent_id"] = 0;
                $tmp_rows[0]["children"][] = $child;
            }
            unset($tmp_rows[$parent_id]);
        }
    }

    $order = array();
    $stack = array();
    $curr_id = 0;
    while(count($tmp_rows)){
        if(empty($seen[$curr_id])){
            if($curr_id!=0){
                $seen[$curr_id] = true;
                $order[$curr_id] = $rows[$curr_id];
                unset($rows[$curr_id]);
                $indent = count($stack)-1;

                // new style of indenting by padding-left
                $order[$curr_id]["indent_cnt"] = $indent*$indentmultiplier;

                // Break up long words in the subject.
                $cut_len = $cut_max - $indent*$cut_indentfactor;
                if ($cut_len < $cut_min) $cut_len = $cut_min;
                $order[$curr_id]["subject"] =
                    wordwrap($order[$curr_id]["subject"], $cut_len, " ", TRUE);
            }
        }
        array_push($stack, $curr_id);
        $data = $tmp_rows[$curr_id];
        if(isset($data["children"])){
            if(count($data["children"])){
                $curr_id = array_shift($tmp_rows[$curr_id]["children"]);
            } else {
                unset($tmp_rows[$curr_id]);
                array_pop($stack);
                $curr_id = array_pop($stack);
            }
        } else {
            unset($tmp_rows[$curr_id]);
            array_pop($stack);
            if(count($tmp_rows[$data["parent_id"]]["children"])){
                $curr_id = array_shift($tmp_rows[$data["parent_id"]]["children"]);
            } else {
                unset($tmp_rows[$data["parent_id"]]);
                array_pop($stack);
                $curr_id = array_pop($stack);
            }
        }

    }

    return $order;
}


/*
// =========================================================================
//  This is the old recursive tree sorting routine, which was replaced by
//  the non-recursive code above.

function phorum_recursive_sort_threads($rows)
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
    global $x;

    $x++;

    if($seed>0){
        $threads[$rows[$seed]["message_id"]]=$rows[$seed];

        // new style of indenting by padding-left
        $threads[$rows[$seed]["message_id"]]["indent_cnt"]=$indent*$PHORUM['TMP']['indentmultiplier'];
        if($indent < 31) {
            $wrapnum=80-($indent*2);
        } else {
            $wrapnum=20;
        }
        $threads[$rows[$seed]["message_id"]]["subject"]=wordwrap($rows[$seed]['subject'],$wrapnum," ",1);

        $indent++;

    }
    if(isset($rows[$seed]["children"])){
        foreach($rows[$seed]["children"] as $child){
            _phorum_recursive_sort($rows, $threads, $child, $indent);
        }
    }
}
*/


?>
