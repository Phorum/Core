<html>
  <head>
    <title>BBcode nápovìda</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>BBcode nápovìda</h2>

    BBcode je zkratka pro "Bulletin Board code". Jedná se o jazyk se znaky, který mùže být použit pro fórum
		k formátování zpráv. Tato nápovìda popisuje, jak mùže být BBCode užíváno  v tomto fóru.

    <h3>Tuèný text: [b]...[/b]<br/>
        Podtržený text: [u]...[/u]<br/>
        Kurzíva: [i]...[/i]<br/>
        Pøeškrtnutý text: [s]...[/s]<br/>
        </h3>

		Tyto znaèky mùžete použít ke zmìne stylu èástí textu.
    Pøíklady:<br/><br/>
    <tt>
    [b]Tento text je tuèný[/b]<br/>
    [i]Tento text je kurzívou[/i]<br/>
    [u]Tento text je podtržený[/u]<br/>
    [s]Tento text je pøeškrtnutý[/s]<br/>
    [b][i]Tento text[/i] je [s]kombinovaný[/s] kombinovaný[/b]
    </tt><br/><br/>
    Pøíklad se zobrazí takto:<br/><br/>
    <b>Tento text je tuèný</b><br/>
    <i>Tento text je kurzívou</i><br/>
    <u>Tento text je podtržený</u><br/>
    <strike>Tento text je pøeškrtnutý</strike><br/>
    <b><i>Tento text</i> je <strike>kombinovaný</strike> kombinovaný</b>


    <h3>Horní index: [sup]...[/sup]<br/>Dolní index: [sub]...[/sub] </h3>
    Použitím tìchto znaèek se èást textu zobrazí jako index.
    "2<sup>4</sup> = 16" or "H<sub>2</sub>O". Pøíklad:<br/><br/>
    <tt>
    [sup]Horní index[/sup] normální [sub]dolní index[/sub]
    </tt><br/><br/>

		Pøíklad se zobrazí takto:<br/><br/>
    <sup>Horní index</sup> normální <sub>dolní index</sub>

    <h3>Barva písma: [color=...]...[/color]</h3>
		Tato znaèka se mùže použít pro barevné odlišení èástí textu.
    Barva musí být ve tvaru platném pro jazyk HTML (napø. "blue", "red",
    "#ff0000", "#888", etc.). Pøíklad:<br/><br/>
    <tt>
    Kdo by se obával
    <nobr>[color=red]èervené[/color],</nobr>
    <nobr>[color=#eeaa00]žluté[/color]</nobr> a
    <nobr>[color=#30f]modré[/color]?</nobr>
    </tt><br/><br/>
		Pøíklad se zobrazí takto:<br/><br/>
    Kdo by se obával
    <span style="color: red">èervené</span>,
    <span style="color: #eeaa00">žluté</span> a
    <span style="color: #30F">modré</span>?

    <h3>Velikost písma: [size=...]...[/size]</h3>
		Tato znaèka se mùže použít pro zmìnu velikosti písma èástí textu.
    Velikost musí být ve tvaru platném pro jazyk HTML (napø. "12px",
    "small", "large", etc.). Pøíklad:<br/><br/>
    <tt>
    <nobr>[size=x-small]To[/size]</nobr>
    <nobr>[size=small]vypadá[/size]</nobr>
    <nobr>[size=medium]jako,[/size]</nobr>
    <nobr>[size=large]že[/size]</nobr>
    <nobr>[size=x-large]rostu![/size]</nobr>
    </tt><br/><br/>
    Pøíklad se zobrazí takto:<br/><br/>
    <span style="font-size: x-small">To</span>
    <span style="font-size: small">vypadá</span>
    <span style="font-size: medium">jako,</span>
    <span style="font-size: large">že</span>
    <span style="font-size: x-large">rostu!</span>

    <h3>Centrovaný text: [center]...[/center]</h3>
    Znaèka se mùže použít pro vycentrování èástí textu.
		Pøíklad:<br/><br/>
    <tt>
    [center]Jsem rád uprostøed toho všeho[/center]
    </tt><br/><br/>
    Pøíklad se zobrazí takto:<br/><br/>
    <center>Jsem rád uprostøed toho všeho</center>

    <h3>Odkaz na obrázek: [img]...[/img]<br/>
        Odkaz na stránku: [url]...[/url] or [url=...]...[/url]<br/>
        Odkaz na emailovou adresu [email]...[/email]</h3>
    Tytro znaèky jsou pro odkazy na zdroje z internetu.
		Zde jsou nìjaké pøíklady:<br/><br/>
    <tt>
    [img]http://www.somesite.com/cool/thumbsup.gif[/img]<br/>
    [url]http://www.phorum.org[/url]<br/>
    [url=http://www.phorum.org]Navštivte Phorum.org![/url]<br/>
    [email]someuser@somesite.com[/email]
    </tt></br></br>
    Pøíklady se zobrazí takto:<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" border="0"/><br/>
    [<a href="http://www.phorum.org">www.phorum.org</a>]<br/>
    <a rel="nofollow" href="http://www.phorum.org">Navštivte Phorum.org!</a><br/>
    <a href="mailto:someuser@somesite.com">someuser@somesite.com</a>

    <h3>Formátovaný kód, písmo se stejnou rozteèí: [code]...[/code]</h3>
    V nìkterých pøípadech jako ASCII art, programový kód, tabulky akordù, atd.,
		mùžete ve zprávì potøebovat text, který vyžaduje použití této znaèky.
		Pøíklad:
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

Bez znaèky [code] dojde ke zhroucení formátovaného textu, jako zde:
<br/><br/>
  _____  _                                <br/>
 |  __ \| |                               <br/>
 | |__) | |__   ___  _ __ _   _ _ __ ___  <br/>
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \ <br/>
 | |    | | | | (_) | |  | |_| | | | | | |<br/>
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|<br/>
<br/>
Se znaèkou  [code] vypadá text takto:
<pre style="border: 1px solid #dde; background-color: #ffe; padding: 0px 0px 0px 10px">
  _____  _
 |  __ \| |
 | |__) | |__   ___  _ __ _   _ _ __ ___
 |  ___/| '_ \ / _ \| '__| | | | '_ ` _ \
 | |    | | | | (_) | |  | |_| | | | | | |
 |_|    |_| |_|\___/|_|   \__,_|_| |_| |_|

</pre>

    <h3>Uvozený text: [quote]...[/quote] or [quote=...]...[/quote]</h3>
		Chcete-li pøidat uvozovky do své zprávy, mùžete použít tuto znaèku.
		Mužete zvolit, zda chcete ke znaèce zahrnout napøíklad také jméno osoby nebo ne.
		Pøíklad:<br/><br/>
    <tt>
    [quote]Phorum je nejlepší![/quote]<br/>
    [quote=Hamlet - William Shakespeare]<br/>
		Být èi nebýt? To je otázka.<br/>
		Je dùstojnìjší strpìt pomyšlení,<br/>
		že násilnický osud do nás bije,<br/>
		nebo vzít zbraò na moøe trápení,<br/>
		a tak s tím skoncovat? Umøít - spát - a dost.<br/>
    [/quote]
    </tt><br/><br/>
    Takto se pøíklad zobrazí:<br/><br/>
    <blockquote class="bbcode">Quote:<div>Phorum je nejlepší!</div></blockquote>
    <blockquote class="bbcode">Quote:<div><strong>Hamlet - William Shakespeare</strong><br />
    Být èi nebýt? To je otázka.
    <br />
    Je dùstojnìjší strpìt pomyšlení,
    <br />
    že násilnický osud do nás bije,
    <br />
    nebo vzít zbraò na moøe trápení,
    <br />
    a tak s tím skoncovat? Umøít - spát - a dost.
    <br /></div></blockquote>

    <h3>Horizontální oddìlovací èára: [hr]</h3>
    K vložení oddìlovací èáry do zprávy vložte znaèku [hr].
    Takto bude vypadat výsledek:
    <hr>
		Tato znaèka je vhodná k rozdìlení struktury u dlouhých zpráv.

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
