<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

                    $curpage = ($offset/100);
                    $threads = phorum_db_get_thread_list($curpage);

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

        if(empty($PHORUM['DBCONFIG']['empty_search_table'])) {
            $ret = phorum_db_rebuild_search_data();
    
    
            $okmsg .= "Searchdata successfully rebuilt.<br />";
        } else {
            
            $okmsg .="<strong>Flag &quot;empty_search_table&quot; set in db configuration. Search table is not going to be rebuild with that.</strong>";
            
        }

    }

    if(isset($_POST['rebuild_userposts']) && !empty($_POST['rebuild_userposts'])) {

        $ret = phorum_db_rebuild_user_posts();


        $okmsg .= "Postcounts for users updated.<br />";

    }

    if (isset($_POST['rebuild_display_names']) && !empty($_POST['rebuild_display_names'])) {
        $redir_url = phorum_admin_build_url(array('module=update_display_names','request=integrity'), TRUE);
        phorum_redirect_by_url($redir_url);
        exit();
    }

    if(isset($_POST['rebuild_forumpaths']) && !empty($_POST['rebuild_forumpaths'])) {
        $forums = phorum_admin_build_path_array();
        unset($forums[0]);

        foreach($forums as $fid => $forumpath)
        {
            $update_forum = array('forum_id'=>$fid, 'forum_path'=>$forumpath);
            phorum_db_update_forum($update_forum);
        }

        $okmsg .= "Forum paths successfully rebuilt.<br />";
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
$row=$frm->addrow( "Rebuild forum statistics", $frm->checkbox('rebuild_forumstats',1,"Yes"));
$frm->addhelp($row, "Rebuild forum statistics", "Phorum keeps the count of messages and threads in a forum and also the date of the last post in some variables in the forums-table. If you manually delete messages from or manually add messages to the forum, this data will usually be out of sync. This leads to wrong paging on the list of messages and wrong counts on the index-page. Therefore run this part to update the forumstatistics this way." );


$row=$frm->addrow( "Rebuild thread info meta data", $frm->checkbox('rebuild_metadata',1,"Yes"));
$frm->addhelp($row, "Rebuild message meta-data", "Phorum stores meta-data about the thread in the first message of the thread. If you manually delete messages from a thread or in case of errors, this data could be out of sync, leading to wrong paging and new-flag information about a thread. Run this part to rebuild the meta-data for all threads in all forums.<br /><strong>ATTENTION:</strong>This can take a long time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

$row=$frm->addrow( "Rebuild search data", $frm->checkbox('rebuild_searchdata',1,"Yes"));
$frm->addhelp($row, "Rebuild search data", "Phorum stores all posts a second time in a separate table for avoiding concurrency issues and building fulltext indexes.<br />In case of manual changes to the messages or crashing servers this data can be outdated or broken, therefore this option rebuilds the search-table from the original messages.<br /><strong>ATTENTION:</strong>This can take a long time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

$row=$frm->addrow( "Rebuild forum paths", $frm->checkbox('rebuild_forumpaths',1,"Yes"));
$frm->addhelp($row, "Rebuild forum paths", "Phorum stores the path from the root-folder to the forum in an array with the forum-data. I case of large changes with virtual roots or moving around forums and folders these can get off and show a wrong breadcrumbs navigation and similar problems. You can rebuild these cached forum-paths for all forums and folders with selecting this option." );

$row=$frm->addrow( "Rebuild user post counts", $frm->checkbox('rebuild_userposts',1,"Yes"));
$frm->addhelp($row, "Rebuild user post counts", "Phorum stores the numbers of posts a user has made in the user-data.<br />In case of manual changes to the database like deleting messages manually, this data can be outdated or broken, therefore this option rebuilds the post-counts from the existing messages for the user-id.<br /><strong>ATTENTION:</strong>This can take a some time with lots of messages and eventually lead to timeouts if your execution timeout is too low." );

$row=$frm->addrow( "Rebuild display names", $frm->checkbox('rebuild_display_names', 1 ,"Yes"));
$frm->addhelp($row, "Rebuild display names", "Phorum stores the name to display for users redundantly in the database at several places. This is done for speeding up Phorum (because this way, Phorum does not need to retrieve the display name separately when showing for example a forum message list or a PM inbox list). The administrator can choose whether to use the username or the user's real name as the name to display.<br/><br/>If for some reason, the display names get out of sync or if you installed a module that modifies the display name (in which case you need to reprocess the display name for all users), you can rebuild all real name data using this option.");

$frm->show();

?>

