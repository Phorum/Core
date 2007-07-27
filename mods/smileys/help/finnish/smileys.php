<?php
  // The definition of the possible uses for a smiley.
  $PHORUM_MOD_SMILEY_USES = array(
      0   => "V",
      1   => "O",
      2   => "VO",
  );
?>
<html>
  <head>
    <title>Hymiö ohje</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Neuvoja hymiöiden käyttöön</h2>

    Tämä forum mahdollistaa hymiöiden käytön. Tavallisista ascii 
    merkeistä muodostetut merkkijonot näytetään viestiä luettaessa
    kuvina. Allaoleva taulukko näyttää kaikki käytettävissä olevat 
    hymiöt. "Käyttö" sarake ilmoittaa missä viestinosissa voit 
    käyttää hymiötä (O = otsikko, V = viesti, VO = molemmissa).
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Hymiö</th>
      <th class="PhorumAdminTableHead">Kuva</th>
      <th class="PhorumAdminTableHead">Kuvaus</th>
      <th class="PhorumAdminTableHead">Käyttö</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
