<?php
if (! defined("PHORUM")) return;

phorum_build_common_urls();

$PHORUM['DATA']['HEADING'] = 'Ajax layer example page';
$PHORUM['DATA']['HTML_DESCRIPTION'] =
    'This page demonstrates some features of ' .
    'the Phorum Ajax layer.';

include phorum_api_template('header');

$clientjs = phorum_api_url(PHORUM_AJAX_URL, 'client');

?>
<script type="text/javascript">

var viewer;

function helloworld() {
  Phorum.Ajax.call({
    "call"          : "helloworld",
    "cache_id"      : "helloworld",
    "onSuccess"     : function (data) {
      viewer.innerHTML = 'Server returned: ' + data;
    },
    "onFailure"     : function (error) {
      alert("Error: " + error);
    }
  });
}

function checkpm() {
  var id = document.getElementById('checkpm_user_id').value;
  Phorum.Ajax.call({
    "call"          : "checkpm",
    "user_id"       : id,
    "onFailure"     : function (error) { alert("Error: " + error); },
    "onSuccess"     : function (data) { viewer.innerHTML = 'Boolean for new PM is: ' + data; }
  });
}

</script>

<b>Client javascript library in use</b>:<br/>
<?php print htmlspecialchars($clientjs) ?><br/>
Version:
<script type="text/javascript">
  document.write(Phorum.library_version)
</script>
<br/>
<br/>

<form method="post" action="" onsubmit="return false">

  <input type="button" style="background-color: red; color: white; font-weight: bold" value="PANIC" onclick="helloworld(); return false"/> &lt;-- do not press this button

  <br/>
  <br/>

  <input type="text" id="checkpm_user_id" value="<?php if($PHORUM['user']['user_id']) print $PHORUM['user']['user_id'] ?>" />
  <input type="button" value="Check PM for user id" onclick="checkpm(); return false"/>

</form>

<strong>Output</strong><br/>
<div id="viewer" style="border: 1px solid #ddd; padding:10px; margin-bottom:5px; font-size: 9px">
</div>
<strong>Debugging information</strong><br/>
<div id="logger" style="border: 1px solid #ddd; padding:10px; font-size: 9px">
</div>
<div id="state" style="border: 1px solid #ddd; border-top: none; padding:10px; font-size: 9px">
</div>

<script type="text/javascript">
viewer = document.getElementById('viewer');
viewer.innerHTML = '';
</script>

<?php
    include phorum_api_template('footer');
?>

