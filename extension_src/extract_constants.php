<?php
/**
 * This script is used to extract neccessary constants from
 * the Phorum software.
 */

$dir = dirname(__FILE__);

chdir("$dir/..");
define('phorum_page', 'extension');
define('PHORUM_ADMIN', 1);
include("common.php");
    
$code = "/* Phorum constants, extracted from Phorum " . PHORUM . " */\n\n"; 
foreach (get_defined_constants() as $k => $v)
{
    if (preg_match('/^PHORUM_[\w_]+_URL$/', $k)) {
        $code .= "#define $k " . (int)$v . "\n";
        continue;
    }

    if ($k == 'PHORUM_FILE_EXTENSION') {
        $code .= "#define $k \"$v\"\n";
    }
}
$code .= "\n";

$fp = fopen("$dir/phorum_constants.h", "w");
if (! $fp) die("Cannot write to $dir/phorum_constants.h\n");
fputs($fp, $code);
fclose($fp);

?>
