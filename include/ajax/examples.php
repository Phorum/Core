<?php
if (! defined("PHORUM")) return;

$PHORUM['DATA']['HEADING'] = 'Ajax layer example page';
$PHORUM['DATA']['HTML_DESCRIPTION'] =
    'This page demonstrates some features of ' .
    'the Phorum Ajax layer.';

include(phorum_get_template('header'));

$clientjs = phorum_get_url(PHORUM_AJAX_URL, 'client');

?>
<script type="text/javascript" src="<?php print $clientjs ?>"></script>
<script type="text/javascript">

var state;
var logger;
var viewer;

function init() {
    state  = document.getElementById('state');
    logger = document.getElementById('logger');
    viewer = document.getElementById('viewer');
    clearState();
}

function setLoading(status) {
    status = status.replace(/&/g, '&amp;');
    status = status.replace(/</g, '&lt;');
    status = status.replace(/>/g, '&gt;');
    status = status.replace(/"/g, '&quot;');
    status = status.replace(/'/g, '&#039;');
    logger.innerHTML += status+"<br/><br/>";
}
function clearState() {
    state.innerHTML = '';
    logger.innerHTML = '';
    viewer.innerHTML = '';
}
function updateState(state) {
    state.innerHTML += "<br/>request state changed to: " +
                       state.readyState;
}

function helloworld() {
  clearState();
  Phorum.Ajax.call({
    "call"          : "helloworld",
    "onRequest"     : function (rb) { setLoading('request: '+rb); },
    "onResponse"    : function (rb) { setLoading('response: '+rb); },
    "onStateChange" : function (xhr) { updateState(xhr); },
    "onSuccess"     : function (data) {
      viewer.innerHTML = '<b>You have been hit by the unstoppable<br/>' +
                         'and terrible hello world example!!<br/>' +
                         'That must have hurt quite a bit.<br/>' +
                         'Don\'t tell me I didn\'t warn you...</b>'+
                         '<br/><br/>Server returned: ' + data;
    },
    "onFailure"     : function (error) { alert("Error: " + error); }
  });
}

function checkpm() {
  var id = document.getElementById('checkpm_user_id').value;
  clearState();
  Phorum.Ajax.call({
    "call"          : "checkpm",
    "user_id"       : id,
    "onRequest"     : function (rb) { setLoading('request: '+rb); },
    "onResponse"    : function (rb) { setLoading('response: '+rb); },
    "onStateChange" : function (xhr) { updateState(xhr); },
    "onFailure"     : function (error) { alert("Error: " + error); },
    "onSuccess"     : function (data) { viewer.innerHTML = 'Boolean for new PM is: ' + data; }
  });
}

</script>

<b>Client javascript library in use</b>:<br/>
<?php print htmlspecialchars($clientjs) ?><br/>
Version:
<script type="text/javascript">
  document.write(Phorum.Ajax.version)
</script>
<br/>
<br/>

<form method="POST" action="" onsubmit="return false">

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
init();
</script>

<?php
    include(phorum_get_template('footer'));
?>

