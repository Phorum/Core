<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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

    // don't allow this page to be loaded directly
    if(!defined("PHORUM_ADMIN")) exit();
    $targetargs = $_SERVER['QUERY_STRING'];
    $target_html = htmlspecialchars(phorum_admin_build_url($targetargs));
    $targs_html = htmlspecialchars($targetargs);
    $post_url = phorum_admin_build_url('base');
    
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
                                    
                   $phorum->redirect($PHORUM['http_path']);
                   
               } elseif(!empty($_POST['continue'])) {
                   
                   if(!empty($_POST['target'])) {
                       $url = phorum_admin_build_url($_POST['target']);
                   } else {
                       $url = phorum_admin_build_url('');
                   }
                
                   $phorum->redirect($url);
               }
               exit();
           }
    }

    // update the token and time
    $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token_time'] = time();
    $sig_data = $GLOBALS["PHORUM"]["user"]['user_id'].time().$GLOBALS["PHORUM"]["user"]['username'];
    $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'] = $phorum->sign($sig_data);

    $tmp_user = array(
             	'user_id'=>$GLOBALS["PHORUM"]["user"]['user_id'],
                'settings_data'=>$GLOBALS["PHORUM"]["user"]['settings_data']
    );
    phorum_api_user_save($tmp_user);
    
    
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


