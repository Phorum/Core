{IF NOT ITEMCOUNT 0}
  {IF NOT ITEMCOUNT 1}
    {VAR DID_TOGGLEBLOCK 1}
    <script type="text/javascript">
    // <![CDATA[
    function checkAll() {
        var lf = document.getElementById('phorum_listform');
        for (var i=0; i<lf.elements. length;i++) {
            var elt=lf.elements[i];
            if (elt.type=='checkbox' && elt.name!='toggle') {
                elt.checked = document.getElementById('toggle').checked;
            }
        }
    }

    document.write( '<input type="checkbox" id="toggle" name="toggle" onclick="checkAll()"/>');
    // ]]>
    </script>
    <noscript>&nbsp;</noscript>
  {/IF}
{/IF}
{IF NOT DID_TOGGLEBLOCK}&nbsp;{/IF}
