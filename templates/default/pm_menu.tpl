<div class="phorum-menu" style="text-align: left; white-space: nowrap">
<ul>
{LANG->PrivateMessages}
{LOOP PM_FOLDERS}
<li><a {IF PM_FOLDERS->id FOLDER_ID}class="phorum-current-page" {/IF}href="{PM_FOLDERS->url}">{PM_FOLDERS->name}</a><small>{IF PM_FOLDERS->total}&nbsp;({PM_FOLDERS->total}){/IF}{IF PM_FOLDERS->new}&nbsp;(<span class="PhorumNewFlag">{PM_FOLDERS->new} {LANG->newflag}</span>){/IF}</small></li>
{/LOOP PM_FOLDERS}
<li style="margin-top: 15px"><a {IF PM_PAGE "folders"}class="phorum-current-page" {/IF}href="{URL->PM_FOLDERS}">{LANG->EditFolders}</a></li>
<li><a {IF PM_PAGE "send"}class="phorum-current-page" {/IF}href="{URL->PM_SEND}">{LANG->SendPM}</a></li>
<li><a {IF PM_PAGE "buddies"}class="phorum-current-page" {/IF} href="{URL->BUDDIES}">{LANG->Buddies}</a></li>
</ul>

</div>

{INCLUDE pm_max_messagecount}

