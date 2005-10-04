{IF MAX_PM_MESSAGECOUNT}

    <?php
        $avail = $PHORUM['DATA']['PM_SPACE_LEFT'];
        $used = $PHORUM['DATA']['PM_MESSAGECOUNT'];
        $total = $avail + $used;
        
        $size = 130;
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
    
    <div class="phorum-menu" style="margin-top: 6px">
    <div style="text-align: center; padding: 10px 0px 10px 0px">
    
    <div class="PhorumTinyFont" style="padding-bottom: 10px">
      {IF PM_SPACE_LEFT}
        {LANG->PMSpaceLeft}
      {ELSE}
        {LANG->PMSpaceFull}
      {/IF}
    </div> 
        
    <table class="PhorumGaugeTable" align="center">
    
    <tr>
      <td class="PhorumGaugePrefix PhorumTinyFont">
        <?php print "{$usedperc}%" ?>
      </td>
      <td class="PhorumGauge" width="<?php print $size?>"><img align="left" src="images/gauge.gif" width="<?php print $usedsize?>" height="15px"/></td>
    </tr>

    </table>

    </div>
    </div>
    
{/IF}
