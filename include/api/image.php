<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * This script implements utility functions for working with images.
 *
 * Phorum does not require this API for the core features. It is mainly
 * provided to offer module writers are stable and powerful API for
 * working with images.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/write_file.php';

// {{{ Function: phorum_api_image_info()
/**
 * Retrieve information for an image.
 *
 * @param string $image
 *     The raw binary image data.
 *
 * @return mixed
 *     FALSE is returned in case retrieving the image information failed.
 *     The function {@link phorum_api_error_message()} can be used to
 *     retrieve information about the error that occurred.
 *
 *     An array is returned in case retrieving the information did work.
 *     This array contains the following fields:
 *
 *     - width  : the width of the image in pixels
 *     - height : the height of the image in pixels
 *     - mime   : the MIME type for the image
 *     - type   : one of the IMAGETYPE_XXX constants indicating the image type
 */
function phorum_api_image_info($image)
{
    global $PHORUM;

    static $cache;
    if (!$cache) $cache = array();

    // Return cached results when available.
    $cache_id = md5($image);
    if (isset($cache[$cache_id])) {
        return $cache[$cache_id];
    }

    // Check if PHP supports the getimagesize() function. I think it
    // always should, but let's check it to be sure.
    if (!function_exists('getimagesize')) return phorum_api_error(
        PHORUM_ERRNO_ERROR,
        'Your PHP installation lacks "getimagesize()" support'
    );

    // Try to determine the image type and size using the getimagesize()
    // PHP function. Unfortunately, this function requires a file on disk
    // to process. Therefore we create a temporary file in the Phorum cache
    // for doing this.
    $tmpdir = $PHORUM['CACHECONFIG']['directory'];
    $tmpfile = $tmpdir .'/scale_image_tmp_'. $cache_id . microtime(TRUE);
    if (!phorum_api_write_file($tmpfile, $image)) return FALSE;

    // Get the image information and clean up the temporary file.
    $image_info = @getimagesize($tmpfile);
    @unlink($tmpfile);
    if ($image_info === FALSE) return phorum_api_error(
        PHORUM_ERRNO_ERROR,
        'Running getimagesize() on the image data failed'
    );

    // We support only GIF, JPG and PNG images.
    if (substr($image_info['mime'], 0, 6) == 'image/') {
        if ($image_info[2] !== IMAGETYPE_JPEG &&
            $image_info[2] !== IMAGETYPE_GIF  &&
            $image_info[2] !== IMAGETYPE_PNG) {
            return phorum_api_error(
                PHORUM_ERRNO_ERROR,
                "Scaling image type \"{$image_info['mime']}\" is not supported"
            );
        }
    } else {
        return phorum_api_error(
            PHORUM_ERRNO_ERROR,
            'The file does not appear to be an image'
        );
    }

    $info = array(
      'width'  => $image_info[0],
      'height' => $image_info[1],
      'mime'   => $image_info['mime'],
      'type'   => $image_info[2]
    );

    $cache[$cache_id] = $info;

    return $info;
}
// }}}

// {{{ Function: phorum_api_image_thumbnail()
/**
 * Create an image thumbnail.
 *
 * This function can be used to create a scaled down thumbnail version of
 * an image. You can specifiy a width and/or a height to which to scale
 * down the image. The aspect ratio will always be kept. If both a width
 * and height are provided, then the one that requires the largest scale
 * down will be leading.
 *
 * In case the image already has the correct size, then still image
 * processing is done. This will take care of normalizing all images
 * that pass this method to PNG.
 *
 * @param string $image
 *     The raw binary image data.
 *
 * @param integer $max_w
 *     The maximum allowed width for the image in pixels.
 *     Use NULL or 0 (zero) to indicate that any width will do.
 *
 * @param integer $max_h
 *     The maximum allowed height for the image in pixels.
 *     Use NULL or 0 (zero) to indicate that any height will do.
 *
 * @param string $method
 *     The method to use for scaling the image. By default, this function
 *     will try to autodetect a working method. Providing a $method parameter
 *     is mostly useful for debugging purposes. Available methods (in the
 *     order in which they are probed in the code) are:
 *     - imagick : using the ImageMagick library (requires extension "imagick")
 *     - gd      : using the GD library (requires extension "gd")
 *     - convert : using the ImageMagick "convert" tool (requires the
 *                 ImageMagick package to be installed on the server and does
 *                 not work in combination with some PHP safety restrictions).
 *
 * @return mixed
 *     FALSE is returned in case creating the thumbnail failed. The function
 *     {@link phorum_api_error_message()} can be used to retrieve information
 *     about the error that occurred.
 *
 *     An array is returned in case creating the thumbnail did work.
 *     This array contains the following fields:
 *     - image    : The scaled down image. NULL if no scaling was needed.
 *     - method   : The method that was used to create the thumbnail.
 *     - cur_w    : The width of the original $image.
 *     - cur_h    : The height of the original $image.
 *     - cur_mime : The MIME type of the original $image.
 *     - new_w    : The width of the scaled down image.
 *     - new_h    : The height of the scaled down image.
 *     - new_mime : The MIME type of the scaled down image,
 */
