<?php

if (!defined("PHORUM")) return;

$sqlqueries[]= "
    ALTER TABLE {$PHORUM["event_logging_table"]} MODIFY
        ip VARCHAR(45) NULL
";

?>
