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
    <title>Smajlící nápovìda</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Smajlící nápovìda</h2>

		Na tomto fóru mùžete použít smajlíky. Smajlící jsou øetìzce znakù, které se ve zprávì zobrazují jako obrázky.
		Ty se používají pro vyjádøení nálady autora pøíspìvku. V tabulce dole jsou všechny dostupné smajlíky. Ve sloupci "Kde"
		je popis, kde je možné smajlík použít (S = subjekt zprávy, B = tìlo zprávy, BS = všude).	
    <br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Smajlík</th>
      <th class="PhorumAdminTableHead">Obrázek</th>
      <th class="PhorumAdminTableHead">Popis</th>
      <th class="PhorumAdminTableHead">Kde</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
