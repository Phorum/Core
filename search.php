<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2003  Phorum Development Team                              //
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
define('phorum_page','search');

include_once("./common.php");

if(!phorum_check_read_common()) {
  return;
}

include_once("./include/format_functions.php");

$PHORUM["DATA"]["SEARCH"]["noresults"] = false;
$PHORUM["DATA"]["SEARCH"]["showresults"] = false;
$PHORUM["DATA"]["SEARCH"]["safe_search"] = "";

function phorum_search_check_valid_vars() {
    $PHORUM=$GLOBALS['PHORUM'];
    $retval=true;
    // these are valid values for some args
    $valid_match_types=array("ALL","ANY","PHRASE");
    $valid_match_forum=array("THISONE","ALL");

    if(!in_array($PHORUM["args"]["match_type"],$valid_match_types)) {
        $retval=false;
    } elseif(!in_array($PHORUM["args"]["match_forum"],$valid_match_forum)) {
        $retval=false;
    } elseif(!is_numeric($PHORUM["args"]["match_dates"])) {
        $retval=false;
    } elseif(!is_numeric($PHORUM["args"]["author"]) || !is_numeric($PHORUM["args"]["subject"]) || !is_numeric($PHORUM["args"]["body"])) {
        // these were the just numeric args
        $retval=false;
    }

    return $retval;
}




if(!empty($_GET["search"]) && !isset($PHORUM["args"]["page"])){
    $search_url = @phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($_GET["search"]), "page=1", "match_type=" . urlencode($_GET['match_type']), "match_dates=" . urlencode($_GET['match_dates']), "match_forum=" . urlencode($_GET['match_forum']), "body=" . urlencode($_GET['body']), "author=" . urlencode($_GET['author']), "subject=" . urlencode($_GET['subject']));
    phorum_redirect_by_url($search_url);
    exit();
}

if(isset($PHORUM["args"]["search"])){
    $phorum_search = $PHORUM["args"]["search"];
}

if(!isset($PHORUM["args"]["match_type"])) $PHORUM["args"]["match_type"]="ALL";
if(!isset($PHORUM["args"]["match_dates"])) $PHORUM["args"]["match_dates"]="30";
if(!isset($PHORUM["args"]["match_forum"])) $PHORUM["args"]["match_forum"]="ALL";
if(!isset($PHORUM["args"]["body"]) || $PHORUM['args']['body'] == "") $PHORUM["args"]["body"] = (isset($phorum_search)) ? 0: 1;
if(!isset($PHORUM["args"]["author"]) || $PHORUM['args']['author'] == "") $PHORUM["args"]["author"] = (isset($phorum_search)) ? 0: 1;
if(!isset($PHORUM["args"]["subject"]) || $PHORUM['args']['subject'] == "") $PHORUM["args"]["subject"] = (isset($phorum_search)) ? 0: 1;

if(!phorum_search_check_valid_vars()) {
    $redir_url=phorum_get_url(PHORUM_LIST_URL);
    phorum_redirect_by_url($redir_url);
    exit();
}

