<?php
/*
 *  $Id: codeCoverageMain.php 23395 2005-06-17 20:00:35Z npac $
 *  
 *  Copyright(c) 2004-2005, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Source License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */
?>
<?php
    /**
     * This file should be executed with following command
     * Linux:
     *   $ (export PHPCOVERAGE_HOME=/path/to/phpcoverage/src; php codeCoverageMain.php)
     * Windows: 
     *   > set PHPCOVERAGE_HOME=/path/to/phpcoverage/src
     *   > php codeCoverageMain.php
     *
     */

?>
<?php
    require_once "phpcoverage.inc.php";
    require_once PHPCOVERAGE_HOME . "/CoverageRecorder.php";
    require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";

    $reporter = new HtmlCoverageReporter("Code Coverage Report", "", "report");

    $includePaths = array(".");
    $excludePaths = array("codeCoverageMain.php");
    $cov = new CoverageRecorder($includePaths, $excludePaths, $reporter);

    $cov->startInstrumentation();
    include "sample.php";
    include "sample2.php";
    $cov->stopInstrumentation();

    $cov->generateReport();
    $reporter->printTextSummary();
?>
