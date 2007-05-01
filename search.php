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
define('phorum_page','search');

include_once("./common.php");
include_once("./include/forum_functions.php");

if(!phorum_check_read_common()) {
  return;
}

include_once("./include/format_functions.php");
// set all our URL's
phorum_build_common_urls();

$PHORUM["DATA"]["SEARCH"]["noresults"] = false;
$PHORUM["DATA"]["SEARCH"]["showresults"] = false;
$PHORUM["DATA"]["SEARCH"]["safe_search"] = "";
$PHORUM["DATA"]["SEARCH"]["safe_author"] = "";

function phorum_search_check_valid_vars() {
    $PHORUM=$GLOBALS['PHORUM'];
    $retval=true;
    // these are valid values for some args
    $valid_match_types=array("ALL","ANY","PHRASE","USER_ID");
    $valid_match_forum=array("THISONE","ALL");

    if(!in_array($PHORUM["args"]["match_type"],$valid_match_types)) {
        $retval=false;
    } elseif(!is_numeric($PHORUM["args"]["match_dates"])) {
        $retval=false;
    }

    return $retval;
}

if((!empty($_GET["search"]) || !empty($_GET["author"])) && !isset($PHORUM["args"]["page"])){

    if(!empty($_GET["match_forum"])){
        if(is_array($_GET["match_forum"])){
            foreach($_GET["match_forum"] as $forum_id){
                if(is_numeric($forum_id)){
                    $match_forum[] = $forum_id;
                } elseif($forum_id=="ALL") {
                    $match_forum="ALL";
                    break;
                }
            }

            if(is_array($match_forum)){
                $match_forum = implode(",", $match_forum);
            }

        } else {
            if(is_numeric($forum_id)){
                $match_forum = $forum_id;
            } elseif($forum_id=="ALL") {
                $match_forum="ALL";
            }
        }
    }

    $search_url = @phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($_GET["search"]), "author=" . urlencode($_GET["author"]), "page=1", "match_type=" . urlencode($_GET['match_type']), "match_dates=" . urlencode($_GET['match_dates']), "match_forum=" . urlencode($match_forum), "match_threads=" . urlencode($_GET['match_threads']));

    if (isset($PHORUM["skip_intermediate_search_page"]) && $PHORUM["skip_intermediate_search_page"]) {
        phorum_redirect_by_url($search_url);
        exit(0);
    } else {
        $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["SearchRunning"];
        $PHORUM["DATA"]["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToSearch"];
        $PHORUM["DATA"]["URL"]["REDIRECT"]=$search_url;
        $PHORUM["DATA"]["REDIRECT_TIME"]=1;
        include phorum_get_template("header");
        phorum_hook("after_header");
        include phorum_get_template("message");
        phorum_hook("before_footer");
        include phorum_get_template("footer");
        return;
    }
}

if(isset($PHORUM["args"]["search"])){
    $phorum_search = $PHORUM["args"]["search"];
} else {
    $phorum_search = "";
}

if(isset($PHORUM["args"]["author"])){
    $phorum_author = $PHORUM["args"]["author"];
} else {
    $phorum_author = "";
}

if(!isset($PHORUM["args"]["match_type"])) $PHORUM["args"]["match_type"]="ALL";
if(!isset($PHORUM["args"]["match_dates"])) $PHORUM["args"]["match_dates"]="30";
if(!isset($PHORUM["args"]["match_forum"])) $PHORUM["args"]["match_forum"]="ALL";
if(!isset($PHORUM["args"]["match_threads"])) $PHORUM["args"]["match_threads"]=true;

settype($PHORUM["args"]["match_threads"], "bool");

if(!phorum_search_check_valid_vars()) {
    $redir_url=phorum_get_url(PHORUM_LIST_URL);
    phorum_redirect_by_url($redir_url);
}

// Check what forums the current user can read.
$allowed_forums = phorum_user_access_list(PHORUM_USER_ALLOW_READ);

