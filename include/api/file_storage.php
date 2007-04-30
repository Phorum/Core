<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

// TODO: phorum_db_file_save should take the $file array and not the 
//       separated arguments. Fixed in new mysql lib already.

/**
 * @package   Phorum File Storage API
 * @author    Maurice Makaay <maurice@phorum.org>
 * @copyright 2007, Phorum Development Team
 * @licence   Phorum License, http://www.phorum.org/license.txt
 * @version   Subversion $Id$
 */

if (!defined("PHORUM")) return;

/**
 * A mapping of file extensions to their MIME types.
 * Used by function phorum_api_file_get_mimetype().
 */
$GLOBALS["PHORUM"]["phorum_api_file_mimetypes"] = array
(
    // This entry is used as the default MIME type for unknown extensions.
    ""     => "application/octet-stream",

    "pdf"  => "application/pdf",
    "doc"  => "application/msword",
    "xls"  => "application/vnd.ms-excel",
    "gif"  => "image/gif",
    "png"  => "image/png",
    "jpg"  => "image/jpeg",
    "jpeg" => "image/jpeg",
    "jpe"  => "image/jpeg",
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

/**
 * Lookup the MIME type for a given filename.
 *
 * @param $filename   - The filename for which to lookup the MIME type.
 * 
 * @return $mime_type - The MIME type for the given filename. 
 */
function phorum_api_file_get_mimetype($filename)
{
    $types = $GLOBALS["PHORUM"]["phorum_api_file_mimetypes"];

    $extension = "";
    $dotpos = strrpos($filename, ".");
    if ($dotpos !== FALSE) {
        $extension = strtolower(substr($filename, $dotpos+1));
    }

    $mime_type = isset($types[$extension]) ? $types[$extension] : $types[""];

    return $mime_type;
}

/**
 * Check if the user has permissions to store a personal file or
 * a message attachment.
 *
 * @param $file    - This is an array, containing information about the file
 *                   that will be uploaded. The array should contain at
 *                   least the "link" field. That field will be used to
 *                   handle checking for personal uploaded files in the
 *                   control center (PHORUM_LINK_USER) or message attachments
 *                   (PHORUM_LINK_MESSAGE). Next to that, interesting file
 *                   fields are "filesize" (to check size maximums) and
 *                   "filename" (to check allowed file type extensions).
 *
 * @return $access - The same array as the one that was used for the $file
 *                   parameter. Two extra fields are added: errno and error.
 *                   If access is allowed, these will be set to NULL.
 *                   If access is denied, then these will contain an error
 *                   code and textual message which describe the problem.
 */
function phorum_api_file_check_write_access($file)
{
    $PHORUM = $GLOBALS["PHORUM"];

    if (!isset($file["link"])) trigger_error(
        "phorum_api_file_check_write_access(): \$file parameter needs a " .
        "\"link\" field.",
        E_USER_ERROR
    );

    $access = FALSE;

    // Check if the user has permission to upload user files.
    if ($file["link"] == PHORUM_LINK_USER)
    {
        // If file uploads are enabled, then access is granted. Access
        // is always granted to administrator users.
        $access = ($PHORUM["file_uploads"] || $PHORUM["user"]["admin"]);

        // TODO
    }

    return $access;
}

/** 
 * Store or update a file in the database.
 *
 * @param $file - An array, containing information for the file.
 *                This array has to contain the following fields:
 *                filename   - The name of the file. 
 *                file_data  - The file data.
 *                filesize   - The size of the file data in bytes.
 *                link       - Describes to what kind of object the file is
 *                             linked. The following values are available:
 *                             PHORUM_LINK_USER
 *                                 This is a private user file.
 *                             PHORUM_LINK_MESSAGE and
 *                                 This is a message attachment for a
 *                                 posted message.
 *                             PHORUM_LINK_EDITOR.
 *                                 This is a message attachment for a
 *                                 message that is being edited (used
 *                                 internally by the posting scripts).
 *                             PHORUM_LINK_TEMPFILE.
 *                                 This is a link for a temporary file in 
 *                                 the storage (used internally by the
 *                                 file API while storing new files).
 *                user_id    - If link = PHORUM_LINK_USER is used, then the
 *                             file is a private file for a user and the
 *                             user_id field must be set to the file owner's
 *                             user_id. Otherwise, the field is set to 0.
 *                message_id - If link = PHORUM_LINK_MESSAGE is used, then the
 *                             file is a message attachment and the message_id
 *                             field has to be set to the id of the message
 *                             to which to attach the file. Otherwise, the
 *                             field is set to 0.
 *
 *                Additionally, the "file_id" field can be set. If it is set,
 *                then the existing file will be updated. If it is not set,
 *                a new file will be created.
 *
 * @return $file - An array containing the data for the stored file.
 *                 The If an error occurs, then the fields "error" and "errno"
 *                 will be set to a non NULL value. So when calling this
 *                 function, you will have to check these fields for errors.
 *                 If the function is called with no "file_id" in the $file
 *                 argument (when a new file is stored), then the new file_id
 *                 will be included in the return $file variable.
 */                
function phorum_api_file_store($file)
{
    // Check if we really got an array argument for $file. 
    if (!is_array($file)) trigger_error(
        "phorum_api_file_store(): \$file parameter must be an array.",
        E_USER_ERROR
    );

    // Check and preprocess the data from the $file argument.
    // First we create a new empty file structure to fill.
    $checkfile = array(
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

            default:
                trigger_error(
                    "phorum_api_file_store(): \$file parameter contains " .
                    'an illegal field "'.htmlspecialchars($k).'".',
                    E_USER_ERROR
                );
        }

        // Force the message_id and user_id to 0, depending on the
        // link type. Also check if the correct object id is set for
        // the used link type.
        switch ($checkfile["link"])
        {
            case PHORUM_LINK_EDITOR:
                $checkfile["message_id"] = 0;
                $checkfile["user_id"] = 0;
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
                $checkfile["user_id"] = 0;
                if (empty($checkfile["message_id"])) trigger_error (
                    "phorum_api_file_store(): \$file set the link type to " .
                    "PHORUM_LINK_USER, but the message_id was not set.",
                    E_USER_ERROR
                );
                break;
        }
    }

    // See if all required values are set.
    foreach ($checkfile as $k => $v) {
        if ($v === NULL) trigger_error(
            "phorum_api_file_store(): \$file parameter misses the " .
            '"' . htmlspecialchars($k) . '" field.',
            E_USER_ERROR
        );
    }

    // All data was checked, so now we can continue with the checked data
    // as $file. Also initialize the "errno" and "error" fields, which can
    // be used for error reporting.
    $file = $checkfile;
    $file["errno"] = NULL;
    $file["error"] = NULL;

    // Insert a skeleton file record in the database. We do this, to
    // get hold of a new file_id. That file_id can be passed on to
    // the hook below, so alternative storage systems know directly
    // for what file_id they will have to store data, without having
    // to store the full data in the database already.
    $file_id = phorum_db_file_save(array(
        "filename"   => $file["filename"],
        "filesize"   => 0,
        "file_data"  => "",
        "user_id"    => 0,
        "message_id" => 0,
        "link"       => PHORUM_LINK_TEMPFILE
    ));
    $file["file_id"] = $file_id;

    // Allow modules to handle file data storage. If a module implements
    // a different data storage method, it can store the file data in its
    // own way and set the "file_data" field to an empty string in $file
    // (it is not mandatory to do so, but it is adviceable, since it
    // would make no sense to store the file data both in an alternative
    // storage and the database at the same time).
    // Modules can return errors by setting the "errno" and "error" fields.
    $file = phorum_hook("file_store", $file);

    // Return if there were any errors returned from modules.
    if ($file["error"] !== NULL)
    {
        // Cleanup the skeleton file from the database.
        phorum_db_file_delete($file["file_id"]); 
        $file["file_id"] = NULL;

        return $file;
    }

    // Phorum stores the files in base64 format in the database, to
    // prevent problems with upgrading and migrating database servers.
    // The ASCII representation for the files will always be safe to upgrade
    // and migrate. So here we will base64 encode the file data.
    //
    // If the file_data field is an empty string by now, then either the
    // file data was really empty to start with or a module handled the
    // storage. In both cases it's fine to keep the data field empty.
    if ($file["file_data"] != '') {
        $file["file_data"] = base64_encode($file["file_data"]);
    }
    
    // Update the skeleton file record that we created to match the real
    // file data. This acts like a commit action for the file storage.
    phorum_db_file_save ($file);

    return $file;
}

