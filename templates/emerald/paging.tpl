<!-- BEGIN TEMPLATE paging.tpl -->
{IF TOTALPAGES}
<div class="paging">
  {LANG->Page} {CURRENTPAGE} {LANG->of} {TOTALPAGES}&nbsp;&nbsp;&nbsp;&nbsp;<strong>{LANG->Pages}:</strong>&nbsp;{IF URL->FIRSTPAGE}<a href="{URL->FIRSTPAGE}" title="{LANG->FirstPage}"><img src="{URL->TEMPLATE}/images/control_first.png" class="icon1616" alt="{LANG->FirstPage}" /></a>{/IF}{IF URL->PREVPAGE}<a href="{URL->PREVPAGE}" title="{LANG->PrevPage}"><img src="{URL->TEMPLATE}/images/control_prev.png" class="icon1616" alt="{LANG->PrevPage}" /></a>{/IF}{LOOP PAGES}{IF PAGES->pageno CURRENTPAGE}<strong class="current-page">{PAGES->pageno}</strong>{ELSE}<a href="{PAGES->url}">{PAGES->pageno}</a>{/IF}{/LOOP PAGES}{IF URL->NEXTPAGE}<a href="{URL->NEXTPAGE}" title="{LANG->NextPage}"><img src="{URL->TEMPLATE}/images/control_next.png" class="icon1616" alt="{LANG->NextPage}" /></a>{/IF}{IF URL->LASTPAGE}<a href="{URL->LASTPAGE}" title="{LANG->LastPage}"><img src="{URL->TEMPLATE}/images/control_last.png" class="icon1616" alt="{LANG->LastPage}" /></a>{/IF}
</div>
{/IF}
<!-- END TEMPLATE paging.tpl -->
