<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    {IF URL->POST}
        <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
    {/IF}
</div>

{IF NOT SEARCH->match_type "USER_ID"}
  <div id="search-form" class="generic">
    <table border="0" cellspacing="0" class="form-table">
        <tr>
            <td width="50%" valign="top">

                <h4>{LANG->SearchMessages}</h4>

                <form action="{URL->ACTION}" method="get">
                    <input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />
                    {POST_VARS}
                    <input type="text" name="search" id="phorum_search_message" size="30" maxlength="" value="{SEARCH->safe_search}" />&nbsp;<input type="submit" value="{LANG->Search}" />
                    <br />
                    <select name="match_forum">
                        <option value="ALL" {IF SEARCH->match_forum "ALL"}selected="selected"{/IF}>{LANG->MatchAllForums}</option>
                        {IF SEARCH->allow_match_one_forum}
                            <option value="THISONE" {IF SEARCH->match_forum "THISONE"}selected="selected"{/IF}>{LANG->MatchThisForum}</option>
                        {/IF}
                    </select>
                    <br />
                    <select name="match_dates">
                        <option value="30" {IF SEARCH->match_dates 30}selected="selected"{/IF}>{LANG->Last30Days}</option>
                        <option value="90" {IF SEARCH->match_dates 90}selected="selected"{/IF}>{LANG->Last90Days}</option>
                        <option value="365" {IF SEARCH->match_dates 365}selected="selected"{/IF}>{LANG->Last365Days}</option>
                        <option value="0" {IF SEARCH->match_dates 0}selected="selected"{/IF}>{LANG->AllDates}</option>
                    </select>
                    <select name="match_type">
                        <option value="ALL" {IF SEARCH->match_type "ALL"}selected="selected"{/IF}>{LANG->MatchAll}</option>
                        <option value="ANY" {IF SEARCH->match_type "ANY"}selected="selected"{/IF}>{LANG->MatchAny}</option>
                        <option value="PHRASE" {IF SEARCH->match_type "PHRASE"}selected="selected"{/IF}>{LANG->MatchPhrase}</option>
                    </select>
                </form>
            </td>
            <td width="50%" valign="top">

                <h4>{LANG->SearchAuthors}</h4>

                <form action="{URL->ACTION}" method="get">
                    <input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />
                    <input type="hidden" name="match_type" value="AUTHOR" />
                    {POST_VARS}
                    <input type="text" id="phorum_search_author" name="search" size="30" maxlength="" value="{SEARCH->safe_search}" />&nbsp;<input type="submit" value="{LANG->Search}" />
                    <br />
                    <select name="match_forum">
                        <option value="ALL" {IF SEARCH->match_forum "ALL"}selected{/IF}>{LANG->MatchAllForums}</option>
                        {IF SEARCH->allow_match_one_forum}
                            <option value="THISONE" {IF SEARCH->match_forum "THISONE"}selected{/IF}>{LANG->MatchThisForum}</option>
                        {/IF}
                    </select>
                    <br />
                    <select name="match_dates">
                        <option value="30" {IF SEARCH->match_dates 30}selected="selected"{/IF}>{LANG->Last30Days}</option>
                        <option value="90" {IF SEARCH->match_dates 90}selected="selected"{/IF}>{LANG->Last90Days}</option>
                        <option value="365" {IF SEARCH->match_dates 365}selected="selected"{/IF}>{LANG->Last365Days}</option>
                        <option value="0" {IF SEARCH->match_dates 0}selected="selected"{/IF}>{LANG->AllDates}</option>
                    </select>
                </form>
            </td>
        </tr>
    </table>
  </div>
{/IF}

{IF SEARCH->noresults}
    <div class="information">
        <h4>{LANG->NoResults}</h4>
        <p>{LANG->NoResultsHelp}</p>
    </div>
{/IF}


{IF SEARCH->showresults}

    {INCLUDE "paging"}

    <div class="nav">
        {LANG->Results} {RANGE_START} - {RANGE_END} {LANG->of} {TOTAL}
    </div>


    <div class="generic search">

        {LOOP MATCHES}

            <div class="search-result">

                <h4><a href="{MATCHES->URL->READ}">{MATCHES->subject}</a><small> - {MATCHES->datestamp}</small></h4>

                <blockquote>{MATCHES->short_body}</blockquote>

                {LANG->by} <strong>{MATCHES->author}</strong>

                {IF MATCHES->forum_id}
                    - <a href="{MATCHES->URL->LIST}">{MATCHES->forum_name}</a>
                {/IF}

            </div>

        {/LOOP MATCHES}
    </div>

    {INCLUDE "paging"}

{/IF}

