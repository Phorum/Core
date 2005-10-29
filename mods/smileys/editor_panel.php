<?php 
if(!defined("PHORUM")) return;
$PHORUM = $GLOBALS["PHORUM"];
$prefix = $PHORUM["mod_smileys"]["prefix"];
?>

<style type="text/css">
#phorum_mod_smileys_panel { display: none; }
#phorum_mod_smileys_dots { display: inline; }
#phorum_mod_smileys_loading { display: none; }
#phorum_mod_smileys { display: none; padding: 0px 5px 5px 5px; }
#phorum_mod_smileys img {
    margin: 0px 7px 0px 0px;
    vertical-align: bottom;
    cursor: pointer;
    cursor: hand;
}
</style>

<script type="text/javascript">

smileys_state = -1;
smileys_count = 0;
loaded_count = 0;
function toggle_smileys()
{
    // On the first request to open the smiley help, load all smiley images.
    if (smileys_state == -1) 
    {
        // Load smiley images.
        <?php
        $smileys_count = 0;
        $c = '';
        foreach ($PHORUM["mod_smileys"]["smileys"] as $id => $smiley) {
            if (! $smiley["active"] || $smiley["is_alias"] || $smiley["uses"] == 1) continue;
            $smileys_count ++;
            $src = htmlspecialchars($prefix . $smiley['smiley']);
            $c.="document.getElementById('smiley-button-{$id}').src='$src';\n";
        }
        print "smileys_count = $smileys_count;\n$c\n"; ?>

        smileys_state = 0;
    }

    // Toggle smiley panel.
    smileys_state = ! smileys_state;
    if (smileys_state) show_smileys(); else hide_smileys();
}

function show_smileys()
{
    // We wait with displaying the smiley help until all smileys are loaded.
    if (loaded_count < smileys_count) return false;

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

function phorum_mod_smileys_load_smiley (imgobj)
{
    loadingobj = document.getElementById('phorum_mod_smileys_loading');

    // Another smiley image was loaded. If we have loaded all
    // smiley images, then show the smileys panel.
    if (imgobj.src != '') {
        loaded_count ++;
        imgobj.onload = '';
        if (loaded_count == smileys_count) {
            loadingobj.style.display = 'none';
            show_smileys();
        } else {
            // Visual feedback for the user while loading the images.
            loadingobj.style.display = 'inline';
            loadingobj.innerHTML = "("
              + "<?php print $PHORUM["DATA"]["LANG"]["LoadingSmileys"]; ?> "
              + Math.floor(loaded_count/smileys_count*100) + "%)";
        }
    }
}

</script>

<div id="phorum_mod_smileys_panel"
     class="PhorumStdBlockHeader PhorumNarrowBlock">

  <a href="javascript:toggle_smileys()">
    <b><?php print $PHORUM["DATA"]["LANG"]["AddSmiley"]?></b>
  </a>
  <div id="phorum_mod_smileys_dots"><b>...</b></div>
  <div id="phorum_mod_smileys_loading">
    (<?php print $PHORUM["DATA"]["LANG"]["LoadingSmileys"]; ?>)
  </div>

  <div id="phorum_mod_smileys"> <?php
    // Create a list of stub smiley images. The real images are only
    // loaded when the user opens the smiley panel.
    foreach($PHORUM["mod_smileys"]["smileys"] as $id => $smiley) {
      if (! $smiley["active"] || $smiley["is_alias"] || $smiley["uses"] == 1) continue;
      print "<img id=\"smiley-button-$id\" onclick=\"phorum_mod_smileys_insert_smiley('" . urlencode($smiley["search"]) . "')\" onload=\"phorum_mod_smileys_load_smiley(this)\" src=\"\"/>";
    } ?>
  </div>

</div>

<script type="text/javascript">
// Display the smileys panel. This way browsers that do not
// support javascript (but which do support CSS) will not
// show the smileys panel.
document.getElementById("phorum_mod_smileys_panel").style.display = 'block';
</script>
