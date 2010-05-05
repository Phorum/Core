{IF EDITOR "posting"}
  <div class="PhorumStdBlockHeader PhorumNarrowBlock">
{ELSEIF EDITOR "pm"}
  <div class="PhorumStdBlockHeader" style="text-align: left; width:99%">
{ELSE} {! unknown type, but let's put in a div }
  <div>
{/IF}
  {CONTENT}
<small>
