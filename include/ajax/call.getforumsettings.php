<?php
/*
CALL

    getforumsettings - retrieve the settings data for a single forum

ARGUMENTS

    <forum_id>

        A forum_id for which to retrieve the settings data. For retrieving
        the default forum settings, use forum_id = 0.

EXAMPLE JSON REQUESTS

    Retrieve forum settings for forum 10:

    { call      : "getforumsettings",
      forum_id  : 10 }

    Retrieve the default forum settings:

    { call      : "getforumsettings",
      forum_id  : 0 }

RETURN VALUE

    An object containing the settings data for a forum.

ERRORS

    The call will return an error if no forum exists for the provided
    forum_id or if the active user does not have read access for the
    forum.

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (! defined('PHORUM')) return;

require_once PHORUM_PATH.'/include/api/forums.php';

// Process the arguments.
$forum_id = phorum_ajax_getarg('forum_id', 'int');

// For forum_id = 0, we return the default settings.
if ($forum_id == 0)
{
    phorum_ajax_return($PHORUM['default_forum_options']);
}

// Retrieve and return the forum data. No permission checking is needed.
// The forum_id parameter is checked from common.php already.
$data = phorum_api_forums_get($forum_id);
if (!$data) phorum_ajax_error("Forum $forum_id does not exist");
phorum_ajax_return($data);

?>
