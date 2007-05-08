<?php

dl("phorum.so");

$nodes = array();
$id = 1;
for ($tree=1; $tree<=20; $tree++)
{
    $newnodes = array(array(
        "id" => $id++,
        "parent_id"  => 0
    ));
    for ($i=1; $i<200; $i++) {
        $j = array_rand($newnodes); 
        $p = $newnodes[$j];
        $new = array(
            "id" => $id++,
            "parent_id" => $p["id"],
        );
        $newnodes[] = $new;
    }
    $nodes = array_merge($nodes, $newnodes);
}

if (!phorum_treesort($nodes, "id", "parent_id", 2)) {
    die("Sort failed.\n");
}

foreach ($nodes as $node) {
    print str_repeat(" ", $node["indent_cnt"]);
    print $node["id"] . " (parent " . $node["parent_id"] . ")\n";
}

?>
