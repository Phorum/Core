<?php
  // The definition of the possible uses for a smiley.
  $PHORUM_MOD_SMILEY_USES = array(
      0 => 'N',
      1 => 'B',
      2 => 'NB'
  );
?>
<html>
  <head>
    <title>Smiley-Hilfe</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS['PHORUM']['http_path'] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Smiley-Hilfe</h2>
    <p>
      In diesem Forum k&ouml;nnen Sie Smileys nutzen. Smileys sind kleine Buchstabengruppen, die als Bilder beim Lesen einer Nachricht angezeigt werden. Meistens dr&uuml;cken diese Stimmungen aus. In der unten stehenden &Uuml;bersicht finden Sie alle verf&uuml;gbaren Smileys. Die &quot;Wo&quot;-Spalte gibt an, wo Smileys verwendet werden k&ouml;nnen (B=Betreff, N=Nachricht, NB=beide).
    </p>
    <table cellspacing="1" width="100%">
      <tr>
        <th class="PhorumAdminTableHead">Smiley</th>
        <th class="PhorumAdminTableHead">Bild</th>
        <th class="PhorumAdminTableHead">Beschreibung</th>
        <th class="PhorumAdminTableHead">Wo</th>
      </tr>
      <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>
  </body>
</html>
