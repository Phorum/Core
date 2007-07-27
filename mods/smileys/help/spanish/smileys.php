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
    <title>Ayuda Smileys</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/smileys/help/help.css"/>
  </head>
  <body>
    <h2>Ayuda para usar los smileys</h2>

	En este foro puedes utilizar smileys.
	Los smileys son cadenas de caracteres que aparecerán como
	una imagen mientras leas el mensaje. Los smileys suelen ser 
	usados para expresar sentimiento en el texto. En la tabla inferior
	puedes ver todos los smileys que están disponibles. La columna 
	"Dónde" indica dónde pueden ser utilizados los smileys (S = Asunto, 
	B = Cuerpo del mensaje, BS = ambos). 
	
	<br/><br/>
    <table cellspacing="1" width="100%">
    <tr>
      <th class="PhorumAdminTableHead">Smiley</th>
      <th class="PhorumAdminTableHead">Imagen</th>
      <th class="PhorumAdminTableHead">Descripción</th>
      <th class="PhorumAdminTableHead">Dónde</th>
    </tr>
    <?php include("./mods/smileys/help/render_smileys_list.php") ?>
    </table>

    <br/><br/><br/>
  </body>
</html>
