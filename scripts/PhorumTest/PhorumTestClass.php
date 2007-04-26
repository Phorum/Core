<?php

    class PhorumTest extends UnitTestCase {
        function PhorumTest() {
            $this->UnitTestCase();
        }
        
        
        function testPhorumConnect() {
            $ret = phorum_db_check_connection();        
            $this->assertTrue($ret);
        }
        
        function testPhorumSettingsLoad() {
            global $PHORUM;
            phorum_db_load_settings();
            
            $this->assertTrue(is_array($PHORUM['SETTINGS']));
            $this->assertTrue(!empty($PHORUM['SETTINGS']));
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
            $res=phorum_db_drop_folder($forum_id);
            $this->assertTrue($res, "Deleting Forum");   
            
            // retrieving this forum again
            $gotforum = phorum_db_get_forums($forum_id);
            
            $this->assertFalse(count($gotforum),"Trying to get the deleted Forum");            
            
        }
        
    
        
    }
?>