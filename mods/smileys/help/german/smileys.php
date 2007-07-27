<?php
  chdir('../../../../');
  define('phorum_page','smiley_help');
  include_once( "./common.php" );

  // The definition of the possible uses for a smiley.
  $PHORUM_MOD_SMILEY_USES = array(
      0   => "N",
      1   => "T",
      2   => "NT",
  );
?>
<html>
  <head>
    <title>Smiley Hilfe</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Smiley Hilfe</h2>

    In diesem Forum k&ouml;nnen Sie Smileys nutzen. Smileys sind kleine Buchstabengruppen,
    die als Bilder beim Lesen einer Nachricht angezeigt werden.
    Meistens dr&uuml;cken diese Stimmungen aus.
    In der unten stehenden &Uuml;bersicht finden Sie alle verf&uuml;gbaren Smileys.
    Die "Wo"-Spalte gibt an, wo die Smilies verwendet werden k&ouml;nnen<br />(T = Thema / Betreff, N = Nachricht / Text, NT = beides).
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Smiley</th>
      <th class="PhorumAdminTableHead">Bild</th>
      <th class="PhorumAdminTableHead">Wo</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
