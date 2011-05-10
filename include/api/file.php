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
 * This script implements the Phorum file storage API.
 *
 * A "Phorum file" is a file which is used from within Phorum as a
 * personal user file (which can be uploaded through the user's control
 * center) or as a message attachment.
 *
 * By default, the contents of a Phorum file are stored in the Phorum
 * database, but this API does support modules that change this behavior
 * (e.g. by storing file contents on a filesystem instead).
 *
 * @package    PhorumAPI
 * @subpackage FileStorageAPI
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

// {{{ Constant and variable definitions

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * that the retrieved Phorum file data has to be returned to the caller.
 */
define("PHORUM_FLAG_GET",              1);

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * that the retrieved Phorum file can be sent to the browser directly.
 */
define("PHORUM_FLAG_SEND",             2);

/**
 * Function call flag, which tells the function to skip any
 * permission checks.
 */
define("PHORUM_FLAG_IGNORE_PERMS",     4);

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * to force a download by the browser by sending an application/octet-stream
 * Content-Type header. This flag will only have effect if the
 * {@link PHORUM_FLAG_SEND} flag is set as well.
 */
define("PHORUM_FLAG_FORCE_DOWNLOAD",   8);

/**
 * A mapping of file extensions to their MIME types.
 * Used by function {@link phorum_api_file_get_mimetype()}.
 */
$GLOBALS["PHORUM"]["phorum_api_file_mimetypes"] = array
(
    "pdf"  => "application/pdf",
    "ps"   => "application/postscript",
    "doc"  => "application/msword",
    "xls"  => "application/vnd.ms-excel",
    "gif"  => "image/gif",
    "png"  => "image/png",
    "jpg"  => "image/jpeg",
    "jpeg" => "image/jpeg",
    "jpe"  => "image/jpeg",
    "bmp"  => "image/x-ms-bmp",
    "tiff" => "image/tiff",
    "tif"  => "image/tiff",
    "xml"  => "text/xml",
    "mpeg" => "video/mpeg",
    "mpg"  => "video/mpeg",
    "mpe"  => "video/mpeg",
    "qt"   => "video/quicktime",
    "mov"  => "video/quicktime",
    "avi"  => "video/x-msvideo",
    "gz"   => "application/x-gzip",
    "tgz"  => "application/x-gzip",
    "zip"  => "application/zip",
    "tar"  => "application/x-tar",
    "exe"  => "application/octet-stream",
    "rar"  => "application/octet-stream",
    "wma"  => "application/octet-stream",
    "wmv"  => "application/octet-stream",
    "mp3"  => "audio/mpeg",
);

// }}}

// {{{ Function: phorum_api_file_get_mimetype
/**
 * Lookup the MIME type for a given filename.
 *
 * This will use an internal lookup list of known file extensions
 * to find the correct content type for a filename. If no content type
 * is known, then "application/octet-stream" will be used as the
 * MIME type (causing the browser to download the file, instead of
 * opening it).
 *
 * @param string $filename
 *     The filename for which to lookup the MIME type.
 *
 * @return string
 *     The MIME type for the given filename.
 */
function phorum_api_file_get_mimetype($filename)
{
    $types = $GLOBALS["PHORUM"]["phorum_api_file_mimetypes"];

    $extension = "";
    $dotpos = strrpos($filename, ".");
    if ($dotpos !== FALSE) {
        $extension = strtolower(substr($filename, $dotpos+1));
    }

    $mime_type = isset($types[$extension])
               ? $types[$extension]
               : "application/octet-stream";

    return $mime_type;
}
// }}}

// {{{ Function: phorum_api_file_check_write_access
/**
 * Check if the active user has permissions to store a personal
 * file or a message attachment.
 *
 * Note that the checks for message attachments aren't all checks that are
 * done by Phorum. The attachment posting script does run some additional
 * checks on the message level (e.g. to see if the maximum cumulative
 * attachment size is not exceeded).
 *
 * @example file_store.php Store a personal file.
 *
 * @param array $file
 *     This is an array, containing information about the
 *     file that will be uploaded. The array should contain at least the
 *     "link" field. That field will be used to handle checking for personal
 *     uploaded files in the control center (PHORUM_LINK_USER) or message
 *     attachments (PHORUM_LINK_MESSAGE). Next to that, interesting file
 *     fields to pass to this function are "filesize" (to check maximum size)
 *     and "filename" (to check allowed file type extensions). A "user_id"
 *     field can either be provided or the user_id of the active Phorum
 *     user will be used.
 *
 * @return array
 *     If access is allowed, then TRUE will be returned. If access is denied,
 *     then FALSE will be returned. The functions {@link phorum_api_strerror()}
 *     and {@link phorum_api_errno()} can be used to retrieve information
 *     about the error which occurred.
 */
