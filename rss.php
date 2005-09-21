<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2005  Phorum Development Team                              //
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
//   July 19 Fixed by Dagon, Date format and Location default                 //
////////////////////////////////////////////////////////////////////////////////

define('phorum_page', 'rss');

include_once("./common.php");
include_once("./include/format_functions.php");

// somehow we got to a folder
if(!empty($PHORUM["folder_flag"])){
    exit();
}


$PHORUM["threaded_list"]=false;
$PHORUM["float_to_top"]=false;

// get the thread set started
$rows = array();

$thread = (isset($PHORUM["args"][1])) ? (int)$PHORUM["args"][1] : 0;
$rows = phorum_db_get_recent_messages(30, $PHORUM["forum_id"], $thread);
$forums = phorum_db_get_forums();

unset($rows["users"]);

$pub_date=0;
foreach($rows as $key => $row){

    if(!$PHORUM["forum_id"]){
        $row["subject"]="[".$forums[$row["forum_id"]]["name"]."] ".$row["subject"];
    }

    $items[]=array(
        "pub_date" => date("r",$row["datestamp"]),
        "url" => phorum_get_url(PHORUM_FOREIGN_READ_URL, $row["forum_id"], $row["thread"], $row["message_id"]),
        "headline" => $row["subject"],
        "description" => strip_tags($row["body"]),
        "author" => $row["author"],
        "category" => $forums[$row["forum_id"]]["name"]
    );


    $pub_date = max($row["datestamp"], $pub_date);

}

if (!$PHORUM['locale']) $PHORUM['locale'] ="en"; //if locale not set make it 'en'

if($PHORUM["forum_id"]){
    $url = phorum_get_url(PHORUM_LIST_URL);
    $name = $PHORUM["name"];
    $description = strip_tags($PHORUM["description"]);
} else {
    $url = phorum_get_url(PHORUM_INDEX_URL);
    $name = $PHORUM["title"];
    $description = "";
}

$channel = array(

    "name" => $name,
    "url" => $url,
    "description" => $description,
    "pub_date" => date("r",$pub_date),
    "language" => $PHORUM['locale']

);


create_rss_feed($channel, $items);

function create_rss_feed($channel, $items)
{

    if(empty($items)){
        return;
    }

    $data ="<?xml version=\"1.0\" ?>\n";
    $data.="<rss version=\"2.0\">\n";
    $data.="  <channel>\n";
    $data.="    <title>$channel[name]</title>\n";
    $data.="    <link>$channel[url]</link>\n";
    $data.="    <description><![CDATA[$channel[description]]]></description>\n";
    $data.="    <language>$channel[language]</language>\n";

    $data.="    <pubDate>$channel[pub_date]</pubDate>\n";
    $data.="    <lastBuildDate>$channel[pub_date]</lastBuildDate>\n";
    $data.="    <category>$channel[name]</category>\n";
    $data.="    <generator>Phorum ".PHORUM."</generator>\n";

    $data.="    <ttl>600</ttl>\n";

    foreach($items as $item){
        $data.="    <item>\n";
        $data.="      <title>".htmlspecialchars($item['headline'])."</title>\n";
        $data.="      <link>$item[url]</link>\n";
        $data.="      <author>".htmlspecialchars($item['author'])."</author>\n";
        $data.="      <description><![CDATA[".htmlspecialchars($item['description'])."]]></description>\n";
        $data.="      <category>".htmlspecialchars($item['category'])."</category>\n";
        $data.="      <guid isPermaLink=\"true\">$item[url]</guid>\n";
        $data.="      <pubDate>$item[pub_date]</pubDate>\n";
        $data.="    </item>\n";
    }

    $data.="  </channel>\n";
    $data.="</rss>\n";

    header("Content-Type: text/xml");

    echo $data;
}


?>
