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
        
        function testPhorumAddForum() {
            global $PHORUM;
            $forum = array(
            'name'=>'PhorumTest Forum',
            'active'=>1,
            'parent_id'=>0,
            'description'=>'PhorumTest forum'
            );

            $forum_id = phorum_db_add_forum($forum);    
            $this->assertTrue($forum_id);   
            
            $gotforum = phorum_db_get_forums($forum_id);
            
            $this->assertTrue(count($gotforum));
            $this->assertTrue(isset($gotforum[$forum_id]));
            
            $checkforum = true;
            foreach($forum as $key => $value) {
                
                if($gotforum[$forum_id][$key] != $value) {
                    $checkforum = false;
                }
                
            }
            
            $this->assertTrue($checkforum);
            
        }
        
    }
?>