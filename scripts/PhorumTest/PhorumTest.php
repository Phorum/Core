<?php

    define('phorum_page','simpletest');
    
    
    // include simpletest on itself
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    

    $cwd = getcwd();
    chdir('../..');
    include './common.php';
    
    $PHORUM['cache_users'] = 0;
    $PHORUM['cache_messages'] = 0;
    $PHORUM['cache_newflags'] = 0;
    $PHORUM['track_user_activity'] = 1;

    class ShowPasses extends HtmlReporter {

        function ShowPasses() {
            $this->HtmlReporter();
        }

        function paintPass($message) {
            parent::paintPass($message);
            print "<span class=\"pass\">Pass</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode("->", $breadcrumb);
            print "->$message<br />\n";
        }
        
        function paintError($message) {
        }
        // to ignore them in the summary
        function getExceptionCount() {
            return 0;
        }
    }
    
    include 'PhorumTestClass.php';
    
    $test = &new PhorumTest();
    $test->run(new ShowPasses());

?>