function phorum_api_file_check_write_access($file)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Reset error storage.
    $GLOBALS["PHORUM"]["API"]["errno"] = NULL;
    $GLOBALS["PHORUM"]["API"]["error"] = NULL;

    if (!isset($file["link"])) trigger_error(
        "phorum_api_file_check_write_access(): \$file parameter needs a " .
        "\"link\" field.",
        E_USER_ERROR
    );

    if (empty($file["user_id"])) {
        $file["user_id"] = $PHORUM["user"]["user_id"];
    }

    // ---------------------------------------------------------------------
    // Handle write access checks for uploading user files.
    // ---------------------------------------------------------------------

    if ($file["link"] == PHORUM_LINK_USER)
    {
        // If file uploads are enabled, then access is granted. Access
        // is always granted to administrator users.
        if (!$PHORUM["file_uploads"] && !$PHORUM["user"]["admin"]) {
            return phorum_api_error_set(
                PHORUM_ERRNO_NOACCESS,
                $PHORUM["DATA"]["LANG"]["UploadNotAllowed"]
            );
        }

        // Check if the file doesn't exceed the maximum allowed file size.
        if (isset($file["filesize"]) && $PHORUM["max_file_size"] > 0 &&
            $file["filesize"] > $PHORUM["max_file_size"]*1024) {
            return phorum_api_error_set(
                PHORUM_ERRNO_NOACCESS,
                $PHORUM["DATA"]["LANG"]["FileTooLarge"]
            );
        }

        // Check if the user won't exceed the file quota when storing the file.
        if(isset($file["filesize"]) && $PHORUM["file_space_quota"] > 0) {
            $sz = phorum_db_get_user_filesize_total($PHORUM["user"]["user_id"]);
            $sz += $file["filesize"];
            if ($sz > $PHORUM["file_space_quota"]*1024) {
                return phorum_api_error_set(
                    PHORUM_ERRNO_NOACCESS,
                    $PHORUM["DATA"]["LANG"]["FileOverQuota"]
                );
            }
        }

        // Check if the file type is allowed.
        if (isset($file["filename"]) &&
            isset($PHORUM["file_types"]) && trim($PHORUM["file_types"]) != '')
        {
            // Determine the file extension for the file.
            $pos = strrpos($file["filename"], ".");
            if ($pos !== FALSE) {
                $ext = strtolower(substr($file["filename"], $pos + 1));
            } else {
                $ext = strtolower($file["filename"]);
            }

            // Create an array of allowed file extensions.
            $allowed_exts = explode(";", strtolower($PHORUM["file_types"]));

            // Check if the extension for the file is an allowed extension.
            if (!in_array($ext, $allowed_exts)) {
                return phorum_api_error_set(
                    PHORUM_ERRNO_NOACCESS,
                    $PHORUM["DATA"]["LANG"]["FileWrongType"]
                );
            }
        }
    }

    // ---------------------------------------------------------------------
    // Handle write access checks for uploading message attachment files.
    // ---------------------------------------------------------------------

    elseif ($file["link"] == PHORUM_LINK_EDITOR ||
            $file["link"] == PHORUM_LINK_MESSAGE) {

        // Check if the file doesn't exceed the maximum allowed file size
        // for the active forum.
        if (isset($file["filesize"]))
        {
            // Find the maximum allowed attachment size. This depends on
            // both the settings for the current forum and the limits
            // that are enforced by the system.
            require_once('./include/upload_functions.php');
            $max_upload = phorum_get_system_max_upload();
            $max_forum = $PHORUM["max_attachment_size"] * 1024;
            if ($max_forum > 0 && $max_forum < $max_upload)
                $max_upload = $max_forum;

            // Check if the file doesn't exceed the maximum allowed size.
            if ($max_upload > 0 && $file["filesize"] > $max_upload) {
                return phorum_api_error_set(
                    PHORUM_ERRNO_NOACCESS,
                    str_replace(
                        '%size%', phorum_filesize($max_upload),
                        $PHORUM["DATA"]["LANG"]["AttachFileSize"]
                    )
                );
            }
        }

        // Check if the file type is allowed for the active forum.
        if (isset($file["filename"]) &&
            isset($PHORUM["allow_attachment_types"]) &&
            trim($PHORUM["allow_attachment_types"]) != '')
        {
            // Determine the file extension for the file.
            $pos = strrpos($file["filename"], ".");
            if ($pos !== FALSE) {
                $ext = strtolower(substr($file["filename"], $pos + 1));
            } else {
                $ext = strtolower($file["filename"]);
            }

            // Create an array of allowed file extensions.
            $allowed_exts = explode(";", strtolower($PHORUM["allow_attachment_types"]));

            // Check if the extension for the file is an allowed extension.
            if (!in_array($ext, $allowed_exts)) {
                return phorum_api_error_set(
                    PHORUM_ERRNO_NOACCESS,
                    $PHORUM["DATA"]["LANG"]["AttachInvalidType"] . " ".
                    str_replace(
                        '%types%', implode(", ", $allowed_exts),
                        $PHORUM["DATA"]["LANG"]["AttachFileTypes"]
                    )
                );
            }
        }
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_api_file_store
/**
 * Store or update a file.
 *
 * @example file_store.php Store a personal file.
 *
 * @param array $file
 *     An array, containing information for the file.
 *     This array has to contain the following fields:
 *     <ul>
 *     <li>filename: The name of the file.</li>
 *     <li>file_data: The file data.</li>
 *     <li>filesize: The size of the file data in bytes.</li>
 *     <li>link: A value describing to what type of entity the file is
 *         linked. The following values are available:
 *         <ul>
 *         <li>PHORUM_LINK_USER</li>
 *         <li>PHORUM_LINK_MESSAGE</li>
 *         <li>PHORUM_LINK_EDITOR</li>
 *         <li>PHORUM_LINK_TEMPFILE</li>
 *         </ul>
 *     </li>
 *     <li>user_id: The user to link a file to. If none is provided, then
           the user_id of the active Phorum user will be used.</li>
 *     <li>message_id: The message to link a file to or 0 if it's no
 *         message attachment.</li>
 *     </ul>
 *
 *     Additionally, the "file_id" field can be set. If it is set,
 *     then the existing file will be updated. If it is not set,
 *     a new file will be created.
 *
 * @return mixed
 *     On error, this function will return FALSE. The functions
 *     {@link phorum_api_strerror()} and {@link phorum_api_errno()} can
 *     be used to retrieve information about the error which occurred.
 *
 *     On success, an array containing the data for the stored file
 *     will be returned. If the function is called with no "file_id"
 *     in the {@link $file} argument (when a new file is stored),
 *     then the new "file_id" and "add_datetime" fields will be
 *     included in the return variable as well.
 */
function phorum_api_file_store($file)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Check if we really got an array argument for $file.
    if (!is_array($file)) trigger_error(
        "phorum_api_file_store(): \$file parameter must be an array.",
        E_USER_ERROR
    );

    // Check and preprocess the data from the $file argument.
    // First we create a new empty file structure to fill.
    $checkfile = array(
        "file_id"     => NULL,
        "user_id"     => NULL,
        "filename"    => NULL,
        "filesize"    => NULL,
        "file_data"   => NULL,
        "message_id"  => NULL,
        "link"        => NULL,
    );

    // Go over all fields in the $file argument and add these to
    // the $checkfile array.
    foreach ($file as $k => $v)
    {
        switch ($k)
        {
            case "file_id":
            case "user_id":
            case "message_id":
            case "filesize":
                if ($v !== NULL) settype($v, "int");
                $checkfile[$k] = $v;
                break;

            case "filename":
                $v = basename($v);
                $checkfile[$k] = $v;
                break;

            case "link":
            case "file_data":
                $checkfile[$k] = $v;
                break;

            case "add_datetime":
                $checkfile[$k] = (int)$v;
                break;

            case "result":
            case "mime_type":
                // These are some dynamic fields which might be present
                // in the data, when storing file data that was returned
                // by the file retrieve function. We simply skip these here.
                break;

            default:
                trigger_error(
                    "phorum_api_file_store(): \$file parameter contains " .
                    'an illegal field "'.htmlspecialchars($k).'".',
                    E_USER_ERROR
                );
        }
    }


    // Set user_id to current user if one was not passed in
    if (empty($checkfile["user_id"]) && isset($PHORUM["user"]["user_id"])) {
        $checkfile["user_id"] = $PHORUM["user"]["user_id"];
    }


    // Force the message_id to 0, depending on the
    // link type. Also check if the required id field (user or
    // message) is set for the used link type.
    switch ($checkfile["link"])
    {
        case PHORUM_LINK_EDITOR:
            $checkfile["message_id"] = 0;
            break;
        case PHORUM_LINK_USER:
            $checkfile["message_id"] = 0;
            if (empty($checkfile["user_id"])) trigger_error (
                "phorum_api_file_store(): \$file set the link type to " .
                "PHORUM_LINK_USER, but the user_id was not set.",
                E_USER_ERROR
            );
            break;
        case PHORUM_LINK_MESSAGE:
            if (empty($checkfile["message_id"])) trigger_error (
                "phorum_api_file_store(): \$file set the link type to " .
                "PHORUM_LINK_MESSAGE, but the message_id was not set.",
                E_USER_ERROR
            );
            break;
        default:
            if (empty($checkfile["message_id"])) {
                $checkfile["message_id"] = 0;
            }
            break;
    }

    // See if all required values are set.
    foreach ($checkfile as $k => $v) {
        if ($k == 'file_id') continue; // is NULL for new files.
        if ($v === NULL) trigger_error(
            "phorum_api_file_store(): \$file parameter misses the " .
            '"' . htmlspecialchars($k) . '" field.',
            E_USER_ERROR
        );
    }

    // All data was checked, so now we can continue with the checked data.
    $file = $checkfile;

    // New files need a file_id and an add_datetime timestamp.
    $created_skeleton_file = FALSE;
    if (empty($file["file_id"]))
    {
      $add_datetime = time();

      // Insert a skeleton file record in the database. We do this, to
      // get hold of a new file_id. That file_id can be passed on to
      // the hook below, so alternative storage systems know directly
      // for what file_id they will have to store data, without having
      // to store the full data in the database already.
      $file_id = phorum_db_file_save(array(
          "filename"     => $file["filename"],
          "filesize"     => 0,
          "file_data"    => "",
          "user_id"      => 0,
          "message_id"   => 0,
          "link"         => PHORUM_LINK_TEMPFILE,
          "add_datetime" => $add_datetime
      ));

      $file["file_id"] = $file_id;
      $file["add_datetime"] = $add_datetime;

      $created_skeleton_file = TRUE;
    }

    // Allow modules to handle file data storage. If a module implements
    // a different data storage method, it can store the file data in its
    // own way and set the "file_data" field to an empty string in $file
    // (it is not mandatory to do so, but it is adviceable, since it
    // would make no sense to store the file data both in an alternative
    // storage and the database at the same time).
    // The hook can use phorum_api_error_set() to return an error.
    // Hooks should be aware that their input might not be $file, but
    // FALSE instead, in which case they should immediately return
    // FALSE themselves.
    if (isset($PHORUM["hooks"]["file_store"]))
    {
        $hook_result = phorum_hook("file_store", $file);

        // Return if a module returned an error.
        if ($hook_result === FALSE)
        {
            // Cleanup the skeleton file from the database.
            if ($created_skeleton_file) {
                phorum_db_file_delete($file["file_id"]);
                $file["file_id"] = NULL;
            }

            return FALSE;
        }

        $file = $hook_result;
    }

    // Phorum stores the files in base64 format in the database, to
    // prevent problems with upgrading and migrating database servers.
    // The ASCII representation for the files will always be safe to dump
    // and restore. So here we will base64 encode the file data.
    //
    // If the file_data field is an empty string by now, then either the
    // file data was really empty to start with or a module handled the
    // storage. In both cases it's fine to keep the data field empty.
    if ($file["file_data"] != '') {
        $file["file_data"] = base64_encode($file["file_data"]);
    }

    // Update the (skeleton) file record to match the real file data.
    // This acts like a commit action for the file storage.
    phorum_db_file_save ($file);

    return $file;
}
// }}}

