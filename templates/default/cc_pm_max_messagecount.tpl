{IF MAX_PM_MESSAGECOUNT}

  <span style="float:right">

    <?php
        $avail = $PHORUM['DATA']['PM_SPACE_LEFT'];
        $used = $PHORUM['DATA']['PM_MESSAGECOUNT'];
        $total = $avail + $used;
        
        $size = 200;
        $usedsize = ceil($used/$total * $size);
        $usedperc = floor($used/$total * 100 + 0.5);
    ?>
    


    <style type="text/css">
    .PhorumGaugeTable {
        border-collapse: collapse;
    }
    
    .PhorumGauge {
        border: 1px solid {tablebordercolor};
        background-color: {navbackcolor};
    }
    
    .PhorumGaugePrefix {
        border: none;
        background-color: white;
        padding-right: 10px;
    }
    </style>
    
    <div align="right">
    
    <div class="PhorumTinyFont" style="padding-bottom: 3px">
      {IF PM_SPACE_LEFT}
        {LANG->PMSpaceLeft}
      {ELSE}
        {LANG->PMSpaceFull}
      {/IF}
    </div> 
        
    <table class="PhorumGaugeTable">
    
    <tr>
      <td class="PhorumGaugePrefix PhorumTinyFont">
        <?php print "{$usedperc}%" ?>
      </td>
      <td class="PhorumGauge" width="<?php print $size?>"><img src="images/gauge.gif" width="<?php print $usedsize?>" height="15px"/></td>
    </tr>

    </table>

    </div>
    
  </span>
  <br/>
  
{/IF}