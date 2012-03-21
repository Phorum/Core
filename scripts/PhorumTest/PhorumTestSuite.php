<?php
define('phorum_page','phpunittest');

/*
 * Prerequisites:
 * install PHPUnit >= 3.1.x
 * install PHP >= 5.2.x
 * install Phorum >= 5.2.x as usual with going through full installation
 * (optional) install xdebug extension for php (for codecoverage report)
 * (optional) install graphviz (for codecoverage report)
 *
 * run with:
 * phpunit PhorumTestSuite
 *
 * or for html/coverage reports:
 * phpunit --report ./report PhorumTestSuite
 * (this needs graphviz and xdebug installed!)
 * (report is put into report-directory in the phorum-directory then!)
 *
 */

$cwd = getcwd();
chdir('../..');
include './common.php';

// need this here to not clutter output with cookie warnings.
ob_start();

$PHORUM['cache_users'] = 1;
$PHORUM['cache_messages'] = 0;
$PHORUM['cache_newflags'] = 0;
$PHORUM['track_user_activity'] = 1;
$PHORUM['tight_security'] = 1;
$PHORUM['use_cookies'] = 0;

require_once('PHPUnit/Framework.php');


//chdir($cwd);
class PhorumTestSuite extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        return new PhorumTestSuite('PhorumTest');
    }

    protected function setUp() {
        // to have the same variable everywhere
        $this->sharedFixture = mt_rand();
    }
}

class PhorumTest extends PHPUnit_Framework_TestCase
{

        function testPhorumConnect() {
            global $PHORUM;
            $ret = $PHORUM['DB']->check_connection();
            $this->assertTrue($ret);
        }

        function testPhorumInstall() {
            // @TODO first needs some install functions to run
        }

        function testPhorumSettingsLoad() {
            global $PHORUM;
            $PHORUM['DB']->load_settings();

            $PHORUM['cache_users'] = 1;
            $PHORUM['cache_messages'] = 0;
            $PHORUM['cache_newflags'] = 0;
            $PHORUM['track_user_activity'] = 1;
            $PHORUM['use_cookies'] = 0;

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

            $forum_id = $PHORUM['DB']->add_forum($forum);
            $this->assertGreaterThan(0,$forum_id,"Forum added call returned");

            // retrieving this forum again
            $gotforum = $PHORUM['DB']->get_forums($forum_id);

            $this->assertGreaterThan(0,count($gotforum),"Got something back");
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

            $res = $PHORUM['DB']->update_forum($forum_update);
            $this->assertTrue($res, "Updating Forum");

            // retrieving this forum again
            $gotforum = $PHORUM['DB']->get_forums($forum_id);

            $this->assertGreaterThan(0,count($gotforum),"Got the updated Forum");

            // checking the retrieved forum against the original one
            $checkforum = true;
            foreach($forum_update as $key => $value) {

                if($gotforum[$forum_id][$key] != $value) {
                    $checkforum = false;
                }

            }

            $this->assertTrue($checkforum,'Comparing updated Forum');

            // deleting a forum
            $res=$PHORUM['DB']->drop_forum($forum_id);
            $this->assertNull($res, "Deleting Forum");

            // retrieving this forum again
            $gotforum = $PHORUM['DB']->get_forums($forum_id);

            $this->assertEquals(0,count($gotforum),"Trying to get the deleted Forum");

        }

        function testUserApiAdd() {

            // do the try/catch run as trigger_error calls are returned as
            // exception from phpunit
            try {
                $user_id=phorum_api_user_save(1);
                $this->fail('Adding user (no input)');
            }
            catch (PHPUnit_Framework_Error $expected) {
                $this->assertTrue(true,'Adding user (no input)');
            }


            try {
                $user_id=phorum_api_user_save(array('user_id'=>'foo'));
                $this->fail('Adding user (wrong user_id)');
            }
            catch (PHPUnit_Framework_Error $expected) {
                $this->assertTrue(true,'Adding user (wrong user_id)');
            }

            $user = array('password'=>'testPwd',
                          'active'=>PHORUM_USER_ACTIVE
                          );

            try {
                $user_id=phorum_api_user_save($user);
                $this->fail('Adding user (missing username, email, user_id)');
            }
            catch (PHPUnit_Framework_Error $expected) {
                $this->assertTrue(true,'Adding user (missing email, username, user_id)');
            }

            $user['user_id'] = NULL;

            try {
                $user_id=phorum_api_user_save($user);
                $this->fail('Adding user (missing username, email)');

            } catch (PHPUnit_Framework_Error $expected) {
                $this->assertTrue(true,'Adding user (missing email, username)');
            }

            $user['username']='testuser'.$this->sharedFixture;

            try {
                $user_id=phorum_api_user_save($user);
                $this->fail('Adding user (missing email)');
            }
            catch (PHPUnit_Framework_Error $expected) {
                $this->assertTrue(true,'Adding user (missing email)');
            }

            $user['email']='testEmail'.$this->sharedFixture.'@example.com';

            $user_id=phorum_api_user_save($user);

            $this->assertGreaterThan(0,$user_id, "Adding user.");

        }