function phorum_api_image_thumbnail(
    $image, $max_w = NULL, $max_h = NULL, $method = NULL)
{
    global $PHORUM;

    // Reset error storage.
    $PHORUM['API']['errno'] = NULL;
    $PHORUM['API']['error'] = NULL;
    $error = NULL;

    if (empty($max_w)) $max_w = NULL;
    if (empty($max_h)) $max_h = NULL;

    // Initialize the return array.
    $img = array(
        'image'    => NULL,
        'new_mime' => NULL
    );

    // Retrieve image info.
    $image_info = phorum_api_image_info($image);
    if ($image_info === FALSE) return FALSE;

    // Add the image data to the return array.
    $img['cur_w']     = $img['new_w']    = $image_info['width'];
    $img['cur_h']     = $img['new_h']    = $image_info['height'];
    $img['cur_mime']  = $img['new_mime'] = $image_info['mime'];

    // Check if scaling is required and if yes, what the new size should be.
    // First, determine width and height scale factors.
    $scale_w = NULL;
    $scale_h = NULL;
    // Need horizontal scaling?
    if ($max_w !== NULL && $max_w < $img['cur_w'])
        $scale_w = $max_w / $img['cur_w'];
    // Need vertical scaling?
    if ($max_h !== NULL && $max_h < $img['cur_h'])
        $scale_h = $max_h / $img['cur_h'];

    // The lowest scale factor wins. Compute the required image size.
    if ($scale_h !== NULL && $scale_w !== NULL)
    {
        if ($scale_w < $scale_h) {
            $img['new_w'] = $max_w;
            $img['new_h'] = floor($img['cur_h']*$scale_w + 0.5);
        } else {
            $img['new_h'] = $max_h;
            $img['new_w'] = floor($img['cur_w']*$scale_h + 0.5);
        }
    }
    elseif ($scale_h !== NULL) {
        $img['new_h'] = $max_h;
        $img['new_w'] = floor($img['cur_w']*$scale_h + 0.5);
    }
    elseif ($scale_w !== NULL) {
        $img['new_w'] = $max_w;
        $img['new_h'] = floor($img['cur_h']*$scale_w + 0.5);
    }

    // Use phorum_api_image_clip() to scale the image.
    return phorum_api_image_clip(
      $image, 0, 0, $img['cur_w'], $img['cur_h'],
      $img['new_w'], $img['new_h'], $method
    );
}
// }}}

