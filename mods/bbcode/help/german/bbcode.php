<html>
  <head>
    <title>BBcode Hilfe</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>BBcode Hilfe</h2>

    BBcode ist die Kurzform f&uuml;r Bulletin Board code. Es ist eine Formatierungssprache
    welche von Forumsnutzern zur Formatierung von Nachrichten verwendet werden kann.
    Diese Hilfeseite beschreibt die in diesem Forum nutzbaren Formatierungen.

    <h3>Fetter Text: [b]...[/b]<br/>
        Unterstrichener Text: [u]...[/u]<br/>
        Kursiver Text: [i]...[/i]<br/>
        Durchgestrichener Text: [s]...[/s]<br/>
        </h3>
    Mit diesen Markierungen k&ouml;nnen Sie Formatierungen zu Textbl&ouml;cken hinzuf&uuml;gen.
    Beispiele:<br/><br/>
    <tt>
    [b]Dieser Text ist fett.[/b]<br/>
    [i]Dieser Text ist kursiv.[/i]<br/>
    [u]Dieser Text ist unterstrichen.[/u]<br/>
    [s]Dieser Text ist durchgestrichen.[/s]<br/>
    [b][i]Dieser Text[/i] ist [s]gemischt[/s][/b]
    </tt><br/><br/>
    Dies wird angezeigt als:<br/><br/>
    <b>Dieser Text ist fett.</b><br/>
    <i>Dieser Text ist kursiv.</i><br/>
    <u>Dieser Text ist unterstrichen.</u><br/>
    <strike>Dieser Text ist durchgestrichen.</strike><br/>
    <b><i>Dieser Text</i> ist <s>gemischt</s></b>


    <h3>Hochgestellt: [sup]...[/sup]<br/>Tiefgestellt: [sub]...[/sub] </h3>
    Mit diesen Markierungen k&ouml;nnen Sie Text hoch- oder tiefstellen.
    Das ist z.B. in den folgenden F&auml;llen sinnvoll:
    "2<sup>4</sup> = 16" oder "H<sub>2</sub>O". Beispiel:<br/><br/>
    <tt>
    [sup]hochgestellt[/sup] normal [sub]tiefgestellt[/sub]
    </tt><br/><br/>
    Dies wird wie folgt angezeigt:<br/><br/>
    <sup>hochgestellt</sup> normal <sub>tiefgestellt</sub>

    <h3>Textfarbe: [color=...]...[/color]</h3>
    Diese Markierung kann genutzt werden, um Textteile farblich hervorzuheben.
    Die Farbe mu&szlig; ein g&uuml;ltiger HTML-Wert sein, (z.B. "blue", "red",
    "#ff0000", "#888", etc.). Beispiel:<br/><br/>
    <tt>
    Wer hat Angst vor
    <nobr>[color=red]rot[/color],</nobr>
    <nobr>[color=#eeaa00]gelb[/color]</nobr> und
    <nobr>[color=#30f]blau[/color]?</nobr>
    </tt><br/><br/>
    Dies wird angezeigt als:<br/><br/>
    Wer hat Angst vor
    <span style="color: red">rot</span>,
    <span style="color: #eeaa00">gelb</span> und
    <span style="color: #30f">blau</span>?

    <h3>Textgr&ouml;&szlig;e: [size=...]...[/size]</h3>
    Diese Markierung kann genutzt werden, um die Schriftgr&ouml;&szlig;e von Textteile zu &auml;ndern.
    Die Gr&ouml;&szlig;e mu&szlig; eine g&uuml;ltige HTML-Angabe sein (z.B. "12px",
    "small", "large", etc.). Beispiel:<br/><br/>
    <tt>
    <nobr>[size=x-small]It[/size]</nobr>
    <nobr>[size=small]looks[/size]</nobr>
    <nobr>[size=medium]like[/size]</nobr>
    <nobr>[size=large]I'm[/size]</nobr>
    <nobr>[size=x-large]growing![/size]</nobr>
    </tt><br/><br/>
    Dies wird angezeigt als:<br/><br/>
    <span style="font-size: x-small">It</span>
    <span style="font-size: small">looks</span>
    <span style="font-size: medium">like</span>
    <span style="font-size: large">I'm</span>
    <span style="font-size: x-large">growing!</span>

    <h3>Zentrierter Text: [center]...[/center]</h3>
    Sie k&ouml;nnen diese Markierung zum Zentrieren von Text
    auf dem Bildschirm nutzen. Beispiel:<br/><br/>
    <tt>
    [center]Ich bin im Zentrum von allem ...[/center]
    </tt><br/><br/>
    Dies wird angezeigt als:<br/><br/>
    <center>Ich bin im Zentrum von allem ...</center>

    <h3>Eine Grafik aus dem Web anzeigen/verlinken: [img]...[/img]<br/>
        Eine Webseite verlinken: [url]...[/url] or [url=...]...[/url]<br/>
        Eine E-Mail-Adresse verlinken [email]...[/email]</h3>
    Dies sind alles Markierungen um Web-Adressen zu verlinken. Hier sind ein paar Beispiele:<br/><br/>
    <tt>
    [img]http://www.somesite.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Visit Phorum.org![/url]<br/>
    [email]someuser@somesite.com[/email]
    </tt></br></br>
    Diese werden angezeigt als:<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Visit Phorum.org!</a><br/>
    <a href="mailto:someuser@somesite.com">someuser@somesite.com</a>

    <h3>Nichtproportionale Schrift, formatierter Code: [code]...[/code]</h3>
    Manchmal hat man Text wie ASCII art oder Programm-Code, welchen man in seiner Nachricht darstellen will.
    F&uuml;r diese F&auml;lle gibt es die [code] Markierung. Beispiel:
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

Ohne das [code] darum, w&uuml;rde es komplett zerst&ouml;rt aussehen
<br/><br/>
 _____  _<br />
|  __ \| |<br />
| |__) | |__   ___  _ __ _   _ _ __ ___<br />
|  ___/| '_ \ / _ \| '__| | | | '_ ` _ \<br />
| |    | | | | (_) | |  | |_| | | | | | |<br />
|_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br />
<br/><br/>
Aber mit [code] darum, sieht es wie folgt aus:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Zitierter Text: [quote]...[/quote] oder [quote=...]...[/quote]</h3>
    Wenn Sie ein Zitat Ihrer Nachricht hinzuf&uuml;gen wollen, dann k&ouml;nnen Sie diese Markierung nutzen.
    Dabei k&ouml;nnen Sie ausw&auml;hlen, ob Sie denjenigen, den Sie zitieren m&ouml;chten, mit angeben oder nicht.
    Beispiele:<br/><br/>
    <tt>
    [quote]Phorum is the best![/quote]<br/>
    [quote=From Hamlet, by William Shakespeare]<br/>
    To be or not to be, --that is the question:--<br/>
    Whether 'tis nobler in the mind to suffer<br/>
    The slings and arrows of outrageous fortune<br/>
    Or to take arms against a sea of troubles,<br/>
    And by opposing end them?<br/>
    [/quote]
    </tt><br/><br/>
    Dies wird angezeigt als:<br/><br/>
    <blockquote class="bbcode">Zitat:<div>Phorum is the best!</div></blockquote>
    <blockquote class="bbcode">Zitat:<div><strong>From Hamlet, by William Shakespeare</strong><br />
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

    <h3>Eine horizontale Linie einf&uuml;gen: [hr]</h3>
    Um eine Trennungslinie einzuf&uuml;gen, k&ouml;nnen Sie die [hr]-Markierung nutzen.
    Das w&uuml;rde dann wie folgt aussehen:
    <hr>
    Dies ist haupts&auml;chlich sinnvoll, um l&auml;ngeren Text zu strukturieren.

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
