<p>{LANG->Message}: <a href="{MESSAGE->URL->READ}">{MESSAGE->subject}</a></p>

<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
</div>

{LOOP CHANGES}

    <div class="message">

        <div class="generic">
            {IF CHANGES->original}
                <h4>{LANG->OriginalMessage}</h4>
                {LANG->Author}: {CHANGES->username}<br />
                {LANG->Date}: {CHANGES->date}<br />
            {ELSE}
                {LANG->ChangeBy}: {CHANGES->username}<br />
                {LANG->ChangeDate}: {CHANGES->date}<br />
            {/IF}
            
        <strong>
            <br />{CHANGES->colored_subject}<br />
        </strong> 
        

            
            
        </div>
       
        
        <div class="message-body">
            {CHANGES->colored_body}
        </div>

    </div>
{/LOOP CHANGES}


