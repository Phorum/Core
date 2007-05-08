<?php

dl("phorum.so");

$nodes = array(
  array(
    "message_id" => 1,
    "parent_id"  => 0
  )
);
for ($i=1; $i<10; $i++) {
    $j = array_rand($nodes); 
    $p = $nodes[$j];
    $new = array(
        "parent_id" => $p["message_id"],
        "message_id" => $i+1
    );
    $nodes[] = $new;
}

phorum_treesort($nodes);

foreach ($nodes as $node) {
    print str_repeat(" ", $node["indent_cnt"]);
    print $node["message_id"] . " (parent " . $node["parent_id"] . ")\n";
}

?>
