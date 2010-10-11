<?php
require_once(dirname(__FILE__) . "/class.captcha_base.php");
require_once(dirname(__FILE__) . "/class.banner.php");

class captcha_image extends captcha_base
{
    function generate_captcha_html($question)
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];

        $captcha = 
            '<div id="spamhurdles_captcha_image">' .
            '<img class="captcha_image" src="{IMAGEURL}" alt="CAPTCHA" ' .
            'title="CAPTCHA"/></div>';

        return array($captcha, "");
    }

    function generate_text_strings()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];
        $strings = parent::generate_text_strings();
        $strings["explain"] .= " " . $lang['CaptchaUnclearExplain'];
        return $strings;
    }

    function generate_image($question)
    {
        // Load the available TTF fonts.
        $fontsdir = dirname(__FILE__) . "/fonts";
        $dh = opendir($fontsdir);
        if (! $dh) die ("captcha_image class cannot read the fonts directory");
        $fonts = array();
        while ($file = readdir($dh)) {
            if (substr($file, -4, 4) == ".ttf") {
                $fonts[] = "$fontsdir/$file";
            }
        }
        if (count($fonts) == 0) trigger_error(
            'captcha_image class did not find any ttf fonts to use for ' .
            'writing the captcha code',
            E_USER_ERROR
        );

        // Create an image, that should be large enough for holding
        // our complete captcha. We will clip it to the right size later on.
        // The exact width is arbitrairy. If a code doesn't fit when using
        // some other fonts, then 60 might have to be made larger below.
        $img = imagecreatetruecolor(60*strlen($question), 100);

        // Fill background color.
        $col = imagecolorallocate($img, 230, 230, 230);
        imagefill($img, 0, 0, $col);

        // Draw random characters for distortion.
        $colors = array();
        for ($i=0; $i<5; $i++) {
            $cc = rand(120, 190);
            $colors[] = imagecolorallocate($img, $cc+30, $cc+20, $cc+10);
        }
        for ($i=0; $i<15; $i++) {
            $x = rand(0, 60*strlen($question));
            $y = rand(0, 100);
            $size = rand(30,90);
            $rfont = $fonts[rand(0, count($fonts)-1)];
            $rcolor = $colors[rand(0, count($colors)-1)];
            $angle = -90 + rand(0,180);
            $char = chr(rand(ord('A'), ord('A')+26));
            imagettftext($img, $size, $angle, $x, $y, $rcolor, $rfont, $char);
        }

        // Draw characters for the CAPTCHA answer.
        $boxwidth = 0;
        $boxheight = 0;
        for ($i=0; $i<strlen($question); $i++)
        {
            $fontcolor = imagecolorallocate($img, rand(0,200), 0, 0);
            $rfont = $fonts[rand(0, count($fonts)-1)];
            $size = rand(25,32);
            $char = substr($question, $i, 1);
            $angle = -15 + rand(0, 30);

            // Determine the bounding box for the rendered character.
            $box = imagettfbbox($size, $angle, $rfont, $char);
            $l = min($box[0], $box[6]);
            $r = max($box[2], $box[4]);
            $w = abs($l - $r);
            $t = min($box[5], $box[7]);
            $b = max($box[1], $box[3]);
            $h = abs($t - $b);

            if ($box[0] > $box[6]) {
                $boxwidth += ($box[0] - $box[6]);
            }

            $chary = 5 + 0 - $t;
            $chary_rand = rand(0,20); 
            $charx = 5 + $boxwidth;
            imagettftext(
                $img, $size, $angle, $charx, $chary+$chary_rand,
                $fontcolor, $rfont, $char
            );
            $boxwidth = $boxwidth + $w;
            if ($boxheight < ($h+$chary_rand)) $boxheight = $h + $chary_rand;
        }
        $boxwidth += 10;
        $boxheight += 10;

        // Crop the code into a new image.
        $cropped = imagecreatetruecolor($boxwidth, $boxheight);
        imagecopy($cropped, $img, 0, 0, 0, 0, $boxwidth, $boxheight);
        imagedestroy($img);

        // Display the image.
        header("Content-Type: image/gif");
        imagegif($cropped);
        imagedestroy($cropped);
    }
}
?>
