<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    {IF URL->LIST}
        <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    {/IF}
</div>

<table class="menu" cellspacing="0" border="0">
    <tr>
        <td class="menu" nowrap="nowrap">

            <div class="generic">

                {LANG->PrivateMessages}
                <ul>
                    {LOOP PM_FOLDERS}
                        <li><a {IF PM_FOLDERS->id FOLDER_ID}class="current" {/IF}href="{PM_FOLDERS->url}">{PM_FOLDERS->name}</a><small>{IF PM_FOLDERS->total}&nbsp;({PM_FOLDERS->total}){/IF}{IF PM_FOLDERS->new}&nbsp;(<span class="new">{PM_FOLDERS->new} {LANG->newflag}</span>){/IF}</small></li>
                    {/LOOP PM_FOLDERS}
                </ul>
                {LANG->Options}
                <ul>
                    <li><a {IF PM_PAGE "send"}class="current" {/IF}href="{URL->PM_SEND}">{LANG->SendPM}</a></li>
                    <li><a {IF PM_PAGE "folders"}class="current" {/IF}href="{URL->PM_FOLDERS}">{LANG->EditFolders}</a></li>
                    <li><a {IF PM_PAGE "buddies"}class="current" {/IF} href="{URL->BUDDIES}">{LANG->Buddies}</a></li>
                </ul>
            </div>

            {IF MAX_PM_MESSAGECOUNT}
                <?php
                    // move into pm.php
                    $avail = $PHORUM['DATA']['PM_SPACE_LEFT'];
                    $used = $PHORUM['DATA']['PM_MESSAGECOUNT'];
                    $total = $avail + $used;
                    $size = 130;
                    $usedsize = ceil($used/$total * $size);
                    $usedperc = floor($used/$total * 100 + 0.5);
                ?>
                <br/>
                <div class="generic">
                    {IF PM_SPACE_LEFT}
                      {LANG->PMSpaceLeft}
                    {ELSE}
                      {LANG->PMSpaceFull}
                    {/IF}
                    <table class="phorum-gaugetable" align="center">
                        <tr>
                            <td class="phorum-gaugeprefix"><?php echo "{$usedperc}%" ?></td>
                            <td class="phorum-gauge" width="<?php echo $size?>"><img align="left" src="{gauge_image}" width="<?php echo $usedsize?>" height="16px" /></td>
                        </tr>
                    </table>
                </div>
            {/IF}
        </td>

        <td class="content">
            {IF ERROR}<div class="attention">{ERROR}</div>{/IF}
            {IF OKMSG}<div class="information">{OKMSG}</div>{/IF}
            {INCLUDE PM_TEMPLATE}
        </td>
    </tr>
</table>

