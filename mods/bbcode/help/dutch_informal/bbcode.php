<html>
  <head>
    <title>BBcode hulp</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>BBcode hulp informatie</h2>

    BBcode is een afkorting voor Bulletin Board code. Dit is een
    opmaaktaal die kan worden gebruikt door forumgebruikers voor
    het opmaken van hun berichten. Deze pagina beschrijft welke
    BBcode gebruikt kan worden op dit forum. Het kan zijn dat de
    beheerder van dit forum niet alle codes heeft geactiveerd, dus
    het kan voorkomen dat sommige codes niet werken.

    <h3>Vetgedrukte tekst: [b]...[/b]<br/>
        Onderstreepte tekst: [u]...[/u]<br/>
        Schuingedrukte tekst: [i]...[/i]<br/>
        Doorgestreepte tekst: [s]...[/s]<br/>
        </h3>
    Met deze tags kan er een stijl worden toegekend aan een stuk tekst.
    Voorbeelden:<br/><br/>
    <tt>
    [b]Deze tekst is vetgedrukt[/b]<br/>
    [u]Deze tekst is onderstreept[/u]<br/>
    [i]Deze tekst is schuingedrukt[/i]<br/>
    [s]Deze tekst is doorgestreept[/s]<br/>
    [b][i]Deze tekst[/i] heeft een [s]miks[/s] mix[/b]
    </tt><br/><br/>
    Deze zullen worden getoond als:<br/><br/>
    <b>Deze tekst is vetgedrukt</b><br/>
    <i>Deze tekst is onderstreept</i><br/>
    <u>Deze tekst is schuingedrukt</u><br/>
    <strike>Deze tekst is doorgestreept</strike><br/>
    <b><i>Deze tekst</i> heeft een <strike>miks</strike> mix</b>


    <h3>Bovenschrift: [sup]...[/sup]<br/>
        Onderschrift: [sub]...[/sub]</h3>
    Met deze tags kan een stuk tekst als bovenschrift of onderschrift
    worden weergegeven. Dit is te gebruiken voor dingen als
    "2<sup>4</sup> = 16" of "H<sub>2</sub>O". Voorbeeld:<br/><br/>
    <tt>
    [sup]bovenschrift[/sup] normaal [sub]onderschrift[/sub]
    </tt><br/><br/>
    Dit zal worden getoond als:<br/><br/>
    <sup>superscript</sup> normal <sub>subscript</sub>

    <h3>Tekstkleur: [color=...]...[/color]</h3>
    Deze tag wordt gebruikt om een kleur toe te kennen aan
    een stuk tekst. De kleur moet een geldige HTML kleurcode zijn
    (bijvoorbeeld "blue", "red", "#ff0000", "#888", etc.).
    Voorbeeld:<br/><br/>
    <tt>
    Wie is er bang voor
    <nobr>[color=red]rood[/color],</nobr>
    <nobr>[color=#eeaa00]geel[/color]</nobr> en
    <nobr>[color=#30f]blauw[/color]?</nobr>
    </tt><br/><br/>
    Dit zal worden getoond als:</br></br>
    Wie is er bang voor
    <span style="color: red">rood</span>,
    <span style="color: #eeaa00">geel</span> en
    <span style="color: #30F">blauw</span>?

    <h3>Tekstgrootte: [size=...]...[/size]</h3>
    Deze tag wordt gebruikt voor het wijzigen van de tekstgrootte
    van een stuk tekst. De tekstgrootte moet als geldige HTML
    tekstgrootte indicator worden opgegeven (bijvoorbeeld "12px",
    "small", "large", etc.). Voorbeeld:<br/><br/>
    <tt>
    <nobr>[size=x-small]Het[/size]</nobr>
    <nobr>[size=small]lijkt[/size]</nobr>
    <nobr>[size=medium]alsof[/size]</nobr>
    <nobr>[size=large]ik[/size]</nobr>
    <nobr>[size=x-large]groei![/size]</nobr>
    </tt><br/><br/>
    Dit zal worden getoond als:</br></br>
    <span style="font-size: x-small">Het</span>
    <span style="font-size: small">lijkt</span>
    <span style="font-size: medium">alsof</span>
    <span style="font-size: large">ik</span>
    <span style="font-size: x-large">groei!</span>

    <h3>Tekst centreren: [center]...[/center]</h3>
    Dit kan worden gebruikt om een stuk tekst
    gecentreerd weer te geven op het scherm.
    Voorbeeld:<br/><br/>
    <tt>
    [center]Ik zit er midden in![/center]
    </tt><br/><br/>
    Dit zal worden getoond als:</br></br>
    <center>Ik zit er midden in!</center>

    <h3>Link een afbeelding van het web: [img]...[/img]<br/>
        Link naar een website: [url]...[/url] or [url=...]...[/url]<br/>
        Link naar een e-mailadres: [email]...[/email]</h3>
    Dit zijn allemaal tags voor het linken van bronnen op het
    internet. Here zijn een paar voorbeelden:<br/><br/>
    <tt>
    [img]http://www.ergens.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Visit Phorum.org![/url]<br/>
    [email]iemand@ergens.com[/email]
    </tt></br></br>
    Dit zal worden getoond als:</br></br>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Visit Phorum.org!</a><br/>
    <a href="mailto:iemand@ergens.com">iemand@ergens.com</a>

    <h3>Monospace, opgemaakte code: [code]...[/code]</h3>
    Soms kun je dingen hebben als ASCII art, programmeercode,
    gitaar tabulatuur, etc., welke je in een bericht wilt gebruiken.
    In die gevallen kun je gebruik maken van de [code] tag. Voorbeeld:
<pre>
[code]
 _____  _
|  __ \| |
| |__) | |__   ___  _ __ _   _ _ __ ___
|  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
| |    | | | | (_) | |  | |_| | | | | | |
|_|    |_| |_|\___/|_|   \__,_|_| |_| |_|
[/code]
</pre>

Zonder gebruik te maken van [code] rond deze ASCII art, zou dit
niet goed worden weergegeven:
<br/><br/>
 _____  _                                <br/>
|  __ \| |                               <br/>
| |__) | |__   ___  _ __ _   _ _ __ ___  <br/>
|  ___/| '_ \ / _ \| '__| | | | '_ ` _ \ <br/>
| |    | | | | (_) | |  | |_| | | | | | |<br/>
|_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br/>
<br/>
Maar met gebruik van [code], ziet het er zo uit:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Aangehaalde tekst: [quote]...[/quote] or [quote=...]...[/quote]</h3>
    Om een quote van iemand aan te halen in een bericht, kan deze
    tag worden gebruikt. De naam van de persoon van wie de quote is
    hoeft niet verplicht te worden opgegeven. Voorbeelden:
    <tt>
    [quote]Phorum is het beste forum![/quote]<br/>
    [quote=Uit Hamlet, door William Shakespeare]<br/>
    To be or not to be, --that is the question:--<br/>
    Whether 'tis nobler in the mind to suffer<br/>
    The slings and arrows of outrageous fortune<br/>
    Or to take arms against a sea of troubles,<br/>
    And by opposing end them?<br/>
    [/quote]
    </tt><br/><br/>
    Deze zullen worden getoond als:<br/><br/>
    <blockquote class="bbcode">Quote:<div>Phorum is het beste forum!</div></blockquote>
    <blockquote class="bbcode">Quote:<div><strong>Uit Hamlet, door William Shakespeare</strong><br />
    To be or not to be, --that is the question:--
    <br />
    Whether 'tis nobler in the mind to suffer
    <br />
    The slings and arrows of outrageous fortune
    <br />
    Or to take arms against a sea of troubles,
    <br />
    And by opposing end them?
    <br /></div></blockquote>

    <h3>Een horizontale scheidingslijn invoegen: [hr]</h3>
    Om een scheidingslijn in een bericht aan te brengen,
    gebruik de [hr] tag. Deze zal worden getoond als:
    <hr>
    Dit is voornamelijk bruikbaar voor het aanbrengen van structuur
    in lange berichten.

    <h3>Lijst met items:<br/>[list]<br/>[*] item 1<br/>[*] item 2<br/>[/list]</h3>

    De [list] tag kan worden gebruikt voor het toevoegen van lijsten met
    items aan een bericht. Standaard worden zogenaamde "bullets" gebruikt
    voor elk item. Door het toewijzen van "1" (cijfers), "a" (letters),
    "A" (hoofdletters), "i" (Romeinse cijfers) of "I" (Romeinse cijfers
    in hoofdletters), kan dit worden gewijzigd.
    <tt>
    [list]<br/>
    [*] item 1<br/>
    [*] item 2<br/>
    [list]<br/>
    [list=A]<br/>
    [*] nog een item 1<br/>
    [*] nog een item 2<br/>
    [/list]<br/>
    </tt><br/><br/>
    Deze zullen worden getoond als:<br/><br/>
    <ul><li>item 1</li><li>item 2</li></ul>
    <ol type="A"><li>nog een item 1</li><li>nog een item 2</li></ol>

    <br/><br/><br/><br/>
  </body>
</html>
