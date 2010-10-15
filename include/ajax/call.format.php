<?php
// TODO formatting options text and collapsed
/*

CALL

    format - format a message and/or body using the Phorum message
             formatting system

ARGUMENTS

    [subject]

        A subject string to format.

    [body]

        A body text to format.

    [format]

        The type of formatting to apply to the message. This can be
        one of "html" (default), "text" and "collapsed".

EXAMPLE JSON REQUESTS

    { call    : "format",
      body    : "[b]Some text to format[/b]"}

RETURN VALUE

    An object containing the formatted subject and body.
    For above example, this would be:

    { body    : "<b>Some text to format</b>",
      subject : "" }

ERRORS

    The call will return an error if one of the arguments is not in
    the right format.

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (! defined('PHORUM')) return;

require_once PHORUM_PATH.'/include/api/format/messages.php';

// Process the arguments.
$subject = phorum_ajax_getarg('subject', 'string', '');
$body    = phorum_ajax_getarg('body',    'string', '');

// Format the strings.
$data = phorum_api_format_messages(array(1 => array(
    'message_id' => 1,
    'subject'    => $subject,
    'body'       => $body
)));

// Apply the read hook to the messages.
if (isset($PHORUM["hooks"]["read"])) {
    $data = phorum_api_hook("read", $data);
}

// Return the results.
phorum_ajax_return(array(
  'subject' => $data[1]['subject'],
  'body'    => $data[1]['body']
));

?>
