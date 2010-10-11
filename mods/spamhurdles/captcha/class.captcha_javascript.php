<?php
require_once(dirname(__FILE__) . "/class.captcha_base.php");
require_once(dirname(__FILE__) . "/class.banner.php");

class captcha_javascript extends captcha_base
{
    function generate_text_strings()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];
        $strings = parent::generate_text_strings();
        $strings["explain"] .= " " . $lang['CaptchaUnclearExplain'];
        return $strings;
    }

    function generate_captcha_html($question)
    {
        $PHORUM = $GLOBALS["PHORUM"];
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];

        // We only have upper case chars in our banner class. 
        $question = strtoupper($question);

        // Create a bitmap for the generated question.
        $banner = new banner();
        $formatted = $banner->format($question);

        // Extract all active pixels from the bitmap.
        $points = array();
        $rows = count($formatted);
        $cols = strlen($formatted[0]);
        foreach ($formatted as $row => $raster) {
            for ($col=0; $col<strlen($raster); $col++) {
                if ($raster[$col] != ' ') {
                    $points[] = array($col, $row);
                }
            }
        }
        
        // Pixelsize.
        $psx = 4;
        $psy = 3;

        // Generate the HTML content.
        ob_start();
        $captcha_h = ($rows+3) * $psy;
        $captcha_w = ($cols+2) * $psx;
        ?>
        <div style="overflow:hidden; position:relative; padding:0px; background-color: #fff; border: 1px solid black; width: <?php print $captcha_w ?>px; height: <?php print $captcha_h ?>px;" id="spamhurdles_captcha_image">
        </div>
        <?php
        $form = ob_get_contents();
        ob_end_clean();
        ob_start();
        ?>
        <script type="text/javascript">

        function spamhurdles_captcha_add_pixel(x,y) {
            if (!document.getElementById) return;

            var captcha = document.getElementById("spamhurdles_captcha_image"); 
            var pixel = document.createElement("div");
            pixel.innerHTML = "<span></span>";
            pixel.style.backgroundColor = randomcolor(20,130);
            pixel.style.width = "<?php print $psx ?>px";
            pixel.style.height = "<?php print $psy ?>px";
            pixel.style.position = "absolute";
            pixel.style.top = ((1+y)*<?php print $psy ?>) + "px";
            pixel.style.left = ((1+x)*<?php print $psx ?>) + "px";
            captcha.appendChild(pixel);
        }

        function spamhurdles_captcha_background() {
            if (!document.getElementById) return;

            var captcha = document.getElementById("spamhurdles_captcha_image"); 
            captcha.style.backgroundColor = randomcolor(150,200);
            var blockwidth = Math.ceil(<?php print ($cols+2) * $psx ?>/10);
            for (i=0; i<10; i++) {
                var block = document.createElement("div");
                block.innerHTML = "<span></span>";
                block.style.backgroundColor = randomcolor(100,230);
                block.style.width = blockwidth + "px";
                block.style.height = "<?php print $captcha_h ?>px";
                block.style.position = "absolute";
                block.style.top = 0;
                block.style.left = (blockwidth * i) + "px";
                captcha.appendChild(block);
            }
        }

        function randomcolor(min, max) {
            r = min + Math.floor(Math.random()*(max-min)+1);
            g = min + Math.floor(Math.random()*(max-min)+1);
            b = min + Math.floor(Math.random()*(max-min)+1);
            return "rgb("+r+","+g+","+b+")";
        }

        spamhurdles_captcha_background();
        <?php 
        shuffle($points);
        foreach ($points as $point) {
            print "spamhurdles_captcha_add_pixel({$point[0]},{$point[1]});\n"; 
        }
        ?>
        </script>
        <?php
        $after_form = ob_get_contents();
        ob_end_clean();

        // Some extra scrambling to make things harder for spammers.
        $form = spamhurdles_iScramble($form, false, false, 
            '<div id="spamhurdles_captcha_image">' .
            $lang["JavascriptCaptchaNoscript"] .
            '</div>'
        );
        $after_form = spamhurdles_iScramble($after_form, false, false, "");

        return array($form, $after_form);
    }
}
?>
