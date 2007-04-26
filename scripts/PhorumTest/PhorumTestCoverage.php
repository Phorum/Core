<?php

    define('phorum_page','simpletest');
    
    if(!function_exists('xdebug_is_enabled')) {
        echo "You need to have XDebug installed for the coverage analysis to work.";
        exit();
    }
    
    // enable code-coverage recorder
    define("PHPCOVERAGE_HOME", "spikephpcoverage/src"); 
    require_once PHPCOVERAGE_HOME . "/CoverageRecorder.php";
    require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";
    
    $reporter = new HtmlCoverageReporter("Phorum5-Trunk Code Coverage Report", "", "report");
    
    $includePaths = array(".");
    $excludePaths = array("scripts/PhorumTest/simpletest","scripts/PhorumTest/spikephpcoverage","include/db/upgrade", "templates");
    $cov = new CoverageRecorder($includePaths, $excludePaths, $reporter);    
    
    // include simpletest on itself
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    
    $cov->startInstrumentation();

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
    
    $cov->stopInstrumentation();
    
    $cov->generateReport();
    $reporter->printTextSummary();
    
?>

<a href="report/index.html">CodeCoverage Report should now be available at this site</a>