// {{{ Function: phorum_api_image_clip()
/**
 * Clip a part from an image and optionally, resize it.
 *
 * This function can be used to clip a part of an image. You can specifiy
 * width and/or a height to which to scale the clipped image.
 *
 * Below, you see an image of the way in which a clip is defined by the
 * function parameters. $clip_x, $clip_y, $clip_w and $clip_h.
 * <code>
 * +------------------------------------------+
 * |                       ^                  |
 * |                       |                  |
 * |                     clip_y               |
 * |                       |                  |
 * |                       v                  |
 * |            +-------------------+   ^     |
 * |            |                   |   |     |
 * |<--clip_x-->|                   |   |     |
 * |            |      CLIPPED      |   |     |
 * |            |       IMAGE       | clip_h  |
 * |            |                   |   |     |
 * |            |                   |   |     |
 * |            +-------------------+   v     |
 * |                                          |
 * |            <-------clip_w------>         |
 * |                                          |
 * +------------------------------------------+
 * </code>
 *
 * @param string $image
 *     The raw binary image data.
 *
 * @param integer $clip_x
 *     The X-offset for the clip, where 0 indicates the left of the image.
 * @param integer $clip_y
 *     The Y-offset for the clip, where 0 indicates the top of the image.
 * @param integer $clip_w
 *     The width of the clip to take out of the image.
 * @param integer $clip_h
 *     The height of the clip to take out of the image.
 *
 * @param integer $dst_w
 *     The width for the created clip image in pixels.
 *     Use NULL or 0 (zero) to indicate that the width should be
 *     the same as the $clip_w parameter.
 * @param integer $dst_h
 *     The height for the created clip image in pixels.
 *     Use NULL or 0 (zero) to indicate that the height should be
 *     the same as the $clip_h parameter.
 *
 * @param string $method
 *     The method to use for scaling the image. By default, this function
 *     will try to autodetect a working method. Providing a $method parameter
 *     is mostly useful for debugging purposes. Available methods (in the
 *     order in which they are probed in the code) are:
 *     - imagick : using the ImageMagick library (requires extension "imagick")
 *     - gd      : using the GD library (requires extension "gd")
 *     - convert : using the ImageMagick "convert" tool (requires the
 *                 ImageMagick package to be installed on the server and does
 *                 not work in combination with some PHP safety restrictions).
 *
 * @return mixed
 *     FALSE is returned in case creating the clip image failed. The function
 *     {@link phorum_api_error_message()} can be used to retrieve information
 *     about the error that occurred.
 *
 *     An array is returned in case creating the clip image did work.
 *     This array contains the following fields:
 *     - image    : The scaled down image. NULL if no scaling was needed.
 *     - method   : The method that was used to create the thumbnail.
 *     - cur_w    : The width of the original $image.
 *     - cur_h    : The height of the original $image.
 *     - cur_mime : The MIME type of the original $image.
 *     - new_w    : The width of the scaled down image.
 *     - new_h    : The height of the scaled down image.
 *     - new_mime : The MIME type of the scaled down image,
 */
