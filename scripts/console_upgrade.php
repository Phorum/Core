<?php
define("PHORUM_ADMIN",1);

include './common.php';

// no database connection?
if(!phorum_db_check_connection()){
    echo "A database connection could not be established.  Please edit include/db/config.php.\n";
    return;
} else {
    echo "Database connection confirmed, we will start the upgrade.\n";
    flush();
}

// no need for upgrade
if(isset($PHORUM['internal_version']) && $PHORUM['internal_version'] == PHORUMINTERNAL){
    echo "Your install is already up-to-date. No database-upgrade needed.\n";
    return;
}

if (! ini_get('safe_mode')) {
    echo "Trying to reset the timeout and rise the memory-limit ...\n";
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

$fromversion=$PHORUM['internal_version'];

$upgradepath="./include/db/upgrade/{$PHORUM['DBCONFIG']['type']}/";

// read in all existing files
$dh=opendir($upgradepath);
$upgradefiles=array();
while ($file = readdir ($dh)) {
    if (substr($file,-4,4) == ".php") {
        $upgradefiles[]=$file;
    }
}
unset($file);
closedir($dh);

// sorting by number
sort($upgradefiles,SORT_NUMERIC);
reset($upgradefiles);

// advance to current version
while(list($key,$val)=each($upgradefiles)) {
    if($val == $fromversion.".php")
    break;
}


while(list($dump,$file) = each($upgradefiles)) {

    // extract the pure version, needed as internal version
    $pure_version = basename($file,".php");

    if(empty($pure_version)){
        die("Something is wrong with the upgrade script.  Please contact the Phorum Dev Team. ($fromversion,$pure_version)");
    }


    $upgradefile=$upgradepath.$file;

    if(file_exists($upgradefile)) {
        echo "Upgrading from db-version $fromversion to $pure_version ... \n";
        flush();

        if (! is_readable($upgradefile))
        die("$upgradefile is not readable. Make sure the file has got the neccessary permissions and try again.");


        $upgrade_queries=array();
        include($upgradefile);
        $err=phorum_db_run_queries($upgrade_queries);
        if($err){
            echo "an error occured: $err ... try to continue.<br />\n";
        } else {
            echo "done.<br />\n";
        }
        $GLOBALS["PHORUM"]["internal_version"]=$pure_version;
        phorum_db_update_settings(array("internal_version"=>$pure_version));
    } else {
        echo "Ooops, the upgradefile is missing. How could this happen?\n";
    }

    $fromversion=$pure_version;

}
?>