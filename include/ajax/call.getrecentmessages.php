<?php
// TODO collapse and text formatting.
// TODO take care of date and URL formatting (this is currenctly hardcoded
//      in read.php, but it might be better off in the message formatting API.
/*
CALL

    getrecentmessages - retrieve the most recent messages

ARGUMENTS

    <count>

        The number of recent messages to retrieve.

    [forum_id]

        A forum_id for which to retrieve the messages.
        If this parameter is not provided, then the messages will
        be retrieved for all forums for which the user has
        read permission.

    [thread_id]

        A thread_id for which to retrieve the messages.

    [threads_only]

        If this argument contains a true value, then the most
        recent threads will be retrieved. If it contains a false
        value, then the most recent messages will be retrieved.
        The default value is false.

    [format]

        The type of formatting to apply to the message. This can be
        one of "html" (default), "text" and "collapsed".

EXAMPLE JSON REQUESTS

    { call          : "getrecentmessages",
      count         : 10 }

    { call          : "getrecentmessages",
      count         : 5,
      format        : "collapsed",
      threads_only  : "true" }

    { call          : "getrecentmessages",
      count         : 20,
      thread_id     : 1234,
      format"       : "text",
      threads_only" : "true" }

RETURN VALUE

    An array containing the most recent threads or messages is returned,
    where the elements are single message objects.

    A single message object looks like this:

        { message_id : 1234,
          subject    : "The message subject",
          body       : "The message body",
          datestamp  : "21 Feb, 2007 12:01:34",
          etc        : ... }

    The returned messages array looks like this:

        [ { ...message object 1... },
          { ...message object 6... },
          { ...message object 7... },
          { ...message object 9... } ]

ERRORS

    The call will return an error if one of the arguments is not in
    the right format.

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (! defined('PHORUM')) return;

require_once PHORUM_PATH.'/include/api/newflags.php';
require_once PHORUM_PATH.'/include/api/format/messages.php';

// Process the arguments.
$count        = phorum_ajax_getarg('count',        'int>0',   NULL);
$forum_id     = phorum_ajax_getarg('forum_id',     'int',     0);
$thread_id    = phorum_ajax_getarg('thread_id',    'int',     0);
$threads_only = phorum_ajax_getarg('threads_only', 'boolean', 0);
$format       = phorum_ajax_getarg('format',       'string',  'html');

// Retrieve the recent messages.
$recent = $PHORUM['DB']->get_recent_messages(
    $count, 0, $forum_id, $thread_id, $threads_only
);

unset($recent["users"]);

// Add newflag info to the messages.
if ($PHORUM["DATA"]["LOGGEDIN"])
{
    $type = $threads_only
          ? PHORUM_NEWFLAGS_BY_THREAD
          : PHORUM_NEWFLAGS_BY_MESSAGE;
    $recent = phorum_api_newflags_apply_to_messages($recent, $type);
}

// Format the messages.
$recent = phorum_api_format_messages($recent);

// Apply the list hook to the messages.
if (isset($PHORUM["hooks"]["list"])) {
    $recent = phorum_api_hook("list", $recent);
}

// Retrieve information about the forums for the active user.
$allowed_forums = phorum_api_user_check_access(
  PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST
);
$forums = $PHORUM['DB']->get_forums($allowed_forums);
foreach ($forums as $id => $forum) {
  $forums[$id]['url'] = phorum_get_url(PHORUM_LIST_URL, $forum['forum_id']);
}

// Add forum info to the messages and clean up data.
foreach ($recent as $id => $message)
{
  $recent[$id]['foruminfo'] = array(
    'id'   => $message['forum_id'],
    'name' => $forums[$message['forum_id']]['name'],
    'url'  => $forums[$message['forum_id']]['url']
  );

  // Strip fields that the caller should not see in the return data.
  unset($recent[$id]['email']);
  unset($recent[$id]['ip']);
  unset($recent[$id]['meta']);
  unset($recent[$id]['msgid']);
}

// Return the results.
phorum_ajax_return(array_values($recent));

?>
