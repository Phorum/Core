<?php
/*
FUNCTION

    helloworld - the obligatory hello world example

ARGUMENTS

    [who]

        The name of the person to greet.
        By default, "world" will be greeted.

EXAMPLE JSON REQUESTS

    { "call": "helloworld" }

    { "call": "helloworld",
      "who": "John Doe" }

RETURN VALUE

    The string "hello, world" or "hello, [who]".
    Why? Ask that question to Brian Kernighan.
    He thought it was a good idea.

ERRORS

    none

AUTHOR

    Maurice Makaay <maurice@phorum.org>

*/

if (!defined("PHORUM")) return;

$who = phorum_ajax_getarg('who', 'string', 'world');

phorum_ajax_return("hello, $who");

?>