// {{{ Function: phorum_api_file_check_read_access
/**
 * Check if a file exists and if the active user has permission to read it.
 *
 * The function will return either an array containing descriptive data
 * for the file or FALSE, in case access was not granted.
 *
 * Note that the file_data field is not available in the return array.
 * That data can be retrieved by the {@link phorum_api_file_retrieve()}
 * function.
 *
 * @param integer $file_id
 *     The file_id of the file for which to check read access.
 *
 * @param integer $flags
 *     If the {@link PHORUM_FLAG_IGNORE_PERMS} flag is used, then permission
 *     checks are fully bypassed. In this case, the function will only check
 *     if the file exists or not.
 *
 * @return mixed
 *     On error, this function will return FALSE.
 *     The functions {@link phorum_api_strerror()} and
 *     {@link phorum_api_errno()} can be used to retrieve information about
 *     the error which occurred.
 *
 *     On success, it returns an array containing descriptive data for
 *     the file. The following fields are available in this array:
 *     <ul>
 *     <li>file_id: The file_id for the requested file.</li>
 *     <li>filename: The name of the file.</li>
 *     <li>filesize: The size of the file in bytes.</li>
 *     <li>add_datetime: Epoch timestamp describing at what time
 *         the file was stored.</li>
 *     <li>message_id: The message to which a message is linked
 *         (in case it is a message attachment).</li>
 *     <li>user_id: The user to which a message is linked
 *         (in case it is a private user file).</li>
 *     <li>link: A value describing to what type of entity the file is
 *         linked. One of {@link PHORUM_LINK_USER},
 *         {@link PHORUM_LINK_MESSAGE}, {@link PHORUM_LINK_EDITOR} and
 *         {@link PHORUM_LINK_TEMPFILE}.</li>
 *     </ul>
 */