/**
 * Check if a file exists and if the user has permission to read the file.
 * The function will return an array containing descriptive data for the file.
 * Note that the file_data field is not available in the return array.
 * That data can be retrieved by the phorum_api_file_retrieve() function.
 *
 * @param $file_id - The file_id of the file.
 * @param $flags   - If the PHORUM_FLAG_IGNORE_PERMS flag is used, then 
 *                   permission checks are fully bypassed. In this case,
 *                   the function will only check if the file exists or not.
 *
 * @return $file   - An array containing descriptive data for the file.
 *                   The following fields are in this array:
 *                   file_id      - The file_id for the requested file.
 *                   user_id      - The user to which a message is linked (in
 *                                  case it is a private file).
 *                   filename     - The name of the file. 
 *                   filesize     - The size of the file in bytes. 
 *                   add_datetime - Epoch timestamp describing when the file
 *                                  was stored.
 *                   message_id   - The message to which a message is linked
 *                                  (in case it is a message attachment).
 *                   link         - A value describing to what the file is
 *                                  linked. The following values are available:
 *                                  PHORUM_LINK_USER
 *                                  PHORUM_LINK_MESSAGE
 *                                  PHORUM_LINK_EDITOR
 *                                  PHORUM_LINK_TEMPFILE
 */
function phorum_api_file_check_read_access($file_id, $flags = 0)
{
    global $PHORUM;

    settype($file_id, "int");

    // Check if the active user has read access for the active forum_id.
    if (!$flags & PHORUM_FLAG_IGNORE_PERMS && !phorum_check_read_common()) {
        return PHORUM_ERRNO_NOACCESS;
    }

    // Retrieve the descriptive file data for the file from the database.
    // Return an error if the file does not exist.
    $file = phorum_db_file_get($file_id, FALSE);
    if (empty($file)) return PHORUM_ERRNO_FILENOTFOUND;

    // For the standard database based file storage, we do not have to
    // do checks for checking file existance (since the data is in the
    // database and we found the record for it). Storage modules might
    // have to do additional checks though (e.g. to see if the file data
    // is on disk), so here we give them a chance to check for it.
    // This hook can also be used for implementing additional access
    // rules. The hook can set one of the file api constants as the 
    // error code to return.
    $errno = phorum_hook("file_check_read_access", 0, $file, $flags); 
    if ($errno) return $errno;

    // If we do not do any permission checking, then we are done.
    if ($flags & PHORUM_FLAG_IGNORE_PERMS) return $file;

    // Is the file linked to a forum message? In that case, we have to
    // check if the message does really belong to the requested forum_id.
    if ($file["link"] == PHORUM_LINK_MESSAGE && !empty($file["message_id"]))
    {
        // Retrieve the message. If retrieving the message is not possible
        // or if the forum if of the message is different from the requested
        // forum_id, then return an error.
        $message = phorum_db_get_message($file["message_id"],"message_id",TRUE);
        if (empty($message)) return PHORUM_ERRNO_INTEGRITY;
        if ($message["forum_id"] != $PHORUM["forum_id"]) {
            return PHORUM_ERRNO_NOACCESS;
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

        // FORUMONLY: Links to forum files are only allowed from the forum.
        // Check if the referrer URL starts with the base Phorum URL.
        if ($PHORUM["file_offsite"] == PHORUM_OFFSITE_FORUMONLY) {
            $refbase = substr($_SERVER["HTTP_REFERER"], 0, strlen($base));
            if (strcasecmp($base, $refbase) != 0) {
                return PHORUM_ERRNO_FILEEXTLINK;
            }
        }
        // THISSITE: Links to forum files are allowed from anywhere on
        // the website where Phorum is hosted.  
        elseif ($PHORUM["file_offsite"] == PHORUM_OFFSITE_THISSITE) {
            if (preg_match($matchhost, $_SERVER["HTTP_REFERER"], $rm) &&
                preg_match($matchhost, $base, $bm) &&
                strcasecmp($rm[1], $bm[1]) != 0) {
                return PHORUM_ERRNO_FILEEXTLINK;
            }
        }
    }

    return $file;
}

/**
 * Retrieve a file. Either return it to the caller or send it directly to
 * the user's browser (based on the $flags parameter).
 *
 * @param $file  - This is either an array containing at least the fields
 *                 "file_id" and "filename" or a numerical file_id value.
 *                 Note that you can use the output of the function
 *                 phorum_api_file_check_read_access() as input for this
 *                 function.
 * @param $flags - These are flags which influence aspects of the function
 *                 call. It is a bitflag value, so you can OR multiple
 *                 flags together. Available flags are:
 *                 PHORUM_FLAG_IGNORE_PERMS
 *                     Permission checks are fully bypassed.
 *                 PHORUM_FLAG_GET
 *                     The function will retrieve the file data and return it.
 *                 PHORUM_FLAG_SEND
 *                     The function will retrieve the file data and send it
 *                     to the browser (this flag has precedence over
 *                     PHORUM_FLAG_GET).
 * 
 * @return $file - If the PHORUM_FLAG_SEND flag is used, then the function
 *                 will always return a NULL value. Otherwise the function
 *                 will return a file description array, containing the
 *                 fields "file_id", "username", "file_data", "mime_type",
 *                 "error" and "errno". If the $file parameter was an array,
 *                 then all fields from that array will be included as well.
 *
 *                 If an error occurs, then the fields "error" and "errno"
 *                 will be set to a non NULL value. So when calling this
 *                 function, you will have to check these fields for errors.
 */
function phorum_api_file_retrieve($file, $flags = PHORUM_FLAG_GET)
{
    $PHORUM = $GLOBALS["PHORUM"];

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
        if (!is_array($file)) {
            return array(
                "file_id" => $file_id,
                "errno"   => $file,
                "error"   => "File not found or permission denied"
            );
        }
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

    // Allow modules to handle the file data retrieval.
    $file["result"]    = 0; 
    $file["mime_type"] = NULL;
    $file["file_data"] = NULL;
    $file["error"]     = NULL;
    $file["errno"]     = NULL;
    $file = phorum_hook("file_retrieve", $file, $flags);

    // If a module returned an error or sent the file data
    // to the browser, then we are done.
    if ($file["error"] !== NULL) return $file;
    if ($file["result"] == PHORUM_FLAG_SEND) return NULL; 

    // If no module handled file retrieval, we will retrieve the
    // file from the Phorum database.
    if ($file["file_data"] === NULL)
    {
        $dbfile = phorum_db_file_get($file["file_id"], TRUE);
        if (empty($dbfile)) {
            $file["error"] = "File {$file["file_id"]} could not be " .
                             "retrieved from the database.";
            return $file;
        }

        // Phorum stores the files in base64 format in the database, to
        // prevent problems with upgrading and migrating database servers.
        // representation for the files will always be safe to upgrade
        // and migrate. So here we have to decode the base64 file data.
        $file["file_data"] = base64_decode($dbfile["file_data"]);
    }

    // Set the MIME type information if it was not set by a module.
    if ($file["mime_type"] === NULL) {
        $file["mime_type"] = phorum_api_file_get_mimetype($file["filename"]);
    }

    // In "send" mode, we directly send the file contents to the browser.
    if ($flags & PHORUM_FLAG_SEND)
    {
        // Get rid of any buffered output so far.
        while (ob_get_level()) ob_end_clean();

        // Avoid using any output compression or handling on the sent data.
        ini_set("zlib.output_compression", "0");
        ini_set("output_handler", "");

        header("Content-Type: " . $file["mime_type"]);
        header("Content-Disposition: filename=\"{$file["filename"]}\"");
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

/**
 * Delete a file.
 *
 * @param $file  - This is either an array containing at least the field
 *                 "file_id" or a numerical file_id value.
 */
function phorum_api_file_delete($file)
{
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
    // non existant file. Therefore modules should accept that case
    // as well, without throwing errors.
    phorum_hook("file_delete", $file_id);

    // Delete the file from the Phorum database.
    phorum_db_file_delete($file);
}

// ------------------------------------------------------------------------
// Alias functions (useful shortcut calls to the main file api functions).
// ------------------------------------------------------------------------

/**
 * Check if a file exists.
 * 
 * @param $file_id - The file_id of the file to check.
 * 
 * @return $exists - TRUE in case the file exists or FALSE if it doesn't.
 */
function phorum_api_file_exists($file_id) {
    $file = phorum_api_file_check_read_access($file_id, PHORUM_FLAG_IGNORE_PERMS);
    $exists = empty($file) ? FALSE : TRUE;
    return $exists;
}

/**
 * Send a file to the browser. This is a wrapper function around the 
 * phorum_api_file_retrieve() function.
 *
 * @param $file  - This is either an array containing at least the fields
 *                 "file_id" and "filename" or a numerical file_id value.
 *                 Note that you can use the output of the function
 *                 phorum_api_file_check_read_access() as input for this function.
 * @param $flags - If the PHORUM_FLAG_IGNORE_PERMS flag is used, then 
 *                 permission checks are fully bypassed.
 *
 * @return This function will always return NULL.
 */
function phorum_api_file_send($file, $flags = 0) {
    return phorum_api_file_retrieve($file, $flags | PHORUM_FLAG_SEND);
}

/**
 * Retrieve and return a file. This is a wrapper function around the 
 * phorum_api_file_retrieve() function.
 *
 * @param $file  - This is either an array containing at least the fields
 *                 "file_id" and "filename" or a numerical file_id value.
 *                 Note that you can use the output of the function
 *                 phorum_api_file_check_read_access() as input for this
 *                 function.
 * @param $flags - If the PHORUM_FLAG_IGNORE_PERMS flag is used, then 
 *                 permission checks are fully bypassed.
 *
 * @return See the return value for the phorum_api_file_retrieve() function.
 */
function phorum_api_file_get($file, $flags = 0) {
    return phorum_api_file_retrieve($file, $flags | PHORUM_FLAG_GET);
}


?>
