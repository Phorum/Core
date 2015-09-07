<?php

if (!defined('PHORUM')) return;

$GLOBALS['PHORUM']['compat_mbstring']['encoding'] =
    isset($GLOBALS['PHORUM']['DATA']['CHARSET'])
    ? $GLOBALS['PHORUM']['DATA']['CHARSET'] : 'UTF-8';

if (!function_exists('mb_substr'))
{
    function mb_substr($str, $offset, $length = NULL, $encoding = NULL)
    {
        settype($str, 'string');
        settype($offset, 'int');
        if ($length !== NULL) settype($length, 'int');

        if ($encoding === NULL) {
            $encoding = $GLOBALS['PHORUM']['compat_mbstring']['encoding'];
        }

        // For non-UTF-8 data, we fallback to substr().

        if (strtolower($encoding) !== 'utf-8')
        {
            if ($length) {
                return substr($str, $offset, $length);
            } else {
                return substr($str, $offset);
            }
        }

        // For UTF-8 data, we make use of the mb_substr() replacement
        // code, as implemented by the docuwiki project.

        // handle trivial cases
        if ($length === 0) return '';
        if ($offset < 0 && $length < 0 && $length < $offset) return '';

        $offset_pattern = '';
        $length_pattern = '';

        // normalise negative offsets (we could use a tail anchored pattern,
        // but they are horribly slow!)
        if ($offset < 0)
        {
            $strlen = strlen(utf8_decode($str));
            $offset = $strlen + $offset;
            if ($offset < 0) $offset = 0;
        }

        // establish a pattern for offset, a non-captured group equal
        // in length to offset
        if ($offset > 0)
        {
            $Ox = (int)($offset/65535);
            $Oy = $offset%65535;
            if ($Ox) $offset_pattern = '(?:.{65535}){'.$Ox.'}';
            $offset_pattern = '^(?:'.$offset_pattern.'.{'.$Oy.'})';
        }
        else
        {
            $offset_pattern = '^'; // offset == 0; just anchor the pattern
        }

        // establish a pattern for length
        if (is_null($length))
        {
            $length_pattern = '(.*)$'; // the rest of the string
        }
        else
        {
            if (!isset($strlen)) $strlen = strlen(utf8_decode($str));
            if ($offset > $strlen) return ''; // another trivial case
            if ($length > 0)
            {
                // reduce any length that would go passed the end of the string
                $length = min($strlen-$offset, $length);

                $Lx = (int)($length/65535);
                $Ly = $length%65535;

                // positive length requires ...
                // a captured group of length characters
                if ($Lx) $length_pattern = '(?:.{65535}){'.$Lx.'}';
                $length_pattern = '('.$length_pattern.'.{'.$Ly.'})';
            }
            else if ($length < 0)
            {
                if ($length < ($offset - $strlen)) return '';

                $Lx = (int)((-$length)/65535);
                $Ly = (-$length)%65535;

                // negative length requires ...
                // capture everything except a group of -length characters
                // anchored at the tail-end of the string
                if ($Lx) $length_pattern = '(?:.{65535}){'.$Lx.'}';
                $length_pattern = '(.*)(?:'.$length_pattern.'.{'.$Ly.'})$';
            }
        }

        if (preg_match('#'.$offset_pattern.$length_pattern.'#us',$str,$match)){
            return $match[1];
        } else {
            return '';
        }
    }
}

if (!function_exists('mb_internal_encoding'))
{
    function mb_internal_encoding($encoding)
    {
        if (empty($encoding))
        {
            // Get
            return $GLOBALS['PHORUM']['compat_mbstring']['encoding'];
        }
        else
        {
            // Set
            $GLOBALS['PHORUM']['compat_mbstring']['encoding'] = $encoding;
            return true;
        }
    }
}

?>