function phorum_api_file_check_read_access($file_id, $flags = 0)
{
    global $PHORUM;

    settype($file_id, "int");

    // Reset error storage.
    $GLOBALS["PHORUM"]["API"]["errno"] = NULL;
    $GLOBALS["PHORUM"]["API"]["error"] = NULL;

    // Check if the active user has read access for the active forum_id.
    if (!($flags & PHORUM_FLAG_IGNORE_PERMS) && !phorum_check_read_common()) {
        return phorum_api_error_set(
            PHORUM_ERRNO_NOACCESS,
            "Read permission for file (id $file_id) denied."
        );
    }

    // Retrieve the descriptive file data for the file from the database.
    // Return an error if the file does not exist.
    $file = phorum_db_file_get($file_id, FALSE);
    if (empty($file)) return phorum_api_error_set(
        PHORUM_ERRNO_NOTFOUND,
        "The requested file (id $file_id) was not found."
    );

    // For the standard database based file storage, we do not have to
    // do checks for checking file existence (since the data is in the
    // database and we found the record for it). Storage modules might
    // have to do additional checks though (e.g. to see if the file data
    // exists on disk), so here we give them a chance to check for it.
    // This hook can also be used for implementing additional access
    // rules. The hook can use phorum_api_error_set() to return an error.
    // Hooks should be aware that their input might not be $file, but
    // FALSE instead, in which case they should immediately return
    // FALSE themselves.
    if (isset($PHORUM["hooks"]["file_check_read_access"])) {
        $file = phorum_hook("file_check_read_access", $file, $flags);
        if ($file === FALSE) return FALSE;
    }

    // If we do not do any permission checking, then we are done.
    if ($flags & PHORUM_FLAG_IGNORE_PERMS) return $file;

    // If PHORUM_ADMIN is defined, we don't need to check permissions
    if (defined("PHORUM_ADMIN")) return $file;

    // Is the file linked to a forum message? In that case, we have to
    // check if the message does really belong to the requested forum_id.
    if ($file["link"] == PHORUM_LINK_MESSAGE && !empty($file["message_id"]))
    {
        // Retrieve the message. If retrieving the message is not possible
        // or if the forum_id of the message is different from the requested
        // forum_id, then return an error.
        $message = phorum_db_get_message($file["message_id"],"message_id",TRUE);
        if (empty($message)) return phorum_api_error_set(
            PHORUM_ERRNO_INTEGRITY,
            "An integrity problem was detected in the database: " .
            "file id $file_id is linked to non existent " .
            "message_id {$file["message_id"]}."
        );
        if ($message["forum_id"] != $PHORUM["forum_id"]) {
            return phorum_api_error_set(
                PHORUM_ERRNO_NOACCESS,
                "Permission denied for reading the file: it does not " .
                "belong to the requested forum_id {$PHORUM["forum_id"]}."
            );
        }
    }

    // A general purpose URL host matching regexp, that we'll use below.
    $matchhost = '!^https?://([^/]+)/!i';

    // See if off site links are allowed. If this is not the case, then
    // check if an off site link is requested. We use the HTTP_REFERER for
    // doing the off site link check. This is not a water proof solution
    // (since HTTP referrers can be faked), but it will be good enough for
    // stopping the majority of the off site requests.
    if (isset($_SERVER["HTTP_REFERER"]) &&
        $PHORUM["file_offsite"] != PHORUM_OFFSITE_ANYSITE &&
        preg_match($matchhost, $_SERVER["HTTP_REFERER"])) {

        // Generate the base URL for the Phorum.
        $base = strtolower(phorum_get_url(PHORUM_BASE_URL));

        // Strip query string from the base URL. We mainly want to
        // check if the location matches the Phorum location.
        // Otherwise, we might get in troubles with things like
        // URI authentication, where a session id is added to the URL.
        $base = preg_replace('/\?.*$/', '', $base);

        // FORUMONLY: Links to forum files are only allowed from the forum.
        // Check if the referrer URL starts with the base Phorum URL.
        if ($PHORUM["file_offsite"] == PHORUM_OFFSITE_FORUMONLY) {
            $refbase = substr($_SERVER["HTTP_REFERER"], 0, strlen($base));
            if (strcasecmp($base, $refbase) != 0) return phorum_api_error_set(
                PHORUM_ERRNO_NOACCESS,
                "Permission denied: links to files in the forum are " .
                "only allowed from the forum itself."
            );
        }
        // THISSITE: Links to forum files are allowed from anywhere on
        // the website where Phorum is hosted.
        elseif ($PHORUM["file_offsite"] == PHORUM_OFFSITE_THISSITE) {
            if (preg_match($matchhost, $_SERVER["HTTP_REFERER"], $rm) &&
                preg_match($matchhost, $base, $bm) &&
                strcasecmp($rm[1], $bm[1]) != 0) return phorum_api_error_set(
                    PHORUM_ERRNO_NOACCESS,
                    "Permission denied: links to files in the forum are " .
                    "only allowed from this web site."
            );
        }
    }

    return $file;
}
// }}}

