<?php 
if(!defined("PHORUM")) return;
$PHORUM = $GLOBALS["PHORUM"];
?>

<style type="text/css">
#phorum_mod_smileys_dots {
    display: inline;
}
#phorum_mod_smileys {
    display: none;
    padding: 0px 5px 5px 5px;
}
#phorum_mod_smileys img {
    margin: 0px 7px 0px 0px;
    vertical-align: bottom;
    cursor: pointer;
    cursor: hand;
}
</style>

<script type="text/javascript">
function toggle_smileys()
{
    if (document.getElementById('phorum_mod_smileys').style.display == 'block')
        hide_smileys();
    else
        show_smileys();
    return false;
}

function show_smileys()
{
    document.getElementById('phorum_mod_smileys').style.display = 'block';
    document.getElementById('phorum_mod_smileys_dots').style.display = 'none';
    return false;
}

function hide_smileys()
{
    document.getElementById('phorum_mod_smileys').style.display = 'none';
    document.getElementById('phorum_mod_smileys_dots').style.display = 'inline';
    return false;
}

function phorum_mod_smileys_insert_smiley(string) 
{
    var area = document.getElementById("phorum_textarea");
    string = unescape(string);
    
    if (area) 
    {
        if (area.createTextRange) /* MSIE */
        {
            area.focus(area.caretPos);
            area.caretPos = document.selection.createRange().duplicate();
            curtxt = area.caretPos.text;
            area.caretPos.text = string + curtxt;
        } 
        else /* Other browsers */
        {
            var pos = area.selectionStart;              
            area.value = 
                area.value.substring(0,pos) + 
                string +
                area.value.substring(pos);
            area.focus();
            area.selectionStart = pos + string.length;
            area.selectionEnd = area.selectionStart;
        }
    } else {
        alert('There seems to be a technical problem. The textarea ' +
              'cannot be found in the page. ' +
              'The textarea should have id="phorum_textarea" in the ' +
              'definition for this feature to be able to find it. ' +
              'If you are not the owner of this forum, then please ' +
              'alert the forum owner about this.');
    }
}

</script>

<div class="PhorumStdBlockHeader PhorumNarrowBlock">

  <a href="#" onclick="return toggle_smileys()">
    <b>Add a smiley <div id="phorum_mod_smileys_dots">...</div></b>
  </a>

  <div id="phorum_mod_smileys">
    <?php
    $prefix = $PHORUM["mod_smileys"]["prefix"];
    foreach($PHORUM["mod_smileys"]["smileys"] as $id => $smiley) {
      if (! $smiley["active"] || $smiley["is_alias"]) continue;
      print "<img onclick=\"phorum_mod_smileys_insert_smiley('" . urlencode($smiley["search"]) . "')\" src=\"{$prefix}{$smiley["smiley"]}\">";
    }
    ?>
  </div>

</div>
