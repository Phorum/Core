<?php

if(!defined("PHORUM")) return;

/*******************************************************************
* PHP Diff and Patch
* Copyright (C)2005 CS Wagner. <cs@kainaw.com>
* This is free software that you may use for any purpose that you
* see fit.  Just don't claim that you wrote it.
********************************************************************
* Edits made by Brian Moon <brian@phorum.org> of the Phorum Dev
* Team to fit the Phorum coding standards.  Original located at
* http://shaunwagner.com/projects/php/diff_patch.html
********************************************************************
* This file contains the diff and patch functions written for PHP.
* Unlike unix diff, these do not use files.  They use strings and
* arrays of strings.
* If you want Unix-like functionality (comparing files instead of
* strings), you'll have to write a function to open each file, read
* the data into a string, and then call diff.
********************************************************************
* diff( initial_string, changed_string, [minimum_match] )
*   initial_string: The initial string to be changed.
*   changed_string: The string containing changes.
*   minimum_match: (optional) Minimum number of characters to match.
* This will return an array of strings containing differences
* between the initial_string and the changed_string.  Each element
* of the diff array begins with an index and a - or +, meaning:
*   - This section has been removed.
*   + This section has been added.
* The optional minimum_match parameter will keep diff from matching
* up short sequences of letters.  Examples of minimum_match:
* diff("Sam", "Bart", 1) = ("0-S", "0+B","2-m", "2+rt")
* diff("Sam", "Bart", 2) = ("0-Sam", "0+Bart")
********************************************************************
* patch( initial_string, diff_array )
*   initial_string: The string to be patched.
*   diff_array: Array of differences.
* This will take a string and apply differences to it to create a
* new string containing all of the differences.
* Note: diff_array may be a single difference string, or an array
* of difference arrays.  Examples are:
* patch("Bart", "0-B") = "art"
* patch("Bart", array("0-B", "0+C")) = "Cart"
* patch("Bart", array(
*   array("0-B", "0+C"),
*   array("1-a", "1+ove"))) = "Covert"
* Using an array of diff arrays will allow you to store incremental
* changes and then apply multiple changes at once.
********************************************************************
* unpatch( final_string, diff_array )
*   This is functionally identical to patch() except that the diffs
*   are removed from the string.  This allows you to undo a patch
*   and get back the original string.
********************************************************************/

/**
* Calculate the differences between two strings.
* $a: Initial string
* $b: Changed string
* $min: (optional) minum match length
* return: array of changes
*/
function phorum_diff($a, $b, $min=3, $i=0) {
    $diff = array();
    if($a == "" && $b == "") return $diff;

	$a=str_replace(array("\r\n", "\r"), "\n", $a);
	$b=str_replace(array("\r\n", "\r"), "\n", $b);

    if($a == "") {
        array_push($diff, "$i+".$b);
        return $diff;
    }
    if($b == "") {
        array_push($diff, "$i-".$a);
        return $diff;
    }
    $match = phorum_diff_match($a, $b);
    if(strlen($match) < $min) {
        array_push($diff, "$i-".$a);
        array_push($diff, "$i+".$b);
        return $diff;
    }
    $ap = strpos($a, $match);
    $bp = strpos($b, $match);
    $diff = phorum_diff(substr($a, 0, $ap), substr($b, 0, $bp), $min, $i);
    return array_merge($diff, phorum_diff(substr($a, $ap+strlen($match)), substr($b, $bp+strlen($match)), $min, $i+$bp+strlen($match)));
}

/**
* Find the longest match between two strings.
* The time limit must be turned off for this function.
* With short strings - you won't notice.
* With long strings, you will easily timeout in 30 seconds (PHP default).
* If you know you won't timeout and do not like turning it off, just remove
* the "set_time_limit(0)" line.
*/
function phorum_diff_match($a, $b, $level="line") {
//    set_time_limit(0);
    $answer = "";
    if($level == "line" || $level == "word") {
        if($level == "line") {
            $as = explode("\n", $a);
            $bs = explode("\n", $b);
        } else {
            $as = explode(" ", $a);
            $bs = explode(" ", $b);
        }

        $last = array();
        $next = array();
        $start = -1;
        $len = 0;
        $answer = "";
        for($i = 0; $i < sizeof($as); $i++) {
            $start+= strlen($as[$i])+1;
            for($j = 0; $j < sizeof($bs); $j++) {
                if($as[$i] != $bs[$j]) {
                    if(isset($next[$j])) unset($next[$j]);
                } else {
                    if(!isset($last[$j-1]))
                        $next[$j] = strlen($bs[$j]) + 1;
                    else
                        $next[$j] = strlen($bs[$j]) + $last[$j-1] + 1;
                    if($next[$j] > $len) {
                        $len = $next[$j];
                        $answer = substr($a, $start-$len+1, $len);
                    }
                }
            }
            // If PHP ever copies pointers here instead of copying data,
            // this will fail.  They better add array_copy() if that happens.
            $last = $next;
        }
    } else {
        $m = strlen($a);
        $n = strlen($b);
        $last = array();
        $next = array();
        $len = 0;
        $answer = "";
        for($i = 0; $i < $m; $i++) {
            for($j = 0; $j < $n; $j++) {
                if($a[$i] != $b[$j]) {
                    if(isset($next[$j])) unset($next[$j]);
                } else {
                    if(!isset($last[$j-1]))
                        $next[$j] = 1;
                    else
                        $next[$j] = 1 + $last[$j-1];
                    if($next[$j] > $len) {
                        $len = $next[$j];
                        $answer = substr($a, $i-$len+1, $len);
                    }
                }
            }
            // If PHP ever copies pointers here instead of copying data,
            // this will fail.  They better add array_copy() if that happens.
            $last = $next;
        }
    }
    if($level == "line" && $answer == "") return phorum_diff_match($a, $b, "word");
    elseif($level == "word" && $answer == "") return phorum_diff_match($a, $b, "letter");
    else return $answer;
}

/**
* Patch a string with changes.
* $text: Initial string
* $diff: Change or array of Changes
* return: Patched string
*/
function phorum_patch($text, $diff) {
    if(!is_array($diff)) {
        $n = 0;
        for($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($diff, $i+1).$post;
            } elseif($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach($diff as $d) {
        $text = phorum_patch($text, $d);
    }
    return $text;
}

/**
* Undo patched changes to a string.
* $text: Final string
* $diff: Changes that were applied
* return: Unpatched string
*/
function phorum_unpatch($text, $diff) {

	$text=str_replace(array("\r\n", "\r"), "\n", $text);

    if(!is_array($diff)) {
        $n = 0;
        for($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($diff, $i+1).$post;
            } elseif($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach(array_reverse($diff) as $d) {
        $text = phorum_unpatch($text, $d);
    }
    return $text;
}

/**
* Undo patched changes to a string.
* $text: Final string
* $diff: Changes that were applied
* return: Unpatched string
*/
function phorum_unpatch_color($text, $diff) {

    $text=str_replace(array("\r\n", "\r"), "\n", $text);

    if(!is_array($diff)) {
        $n = 0;
        for($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre."[phorum removal]".substr($diff, $i+1)."[/phorum removal]".$post;
            } elseif($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                $colored_text = substr($diff, $i+1);
                return $pre."[phorum addition]".$colored_text."[/phorum addition]".substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach(array_reverse($diff) as $d) {
        $text = phorum_unpatch_color($text, $d);
    }
    return $text;
}

?>