// {{{ Function: phorum_api_file_retrieve
/**
 * Retrieve a Phorum file.
 *
 * This function can handle Phorum file retrieval in multiple ways:
 * either return the file to the caller or send it directly to the user's
 * browser (based on the $flags parameter). Sending it directly to the
 * browser allows for the implementation of modules that don't have to buffer
 * the full file data before sending it (a.k.a. streaming, which provides the
 * advantage of using less memory for sending files).
 *
 * @param mixed $file
 *     This is either an array containing at least the fields "file_id"
 *     and "filename" or a numerical file_id value. Note that you can
 *     use the return value of the function
 *     {@link phorum_api_file_check_read_access()} as input for this function.
 *
 * @param integer $flags
 *     These are flags which influence aspects of the function call. It is
 *     a bitflag value, so you can OR multiple flags together. Available
 *     flags for this function are: {@link PHORUM_FLAG_IGNORE_PERMS},
 *     {@link PHORUM_FLAG_GET}, {@link PHORUM_FLAG_SEND} and
 *     {@link PHORUM_FLAG_FORCE_DOWNLOAD}. The SEND flag has precedence
 *     over the GET flag.
 *
 * @return mixed
 *     On error, this function will return FALSE.
 *     The functions {@link phorum_api_strerror()} and
 *     {@link phorum_api_errno()} can be used to retrieve information about
 *     the error which occurred.
 *
 *     If the {@link PHORUM_FLAG_SEND} flag is used, then the function will
 *     return NULL.
 *
 *     If the {@link PHORUM_FLAG_GET} flag is used, then the function
 *     will return a file description array, containing the fields "file_id",
 *     "username", "file_data", "mime_type".
 *     If the {@link $file} parameter was an array, then all fields from that
 *     array will be included as well.
 */
