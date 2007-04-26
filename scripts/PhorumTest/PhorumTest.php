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
    }
    
    include 'PhorumTestClass.php';
    
    $test = &new PhorumTest();
    $test->run(new ShowPasses());

?>