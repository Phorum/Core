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
    <title>Hymi� ohje</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Neuvoja hymi�iden k�ytt��n</h2>

    T�m� forum mahdollistaa hymi�iden k�yt�n. Tavallisista ascii
    merkeist� muodostetut merkkijonot n�ytet��n viesti� luettaessa
    kuvina. Allaoleva taulukko n�ytt�� kaikki k�ytett�viss� olevat
    hymi�t. "K�ytt�" sarake ilmoittaa miss� viestinosissa voit
    k�ytt�� hymi�t� (O = otsikko, V = viesti, VO = molemmissa).
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Hymi�</th>
      <th class="PhorumAdminTableHead">Kuva</th>
      <th class="PhorumAdminTableHead">Kuvaus</th>
      <th class="PhorumAdminTableHead">K�ytt�</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