// setup some stuff based on the url passed
if(!empty($phorum_search) || !empty($phorum_author)){

    $PHORUM["DATA"]["SEARCH"]["safe_search"] = htmlspecialchars($phorum_search);
    $PHORUM["DATA"]["SEARCH"]["safe_author"] = htmlspecialchars($phorum_author);

    include_once("./include/format_functions.php");

    if(isset($PHORUM["args"]["page"])){
        $PHORUM["args"]["page"] = (int)$PHORUM["args"]["page"];
    } else {
        $PHORUM["args"]["page"] = 1;
    }

    $offset = (empty($PHORUM["args"]["page"])) ? 0 : $PHORUM["args"]["page"]-1;

    if($offset < 0)
        $offset = 0;

    if(empty($PHORUM["list_length"])) $PHORUM["list_length"]=30;

    $start = ($offset * $PHORUM["list_length"]);

    settype($PHORUM["args"]["match_dates"], "int");

    // setup the needed data for an alternate search backend
    // needs to get fed by posted messages
    $search_request_data = array(
    'search' => $phorum_search,
    'author' => $phorum_author,
    'offset' => $start,
    'length' => $PHORUM["list_length"],
    'match_type'  => $PHORUM["args"]["match_type"],
    'match_dates' => $PHORUM["args"]["match_dates"],
    'match_forum' => $PHORUM["args"]["match_forum"],
    'match_threads' => $PHORUM["args"]["match_threads"],
    'results' => array(),
    'raw_body' => 0,
    'totals' => 0,
    'continue' => 1
    );

    $search_request_data = phorum_hook('search_action',$search_request_data);

    // only continue if our hook was either not run or didn't return a stop request
    if($search_request_data['continue']) {
        $arr = phorum_db_search($phorum_search, $phorum_author, $PHORUM["args"]["match_threads"], $offset, $PHORUM["list_length"], $PHORUM["args"]["match_type"], $PHORUM["args"]["match_dates"], $PHORUM["args"]["match_forum"]);
        $raw_body = 0;
    } else {
        $arr['rows'] = $search_request_data['results'];
        $arr['count']= $search_request_data['totals'];
        $raw_body = $search_request_data['raw_body'];
    }

    if(count($arr["rows"])){

        $match_number = $start + 1;

        $forums = phorum_db_get_forums(0, NULL, $PHORUM["vroot"]);

        foreach($arr["rows"] as $key => $row){
            $arr["rows"][$key]["number"] = $match_number;

            $arr["rows"][$key]["URL"]["READ"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $row["forum_id"], $row["thread"], $row["message_id"]);

            // strip HTML & BB Code
            if(!$raw_body) {
                $body = phorum_strip_body($arr["rows"][$key]["body"]);
                $arr["rows"][$key]["short_body"] = substr($body, 0, 400);
                $arr["rows"][$key]["short_body"] = htmlspecialchars($arr["rows"][$key]["short_body"]);
            }
            $arr["rows"][$key]["raw_datestamp"] = $row["datestamp"];
            $arr["rows"][$key]["datestamp"] = phorum_relative_date($row["datestamp"]);
            $arr["rows"][$key]["author"] = htmlspecialchars($row["author"]);

            $forum_ids[$row["forum_id"]] = $row["forum_id"];

            $match_number++;
        }

        foreach($arr["rows"] as $key => $row){
            $arr["rows"][$key]["URL"]["LIST"] = phorum_get_url(PHORUM_LIST_URL, $row["forum_id"]);
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
                "url" => phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "author=" . urlencode($phorum_author), "page=$pageno", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum=".urlencode($PHORUM['args']['match_forum']))
                );
        }

        $PHORUM["DATA"]["CURRENTPAGE"] = $page;
           $PHORUM["DATA"]["TOTALPAGES"] = $pages;

        if ($page_start > 1){
            $PHORUM["DATA"]["URL"]["FIRSTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "author=" . urlencode($_GET["author"]), "page=1", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum=".urlencode($PHORUM['args']['match_forum']));
        }

        if ($pageno < $pages){
            $PHORUM["DATA"]["URL"]["LASTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "author=" . urlencode($_GET["author"]), "page=$pages", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum=".urlencode($PHORUM['args']['match_forum']));
        }

        if ($pages > $page){
            $nextpage = $page + 1;
            $PHORUM["DATA"]["URL"]["NEXTPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "author=" . urlencode($phorum_author), "page=$nextpage", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum=".urlencode($PHORUM['args']['match_forum']));
        }
        if ($page > 1){
            $prevpage = $page-1;
            $PHORUM["DATA"]["URL"]["PREVPAGE"] = phorum_get_url(PHORUM_SEARCH_URL, "search=" . urlencode($phorum_search), "author=" . urlencode($phorum_author), "page=$prevpage", "match_type={$PHORUM['args']['match_type']}", "match_dates={$PHORUM['args']['match_dates']}", "match_forum=".urlencode($PHORUM['args']['match_forum']));
        }



        $arr["rows"] = phorum_hook("search", $arr["rows"]);
        $arr["rows"] = phorum_format_messages($arr["rows"]);
        $PHORUM["DATA"]["MATCHES"] = $arr["rows"];

    }else{
        $PHORUM["DATA"]["SEARCH"]["noresults"] = true;
        $PHORUM["DATA"]["FOCUS_TO_ID"] = 'phorum_search_message';
    }

} else {
    // Set cursor focus to message search entry.
    $PHORUM["DATA"]["FOCUS_TO_ID"] = 'phorum_search_message';
}

