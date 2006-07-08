{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF MESSAGE}
    <div class="information">
        {MESSAGE}
        {IF URL->CLICKHERE}
            <p><a href="{URL->CLICKHERE}">{CLICKHEREMSG}</a></p>
        {/IF}
        {IF URL->REDIRECT}
            <p><a href="{URL->REDIRECT}">{BACKMSG}</a></p>
        {/IF}
    </div>
{/IF}

