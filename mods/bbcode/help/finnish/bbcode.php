<html>
  <head>
    <title>BBcode ohje</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>BBcode ohje</h2>

    BBCode on lyhennys sanoista Bulletin Board code. Se on kuvauskieli
    mit‰ forumin k‰ytt‰j‰t voivat k‰ytt‰‰ viestiens‰ muotoiluun. T‰m‰
    ohje kertoo mit‰ BB koodeja t‰ss‰ Forumissa voi k‰ytt‰‰.

    <h3>Lihavoitu teksti: [b]...[/b]<br/>
        Alleviivattu teksti: [u]...[/u]<br/>
        Kursivoitu teksti: [i]...[/i]<br/>
        Yliviivattu teksti: [s]...[/s]<br/>
        </h3>
    K‰ytt‰m‰ll‰ n‰it‰ elementtej‰ voit vaikuttaa tekstin muotoiluun.
    Esimerkiksi:<br/><br/>
    <tt>
    [b]T‰m‰ teksti on lihavoitu[/b]<br/>
    [u]T‰m‰ teksti on alleviivattu[/u]<br/>
    [i]T‰m‰ teksti on kursivoitu[/i]<br/>
    [s]T‰m‰ teksti on yliviivattu[/s]<br/>
    [b][i]T‰ss‰ tekstiss‰[/i] on [s]usiampia[/s] useampia[/b] tyylej‰
    </tt><br/><br/>
    Ne n‰ytt‰v‰t t‰lt‰:<br/><br/>
    <b>T‰m‰ teksti on lihavoitu</b><br/>
    <u>T‰m‰ teksti on alleviivattu</u><br/>
    <i>T‰m‰ teksti on kursivoitu</i><br/>
    <strike>T‰m‰ teksti on yliviivattu</strike><br/>
    <b><i>T‰ss‰ tekstiss‰</i> on <strike>usiampia</strike> useampia</b> tyylej‰


    <h3>Yl‰viite: [sup]...[/sup]<br/>Alaviite: [sub]...[/sub] </h3>
    K‰ytt‰m‰ll‰ n‰it‰ elementtej‰ voit k‰ytt‰‰ yl‰- tai alaviitteit‰.
    N‰m‰ elementit ovat hyˆdyllisi‰ k‰ytt‰ess‰si esimerkiksi
    "2<sup>4</sup> = 16" tai "H<sub>2</sub>O". esimerkki:<br/><br/>
    <tt>
    [sup]yl‰viite[/sup] tavallinen [sub]alaviite[/sub]
    </tt><br/><br/>
    T‰m‰ n‰ytt‰‰ t‰lt‰:<br/><br/>
    <sup>yl‰viite</sup> tavallinen <sub>alaviite</sub>

    <h3>Kirjasin v‰ri: [color=...]...[/color]</h3>
    T‰m‰ elementti mahdollistaa tekstin tai sen osan v‰rj‰‰misen.
    V‰rin t‰ytyy olla oikea HTML v‰rikoodi (kuten "blue", "red",
    "#ff0000", "#888", jne.). Esimerkiksi:<br/><br/>
    <tt>
    Kuka pelk‰‰
    <nobr>[color=red]punaista[/color],</nobr>
    <nobr>[color=#eeaa00]keltaista[/color]</nobr> ja
    <nobr>[color=#30f]sinist‰[/color]?</nobr>
    </tt><br/><br/>
    T‰m‰ n‰ytt‰‰ t‰lt‰:<br/><br/>
    Kuka pelk‰‰
    <span style="color: #FF3300">punaista</span>,
    <span style="color: #eeaa00">keltaista</span> ja
    <span style="color: #3300FF">sinist‰</span>?

    <h3>Kirjasin koko: [size=...]...[/size]</h3>
    T‰m‰ elementti mahdollistaa tekstin koon muuttamisen.
    Koon pit‰‰ olla oikea HTML koko elementin mukainen (kuten "12px",
    "small", "large", jne.). Esimerkiksi:<br/><br/>
    <tt>
    <nobr>[size=x-small]N‰ytt‰‰[/size]</nobr>
    <nobr>[size=small]silt‰[/size]</nobr>
    <nobr>[size=medium]ett‰[/size]</nobr>
    <nobr>[size=large]kasvan[/size]</nobr>
    <nobr>[size=x-large]suuremmaksi![/size]</nobr>
    </tt><br/><br/>
    Ja se n‰ytt‰‰ t‰lt‰:<br/><br/>
    <span style="font-size: x-small">N‰ytt‰‰</span>
    <span style="font-size: small">silt‰</span>
    <span style="font-size: medium">ett‰</span>
    <span style="font-size: large">kasvan</span>
    <span style="font-size: x-large">suuremmaksi!</span>

    <h3>Tekstin keskitys: [center]...[/center]</h3>
    T‰m‰ elementti mahdollistaa tekstin keskitt‰misen sivulla.
    Esimerkiksi:<br/><br/>
    <tt>
    [center]Olen selke‰sti kaiken keskell‰[/center]
    </tt><br/><br/>
    Mik‰ n‰ytt‰‰ t‰lt‰:<br/><br/>
    <center>Olen selke‰sti kaiken keskell‰</center>

    <h3>Liit‰ kuva muualta netist‰: [img]...[/img]<br/>
        Lis‰‰ nettisivun osoite: [url]...[/url] or [url=...]...[/url]<br/>
        Lis‰‰ s‰hkˆpostiosoite [email]...[/email]</h3>
    N‰ill‰ elementeill‰ m‰‰ritell‰‰n linkkej‰ muihin netin osoitteisiin.
    T‰ss‰ muutama esimerkki:<br/><br/>
    <tt>
    [img]http://www.somesite.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Vierailu Phorum.orgissa![/url]<br/>
    [email]someuser@somesite.com[/email]
    </tt></br></br>
    Mik‰ n‰ytt‰‰ t‰lt‰:<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Vieraile Phorum.orgissa!</a><br/>
    <a href="mailto:someuser@somesite.com">someuser@somesite.com</a>

    <h3>Vakiomittainen, muotoiltu koodi: [code]...[/code]</h3>
    Voi olla ett‰ joskus haluat liitt‰ viestiin ASCII kuvia,
    ohjelmakoodin p‰tki‰, tabulatuureja jne. N‰iss‰ tapauksissa
    voit k‰ytt‰‰ [code] elementti‰. Esimerkki:
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

ilman ymp‰rˆiv‰‰ [code] elementti‰, t‰m‰ n‰ytt‰isi k‰sitt‰m‰ttˆm‰lt‰ kuten:
<br/><br/>
  _____  _                                <br/>
 |  __ \| |                               <br/>
 | |__) | |__   ___  _ __ _   _ _ __ ___  <br/>
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \ <br/>
 | |    | | | | (_) | |  | |_| | | | | | |<br/>
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br/>
<br/>
Mutta [code] sen ymp‰rill‰, se n‰ytt‰‰ t‰lt‰:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Lainattu teksti: [quote]...[/quote] tai [quote=...]...[/quote]</h3>
    Jos haluat n‰ytt‰‰ lainauksen viestiss‰si voit k‰ytt‰‰ t‰t‰
    elementti‰. Voit valita n‰ytet‰‰nkˆ lainaamasi henkilˆn nime‰
    tai tunnusta. Esimerkki:<br/><br/>
    <tt>
    [quote]Phorum on paras![/quote]<br/>
    [quote=William Shakespearen Hamletista]<br/>
    To be or not to be, --that is the question:--<br/>
    Whether 'tis nobler in the mind to suffer<br/>
    The slings and arrows of outrageous fortune<br/>
    Or to take arms against a sea of troubles,<br/>
    And by opposing end them?<br/>
    [/quote]
    </tt><br/><br/>
    N‰kyy n‰in:<br/><br/>
    <blockquote class="bbcode">Quote:<div>Phorum on paras!</div></blockquote>
    <blockquote class="bbcode">Quote:<div><strong>William Shakespearen Hamletista</strong><br />
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

    <h3>Lis‰‰ vaakaviiva: [hr]</h3>
    Lis‰t‰ksesi erottavan vaakaviivan voit k‰ytt‰‰ [hr] elementti‰.
    Se n‰ytt‰‰ t‰lt‰:
    <hr>
    K‰yt‰ esimerkiksi pitkien viestien jaksottamiseen ja j‰sent‰miseen.

    <h3>Itemized list:<br/>[list]<br/>[*] item 1<br/>[*] item 2<br/>[/list]</h3>


    The [list] tag can be used for adding lists of items to your message.
    By default, the list items will be shown using bullets in front of
    them. By assigning one of "1" (numbers), "a" (letters), "A" (capital
    letters), "i" (Roman numbers) or "I" (Roman capital numbers), the
    bullet type can be changed. Examples:<br/><br/>
    <tt>
    [list]<br/>
    [*] item 1<br/>
    [*] item 2<br/>
    [list]<br/>
    [list=A]<br/>
    [*] another item 1<br/>
    [*] another item 2<br/>
    [/list]<br/>
    </tt><br/><br/>
    These will be displayed as:<br/><br/>
    <ul><li>item 1</li><li>item 2</li></ul>
    <ol type="A"><li>another item 1</li><li>another item 2</li></ol>

    <br/><br/><br/><br/>
  </body>
</html>
