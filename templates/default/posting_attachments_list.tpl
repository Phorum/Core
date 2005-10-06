<div class="PhorumStdBlock PhorumNarrowBlock" style="border-top: none; text-align: left"> 
  <table class="" cellspacing="0" width="100%">
    
    <?php $alt = "Alt"; ?>
    {ASSIGN LIST POST->attachments}
    {LOOP LIST}
      <?php $alt = $alt == "" ? "Alt" : "" ;?>
      {IF LIST->keep}
        <tr>
          <td class="PhorumTableRow<?php print $alt?>">
            {LIST->name} ({LIST->size})
          </td>
          <td class="PhorumTableRow<?php print $alt?>" align="right">
            <?php phorum_hook('editor_attachment_buttons', $PHORUM['TMP']['LIST']); ?>
            <input type="submit" name="detach:{LIST->file_id}"
             value="{LANG->Detach}" class="PhorumAttachmentButton" />
          </td>
        </tr>
      {/IF}
    {/LOOP LIST}
    
  </table>   
</div>