<?php
  // The definition of the possible uses for a smiley.
  $PHORUM_MOD_SMILEY_USES = array(
      0   => "B",
      1   => "S",
      2   => "BS",
  );
?>
<html>
  <head>
    <title>Assistenza sugli Smiley</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Informazione sull'uso degli Smileys</h2>

    In questo forum puoi usare le icone per esprimerti. Le
    icone sono tradotte in caratteri che una volta pubblicato
    il messaggio diventano immagini. Le icone vengono
    principalmente utilizzate per esprimere l'umore dell'autore.
    Nel riquadro in basso si possono trovare tutte le icone
    disponibili.
    
    Nella colonna denominata "Dove" viene indicato dove
    queste icone possono essere utilizzate.
    
    <P>S  = Solo nel campo riservato al soggetto</P>
    <P>B  = Solo nello spazio riservato al testo</P>
    <P>BS = In tutti e due i campi</P>
    
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Icona</th>
      <th class="PhorumAdminTableHead">Immagine</th>
      <th class="PhorumAdminTableHead">Descrizione</th>
      <th class="PhorumAdminTableHead">Dove</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
