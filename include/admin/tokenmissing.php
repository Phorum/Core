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
////////////////////////////////////////////////////////////////////////////////

    // Don't allow this page to be loaded directly.
    if(!defined("PHORUM_ADMIN")) exit();

    if(count($_POST)) {
        if(!empty($_POST['phorum_admin_token']) && 
            $_POST['phorum_admin_token'] == $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'] &&
            time()-PHORUM_ADMIN_TOKEN_TIMEOUT < $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token_time']
           ) {

               if(!empty($_POST['cancel'])) {
                    
                   $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'] = "";

                   $tmp_user = array(
             			'user_id'=>$GLOBALS["PHORUM"]["user"]['user_id'],
                		'settings_data'=>$GLOBALS["PHORUM"]["user"]['settings_data']
                   );
                   phorum_api_user_save($tmp_user);  
                                    
                   phorum_redirect_by_url($PHORUM['http_path']);
                   
               } elseif(!empty($_POST['continue'])) {
                   
                   if(!empty($_POST['target'])) {
                       $url = phorum_admin_build_url($_POST['target'], TRUE);
                   } else {
                       $url = phorum_admin_build_url('', TRUE);
                   }
                
                   phorum_redirect_by_url($url);
               }
               exit();
           }
    }

    // We have no token or our token expired.
    // Generate a fresh token.
    $admin_token_time = time();
    $admin_token = phorum_generate_data_signature(
        $PHORUM['user']['user_id'].
        microtime().
        $PHORUM['user']['username'].
        $PHORUM['user']['sessid_st']
    );
    phorum_api_user_save_settings(array(
        'admin_token_time' => $admin_token_time,
        'admin_token'      => $admin_token
    ));
    $PHORUM['admin_token'] = $admin_token;

    // If there are no POST or GET variables in the request, besides
    // "module" and/or "phorum_admin_token", then we can safely load
    // the requested admin page, without bugging the admin about the
    // token timeout.
    $post = $_POST; unset($post['module']); unset($post['phorum_admin_token']);
    $get  = $_GET;  unset($get['module']);  unset($get['phorum_admin_token']);
    if (empty($post) && empty($get)) {
        $module = ''; 
        if (isset($_POST['module'])) {
            $module = basename($_POST['module']);
        } elseif (isset($_GET['module'])) {
            $module = basename($_GET['module']);
        }
        $url = phorum_admin_build_url('module='.urlencode($module), TRUE);
        phorum_redirect_by_url($url);
    }

    $targetargs = $_SERVER['QUERY_STRING'];
    $target_html = htmlspecialchars(phorum_admin_build_url($targetargs));
    $targs_html = htmlspecialchars($targetargs);
    $post_url = phorum_admin_build_url('base');
?>
You are accessing the admin after a security timeout.<br /><br />
The requested URL was: 
<pre><?php echo $target_html;?></pre><br />
<strong>Please make sure that you really want to access this URL and weren't tricked to go to the admin.</strong><br />
Please click on <strong>continue</strong> to go to this URL or on <strong>cancel</strong> to go to the forum homepage.
<br /><br />
<form action="<?php echo $post_url;?>" method="POST">
<input type="hidden" name="module" value="tokenmissing" />
<input type="hidden" name="phorum_admin_token" value="<?php echo $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'];?>" />
<input type="hidden" name="target" value="<?php echo $targs_html;?>" />
<input type="submit" name="cancel" value="cancel" />
<input type="submit" name="continue" value="continue" />
</form>