// setup some stuff based on the url passed
if(!empty($phorum_search)){

    $PHORUM["DATA"]["SEARCH"]["safe_search"] = htmlspecialchars($phorum_search);

    $offset = (empty($PHORUM["args"]["page"])) ? 0 : $PHORUM["args"]["page"]-1;

    if(empty($PHORUM["list_length"])) $PHORUM["list_length"]=30;

    $start = ($offset * $PHORUM["list_length"]);

    settype($PHORUM["args"]["match_dates"], "int");

    $arr = phorum_db_search($phorum_search, $offset, $PHORUM["list_length"], $PHORUM["args"]["match_type"], $PHORUM["args"]["match_dates"], $PHORUM["args"]["match_forum"], $PHORUM["args"]["body"], $PHORUM["args"]["author"], $PHORUM["args"]["subject"]);

    if(count($arr["rows"])){

        $match_number = $start + 1;
        $forum_ids = array();

        foreach($arr["rows"] as $key => $row){
            $arr["rows"][$key]["number"] = $match_number;

            // For announcements, put the current forum_id in the record.
            // Else the message cannot be read (Phorum will redirect the
            // user back to the index page if the forum id is not set).
            if ($row["forum_id"] == 0) $row["forum_id"] = $PHORUM["forum_id"];

            $arr["rows"][$key]["url"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $row["forum_id"], $row["thread"], $row["message_id"]);

            // strip HTML & BB Code
            $body = strip_body($arr["rows"][$key]["body"]);
            $arr["rows"][$key]["short_body"] = substr($body, 0, 200);
            $arr["rows"][$key]["datestamp"] = phorum_date($PHORUM["short_date"], $row["datestamp"]);
            $arr["rows"][$key]["author"] = htmlspecialchars($row["author"]);

            $forum_ids[$row["forum_id"]] = $row["forum_id"];

            $match_number++;
        }

        $forums = phorum_db_get_forums($forum_ids);

        foreach($arr["rows"] as $key => $row){
            $arr["rows"][$key]["forum_url"] = phorum_get_url(PHORUM_LIST_URL, $row["forum_id"]);

            $arr["rows"][$key]["forum_name"] = $forums[$row["forum_id"]]["name"];
        }

        $PHORUM["DATA"]["RANGE_START"] = $start + 1;
        $PHORUM["DATA"]["RANGE_END"] = $start + count($arr["rows"]);
        $PHORUM["DATA"]["TOTAL"] = $arr["count"];
        $PHORUM["DATA"]["SEARCH"]["showresults"] = true;
        // figure out paging
        $pages = ceil($arr["count"] / $PHORUM["list_length"]);
        $page = $offset + 1;

        if ($pages <= 5){
            $page_start = 1;
        }elseif ($pages - $page < 2){
            $page_start = $pages-4;
        }elseif ($pages > 5 && $page > 3){
            $page_start = $page-2;
        }else{
            $page_start = 1;
        }

        $pageno = 1;
        for($x = 0;$x < 5 && $x < $pages;$x++){
            $pageno = $x + $page_start;
            $PHORUM["DATA"]["PAGES"][] = array("pageno" => $pageno,
                "url" => phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "page=$pageno", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum={$PHORUM['args']['match_forum']}", "body={$PHORUM['args']['body']}", "author={$PHORUM['args']['author']}", "subject={$PHORUM['args']['subject']}")
                );
        }

        $PHORUM["DATA"]["CURRENTPAGE"] = $page;
        $PHORUM["DATA"]["TOTALPAGES"] = $pages;

        if ($page_start > 1){
            $PHORUM["DATA"]["URL"]["FIRSTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "page=1", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum={$PHORUM['args']['match_forum']}", "body={$PHORUM['args']['body']}", "author={$PHORUM['args']['author']}", "subject={$PHORUM['args']['subject']}");
        }

        if ($pageno < $pages){
            $PHORUM["DATA"]["URL"]["LASTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "page=$pages", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum={$PHORUM['args']['match_forum']}", "body={$PHORUM['args']['body']}", "author={$PHORUM['args']['author']}", "subject={$PHORUM['args']['subject']}");
        }

        if ($pages > $page){
            $nextpage = $page + 1;
            $PHORUM["DATA"]["URL"]["NEXTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "page=$nextpage", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum={$PHORUM['args']['match_forum']}", "body={$PHORUM['args']['body']}", "author={$PHORUM['args']['author']}", "subject={$PHORUM['args']['subject']}");
        }
        if ($page > 1){
            $prevpage = $page-1;
            $PHORUM["DATA"]["URL"]["PREVPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "page=$prevpage", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum={$PHORUM['args']['match_forum']}", "body={$PHORUM['args']['body']}", "author={$PHORUM['args']['author']}", "subject={$PHORUM['args']['subject']}");
        }


        $arr["rows"] = phorum_hook("search", $arr["rows"]);
        $arr["rows"] = phorum_format_messages($arr["rows"]);

        $PHORUM["DATA"]["MATCHES"] = $arr["rows"];

    }else{
        $PHORUM["DATA"]["SEARCH"]["noresults"] = true;
    }
}

// set all our URL's
phorum_build_common_urls();

$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_SEARCH_ACTION_URL);
$PHORUM["DATA"]["SEARCH"]["forum_id"] = $PHORUM["forum_id"];
$PHORUM["DATA"]["SEARCH"]["body"] = $PHORUM["args"]["body"];
$PHORUM["DATA"]["SEARCH"]["author"] = $PHORUM["args"]["author"];
$PHORUM["DATA"]["SEARCH"]["subject"] = $PHORUM["args"]["subject"];
$PHORUM["DATA"]["SEARCH"]["match_type"] = $PHORUM["args"]["match_type"];
$PHORUM["DATA"]["SEARCH"]["match_dates"] = $PHORUM["args"]["match_dates"];
$PHORUM["DATA"]["SEARCH"]["match_forum"] = $PHORUM["args"]["match_forum"];
$PHORUM["DATA"]["SEARCH"]["allow_match_one_forum"] = $PHORUM["forum_id"];

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("search");
phorum_hook("before_footer");
include phorum_get_template("footer");

?>
