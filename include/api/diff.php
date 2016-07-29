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

/**
 * This script implements tools for creating text diffs.
 * Phorum uses this to show differences between edited versions
 * of forum messages.
 *
 * Based on: PHP Diff and Patch
 * **********************************************************************
 * Copyright (C)2005 CS Wagner. <cs@kainaw.com>
 * This is free software that you may use for any purpose that you
 * see fit.  Just don't claim that you wrote it.
 * **********************************************************************
 * Edits made by Brian Moon <brian@phorum.org> and
 * Maurice Makaay <maurice@phorum.org> of the Phorum Dev Team to fit
 * the Phorum coding standards. Original located at
 * http://shaunwagner.com/projects/php/diff_patch.html
 * **********************************************************************
 *
 * @package    PhorumAPI
 * @subpackage Diff
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

/**
 * Calculate the differences between two strings.
 *
 * This will return an array of strings containing differences
 * between the initial_string and the changed_string. Each element
 * of the diff array begins with an index and a - or +, meaning:
 *
 *   - This section has been removed.
 *   + This section has been added.
 *
 * The optional minimum_match parameter will keep diff from matching
 * up short sequences of letters.  Examples of minimum_match:
 *
 *   diff("Sam", "Bart", 1) = ("0-S", "0+B","2-m", "2+rt")
 *   diff("Sam", "Bart", 2) = ("0-Sam", "0+Bart")
 *
 * @param string $a
 *     The initial string.
 *
 * @param string $b
 *     The changed strings.
 *
 * @param integer $min
 *     Optional: the minimum match length. When omitted, the default
 *     minimum match length will be 3.
 *
 * @return array
 *     An array, describing the differences between the initial
 *     and the changed string.
 */
function phorum_api_diff($a, $b, $min=3, $i=0)
{
    $diff = array();

    if ($a == "" && $b == "") return $diff;

    $a = str_replace(array("\r\n", "\r"), "\n", $a);
    $b = str_replace(array("\r\n", "\r"), "\n", $b);

    if ($a == "") {
        array_push($diff, "$i+".$b);
        return $diff;
    }
    if ($b == "") {
        array_push($diff, "$i-".$a);
        return $diff;
    }

    $match = phorum_api_diff_match($a, $b);
    if (strlen($match) < $min) {
        array_push($diff, "$i-".$a);
        array_push($diff, "$i+".$b);
        return $diff;
    }

    $ap = strpos($a, $match);
    $bp = strpos($b, $match);
    $diff = phorum_api_diff(substr($a, 0, $ap), substr($b, 0, $bp), $min, $i);
    return array_merge(
        $diff,
        phorum_api_diff(
            substr($a, $ap+strlen($match)),
            substr($b, $bp+strlen($match)),
            $min, $i+$bp+strlen($match)
        )
    );
}

/**
 * Find the longest match between two strings.
 *
 * The time limit must be turned off for this function.
 * With short strings - you won't notice.
 * With long strings, you will easily timeout in 30 seconds (PHP default).
 * If you know you won't timeout and do not like turning it off, just remove
 * the "set_time_limit(0)" line.
 *
 * @param string $a
 * @param string $b
 * @param string $level
 */
function phorum_api_diff_match($a, $b, $level="line")
{
    // set_time_limit(0); No, we don't like it.
    $answer = "";
    if ($level == "line" || $level == "word")
    {
        if ($level == "line") {
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
        for ($i = 0; $i < sizeof($as); $i++) {
            $start+= strlen($as[$i])+1;
            for ($j = 0; $j < sizeof($bs); $j++) {
                if ($as[$i] != $bs[$j]) {
                    if (isset($next[$j])) unset($next[$j]);
                } else {
                    if (!isset($last[$j-1]))
                        $next[$j] = strlen($bs[$j]) + 1;
                    else
                        $next[$j] = strlen($bs[$j]) + $last[$j-1] + 1;
                    if ($next[$j] > $len) {
                        $len = $next[$j];
                        $answer = substr($a, $start-$len+1, $len);
                    }
                }
            }
            // If PHP ever copies pointers here instead of copying data,
            // this will fail.  They better add array_copy() if that happens.
            $last = $next;
        }
    }
    else
    {
        $m = strlen($a);
        $n = strlen($b);
        $last = array();
        $next = array();
        $len = 0;
        $answer = "";
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($a[$i] != $b[$j]) {
                    if (isset($next[$j])) unset($next[$j]);
                } else {
                    if (!isset($last[$j-1]))
                        $next[$j] = 1;
                    else
                        $next[$j] = 1 + $last[$j-1];
                    if ($next[$j] > $len) {
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

    if ($level == "line" && $answer == "") {
        return phorum_api_diff_match($a, $b, "word");
    } elseif ($level == "word" && $answer == "") {
        return phorum_api_diff_match($a, $b, "letter");
    } else {
        return $answer;
    }
}

/**
 * Patch a string using a diff, to create a new string containing
 * all the differences from the diff.
 *
 * Examples:
 *
 * patch("Bart", "0-B") = "art"
 * patch("Bart", array("0-B", "0+C")) = "Cart"
 * patch("Bart", array(array("0-B", "0+C"), array("1-a", "1+ove"))) = "Covert"
 *
 * @param string $text
 *     The original string.
 *
 * @param array|string $diff
 *     A single diff string, a diff array or an array of diff arrays.
 *     Using an array of diff arrays will allow you to store incremental
 *     changes and then apply multiple changes at once.
 *
 * @return string
 *     The patched string.
 */
function phorum_api_diff_patch($text, $diff)
{
    if (!is_array($diff))
    {
        $n = 0;
        for ($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if ($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($diff, $i+1).$post;
            } elseif ($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach ($diff as $d) {
        $text = phorum_api_diff_patch($text, $d);
    }
    return $text;
}

/**
 * Undo patched changes to a string.
 *
 * This is functionally identical to patch() except that the diffs
 * are removed from the string. This allows you to undo a patch
 * and get back the original string.
 *
 * @param string $text
 * @param array|string $diff
 * @return string
 */
function phorum_api_diff_unpatch($text, $diff)
{
    $text = str_replace(array("\r\n", "\r"), "\n", $text);

    if (!is_array($diff)) {
        $n = 0;
        for ($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if ($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($diff, $i+1).$post;
            } elseif ($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre.substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach (array_reverse($diff) as $d) {
        $text = phorum_api_diff_unpatch($text, $d);
    }
    return $text;
}

/**
 * Undo patched changes to a string.
 *
 * @param string $text
 * @param array|string $diff
 * @return string
 */
function phorum_api_diff_unpatch_color($text, $diff)
{
    $text = str_replace(array("\r\n", "\r"), "\n", $text);

    if (!is_array($diff))
    {
        $n = 0;
        for ($i=0; $i<strlen($diff); $i++) {
            $c = substr($diff, $i, 1);
            if ($c == "-") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                return $pre."[phorum removal]".substr($diff, $i+1)."[/phorum removal]".$post;
            } elseif ($c == "+") {
                $n = substr($diff, 0, $i);
                $pre = substr($text, 0, $n);
                $post = substr($text, $n);
                $colored_text = substr($diff, $i+1);
                return $pre."[phorum addition]".$colored_text."[/phorum addition]".substr($post, strlen($diff)-$i-1);
            }
        }
        return $text;
    }
    foreach (array_reverse($diff) as $d) {
        $text = phorum_api_diff_unpatch_color($text, $d);
    }
    return $text;
}

?>
