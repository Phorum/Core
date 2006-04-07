{IF FOLDERS}
<div style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/folder.png');" id="folders-header">{LANG->Folders}</div>
<div id="folders">
    {LOOP FOLDERS}
    <div class="folder">
    <a href="{FOLDERS->url}">{FOLDERS->name}</a>
    <p>{FOLDERS->description}</p>
    </div>
    {/LOOP FOLDERS}
</div>
{/IF FOLDERS}

{IF FORUMS}
<table id="forums" cellspacing="0">
  <tr class="folder">
    <th style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/folder.png');" class="folder-name">{LANG->Forums}</th>
    <th class="folder-threads">{LANG->Threads}</th>
    <th class="folder-posts">{LANG->Posts}</th>
    <th class="folder-last-post">{LANG->LastPost}</th>
  </tr>

  {LOOP FORUMS}
  {IF FORUMS->folder_flag}
      <tr class="forum">
        <td class="forum-name"><a href="{FORUMS->url}">{FORUMS->name}</a>
            <p>{FORUMS->description}</p>
            <small>
                {IF USER->user_id}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/tag_green.png');" href="{FORUMS->URL->MARKREAD}">{LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                {IF FORUMS->URL->RSS}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/feed.png');" href="{FORUMS->URL->RSS}">{LANG->RSS}</a>{/IF}
            </small>
        </td>
        <td class="forum-threads" nowrap="nowrap">
          {FORUMS->thread_count}
          {IF FORUMS->new_threads}
            (<span class="PhorumNewFlag">{FORUMS->new_threads} {LANG->newflag}</span>)
          {/IF}
        </td>
        <td class="forum-posts" nowrap="nowrap">
          {FORUMS->message_count}
          {IF FORUMS->new_messages}
            (<span class="PhorumNewFlag">{FORUMS->new_messages} {LANG->newflag}</span>)
          {/IF}
        </td>
        <td class="forum-last-post" nowrap="nowrap">{FORUMS->last_post}</td>
      </tr>
  {ELSE}
      <tr class="forum">
        <td class="forum-name"><a href="{FORUMS->url}">{FORUMS->name}</a>
            <p>{FORUMS->description}</p>
            <small>
                {IF USER->user_id}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/tag_green.png');" href="{FORUMS->URL->markread}">{LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                {IF FORUMS->url_rss}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/feed.png');" href="{FORUMS->url_rss}">{LANG->RSS}</a>{/IF}
            </small>
        </td>
        <td class="forum-threads" nowrap="nowrap">
          {FORUMS->thread_count}
          {IF FORUMS->new_threads}
            (<span class="PhorumNewFlag">{FORUMS->new_threads} {LANG->newflag}</span>)
          {/IF}
        </td>
        <td class="forum-posts" nowrap="nowrap">
          {FORUMS->message_count}
          {IF FORUMS->new_messages}
            (<span class="PhorumNewFlag">{FORUMS->new_messages} {LANG->newflag}</span>)
          {/IF}
        </td>
        <td class="forum-last-post" nowrap="nowrap">{FORUMS->last_post}</td>
      </tr>
  {/IF}
  {/LOOP FORUMS}
</table>
{/IF FORUMS}