        function testUserApiGet() {


            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $gotten_user = phorum_api_user_get($user_id,true);

            $this->assertTrue((is_array($gotten_user) && count($gotten_user)),'Retrieve User');

        }

        function testUserApiSave() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $gotten_user = phorum_api_user_get($user_id,true);

            // now for saving the user
            $gotten_user['real_name'] = 'foo';

            $ret = phorum_api_user_save($gotten_user);

            $this->assertGreaterThan(0,$ret, 'Saved changed user.');

            $mod_user2 = array('user_id' => $gotten_user['user_id'],'real_name'=>'test');

            // and saving it raw too
            $ret = phorum_api_user_save_raw($mod_user2);

            $this->assertTrue($ret, 'Saved changed user (raw).');

        }

        function testUserApiSettings() {


            // now handling user-settings
            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $ret = phorum_api_user_save_settings(array());

            $this->assertNull($ret, 'Saving user-settings (no user_id).');

            $GLOBALS['PHORUM']['user']['user_id']=$user_id;

            $ret = phorum_api_user_save_settings(array());

            $this->assertTrue($ret, 'Saving user-settings (empty settings).');

            $ret = phorum_api_user_save_settings(array('foo'=>'bar'));

            $this->assertTrue($ret, 'Saving user-settings.');

            // getting settings
            $ret = phorum_api_user_get_setting('foo');

            $this->assertEquals($ret,'bar','Getting user-settings.');

            $ret = phorum_api_user_get_setting('bar');

            $this->assertNull($ret,'Getting user-settings (unknown key).');

        }


        function testUserApiAuthentication() {

            //var_dump($GLOBALS['PHORUM']);

            // authentication
            $username = 'testuser'.$this->sharedFixture;

            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'');
            $this->assertFalse($ret,'User authenticated without password.');

            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'FOO');
            $this->assertFalse($ret,'User authenticated with wrong password.');

            $ret = phorum_api_user_authenticate(PHORUM_FORUM_SESSION,$username,'testPwd');
            $this->assertGreaterThan(0,$ret,'User authenticated with correct password.');


        }

        function testUserApiDisplayName() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            // display-name handling
            $ret = phorum_api_user_get_display_name($user_id,NULL,PHORUM_FLAG_HTML);

            $this->assertType('string',$ret,'Getting displayname for user (HTML).');

            $ret = phorum_api_user_get_display_name($user_id,NULL,PHORUM_FLAG_PLAINTEXT);

            $this->assertType('string',$ret,'Getting displayname for user (Plaintext).');

        }

        function testUserApiPostcount() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');


            $GLOBALS['PHORUM']['user']['user_id']=$user_id;

            // incrementing post-count
            $ret = phorum_api_user_increment_posts(NULL);

            $this->assertTrue($ret,'Incrementing post count for current user.');

            // incrementing post-count
            $ret = phorum_api_user_increment_posts($user_id);

            $this->assertTrue($ret,'Incrementing post count for user by id.');

            $user_get = phorum_api_user_get($user_id);

            $this->assertTrue($user_get['posts'] == 2,'Checking post count.');

        }

        function testUserApiSetActiveUser() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $ret = phorum_api_user_set_active_user(PHORUM_FORUM_SESSION,$user_id);

            $this->assertTrue($ret,'Setting given user_id active again.');

            $ret = phorum_api_user_set_active_user(PHORUM_FORUM_SESSION,array('foo'=>'bar'));

            $this->assertFalse($ret,'set_active_user with invalid array given.');

            $ret = phorum_api_user_set_active_user(PHORUM_FORUM_SESSION,array('foo'));
            $this->assertFalse($ret,'set_active_user with invalid user-input.');

            // set active user
            $GLOBALS['PHORUM']['user'] = phorum_api_user_get($user_id);

            // create session
            $ret = phorum_api_user_session_create(PHORUM_FORUM_SESSION);

            $this->assertTrue($ret,'Creating user-session');
        }

        function testUserApiSessionRestore() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $GLOBALS['PHORUM']['user']['user_id']=$user_id;
            $GLOBALS['PHORUM']['user'] = phorum_api_user_get($user_id);

            $ret = phorum_api_user_session_restore(PHORUM_FORUM_SESSION);

            $this->assertTrue($ret,'Restore user-session');
        }

        function testUserApiSearch() {
            $ret = phorum_api_user_search('username','test%','LIKE');
            $this->assertGreaterThan(0,$ret,'User search.');
        }

        function testUserApiUserList() {
            $ret = phorum_api_user_list();

            $this->assertGreaterThan(0,count($ret),'Getting User-List.');
        }

        // very last one
        function testUserApiDelete() {

            $user_id = phorum_api_user_search('username','testuser'.$this->sharedFixture,'=');

            $ret = phorum_api_user_delete($this->user_id_used);
            $this->assertTrue($ret,'User delete.');



            $ret = phorum_api_user_get($this->user_id_used);

            $this->assertNull($ret,'Checking for deleted user.');


        }
    }
?>