function phorum_api_image_clip(
    $image,
    $clip_x = 0,    $clip_y = 0,
    $clip_w = NULL, $clip_h = NULL,
    $dst_w  = NULL, $dst_h  = NULL,
    $method = NULL)
{
    global $PHORUM;

    settype($clip_x, 'int');
    settype($clip_y, 'int');
    settype($clip_w, 'int');
    settype($clip_h, 'int');
    settype($dst_w,  'int');
    settype($dst_h,  'int');

    // Reset error storage.
    $PHORUM['API']['errno'] = NULL;
    $PHORUM['API']['error'] = NULL;
    $error = NULL;

    // Initialize the return array.
    $img = array(
        'image'    => NULL,
        'new_mime' => NULL
    );

    // Retrieve image info.
    $image_info = phorum_api_image_info($image);
    if ($image_info === FALSE) return FALSE;

    // Derive a name for the image type.
    switch ($image_info['type']) {
        case IMAGETYPE_JPEG : $type = 'jpeg'; break;
        case IMAGETYPE_GIF  : $type = 'gif';  break;
        case IMAGETYPE_PNG  : $type = 'png';  break;
        default             : $type = 'unknown'; // should not occur
    }

    // The clip width and height are inherited from the image
    // width and height, unless they are set.
    if (empty($clip_w)) $clip_w = $image_info['width']  - $clip_x;
    if (empty($clip_h)) $clip_h = $image_info['height'] - $clip_y;

    // The target image width and height are inherited from the clip
    // width and height, unless they are set.
    if (empty($dst_w)) $dst_w = $clip_w;
    if (empty($dst_h)) $dst_h = $clip_h;

    // Add the image data to the return array.
    $img['cur_w']     = $image_info['width'];
    $img['cur_h']     = $image_info['height'];
    $img['new_w']     = $dst_w;
    $img['new_h']     = $dst_h;
    $img['cur_mime']  = $img['new_mime'] = $image_info['mime'];

    // Check if the requested clip fits the source image size.
    if (($clip_x + $clip_w) > $img['cur_w']) {
        return phorum_api_error(
            PHROM_ERRNO_ERROR,
            "The clip X offset $clip_x + clip width $clip_w exceeds " .
            "the source image width {$img['cur_w']}"
        );
    }
    if (($clip_y + $clip_h) > $img['cur_h']) {
        return phorum_api_error(
            PHROM_ERRNO_ERROR,
            "The clip Y offset $clip_y + clip height $clip_h exceeds " .
            "the source image height {$img['cur_h']}"
        );
    }

    // -----------------------------------------------------------------
    // Try to use the imagick library tools
    // -----------------------------------------------------------------

    if (($method === NULL || $method == 'imagick') &&
        extension_loaded('imagick') && class_exists('Imagick'))
    {
        $method = NULL;

        $imagick = new Imagick();
        $imagick->readImageBlob($image);
        $imagick->flattenImages();

        $tmp = new Imagick();
        $tmp->newPseudoImage($img['cur_w'], $img['cur_h'], 'xc:white');
        $tmp->compositeImage($imagick, imagick::COMPOSITE_OVER, 0, 0);
        $imagick = $tmp;

        $imagick->cropImage($clip_w, $clip_h, $clip_x, $clip_y);
        $imagick->thumbnailImage($dst_w, $dst_h, FALSE);

        $imagick->setImagePage($clip_w, $clip_h, 0, 0);

        $imagick->setFormat('png');
        $img['image']    = $imagick->getImagesBlob();
        $img['new_mime'] = 'image/png';
        $img['method']   = 'imagick';

        return $img;
    }

    // -----------------------------------------------------------------
    // Try to use the GD library tools
    // -----------------------------------------------------------------

    if (($method === NULL || $method == 'gd') &&
        extension_loaded('gd') &&
        function_exists('gd_info')) // might be absent in really old versions
    {
        // We need gd_info() to check whether GD has the required
        // image support for the type of image that we are handling.
        $gd = gd_info();

        // We always need PNG support for the scaled down image.
        if (empty($gd['PNG Support'])) {
            $error = 'GD: no PNG support available for processing images';
        }
        elseif (($type == 'gif'  && empty($gd['GIF Read Support'])) ||
            ($type == 'jpeg' &&
             (empty($gd['JPG Support']) && empty($gd['JPEG Support']))) ||
            ($type == 'png'  && empty($gd['PNG Support']))) {
            $error = "GD: no support available for image type \"$type\"";
        }
        else
        {
            // Create a GD image handler based on the image data.
            // imagecreatefromstring() spawns PHP warnings if the file cannot
            // be processed. We do not care to see that in the event logging,
            // so if event logging is loaded, we suspend it here.
            // Instead, we catch error output and try to mangle it into a
            // usable error message.
            if (defined('EVENT_LOGGING')) phorum_mod_event_logging_suspend();
            ob_start();
            $original = @imagecreatefromstring($image);
            $error = ob_get_contents();
            ob_end_clean();

            $error = trim(preg_replace(
                '!(^(?:\w+:\s*).*?:|in /.*$)!', '',
                trim($error)
            ));
            if (defined('EVENT_LOGGING')) phorum_mod_event_logging_resume();
            if (!$original)
            {
                if ($error == '') {
                    $error = "GD: Cannot process the $type image using GD";
                } else {
                    $error = 'GD: ' . $error;
                }
            }
            else
            {
                // Create the scaled image.
                $scaled = imagecreatetruecolor($dst_w, $dst_h);

                // Fill the image to have a background for transparent pixels.
                $white = imagecolorallocate($scaled, 255, 255, 255);
                imagefill($scaled, 0, 0, $white);

                // Scale the image.
                imagecopyresampled(
                    $scaled,          // destination image
                    $original,        // source image
                    0,       0,       // destination x + y
                    $clip_x, $clip_y, // source x + y
                    $dst_w,  $dst_h,  // destination width + height
                    $clip_w, $clip_h  // source width + height
                );

                // Create the png output data for the scaled image.
                ob_start();
                imagepng($scaled);
                $image = ob_get_contents();
                $size = ob_get_length();
                ob_end_clean();

                imagedestroy($original);
                imagedestroy($scaled);

                $img['image']    = $image;
                $img['new_mime'] = 'image/png';
                $img['method']   = 'gd';

                return $img;
            }
        }
    }

    // -----------------------------------------------------------------
    // Try to use the ImageMagick "convert" tool
    // -----------------------------------------------------------------

    if ($method === NULL || $method == 'convert')
    {
        // Try to find the "convert" utility.
        // First, check if it is configured in the Phorum settings.
        $convert = NULL;
        if (isset($PHORUM['imagemagick_convert_path'])) {
            $path = $PHORUM['imagemagick_convert_path'];
            if (is_executable($path)) $convert = $path;
        }
        // Not found? Then simply use "convert" and hope that it
        // can be found in the OS' path.
        if ($convert === NULL) {
            $convert = 'convert';
        }

        // Build the command line.
        $cmd = escapeshellcmd($convert) . ' ' .
               '- ' . // pseudo-filename '-' for STDIN (standard in)
               "-crop {$clip_w}x{$clip_h}+{$clip_x}+{$clip_y} " .
               '+repage ' .
               '-thumbnail ' . $dst_w .'x'. $dst_h . '\! ' .
               'png:-'; // explicit image format

        // Run the command.
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $process = proc_open($cmd, $descriptors, $pipes);
        if ($process == FALSE) {
            $error = 'Failed to execute "convert".';
        }
        else
        {
            // Feed convert the image data on STDIN.
            fwrite($pipes[0], $image);
            fclose($pipes[0]);

            // Read the scaled image from STDOUT.
            $scaled = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read errors.
            $errors = trim(stream_get_contents($pipes[2]));
            fclose($pipes[2]);

            $exit = proc_close($process);

            if ($exit == 0) {
                $img['image']    = $scaled;
                $img['new_mime'] = 'image/png';
                $img['method']   = 'convert';

                return $img;
            }

            // Some error occurred.
            if ($errors == '') {
                $error = 'Got exit code ' . $exit . ' from "convert".';
            } else {
                $error = $errors;
            }
        }
    }

    // -----------------------------------------------------------------
    // Safety nets.
    // -----------------------------------------------------------------

    // Return error if one was set.
    if ($error) return phorum_api_error(
        PHORUM_ERRNO_ERROR,
        $error
    );

    // Catch illegal methods
    if ($method !== NULL) {
        return phorum_api_error(
            PHORUM_ERRNO_ERROR,
            'Illegal scaling method: ' . $method
        );
    }

    // If we get here, then we were totally out of luck.
    return phorum_api_error(
        PHORUM_ERRNO_ERROR,
        'No working image scaling method found'
    );
}
// }}}