$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_SEARCH_ACTION_URL);
$PHORUM["DATA"]["SEARCH"]["forum_id"] = $PHORUM["forum_id"];
$PHORUM["DATA"]["SEARCH"]["match_type"] = $PHORUM["args"]["match_type"];
$PHORUM["DATA"]["SEARCH"]["match_dates"] = $PHORUM["args"]["match_dates"];
$PHORUM["DATA"]["SEARCH"]["match_forum"] = $PHORUM["args"]["match_forum"];
$PHORUM["DATA"]["SEARCH"]["match_threads"] = (int)$PHORUM["args"]["match_threads"];

$PHORUM["DATA"]["SEARCH"]["forum_list"] = phorum_build_forum_list();
if(isset($PHORUM["args"]["match_forum"])){
    $match_forum = explode(",", $PHORUM["args"]["match_forum"]);
    foreach($PHORUM["DATA"]["SEARCH"]["forum_list"] as $key=>$list_item){
        if(in_array($list_item["forum_id"], $match_forum)){
            $PHORUM["DATA"]["SEARCH"]["forum_list"][$key]["selected"] = true;
        }
    }
}

$PHORUM["DATA"]["SEARCH"]["forum_list_length"] = min(10, count($PHORUM["DATA"]["SEARCH"]["forum_list"])+1);

if ($PHORUM["args"]["match_type"] == "USER_ID")
{
    $search_user = phorum_user_get((int)$phorum_search);
    $search_name = $search_user == NULL ? $PHORUM["DATA"]["LANG"]["AnonymousUser"] : $search_user["username"];
    $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["SearchAllPosts"];
    $PHORUM["DATA"]["HTML_TITLE"] = $PHORUM["DATA"]["LANG"]["SearchAllPosts"];
    $PHORUM["DATA"]["DESCRIPTION"] = str_replace("%user%", htmlspecialchars($search_name), $PHORUM["DATA"]["LANG"]["SearchAllPostsHelp"]);
} else {
    $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["Search"];

    $PHORUM["DATA"]["HTML_TITLE"] = $PHORUM["html_title"].PHORUM_SEPARATOR.$PHORUM["DATA"]["LANG"]["Search"];
    if(!empty($phorum_search)){
        $PHORUM["DATA"]["HTML_TITLE"] .= " - ".$phorum_search;
    }

    $PHORUM["DATA"]["DESCRIPTION"] = $PHORUM["DATA"]["LANG"]["SearchHelp"];
}

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("search");
phorum_hook("before_footer");
include phorum_get_template("footer");

?>
