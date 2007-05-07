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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if ( !defined( "PHORUM_ADMIN" ) ) return;

$error = "";

if ( count( $_POST ) ) {
    $okmsg = "";

    if(isset($_POST['rebuild_forumstats']) && !empty($_POST['rebuild_forumstats'])) {
        // we need to rebuild the forumstats
        $forums = phorum_db_get_forums();

        // shouldn't be needed but just in case ...
        $old_forum_id = $PHORUM['forum_id'];

        $forums_updated=0;
        foreach ($forums as $fid => $fdata) {

            if($fdata['folder_flag'] == 0) {

                $PHORUM['forum_id'] = $fid;

                phorum_db_update_forum_stats(true);

                $forums_updated++;
            }
        }

        $PHORUM['forum_id'] = $old_forum_id;

        $okmsg .= "$forums_updated forum(s) updated.<br />";

    }

    if(isset($_POST['rebuild_metadata']) && !empty($_POST['rebuild_metadata'])) {
        include_once './include/thread_info.php';

        // we need to rebuild the forumstats
        $forums = phorum_db_get_forums();

        // shouldn't be needed but just in case ...
        $old_forum_id = $PHORUM['forum_id'];

        // initialize some variables
        $PHORUM["float_to_top"] = 0;
        $PHORUM["threaded_list"] = 0;
        $PHORUM['list_length_flat'] = 100;

        $threads_updated=0;
        foreach ($forums as $fid => $fdata) {


            if($fdata['folder_flag'] == 0) {

                $PHORUM['forum_id'] = $fid;
                $PHORUM['vroot'] = $fdata['vroot'];

                $offset = 0;

                while($offset < $fdata['thread_count']) {

                    $threads = phorum_db_get_thread_list($offset);

                    $num_threads = count($threads);

                    if($num_threads) {

                        foreach($threads as $tid => $tdata) {
                            phorum_update_thread_info($tid);
                        }

                        $threads_updated+=$num_threads;
                        // if we got less messages, we can jump out - last page hopefully
                        if($num_threads < 100) {
                            break;
                        }
                    }

                    $offset+=100;
                }



                $forums_updated++;
            }
        }

        $PHORUM['forum_id'] = $old_forum_id;

        $okmsg .= "$threads_updated threads updated.<br />";

    }



    if(isset($_POST['rebuild_searchdata']) && !empty($_POST['rebuild_searchdata'])) {

        $ret = phorum_db_rebuild_search_data();


        $okmsg .= "Searchdata successfully rebuilt.<br />";

    }

    if(isset($_POST['rebuild_userposts']) && !empty($_POST['rebuild_userposts'])) {

        $ret = phorum_db_rebuild_user_posts();


        $okmsg .= "Postcounts for users updated.<br />";

    }

    if (isset($_POST['rebuild_display_names']) && !empty($_POST['rebuild_display_names'])) {
        phorum_redirect_by_url($PHORUM['admin_http_path'] . "?module=update_display_names&request=integrity");
        exit();
    }
}

if ( $error ) {
    phorum_admin_error( $error );
} elseif( $okmsg ) {
    phorum_admin_okmsg ( $okmsg);
}

include_once "./include/admin/PhorumInputForm.php";

$frm = &new PhorumInputForm ( "", "post" );
$frm->hidden( "module", "rebuild" );
$frm->addbreak( "Rebuild parameters" );
$row=$frm->addrow( "Rebuild forumstatistics", $frm->checkbox('rebuild_forumstats',1,"rebuild forumstats"));
$frm->addhelp($row, "Rebuild forumstatistics", "Phorum keeps the count of messages and threads in a forum and also the date of the last post in some variables in the forums-table. If you manually delete messages from or manually add messages to the forum, this data will usually be out of sync. This leads to wrong paging on the list of messages and wrong counts on the index-page. Therefore run this part to update the forumstatistics this way." );


$row=$frm->addrow( "Rebuild message meta-data", $frm->checkbox('rebuild_metadata',1,"rebuild metadata"));
$frm->addhelp($row, "Rebuild message meta-data", "Phorum stores meta-data about the thread in the meta-field of the first message in a thread. If you manually delete messages from a thread or in case of errors, this data could be out of sync, leading to wrong paging and new-flag information about a thread. Run this part to rebuild the meta-data for all threads in all forums.<br /><strong>ATTENTION:</strong>This can take a long time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

$row=$frm->addrow( "Rebuild search-data", $frm->checkbox('rebuild_searchdata',1,"rebuild searchdata"));
$frm->addhelp($row, "Rebuild search-data", "Phorum stores all posts a second time in a separate table for avoiding concurrency issues and building fulltext indexes.<br />In case of manual changes to the messages or crashing servers this data can be outdated or broken, therefore this option rebuilds the search-table from the original messages.<br /><strong>ATTENTION:</strong>This can take a long time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

$row=$frm->addrow( "Rebuild user post-counts", $frm->checkbox('rebuild_userposts',1,"rebuild userposts"));
$frm->addhelp($row, "Rebuild user post-counts", "Phorum stores the numbers of posts a user has made in the user-data.<br />In case of manual changes to the database like deleting messages manually, this data can be outdated or broken, therefore this option rebuilds the post-counts from the existing messages for the user-id.<br /><strong>ATTENTION:</strong>This can take a some time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

// TODO add help
$row=$frm->addrow( "Rebuild display names", $frm->checkbox('rebuild_display_names', 1 ,"rebuild display names"));

$frm->show();

?>

