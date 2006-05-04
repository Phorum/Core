{IF FOLDERS}
<div class="icon-folder" id="folders-header">{LANG->Folders}</div>
<div id="folders">
    {LOOP FOLDERS}
    <div class="folder">
    <a href="{FOLDERS->URL->LIST}">{FOLDERS->name}</a>
    <p>{FOLDERS->description}</p>
    </div>
    {/LOOP FOLDERS}
</div>
{/IF FOLDERS}

{IF FORUMS}
<table id="forums" cellspacing="0">
  <tr class="folder">
    <th class="folder-name icon-folder">{LANG->Forums}</th>
    <th class="folder-threads">{LANG->Threads}</th>
    <th class="folder-posts">{LANG->Posts}</th>
    <th class="folder-last-post">{LANG->LastPost}</th>
  </tr>

  {LOOP FORUMS}
      <tr class="forum">
        <td class="forum-name"><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a>
            <p>{FORUMS->description}</p>
            <small>
                {IF USER->user_id}<a class="icon icon-tag-green" href="{FORUMS->URL->MARK_READ}">{LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                {IF FORUMS->URL->RSS}<a class="icon icon-feed" href="{FORUMS->URL->RSS}">{LANG->RSS}</a>{/IF}
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
  {/LOOP FORUMS}
</table>
{/IF FORUMS}
