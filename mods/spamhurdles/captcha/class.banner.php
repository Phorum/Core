<?php
class banner
{
    function banner($font = "banner.fnt")
    {
        $font = basename($font);

        $fp = fopen(dirname(__FILE__) . "/fonts/$font", "r");
        if (! $fp) {
            die("banner class cannot read file " .
                dirname(__FILE__) . "/fonts/$font");
        }
        $curchar = null;
        $curbitmap = array();
        $font = array();
        $line = null;
        while (true) {
            $line = fgets($fp);

            if ($line !== null && $line !== false) {
                $line = preg_replace('/\n|\r/', '', $line);
            }

            if ($line !== null) {
                if ($line === false || preg_match('/^char:(.)$/', $line, $m)) {
                    if (count($curbitmap)) {
                        $font[$curchar] = $curbitmap;
                        $curbitmap = array();
                    }
                    if ($line !== false) {
                        $curchar = $m[1];
                    }
                }
                elseif (preg_match('/^[\.\#]+$/', $line)) {
                    $curbitmap[] = $line;
                }
            }

            if ($line === false) break;
        }
        fclose($fp);

        $this->font = $font;
    }

    function format($string) {
        $grid = array();
        for ($i=0; $i<strlen($string); $i++)
        {
            $char = substr($string, $i, 1);

            if (! isset($this->font[$char])) {
                die("banner class does not have character " .
                    htmlspecialchars($char) . " defined in its font.");
            }
            $fontchar = $this->font[$char];

            for ($r=0; $r<count($fontchar); $r++) {
                if (! isset($grid[$r])) {
                    $grid[$r] = "." . $fontchar[$r];
                } else {
                    $grid[$r] .= "." . $fontchar[$r];
                }
            }
        }
        foreach ($grid as $row => $raster) {
            $raster = str_replace(".", " ", $raster);
            $grid[$row] = str_replace("#", "#", $raster);
        }
        return $grid;
    }
}
?>
