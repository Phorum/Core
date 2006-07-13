<?php
foreach ($PHORUM["mod_smileys"]["smileys"] as $id => $item)
{
    if (! $item["active"]) continue;

    $used_for_txt = $PHORUM_MOD_SMILEY_USES[$item['uses']];
    foreach ($item as $key => $val) {
        $item[$key] = htmlspecialchars($val);
    }

    print "<tr>\n";
    print "  <td class=\"smiley_column\">{$item["search"]}</td>\n";
    print "  <td>";
    print "<img src=\"../../../../{$PHORUM["mod_smileys"]["prefix"]}{$item["smiley"]}\"/>";
    print "  </td>\n";
    print "  <td>{$item["alt"]}</td>\n";
    print "  <td>$used_for_txt</td>\n";
    print "</tr>\n";
}
?>
