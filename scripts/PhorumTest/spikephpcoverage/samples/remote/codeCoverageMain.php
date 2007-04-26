<?php
    /*
    *  $Id: codeCoverageMain.php 16428 2005-04-15 16:37:04Z npac $
    *  
    *  Copyright(c) 2004-2005, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Source License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php
    require_once "phpcoverage.inc.php";
    require_once PHPCOVERAGE_HOME . "/remote/RemoteCoverageRecorder.php";
    require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";

    if(empty($PHPCOVERAGE_REPORT_DIR)) {
        $PHPCOVERAGE_REPORT_DIR = "report";
    }
    $web_url = "http://localhost/coverage/";
    $cov_url = $web_url . "phpcoverage.remote.top.inc.php";
    file_get_contents($cov_url . "?phpcoverage-action=init&cov-file-name=". urlencode("phpcoverage.data.xml") . "&tmp-dir=". urlencode("/tmp"));

    file_get_contents($web_url . "sample.php");

    // Configure reporter, and generate report
    $covReporter = new HtmlCoverageReporter(
        "Sample Web Test Code Coverage", "", $PHPCOVERAGE_REPORT_DIR);
    $excludePaths = array(); 
    // Set the include path for the web-app
    // PHPCOVERAGE_APPBASE_PATH is passed on the commandline
    $includePaths = array(realpath($PHPCOVERAGE_APPBASE_PATH));

    // Notice the coverage recorder is of type RemoteCoverageRecorder
    $cov = new RemoteCoverageRecorder($includePaths, $excludePaths, $covReporter);
    // Pass the code coverage XML url into the generateReport function
    $cov->generateReport($cov_url . urlencode("?phpcoverage-action=get-coverage-xml"), true);
    $covReporter->printTextSummary($PHPCOVERAGE_REPORT_DIR . "/report.txt");
    // Clean up
    file_get_contents($cov_url . "?phpcoverage-action=cleanup");

?>
