<?php

if (! extension_loaded('phorum')) {
    @dl('phorum.so');
}

define("NR_OF_THREADS", 20);
define("NR_OF_MESSAGES", 1000);

$chars = array(
    'a','b','c','d','e','f','g',
    'h','i','j','k','l','m','n',
    'o','p','q','r','s','t','u',
    'v','w','x','y','z',
    ' ', ' ', ' '
);

$nodes = array();
$id = 1;
for ($tree=1; $tree<=NR_OF_THREADS; $tree++)
{
    $newnodes = array(array(
        "id" => $id++,
        "parent_id"  => 0,
        "subject" => "Start of thread id $id"
    ));
    for ($i=1; $i<NR_OF_MESSAGES; $i++)
    {
        $j = array_rand($newnodes); 
        $p = $newnodes[$j];

        $subject = '';
        $c = rand(1,40);
        for ($k=0;$k<$c;$k++) {
            $subject .= $chars[array_rand($chars)]; 
        }

        $new = array(
            "id" => $id++,
            "parent_id" => $p["id"],
            "subject" => $subject,
        );
        $newnodes[] = $new;
    }
    $nodes = array_merge($nodes, $newnodes);
}

if (!phorum_ext_sort_threads($nodes, "id", "parent_id", 2, TRUE)) {
    die("Sort failed.\n");
}
print "Sort OK\n";
exit(0);

foreach ($nodes as $node) {
    if ($node["parent_id"] == 0) {
        print str_repeat("-", 72) . "\n";
    }
    print str_repeat(" ", $node["indent_cnt"]);
    print $node["id"] . " (parent " . $node["parent_id"] . ")\n";
    print "   " . str_repeat(" ", $node["indent_cnt"]);
    print $node["subject"] . "\n";
}

?>
