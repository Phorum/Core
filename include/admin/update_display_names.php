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

include_once "./include/admin/PhorumInputForm.php";

// Find the update step that we have to run.
$step = empty($_REQUEST["step"]) ? 0 : $_REQUEST["step"];

// Find out from where the request for updating was made.
$req = empty($_REQUEST["request"]) ? 'settings' : $_REQUEST["request"];

if ($step == 0)
{
    $frm = new PhorumInputForm ("", "post", "Continue -&gt;");
    if ($req == 'integrity') {
        $frm->addbreak("Rebuild display names");
        $frm->addmessage(
            "You have requested a rebuild of the display names.
             Click \"Continue\" to start the updates."
        );
    } else {
        $frm->addbreak("Display name update");
        $frm->addmessage(
            "A change was made to the display name configuration of 
             Phorum. This requires some updates in the database.
             Click \"Continue\" to start the updates."
        );
    }
    $frm->hidden("module", "update_display_names");
    $frm->hidden("step", "1");
    $frm->show();
} 
else 
{
    // The number of users to process in a single batch.
    $batchsize = 100;

    // Quickly show an empty update progress screen.
    if ($step == 1) {
        $batch = -1;
    }
    // Process batches.
    else {
        // Find the update batch that we have to run.
        $batch = empty($_REQUEST["batch"]) ? 0 : $_REQUEST["batch"];

        // Retrieve users for this batch.
        $res = phorum_db_user_get_all($batch * $batchsize, $batchsize);

        // Handle batch.
        $updated = 0;
        while ($user = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
        {
            $updated ++;

            // We save an empty user, to make sure that the display name in the
            // database is up-to-date. This will already run needed updates in
            // case the display name changed ...
            phorum_api_user_save(array("user_id" => $user["user_id"]));

            // ... but still we run the name updates here, so inconsitencies
            // are flattened out.
            $user = phorum_api_user_get($user["user_id"]);
            phorum_db_user_display_name_updates(array(
                "user_id"      => $user["user_id"],
                "display_name" => $user["display_name"]
            ));
        }

        if ($updated == 0) {
            $frm = new PhorumInputForm ("", "post", "Finish");
            $frm->addbreak("Display names updated");
            $frm->addmessage(
                "The display names are all updated successfully."
            );
            $frm->show();
            return;
        }
    }

    // Retrieve user count.
    $user_count = isset($_REQUEST['user_count']) 
                ? (int) $_REQUEST['user_count']
                : phorum_db_user_count();

    $perc = floor((($batch+1) * $batchsize) / $user_count * 100);
    if ($perc > 100) $perc = 100; ?>

    <strong>Running display name updates.</strong><br/>
    <strong>This might take a while ...</strong><br/><br/>
    <table><tr><td>
    <div style="height:20px;width:300px; border:1px solid black">
    <div style="height:20px;width:<?php print $perc ?>%;background-color:green">
    </div></div></td><td style="padding-left:10px">
      <?php 
          $update_count = min(($batch+1)*$batchsize, $user_count);
          print "$update_count users of $user_count updated" ?>
    </td></tr></table> <?php
    $redir = phorum_admin_build_url(array('module=update_display_names',"batch=".($batch+1),'step=2','user_count='.$user_count), TRUE);
    ?>

    <script type="text/javascript">
    window.onload = function () {
        document.location.href = '<?php print addslashes($redir) ?>';
    }
    </script> <?php
}

?>
