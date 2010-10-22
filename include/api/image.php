<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;


// {{{ Function: phorum_api_image_thumbnail()
/**
 * Create an image thumbnail.
 *
 * This function can be used to create a scaled down thumbnail version of
 * an image. You can specifiy a width and/or a height to which to scale
 * down the image. The aspect ratio will always be kept. If both a width
 * and height are provided, then the one which requires the largest scale
 * down will be leading.
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
 *     - gd: using the GD library (requires extension "gd")
 *     - imagick: using the ImageMagick library (requires extension "imagick")
 *     - convert: using the ImageMagick "convert" tool (requires the
 *       ImageMagick package to be installed on the server, does not work
 *       in combination with some PHP safety restrictions).
 *
 * @return mixed
 *     NULL is returned in case creating the thumbnail failed. The function
 *     {@link phorum_api_strerror()} can be used to retrieve information
 *     about the error which occurred.
 *
 *     An array is returned in case creating the thumbnail did work.
 *     This array contains the following fields:
 *     - image:     The scaled down image. NULL if no scaling was needed.
 *     - method:    The method that was used to create the thumbnail.
 *     - cur_w:     The width of the original $image.
 *     - cur_h:     The height of the original $image.
 *     - cur_mime:  The MIME type of the original $image.
 *     - new_w:     The width of the scaled down image.
 *     - new_h:     The height of the scaled down image.
 *     - new_mime:  The MIME type of the scaled down image,
 */
function phorum_api_image_thumbnail($image, $max_w = NULL, $max_h = NULL, $method = NULL)
{
    // Reset error storage.
    $GLOBALS['PHORUM']['API']['errno'] = NULL;
    $GLOBALS['PHORUM']['API']['error'] = NULL;
    $error = NULL;

    if (empty($max_w)) $max_w = NULL;
    if (empty($max_h)) $max_h = NULL;

    // Initialize the return array.
    $img = array(
        'image'    => NULL,
        'new_mime' => NULL
    );

    // Check if PHP supports the getimagesize() function. I think it
    // always should, but let's check it to be sure.
    if (!function_exists('getimagesize')) return phorum_api_error_set(
        PHORUM_ERRNO_ERROR,
        'Your PHP installation lacks "getimagesize()" support'
    );

    // Try to determine the image type and size using the getimagesize()
    // PHP function. Unfortunately, this function requires a file on disk
    // to process. Therefore we create a temporary file in the Phorum cache
    // for doing this.
    require_once('./include/api/write_file.php');
    $tmpdir = $GLOBALS['PHORUM']['cache'];
    $tmpfile = $tmpdir .'/scale_image_tmp_'. md5($image . microtime());
    if (!phorum_api_write_file($tmpfile, $image)) return NULL;

    // Get the image information and clean up the temporary file.
    $file_info = getimagesize($tmpfile);
    @unlink($tmpfile);
    if ($file_info === FALSE) return phorum_api_error_set(
        PHORUM_ERRNO_ERROR,
        'Running getimagesize() on the image data failed'
    );

    // Add the image data to the return array.
    $img['cur_w']     = $img['new_w']    = $file_info[0];
    $img['cur_h']     = $img['new_h']    = $file_info[1];
    $img['cur_mime']  = $img['new_mime'] = $file_info['mime'];

    // We support only GIF, JPG and PNG images.
    if (substr($img['cur_mime'], 0, 6) == 'image/') {
        $type = substr($img['cur_mime'], 6);
        if ($type != 'jpeg' && $type != 'gif' && $type != 'png') {
            return phorum_api_error_set(
                PHORUM_ERRNO_ERROR,
                "Scaling image type \"{$img['cur_mime']}\" is not supported"
            );
        }
    } else {
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            'The file does not appear to be an image'
        );
    }

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

    // No scaling needed, return.
    if ($scale_w === NULL && $scale_h === NULL) return $img;

    // The lowest scale factor wins. Compute the required image size.
    if ($scale_h === NULL || ($scale_w !== NULL && $scale_w < $scale_h)) {
        $img['new_w'] = $max_w;
        $img['new_h'] = floor($img['cur_h']*$scale_w + 0.5);
    } else {
        $img['new_w'] = floor($img['cur_w']*$scale_h + 0.5);
        $img['new_h'] = $max_h;
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
        $imagick->thumbnailImage($img['new_w'], $img['new_h'], TRUE);
        $imagick->setFormat("jpg");
        $img['image']    = $imagick->getimageblob();
        $img['new_mime'] = 'image/jpeg';
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

        // We always need JPEG support for the scaled down image.
        if (empty($gd['JPG Support']) && empty($gd['JPEG Support'])) {
            $error = "GD: no JPEG support available for creating thumbnail";
        }
        elseif (($type == 'gif'  && empty($gd['GIF Read Support'])) ||
            ($type == 'jpeg' && (empty($gd['JPG Support']) && empty($gd['JPEG Support']))) ||
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
            $original = imagecreatefromstring($image);
            $error = ob_get_contents();
            ob_end_clean();
            $error = trim(preg_replace(
                '!(^(?:\w+:\s*).*?:|in /.*$)!', '',
                trim($error)
            ));
            if (defined('EVENT_LOGGING')) phorum_mod_event_logging_resume();
            if (!$original) {
                if ($error == '') {
                    $error = "GD: Cannot process the $type image using GD";
                } else {
                    $error = 'GD: ' . $error;
                }
            }
            else
            {
                // Create the scaled image.
                $scaled = imagecreatetruecolor($img['new_w'], $img['new_h']);

                //Retain transparency.
                $trans_idx = imagecolortransparent($original);
                if ($trans_idx >= 0) {
                    $trans = imagecolorsforindex($original, $trans_idx);
                    $idx = imagecolorallocate(
                        $scaled,
                        $trans['red'], $trans['green'], $trans['blue']
                    );
                    imagefill($scaled, 0, 0, $idx);
                    imagecolortransparent($scaled, $idx);
                } elseif ($type == 'png') {
                    imagealphablending($scaled, FALSE);
                    $trans = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
                    imagefill($scaled, 0, 0, $trans);
                    imagesavealpha($scaled, TRUE);
                }

                // Scale the image.
                imagecopyresampled(
                    $scaled, $original, 0, 0, 0, 0,
                    $img['new_w'], $img['new_h'],
                    $img['cur_w'], $img['cur_h']
                );

                // Create the jpeg output data for the scaled image.
                ob_start();
                imagejpeg($scaled);
                $image = ob_get_contents();
                $size = ob_get_length();
                ob_end_clean();

                $img['image']    = $image;
                $img['new_mime'] = 'image/jpeg';
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
               '- ' .
               '-thumbnail ' . $img['new_w'] .'x'. $img['new_h'] . ' ' .
               '-write jpeg:- ' .
               '--'; // Otherwise I get: option requires an argument `-write'

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
                $img['new_mime'] = 'image/jpeg';
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
    if ($error) return phorum_api_error_set(
        PHORUM_ERRNO_ERROR,
        $error
    );

    // Catch illegal methods
    if ($method !== NULL) {
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            'Illegal scaling method: ' . $method
        );
    }

    // If we get here, then we were totally out of luck.
    return phorum_api_error_set(
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