function phorum_api_file_retrieve($file, $flags = PHORUM_FLAG_GET)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Reset error storage.
    $GLOBALS["PHORUM"]["API"]["errno"] = NULL;
    $GLOBALS["PHORUM"]["API"]["error"] = NULL;

    // If $file is not an array, we are handling a numerical file_id.
    // In that case, first retrieve the file data through the access check
    // function. All the function flags are passed on to that function,
    // so the PHORUM_FLAG_IGNORE_PERMS flag can be set for ignoring access
    // permissions.
    if (!is_array($file))
    {
        $file_id = (int) $file;
        $file = phorum_api_file_check_read_access($file_id, $flags);

        // Return in case of errors.
        if ($file === FALSE) return FALSE;
    }

    // A small basic check to see if we have a proper $file array.
    if (!isset($file["file_id"])) trigger_error(
        "phorum_api_file_get(): \$file parameter needs a \"file_id\" field.",
        E_USER_ERROR
    );
    if (!isset($file["filename"])) trigger_error(
        "phorum_api_file_get(): \$file parameter needs a \"filename\" field.",
        E_USER_ERROR
    );
    settype($file["file_id"], "int");

    /*
     * [hook]
     *     file_retrieve
     *
     * [description]
     *     This hook allows modules to handle the file data retrieval.
     *     The hook can use <literal>phorum_api_error_set()</literal>
     *     to return an error. Hooks should be aware that their input might
     *     not be <literal>$file</literal>, but <literal>FALSE</literal>
     *     instead, in which case they should immediately return
     *     <literal>FALSE</literal> themselves.
     *
     * [category]
     *     File storage
     *
     * [when]
     *     In <filename>include/api/file_storage.php</filename>,
     *     right before a file attachment is retrieved from the database.
     *
     * [input]
     *     Two part array where the first element is an empty file array
     *     and the second element is the flags variable.
     *
     * [output]
     *     Same as input with file_data filled in.
     */
    $file["result"]    = 0;
    $file["mime_type"] = NULL;
    $file["file_data"] = NULL;
    if (isset($PHORUM["hooks"]["file_retrieve"])) {
        list($file,$flags) = phorum_hook("file_retrieve", array($file,$flags));
        if ($file === FALSE) return FALSE;

        // If a module sent the file data to the browser, then we are done.
        if ($file["result"] == PHORUM_FLAG_SEND) return NULL;
    }

    // If no module handled file retrieval, we will retrieve the
    // file from the Phorum database.
    if ($file["file_data"] === NULL)
    {
        $dbfile = phorum_db_file_get($file["file_id"], TRUE);
        if (empty($dbfile)) return phorum_api_error_set(
            PHORUM_ERRNO_NOTFOUND,
            "Phorum file (id {$file["file_id"]}) could not be " .
            "retrieved from the database."
        );

        // Phorum stores the files in base64 format in the database, to
        // prevent problems with dumping and restoring databases.
        $file["file_data"] = base64_decode($dbfile["file_data"]);
    }

    $mime_type_verified = FALSE;
    // Set the MIME type information if it was not set by a module.
    if ($file["mime_type"] === NULL)
    {
        $extension_mime_type = phorum_api_file_get_mimetype($file["filename"]);

        // mime magic file in case its needed
        if(!empty($PHORUM['mime_magic_file'])) {
            $mime_magic_file = $PHORUM['mime_magic_file'];
        } else {
            $mime_magic_file = NULL;
        }
        // retrieve the mime-type using the fileinfo extension if its available and enabled
        if(function_exists("finfo_open") &&
           (!isset($PHORUM['file_fileinfo_ext']) || !empty($PHORUM['file_fileinfo_ext'])) &&
           $finfo = @finfo_open(FILEINFO_MIME,$mime_magic_file)) {

            $file["mime_type"] = finfo_buffer($finfo,$file['file_data']);
            finfo_close($finfo);
            if ($file["mime_type"] === FALSE) return phorum_api_error_set(
                PHORUM_ERRNO_ERROR,
                "The mime-type of file {$file["file_id"]} couldn't be determined through the" .
                "fileinfo-extension"
            );
            // extension mime-type doesn't fit the signature mime-type
            // make it a download then
            if($extension_mime_type != $file["mime_type"]) {
                $flags = $flags | PHORUM_FLAG_FORCE_DOWNLOAD;
            }
            $mime_type_verified = TRUE;
        } else {
            $file["mime_type"] = $extension_mime_type;
        }
    }

    // If the file is not requested for downloading, then check if it is
    // safe for the browser to view this file. If it is not, then
    // enable the force download flag to make sure that the browser will
    // download the file.
    $safe_to_cache = TRUE;
    $safe_to_view  = TRUE;
    if (!($flags & PHORUM_FLAG_FORCE_DOWNLOAD) && !$mime_type_verified)
    {
        list ($safe_to_view, $safe_to_cache) =
            phorum_api_file_safe_to_view($file);
        if (!$safe_to_view) {
            $flags = $flags | PHORUM_FLAG_FORCE_DOWNLOAD;
        }
    }

    // Allow for post processing on the retrieved file.
    list($file,$flags) = phorum_hook("file_after_retrieve", array($file,$flags));

    // In "send" mode, we directly send the file contents to the browser.
    if ($flags & PHORUM_FLAG_SEND)
    {
        // Get rid of any buffered output so far.
        phorum_ob_clean();

        // Avoid using any output compression or handling on the sent data.
        ini_set("zlib.output_compression", "0");
        ini_set("output_handler", "");

        $time = (int)$file['add_datetime'];

        // Handle client side caching.
        if ($safe_to_cache)
        {
            if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                $header = preg_replace('/;.*$/','',$_SERVER['HTTP_IF_MODIFIED_SINCE']);
                $modified_since = strtotime($header);

                if ($modified_since >= $time)
                {
                    $proto = empty($_SERVER['SERVER_PROTOCOL'])
                           ? 'HTTP/1.0' : $_SERVER['SERVER_PROTOCOL'];
                    header("$proto 304 Not Modified");
                    header('Status: 304');
                    exit(0);
                }
            }
            header("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', $time));
            header('Cache-Control: max-age=5184000'); // 60 days
            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time()+5184000));
        }
        else
        {
            // Expire in the past.
            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - 99999));
            // Always modified.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', time()));
            // HTTP/1.1
            header('cache-Control: no-store, no-cache, must-revalidate');
            header('cache-Control: post-check=0, pre-check=0', FALSE);
            // HTTP/1.0
            header('Pragma: no-cache');
        }

        if ($flags & PHORUM_FLAG_FORCE_DOWNLOAD) {
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"{$file["filename"]}\"");
        } else {
            header("Content-Type: " . $file["mime_type"]);
            header("Content-Disposition: filename=\"{$file["filename"]}\"");
        }

        header('Content-Length: ' . strlen($file['file_data']));

        print $file["file_data"];

        return NULL;
    }

    // In "get" mode, we return the full file data array to the caller.
    elseif ($flags & PHORUM_FLAG_GET) {
        return $file;
    }

    // Safety net.
    else trigger_error(
        "phorum_api_file_retrieve(): no retrieve mode specified in the " .
        "flags (either use PHORUM_FLAG_GET or PHORUM_FLAG_SEND).",
        E_USER_ERROR
    );
}
// }}}

// {{{ Function: phorum_api_file_check_delete_access
/**
 * Check if the active user has permission to delete a file.
 *
 * @example file_delete.php Delete a file.
 *
 * @param integer $file_id
 *     The file_id of the file for which to check the delete access.
 *
 * @return boolean
 *     TRUE if the user has rights to delete the file, FALSE otherwise.
 */
function phorum_api_file_check_delete_access($file_id)
{
    global $PHORUM;

    settype($file_id, "int");

    // Administrator users always have rights to delete files.
    if ($PHORUM["user"]["admin"]) {
        return TRUE;
    }

    // Anonymous users never have rights to delete files.
    if (empty($PHORUM["user"]["user_id"])) {
        return FALSE;
    }

    // For other users, the file information has to be retrieved
    // to be able to check the delete access.
    $file = phorum_api_file_check_read_access(
        $file_id,
        PHORUM_FLAG_IGNORE_PERMS
    );

    // To prevent permission errors after deleting the same file twice,
    // we'll return TRUE if we did not find a file (if the file is not found,
    // then there's no harm in deleting it; the file storage API will
    // silently ignore deleting non-existent files). If some other error
    // occurred, then we return FALSE (most likely, the user does not
    // even have read permission for the file, so delete access would
    // be out of the question too).
    if ($file === FALSE) {
        if (phorum_api_errno() == PHORUM_ERRNO_NOTFOUND) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // We don't care about deleting temporary files and files that
    // are linked to the posting editor (during writing a post).
    // Those are both intermediate states for files, without them
    // being available on the forum. So for those, we always grant
    // delete access.
    if ($file["link"] == PHORUM_LINK_TEMPFILE ||
        $file["link"] == PHORUM_LINK_EDITOR) {
        return TRUE;
    }

    // If the file is owned by the user, then the user has rights
    // to delete the file (this would be a personal user file).
    if (!empty($file["user_id"]) &&
        $file["user_id"] == $PHORUM["user"]["user_id"]) {
        return TRUE;
    }

    // The file is not owned by the user. In that case, the user only has
    // rights to delete it if it is a file that is linked to a message which
    // the user posted himself of which was posted in a forum for which
    // the user is a moderator.
    if ($file["link"] == PHORUM_LINK_MESSAGE)
    {
        // Retrieve the message to which the file is linked.
        $message = phorum_db_get_message($file["message_id"]);

        // If the message cannot be found, we do not care if the linked
        // file is deleted. It's clearly an orphin file.
        if (! $message) {
            return TRUE;
        }

        // Check if the user posted the message himself.
        if (!empty($message["user_id"]) &&
            $message["user_id"] == $PHORUM["user"]["user_id"]) {
            return TRUE;
        }

        // Check if the user is moderator for the forum_id of the message.
        if (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES, $message["forum_id"])) {
            return TRUE;
        }
    }

    // The default policy for any unhandled case is to deny access.
    return FALSE;
}
// }}}

