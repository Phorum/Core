<html>
  <head>
    <title>Assistenza su BBcode (codiceBB)</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>Assistenza su BBcode</h2>

	BBcode é l'acronimo in inglese che sta per Bulletin Board code.
    Sono dei codici che possono essere usati per formattare il
    testo. Questa pagina aiuta a capire come puó essere utilizzato
    il BBcode su questo forum.

    <h3>Testo in grasseto: [b]...[/b]<br/>
        Testo sottolineato: [u]...[/u]<br/>
        Testo italico: [i]...[/i]<br/>
        Linea sul testo: [s]...[/s]<br/>
        </h3>
	Facendo uso di questi codici si possono applicare stili
	diversi su parti del testo.
    <p>Per esempio, se scriviamo le seguenti frasi nel modo seguente:</p>

    <tt>
    [b]Questo testo é in grasseto[/b]<br/>
    [i]Questo testo é in italico[/i]<br/>
    [u]Questo testo é sottolineato[/u]<br/>
    [s]Questo testo ha una riga sopra[/s]<br/>
    [b][i]Questo testo[/i] é [s]vario[/s] e misto[/b]
    </tt><br/><br/>

    Il testo, una volta pubblicato, diventerá:<br/><br/>
    <b>Questo testo é in grasseto</b><br/>
    <i>Questo testo é in italico</i><br/>
    <u>Questo testo é sottolineato</u><br/>
    <strike>Questo testo ha una riga sopra</strike><br/>
    <b><i>Questo testo</i> é <strike>vario</strike> e misto</b>


    <h3>Posto in alto: [sup]...[/sup]<br/>Posto in basso: [sub]...[/sub] </h3>
    Inserendo questi codici, potete scrivere ponendo il testo in alto
    oppure in basso rispetto alla riga. Questo ad esempio puó tornare
    utile nell'utilizzo dei numeri come
    "2<sup>4</sup> = 16" or "H<sub>2</sub>O".

    Ad esempio:<br/><br/>
    <tt>
    [sup]posto in alto[/sup] normale [sub]posto in basso[/sub]
    </tt><br/><br/>
    diventeranno:<br/><br/>
    <sup>posto in alto</sup> normale <sub>posto in basso</sub>

    <h3>Colore dei caratteri: [color=...]...[/color]</h3>
    Questo codice puó essere utilizzato per imporre un colore
    in un testo o parte di esso. Il colore dev'essere un codice
    valido di HTML (per esempio "blu", "rosso", il codice HTML
    é "#ff0000", "#888", eccetera, eccetera). </br>
    Ad esempio:<br/><br/>
    <tt>
    Chi ha paura del
    <nobr>[color=red]rosso[/color],</nobr>
    <nobr>[color=#eeaa00]giallo[/color]</nobr> e del
    <nobr>[color=#30f]blu[/color]?</nobr>
    </tt><br/><br/>
    Diventerá:<br/><br/>
    Chi ha paura del
    <span style="color: red">rosso</span>,
    <span style="color: #eeaa00">giallo</span> e
    <span style="color: #30F">blu</span>?

    <h3>Dimensioni carattere: [size=...]...[/size]</h3>
    Questo codice puó essere utilizzato per dare una dimensione al testo intero o parziale.
    L'indicazione della dimensione deve essere un codice valido HTML (ad esempio "12px" oppure
    "small", "large", etc.). Example:<br/><br/>
    <tt>
    <nobr>[size=x-small]SEMBRA[/size]</nobr>
    <nobr>[size=small]CHE[/size]</nobr>
    <nobr>[size=medium]IO[/size]</nobr>
    <nobr>[size=large]STIA[/size]</nobr>
    <nobr>[size=x-large]CRESCENDO![/size]</nobr>
    </tt><br/><br/>
    Diventerá:<br/><br/>
    <span style="font-size: x-small">SEMBRA</span>
    <span style="font-size: small">CHE</span>
    <span style="font-size: medium">IO</span>
    <span style="font-size: large">STIA</span>
    <span style="font-size: x-large">CRESCENDO!</span>

    <h3>Testo centrato: [center]...[/center]</h3>
    Puoi usare questo codice per impostare il testo al centro dello schermo. Per esempio:<br/><br/>
    <tt>
    [center]Mi trovo proprio nel mezzo di tutto[/center]
    </tt><br/><br/>
    Diventerá:<br/><br/>
    <center>Mi trovo proprio nel mezzo di tutto</center>

    <h3>Crea un link ad un immagine da un indirizzo internet: [img]...[/img]<br/>
        Link ad un sito web: [url]...[/url] or [url=...]...[/url]<br/>
        Link ad un indirizzo e-mail [email]...[/email]</h3>
    Questi sono tutti codici per creare links da risorse disponibili nel web.
    Alcuni esempi:<br/><br/>
    <tt>
    [img]http://www.somesite.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Visit Phorum.org![/url]<br/>
    [email]someuser@somesite.com[/email]
    </tt></br></br>
    Questo é come apparirá:<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Visit Phorum.org!</a><br/>
    <a href="mailto:someuser@somesite.com">someuser@somesite.com</a>

    <h3>Codice formattato, monospazio: [code]...[/code]</h3>
    A volte si vuole utilizzare la codici di programmazione, arte ASCII,
    codici/segni di chitarra, eccetera, e si vogliono inserire nel messaggio.
	In questi casi si usano i codici [tag]. Esempio:
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

Senza [code] prima e [/code] alla fine, questo testo apparirebbe completamente sconnesso cosí:
<br/><br/>
  _____  _                                <br/>
 |  __ \| |                               <br/>
 | |__) | |__   ___  _ __ _   _ _ __ ___  <br/>
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \ <br/>
 | |    | | | | (_) | |  | |_| | | | | | |<br/>
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br/>
<br/>
Ma inserendo [code] prima e [/code] alla fine, apparirá cosí:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Citare il testp: [quote]...[/quote] or [quote=...]...[/quote]</h3>
    Se si vuole evidenziare del testo, si puó utilizzare questo codice. Si
    puó scegliere se si desidera includere il nome della persona da citare
    oppure no. Per esempio:<br/><br/>
    <tt>
    [quote]DubaiMania.NET é il miglior sito![/quote]<br/>
    [quote=Dall'Amleto, di William Shakespeare]<br/>
    Essere o non essere, --questo il dilemma:--<br/>
    Che questa nobile mente soffra<br/>
    Quest'arco e frecce d'orribile fortuna<br/>
    O prendere le braccia contro il mare in tempesta,<br/>
    E metterseli contro?<br/>
    [/quote]
    </tt><br/><br/>
    Diventerá:<br/><br/>
    <blockquote class="bbcode">Quote:<div>DubaiMania.NET é il miglior sito!</div></blockquote>
    <blockquote class="bbcode">Quote:<div><strong>Dall'Amleto, di William Shakespeare</strong><br />
    Essere o non essere, --questo il dilemma:--
    <br />
    Che questa nobile mente soffra
    <br />
    Quest'arco e frecce d'orribile fortuna
    <br />
    O prendere le braccia contro il mare in tempesta,
    <br />
    E metterseli contro?
    <br /></div></blockquote>

    <h3>Aggiungere linea di separazione: [hr]</h3>
    Per aggiungere una linea di separazione si puó usare [hr].
    Apparirá cosí:
    <hr>
    Questa funzione servirá soprattutto per aggiungere una struttura ad un lungo messaggio

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
