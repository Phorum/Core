<?php
  chdir('../../../../');
  define('phorum_page','smiley_help');
  include_once( "./common.php" );

  // The definition of the possible uses for a smiley.
  $PHORUM_MOD_SMILEY_USES = array(
      0   => "B",
      1   => "O",
      2   => "BO",
  );
?>
<html>
  <head>
    <title>Smiley hulp</title>
    <link rel="stylesheet" type="text/css" href="../help.css"/>
  </head>
  <body>
    <h2>Smiley hulp informatie</h2>

    Op dit forum kan er gebruik worden gemaakt van smileys.
    Een smiley bestaat uit een reeks karakters, die bij het
    lezen van een bericht getoond zal worden als 
    afbeelding. In de onderstaande tabel staan alle
    beschikbare smileys. De kolom "Waar" geeft aan waar in
    het bericht de smileys gebruikt kunnen worden
    (O = onderwerp, B = berichttekst, BO = beide).
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Smiley</th>
      <th class="PhorumAdminTableHead">Image</th>
      <th class="PhorumAdminTableHead">Description</th>
      <th class="PhorumAdminTableHead">Where</th>
    </tr>
    <?php include("./mods/editor_tools/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