// {{{ Function: phorum_api_file_delete
/**
 * Delete a Phorum file.
 *
 * @example file_delete.php Delete a file.
 *
 * @param mixed $file
 *     This is either an array containing at least the field "file_id"
 *     or a numerical file_id value.
 */
function phorum_api_file_delete($file)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Find the file_id parameter to use.
    if (is_array($file)) {
        if (!isset($file["file_id"])) trigger_error(
            "phorum_api_file_delete(): \$file parameter needs a " .
            "\"file_id\" field.",
            E_USER_ERROR
        );
        $file_id = (int) $file["file_id"];
    } else {
        $file_id = (int) $file;
    }

    // Allow storage modules to handle the file data removal.
    // Modules should be aware of the fact that files don't have to
    // exist. The Phorum core does not throw errors when deleting a
    // non existent file. Therefore modules should accept that case
    // as well, without throwing errors.
    if (isset($PHORUM["hooks"]["file_delete"]))
        phorum_hook("file_delete", $file_id);

    // Delete the file from the Phorum database.
    phorum_db_file_delete($file_id);
}
// }}}

// {{{ Function: phorum_api_file_list()
/**
 * Retrieve a list of files.
 *
 * @param string $link_type
 *     The type of link to retrieve from the database. Normally this is one
 *     of the Phorum built-in link types, but it can also be a custom
 *     link type (e.g. if a module uses the file storage on its own).
 *     This parameter can be NULL to retrieve any link type.
 *
 * @param integer $user_id
 *     The user_id to retrieve files for or NULL to retrieve files for
 *     any user_id.
 *
 * @param integer $message_id
 *     The message_id to retrieve files for or NULL to retrieve files for
 *     any message_id.
 *
 * @return array
 *     An array of files, indexed by file_id.
 *     The array elements are arrays containing the fields:
 *     file_id, filename, filesize and add_datetime.
 */
function phorum_api_file_list($link_type = NULL, $user_id = NULL, $message_id = NULL)
{
    return phorum_db_get_file_list($link_type, $user_id, $message_id);
}
// }}}

// {{{ Function: phorum_api_file_purge_stale()
/**
 * This function is used for purging stale files from the Phorum system.
 *
 * @param boolean $do_purge
 *     If this parameter is set to a false value (the default), then no
 *     actual purging will take place. The function will only return an
 *     array of stale files. If the parameter is set to a true value,
 *     then the stale files will be purged for real.
 *
 * @return array
 *     An array of stale Phorum files, indexed by file_id. Every item in
 *     this array is an array on its own, containing the fields:
 *     - file_id: the file id of the stale file
 *     - filename: the name of the stale file
 *     - filesize: the size of the file in bytes
 *     - add_datetime: the time (epoch) at which the file was added
 *     - reason: the reason why it's a stale file
 *     This array will be returned, regardless of the $do_purge parameter.
 */
