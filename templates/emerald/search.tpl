{IF SEARCH->noresults}
    <div class="information">
        <h4>{LANG->NoResults}</h4>
        <p>{LANG->NoResultsHelp}</p>
    </div>
{/IF}


{IF SEARCH->showresults}

    <div class="nav">
        {INCLUDE "paging"}
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

    <div class="nav">
        {INCLUDE "paging"}
    </div>

    <br />
    <br />
{/IF}

{IF NOT SEARCH->match_type "USER_ID"}
    <div class="nav">
        {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
        {IF URL->POST}
            <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
        {/IF}
    </div>

    <div class="generic" id="search-form">

        <form action="{URL->ACTION}" method="get">
            {POST_VARS}
            {LANG->SearchMessages}:<br />
            <input type="text" name="search" id="phorum_search_message" size="30" maxlength="" value="{SEARCH->safe_search}" />
            <select name="match_type">
                <option value="ALL" {IF SEARCH->match_type "ALL"}selected="selected"{/IF}>{LANG->MatchAll}</option>
                <option value="ANY" {IF SEARCH->match_type "ANY"}selected="selected"{/IF}>{LANG->MatchAny}</option>
                <option value="PHRASE" {IF SEARCH->match_type "PHRASE"}selected="selected"{/IF}>{LANG->MatchPhrase}</option>
            </select>
            <input type="submit" value="{LANG->Search}" /><br />
            <br />
            {LANG->SearchAuthors}:<br />
            <input type="text" id="phorum_search_author" name="author" size="30" maxlength="" value="{SEARCH->safe_author}" /><br />
            <br />
            {LANG->Forums}:<br />
            <select name="match_forum[]" size="{SEARCH->forum_list_length}" multiple="multiple">
                <option value="ALL" {IF SEARCH->match_forum "ALL"}selected="selected"{/IF}>{LANG->MatchAllForums}</option>
                {LOOP SEARCH->forum_list}
                    {IF SEARCH->forum_list->folder_flag}
                        <optgroup style="padding-left: {SEARCH->forum_list->indent}px" label="{SEARCH->forum_list->name}" />
                    {ELSE}
                        <option style="padding-left: {SEARCH->forum_list->indent}px" value="{SEARCH->forum_list->forum_id}" {IF SEARCH->forum_list->selected}selected="selected"{/IF}>{SEARCH->forum_list->name}</option>
                    {/IF}
                {/LOOP SEARCH->forum_list}
            </select>
            <br />
            <br />
            {LANG->Options}:<br />
            <select name="match_threads">
                <option value="1" {IF SEARCH->match_threads "1"}selected="selected"{/IF}>{LANG->MatchThreads}</option>
                <option value="0" {IF SEARCH->match_threads "0"}selected="selected"{/IF}>{LANG->MatchMessages}</option>
            </select>
            &nbsp;
            &nbsp;
            <select name="match_dates">
                <option value="30" {IF SEARCH->match_dates 30}selected="selected"{/IF}>{LANG->Last30Days}</option>
                <option value="90" {IF SEARCH->match_dates 90}selected="selected"{/IF}>{LANG->Last90Days}</option>
                <option value="365" {IF SEARCH->match_dates 365}selected="selected"{/IF}>{LANG->Last365Days}</option>
                <option value="0" {IF SEARCH->match_dates 0}selected="selected"{/IF}>{LANG->AllDates}</option>
            </select>
            <br />
        </form>
    </div>
{/IF}


