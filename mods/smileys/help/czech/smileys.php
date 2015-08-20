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
    <title>Smajl�c� n�pov�da</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Smajl�c� n�pov�da</h2>

    Na tomto f�ru m��ete pou��t smajl�ky. Smajl�c� jsou �et�zce znak�, kter� se ve zpr�v� zobrazuj� jako obr�zky.
    Ty se pou��vaj� pro vyj�d�en� n�lady autora p��sp�vku. V tabulce dole jsou v�echny dostupn� smajl�ky. Ve sloupci "Kde"
    je popis, kde je mo�n� smajl�k pou��t (S = subjekt zpr�vy, B = t�lo zpr�vy, BS = v�ude).
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Smajl�k</th>
      <th class="PhorumAdminTableHead">Obr�zek</th>
      <th class="PhorumAdminTableHead">Popis</th>
      <th class="PhorumAdminTableHead">Kde</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
