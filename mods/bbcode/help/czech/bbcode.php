<html>
  <head>
    <title>BBcode n�pov�da</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>BBcode n�pov�da</h2>

    BBcode je zkratka pro "Bulletin Board code". Jedn� se o jazyk se znaky, kter� m��e b�t pou�it pro f�rum
    k form�tov�n� zpr�v. Tato n�pov�da popisuje, jak m��e b�t BBCode u��v�no  v tomto f�ru.

    <h3>Tu�n� text: [b]...[/b]<br/>
        Podtr�en� text: [u]...[/u]<br/>
        Kurz�va: [i]...[/i]<br/>
        P�e�krtnut� text: [s]...[/s]<br/>
        </h3>

    Tyto zna�ky m��ete pou��t ke zm�ne stylu ��st� textu.
    P��klady:<br/><br/>
    <tt>
    [b]Tento text je tu�n�[/b]<br/>
    [i]Tento text je kurz�vou[/i]<br/>
    [u]Tento text je podtr�en�[/u]<br/>
    [s]Tento text je p�e�krtnut�[/s]<br/>
    [b][i]Tento text[/i] je [s]kombinovan�[/s] kombinovan�[/b]
    </tt><br/><br/>
    P��klad se zobraz� takto:<br/><br/>
    <b>Tento text je tu�n�</b><br/>
    <i>Tento text je kurz�vou</i><br/>
    <u>Tento text je podtr�en�</u><br/>
    <strike>Tento text je p�e�krtnut�</strike><br/>
    <b><i>Tento text</i> je <strike>kombinovan�</strike> kombinovan�</b>


    <h3>Horn� index: [sup]...[/sup]<br/>Doln� index: [sub]...[/sub] </h3>
    Pou�it�m t�chto zna�ek se ��st textu zobraz� jako index.
    "2<sup>4</sup> = 16" or "H<sub>2</sub>O". P��klad:<br/><br/>
    <tt>
    [sup]Horn� index[/sup] norm�ln� [sub]doln� index[/sub]
    </tt><br/><br/>

    P��klad se zobraz� takto:<br/><br/>
    <sup>Horn� index</sup> norm�ln� <sub>doln� index</sub>

    <h3>Barva p�sma: [color=...]...[/color]</h3>
    Tato zna�ka se m��e pou��t pro barevn� odli�en� ��st� textu.
    Barva mus� b�t ve tvaru platn�m pro jazyk HTML (nap�. "blue", "red",
    "#ff0000", "#888", etc.). P��klad:<br/><br/>
    <tt>
    Kdo by se ob�val
    <nobr>[color=red]�erven�[/color],</nobr>
    <nobr>[color=#eeaa00]�lut�[/color]</nobr> a
    <nobr>[color=#30f]modr�[/color]?</nobr>
    </tt><br/><br/>
    P��klad se zobraz� takto:<br/><br/>
    Kdo by se ob�val
    <span style="color: red">�erven�</span>,
    <span style="color: #eeaa00">�lut�</span> a
    <span style="color: #30F">modr�</span>?

    <h3>Velikost p�sma: [size=...]...[/size]</h3>
    Tato zna�ka se m��e pou��t pro zm�nu velikosti p�sma ��st� textu.
    Velikost mus� b�t ve tvaru platn�m pro jazyk HTML (nap�. "12px",
    "small", "large", etc.). P��klad:<br/><br/>
    <tt>
    <nobr>[size=x-small]To[/size]</nobr>
    <nobr>[size=small]vypad�[/size]</nobr>
    <nobr>[size=medium]jako,[/size]</nobr>
    <nobr>[size=large]�e[/size]</nobr>
    <nobr>[size=x-large]rostu![/size]</nobr>
    </tt><br/><br/>
    P��klad se zobraz� takto:<br/><br/>
    <span style="font-size: x-small">To</span>
    <span style="font-size: small">vypad�</span>
    <span style="font-size: medium">jako,</span>
    <span style="font-size: large">�e</span>
    <span style="font-size: x-large">rostu!</span>

    <h3>Centrovan� text: [center]...[/center]</h3>
    Zna�ka se m��e pou��t pro vycentrov�n� ��st� textu.
    P��klad:<br/><br/>
    <tt>
    [center]Jsem r�d uprost�ed toho v�eho[/center]
    </tt><br/><br/>
    P��klad se zobraz� takto:<br/><br/>
    <center>Jsem r�d uprost�ed toho v�eho</center>

    <h3>Odkaz na obr�zek: [img]...[/img]<br/>
        Odkaz na str�nku: [url]...[/url] or [url=...]...[/url]<br/>
        Odkaz na emailovou adresu [email]...[/email]</h3>
    Tytro zna�ky jsou pro odkazy na zdroje z internetu.
    Zde jsou n�jak� p��klady:<br/><br/>
    <tt>
    [img]http://www.somesite.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Nav�tivte Phorum.org![/url]<br/>
    [email]someuser@somesite.com[/email]
    </tt></br></br>
    P��klady se zobraz� takto:<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Nav�tivte Phorum.org!</a><br/>
    <a href="mailto:someuser@somesite.com">someuser@somesite.com</a>

    <h3>Form�tovan� k�d, p�smo se stejnou rozte��: [code]...[/code]</h3>
    V n�kter�ch p��padech jako ASCII art, programov� k�d, tabulky akord�, atd.,
    m��ete ve zpr�v� pot�ebovat text, kter� vy�aduje pou�it� t�to zna�ky.
    P��klad:
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

Bez zna�ky [code] dojde ke zhroucen� form�tovan�ho textu, jako zde:
<br/><br/>
  _____  _                                <br/>
 |  __ \| |                               <br/>
 | |__) | |__   ___  _ __ _   _ _ __ ___  <br/>
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \ <br/>
 | |    | | | | (_) | |  | |_| | | | | | |<br/>
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br/>
<br/>
Se zna�kou  [code] vypad� text takto:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Uvozen� text: [quote]...[/quote] or [quote=...]...[/quote]</h3>
    Chcete-li p�idat uvozovky do sv� zpr�vy, m��ete pou��t tuto zna�ku.
    Mu�ete zvolit, zda chcete ke zna�ce zahrnout nap��klad tak� jm�no osoby nebo ne.
    P��klad:<br/><br/>
    <tt>
    [quote]Phorum je nejlep��![/quote]<br/>
    [quote=Hamlet - William Shakespeare]<br/>
    B�t �i neb�t? To je ot�zka.<br/>
    Je d�stojn�j�� strp�t pomy�len�,<br/>
    �e n�silnick� osud do n�s bije,<br/>
    nebo vz�t zbra� na mo�e tr�pen�,<br/>
    a tak s t�m skoncovat? Um��t - sp�t - a dost.<br/>
    [/quote]
    </tt><br/><br/>
    Takto se p��klad zobraz�:<br/><br/>
    <blockquote class="bbcode">Quote:<div>Phorum je nejlep��!</div></blockquote>
    <blockquote class="bbcode">Quote:<div><strong>Hamlet - William Shakespeare</strong><br />
    B�t �i neb�t? To je ot�zka.
    <br />
    Je d�stojn�j�� strp�t pomy�len�,
    <br />
    �e n�silnick� osud do n�s bije,
    <br />
    nebo vz�t zbra� na mo�e tr�pen�,
    <br />
    a tak s t�m skoncovat? Um��t - sp�t - a dost.
    <br /></div></blockquote>

    <h3>Horizont�ln� odd�lovac� ��ra: [hr]</h3>
    K vlo�en� odd�lovac� ��ry do zpr�vy vlo�te zna�ku [hr].
    Takto bude vypadat v�sledek:
    <hr>
    Tato zna�ka je vhodn� k rozd�len� struktury u dlouh�ch zpr�v.

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