// {{{ Function: phorum_api_image_supported()
/**
 * Check if platform support is available for scaling images.
 *
 * @return boolean|string
 *   FALSE is returned in case no platform support is available for
 *   scaling images. When support is available, then the name of
 *   the scaling method is returned.
 */
function phorum_api_image_supported()
{
    // Simple grey box, 140x140 pixels.
    $test_image = base64_decode(
        'R0lGODlhjACMAIAAAJWVlQAAACH5BAAAAAAALAAAAACMAIwAAAKshI+py+0Po5y02ou' .
        'z3rz7D4biSJbmiabqyrbuC8fyTNf2jef6zvf+DwwKh8Si8YhMKpfMpvMJjUqn1Kr1is' .
        '1qt9yu9wsOi8fksvmMTqvX7Lb7DY/L5/S6/Y7P6/f8vv8PGCg4SFhoeIiYqLjI2Oj4C' .
        'BkpOUlZaXmJmam5ydnp+QkaKjpKWmp6ipqqusra6voKGys7S1tre4ubq7vL2+v7Cxws' .
        'PExcbHyMnFxWAAA7'
    );

    // Try to create a thumbnail out of the image.
    $clipped = phorum_api_image_thumbnail($test_image, 100, 100);
    if ($clipped === FALSE) return FALSE;

    // Creating the thumbnail worked. Return the method that was used.
    return $clipped['method'];
}
// }}}

?>