function phorum_api_file_purge_stale($do_purge)
{
    $stale_files = phorum_db_list_stale_files();

    /**
     * [hook]
     *     file_purge_stale
     *
     * [description]
     *     This hook can be used to feed the file storage API function
     *     phorum_api_file_purge_stale() extra stale files. This can be
     *     useful for modules that handle their own files, using a
     *     custom link type.
     *
     * [category]
     *     File storage
     *
     * [when]
     *     Right after Phorum created its own list of stale files.
     *
     * [input]
     *     An array containing stale files, indexed by file_id. Each item
     *     in this array is an array on its own, containing the following
     *     fields:
     *     <ul>
     *     <li>file_id:
     *         the file id of the stale file</li>
     *     <li>filename:
     *         the name of the stale file</li>
     *     <li>filesize:
     *         the size of the file in bytes</li>
     *     <li>add_datetime:
     *         the time (epoch) at which the file was added</li>
     *     <li>reason:
     *         the reason why it's a stale file</li>
     *     </ul>
     *
     * [output]
     *     The same array as the one that was used for the hook call
     *     argument, possibly extended with extra files that are
     *     considered to be stale.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['file_purge_stale']))
        $stale_files = phorum_hook('file_purge_stale', $stale_files);

    // Delete the files if requested.
    if ($do_purge) {
        foreach ($stale_files as $file) {
            phorum_api_file_delete($file);
        }
    }

    return $stale_files;
}
// }}}

// ------------------------------------------------------------------------
// File security checking
// ------------------------------------------------------------------------

// {{{ Function: phorum_api_file_safe_to_view()
/**
 * Check if the file is safe to view in the browser.
 *
 * This will emulate MIME-sniffing as done by browsers to see if
 * the file could be interpreted as an HTML file by the browser.
 *
 * @param array $file
 *     An array, containing information for the file.
 *     This array has to contain at least the file_data field.
 *
 * @return boolean
 *     TRUE if the browser might qualify the file as HTML code,
 *     FALSE otherwise.
 *
 * @return boolean
 *     TRUE if it is safe to cache the file in the browser, FALSE otherwise.
 */
function phorum_api_file_safe_to_view($file)
{
    if (!isset($file['file_data'])) trigger_error(
        "phorum_api_file_safe_to_view(): \$file parameter needs a " .
        "\"file_data\" field.",
        E_USER_ERROR
    );

    $safe_to_cache = TRUE;
    $safe_to_view  = TRUE;

    // Based on info from:
    // http://webblaze.cs.berkeley.edu/2009/content-sniffing/
    //
    // Sniffing buffer in various browsers:
    // - MSIE7 = 256 Bytes
    // - FF3 & Safari 3.1 = 1024 Bytes
    // - Google Chrome & HTML5 spec = 512 Bytes
    // A conservative approach requires checking of 1024 Bytes.
    //
    // The trim() call is used, because some browser checks
    // look at the first non-whitespace byte of the file data.
    //
    $chunk = trim(substr($file['file_data'], 0, 1024));

    if (preg_match('/
        ^<!|              # FF3            CHROME HTML5
        ^<\?|             # FF3
        <html|            # FF3 IE7 SAF3.1 CHROME HTML5
        <script|          # FF3 IE7 SAF3.1 CHROME HTML5
        <title|           # FF3 IE7 SAF3.1 CHROME
        <body|            # FF3 IE7        CHROME
        <head|            # FF3 IE7        CHROME HTML5
        <plaintext|       #     IE7
        <table[ >]|       # FF3 IE7        CHROME
        <img[ >]|         # FF3 IE7
        <pre[ >]|         # FF3 IE7
        text\/html|       #         SAF3.1
        <a[ >]|           # FF3 IE7 SAF3.1 CHROME
        ^<frameset[ >]|   # FF3
        ^<iframe[ >]|     # FF3            CHROME
        ^<link[ >]|       # FF3
        ^<base[ >]|       # FF3
        ^<style[ >]|      # FF3            CHROME
        ^<div[ >]|        # FF3            CHROME
        ^<p[ >]|          # FF3            CHROME
        ^<font[ >]|       # FF3            CHROME
        ^<applet[ >]|     # FF3
        ^<meta[ >]|       # FF3
        ^<center[ >]|     # FF3
        ^<form[ >]|       # FF3
        ^<isindex[ >]|    # FF3
        ^<h1[ >]|         # FF3            CHROME
        ^<h2[ >]|         # FF3
        ^<h3[ >]|         # FF3
        ^<h4[ >]|         # FF3
        ^<h5[ >]|         # FF3
        ^<h6[ >]|         # FF3
        ^<b[ >]|          # FF3            CHROME
        ^<br[ >]          #                CHROME
        /xi', $chunk, $m)) {

        $safe_to_view = FALSE;

        // The file could be interpreted as HTML by the browser.
        // As an additional check, we check if MSIE 6 or lower is in use.
        // For those, it is not safe to cache the file. In some cases,
        // they could interpret the file from cache, even when we tell
        // the browser that the file should be downloaded.
        if (!empty($_SERVER['HTTP_USER_AGENT']) &&
            preg_match('/MSIE [654]\D/', $_SERVER['HTTP_USER_AGENT'])) {
            $safe_to_cache = FALSE;
        }
    }

    return array($safe_to_view, $safe_to_cache);
}
// }}}

// ------------------------------------------------------------------------
// Alias functions (useful shortcut calls to the main file api functions).
// ------------------------------------------------------------------------

// {{{ Function: phorum_api_file_exists
/**
 * Check if a Phorum file exists.
 *
 * (this is a simple wrapper function around the
 * {@link phorum_api_file_check_read_access()} function)
 *
 * @param integer $file_id
 *     The file_id of the Phorum file to check.
 *
 * @return bool
 *     TRUE in case the file exists or FALSE if it doesn't.
 */
function phorum_api_file_exists($file_id) {
    $file = phorum_api_file_check_read_access($file_id, PHORUM_FLAG_IGNORE_PERMS);
    $exists = empty($file) ? FALSE : TRUE;
    return $exists;
}
// }}}

// {{{ Function: phorum_api_file_send
/**
 * Send a file to the browser.
 *
 * (this is a simple wrapper function around the
 * {@link phorum_api_file_retrieve()} function)
 *
 * @param mixed
 *    This is either an array containing at least the fields "file_id"
 *    and "filename" or a numerical file_id value. Note that you can
 *    use the return value of the function
 *    {@link phorum_api_file_check_read_access()} as input for this function.
 *
 * @param integer
 *     If the {@link PHORUM_FLAG_IGNORE_PERMS} flag is used, then permission
 *     checks are fully bypassed. If {@link PHORUM_FLAG_FORCE_DOWNLOAD} is
 *     used, then a download by the browser is forced (instead of opening
 *     the file in an appliction that the browser finds appropriate for
 *     the file type).
 */
function phorum_api_file_send($file, $flags = 0) {
    phorum_api_file_retrieve($file, $flags | PHORUM_FLAG_SEND);
}
// }}}

// {{{ Function: phorum_api_file_get
/**
 * Retrieve and return a Phorum file.
 *
 * (this is a simple wrapper function around the
 * {@link phorum_api_file_retrieve()} function)
 *
 * @param mixed $file
 *    This is either an array containing at least the fields "file_id"
 *    and "filename" or a numerical file_id value. Note that you can
 *    use the return value of the function
 *    {@link phorum_api_file_check_read_access()} as input for this function.
 *
 * @param integer $flags
 *     If the {@link PHORUM_FLAG_IGNORE_PERMS} flag is used, then permission
 *     checks are fully bypassed.
 *
 * @return mixed
 *     See the return value for the {@link phorum_api_file_retrieve()}
 *     function.
 */
function phorum_api_file_get($file, $flags = 0) {
    return phorum_api_file_retrieve($file, $flags | PHORUM_FLAG_GET);
}
// }}}

?>
