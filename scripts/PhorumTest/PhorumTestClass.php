<?php

    class PhorumTest extends UnitTestCase {
        
        var $tempvar;
        var $randvar;
        var $user_id_used;
        
        function PhorumTest() {
            $this->UnitTestCase();
        }
        
        
        function testPhorumConnect() {
            $ret = phorum_db_check_connection();        
            $this->assertTrue($ret);
        }
        
        function testPhorumInstall() {
            
        }
        
        function testPhorumSettingsLoad() {
            global $PHORUM;
            phorum_db_load_settings();
            $this->assertTrue(!empty($PHORUM['internal_version']));
        }
        
        function testPhorumDbForum() {
            global $PHORUM;
            
            // trying to add a forum first
            $forum = array(
            'name'=>'PhorumTest Forum',
            'active'=>1,
            'parent_id'=>0,
            'description'=>'PhorumTest forum'
            );

            $forum_id = phorum_db_add_forum($forum);    
            $this->assertTrue($forum_id,"Forum added call returned");   
            
            // retrieving this forum again
            $gotforum = phorum_db_get_forums($forum_id);
            
            $this->assertTrue(count($gotforum),"Got something back");
            $this->assertTrue(isset($gotforum[$forum_id]),"Forum really added");
            
            // checking the retrieved forum against the original one
            $checkforum = true;
            foreach($forum as $key => $value) {
                
                if($gotforum[$forum_id][$key] != $value) {
                    $checkforum = false;
                }
                
            }
            
            $this->assertTrue($checkforum,'Comparing added Forum');
            
            // trying to change that forum
            $forum_update = array(
            'forum_id' => $forum_id,
            'name'=>'PhorumTest Forum - Test2',
            'active'=>0,
            'parent_id'=>1,
            'description'=>'PhorumTest forum - Test2'
            );            
            
            $res = phorum_db_update_forum($forum_update);    
            $this->assertTrue($res, "Updating Forum");   
            
            // retrieving this forum again
            $gotforum = phorum_db_get_forums($forum_id);
            
            $this->assertTrue(count($gotforum),"Got the updated Forum");
            
            // checking the retrieved forum against the original one
            $checkforum = true;
            foreach($forum_update as $key => $value) {
                
                if($gotforum[$forum_id][$key] != $value) {
                    $checkforum = false;
                }
                
            }
            
            $this->assertTrue($checkforum,'Comparing updated Forum');
            
            // deleting a forum
            $res=phorum_db_drop_forum($forum_id);
            $this->assertNull($res, "Deleting Forum");   
            
            // retrieving this forum again
            $gotforum = phorum_db_get_forums($forum_id);
            
            $this->assertFalse(count($gotforum),"Trying to get the deleted Forum");            
            
        }
        
        function testUserApiAdd() {
                        
            $user = array('password'=>'testPwd',
            			  'active'=>PHORUM_USER_ACTIVE
                          );
                          

            $user_id=phorum_api_user_save($user);
            
            $this->randvar = mt_rand();
            
            $this->assertNull($user_id,'Adding user (missing username, email, user_id)');                          
                          
            $user['username']='testuser'.$this->randvar;
            
            $user_id=phorum_api_user_save($user);
            
            $this->assertNull($user_id,'Adding user (missing email, user_id)');
            
            $user['email']='testEmail'.$this->randvar.'@example.com';
            
            $user_id=phorum_api_user_save($user);
            
            $this->assertNull($user_id,'Adding user (missing user_id)');
            
            $user['user_id'] = NULL;
            
            $user_id=phorum_api_user_save($user);
            
            $this->tempvar = $user_id;
            $this->user_id_used = $user_id;
            
            $this->assertTrue($user_id, "Adding user.");   
            
        }
        
        function testUserApiGet() {
            
            $gotten_user = phorum_api_user_get($this->tempvar,true);
            
            $this->tempvar = $gotten_user;
            
            $this->assertTrue((is_array($gotten_user) && count($gotten_user)),'Retrieve User');
            
        }
        
        function testUserApiModify() {
            
            $mod_user = $this->tempvar;
            
            $mod_user['real_name'] = 'foo';
            
            $ret = phorum_api_user_save($mod_user);
            
            $this->assertTrue($ret, 'Saved changed user.');
            
            $mod_user2 = array('user_id' => $mod_user['user_id'],'real_name'=>'test');
            
            $ret = phorum_api_user_save_raw($mod_user2);
            
            $this->assertTrue($ret, 'Saved changed user (raw).');
            
        }
        
        function testUserApiSaveSettings() {
            
            $user = $this->tempvar;
            $user_id = $user['user_id'];
                        
            $ret = phorum_api_user_save_settings(array());
            
            $this->assertNull($ret, 'Saving user-settings (no user_id).');
            
            $GLOBALS['PHORUM']['user']['user_id']=$user_id;
            
            $ret = phorum_api_user_save_settings(array());
            
            $this->assertTrue($ret, 'Saving user-settings (empty settings).');
            
            $ret = phorum_api_user_save_settings(array('foo'=>'bar'));
            
            $this->assertTrue($ret, 'Saving user-settings.');
            
        }
        
        function testUserApiGetSettings() {
            
                        
            $ret = phorum_api_user_get_setting('foo');
            
            $this->assertEqual($ret,'bar','Getting user-settings.');
            
            $ret = phorum_api_user_get_setting('bar');
            
            $this->assertNull($ret,'Getting user-settings (unknown key).');            
            
            
        }        
        
        function testUserApiSearch() {
            $ret = phorum_api_user_search('username','test%','LIKE');
            
            $this->assertTrue($ret,'User search.');
        }
        
        function testUserApiAuthenticate() {
            $randval = $this->randvar;
            
            $username = 'testuser'.$randval;
                        
            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'');
            $this->assertFalse($ret,'User authenticated without password.');
            
            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'FOO');
            $this->assertFalse($ret,'User authenticated with wrong password.');
            
            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'testPwd');
            $this->assertTrue($ret,'User authenticated with correct password.');
        }
        
        function testUserApiGetDisplayName() {
            $ret = phorum_api_user_get_display_name($this->user_id_used,NULL,PHORUM_FLAG_HTML);
            
            $this->assertTrue($ret,'Getting displayname for user (HTML).');
            
            $ret = phorum_api_user_get_display_name($this->user_id_used,NULL,PHORUM_FLAG_PLAINTEXT);
            
            $this->assertTrue($ret,'Getting displayname for user (Plaintext).');
        }
        
        function testUserApiUserList() {
            $ret = phorum_api_user_list();
            
            $this->assertTrue(count($ret),'Getting User-List.');
        }
        
        function testUserApiIncrementPostcount() {
            $ret = phorum_api_user_increment_posts($this->user_id_used);
            
            $this->assertTrue($ret,'Incrementing post count for user.');
            
            $user_get = phorum_api_user_get($this->user_id_used);
            
            $this->assertTrue($user_get['posts'] == 1,'Checking post count.');
        }
        
        function testUserApiSetActiveUser() {
            $GLOBALS['PHORUM']['user'] = phorum_api_user_get($this->user_id_used);
            
            $ret = phorum_api_user_set_active_user(PHORUM_FORUM_SESSION,$this->user_id_used);
            
            $this->assertTrue($ret,'Setting user active again.');
        }
        
        function testUserApiCreateSession() {
            $ret = phorum_api_user_session_create(PHORUM_FORUM_SESSION);
            
            $this->assertTrue($ret,'Creating user-session');
        }
    
        // very last one
        function testUserApiDelete() {
            
            $ret = phorum_api_user_delete($this->user_id_used);
            $this->assertTrue($ret,'User delete.');
            
            
            
            $ret = phorum_api_user_get($this->user_id_used);
            
            $this->assertNull($ret,'Checking for deleted user.');
            
            
        }
    }
?>
