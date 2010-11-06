<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
// Copyright (C) 2010  Phorum Development Team                                //
// http://www.phorum.org                                                      //
//                                                                            //
// This program is free software. You can redistribute it and/or modify       //
// it under the terms of either the current Phorum License (viewable at       //
// phorum.org) or the Phorum License that was distributed with this file      //
//                                                                            //
// This program is distributed in the hope that it will be useful,            //
// but WITHOUT ANY WARRANTY, without even the implied warranty of             //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                       //
//                                                                            //
// You should have received a copy of the Phorum License                      //
// along with this program.                                                   //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if (!defined('PHORUM')) return;

/**
 * This file holds the core parsing and rendering code for doing
 * Phorum BBcode processing. The parsing is split into separate functions,
 * to make it possible to cache parts of the processing steps. The steps
 * that are implemented are:
 *
 * - gathering information about all enabled tags;
 * - turning this tag information into parsing trees and tag information;
 * - splitting a text into bbcode tokens;
 * - rendering bbcode tokens into HTML.
 *
 * The first two steps are combined in the function
 * {@link bbcode_api_initparser()}. This function will rebuild the
 * tag information and parse tree when necessary by calling the
 * {@link bbcode_api_buildparser} function. The third step is
 * implemented by {@link bbcode_api_tokenize()}. The final fourth step
 * is handled by {@link bbcode_api_render()}.
 *
 * @todo Implement a good parsing rule for tags that take the inner content
 *       and put that inside a new HTML tag. Those tags should not allow
 *       nested BBcode tags when the content is used. It is no problem when
 *       a bbcode argument is used, so that should still be allowed. Because
 *       this sounds terribly confusing, here are some examples for the
 *       distinct cases:
 *
 *       [url]http://some.url[tag]nested data[/tag][/url]
 *       For this case, the parser would turn [tag]nested data[/tag] into
 *       <some>html tag</some>. Then, the parser would see:
 *       [url]http://some.url<some>html tag</some>[/url]
 *       When using this content for <a href="...">, this would introduce
 *       HTML code inside the href, possibly opening options for XSS.
 *
 *       [url=http://some.url][tag]nested data[/tag][/url]
 *       This case is okay. The content here is put between <a href> and </a>
 *       in the rendered output. This case should still be valid after
 *       implementing the new parsing rule.
 */

// --------------------------------------------------------------------
// Constants for the BBcode tag descriptions
// --------------------------------------------------------------------

/**
 * BBcode tag description field: a description (HTML format) for the tag,
 * for displaying in the BBcode module settings screen. This can also show an
 * example use case for clarifying the exact use to the admin.
 */
define('BBCODE_INFO_DESCRIPTION', 1);

/**
 * BBcode tag description field: whether there is an editor tool available
 * (supported by the Editor Tools module) for the tag, which can be enabled
 * in the module settings screen.
 */
define('BBCODE_INFO_HASEDITORTOOL', 2);

/**
 * BBcode tag description field: the default setting to use for the tag in
 * the BBcode module settings screen. Possible values to use are:
 * 0) the tag is disabled, 1) the tag is enabled, but not the editor tool
 * for the tag, 2) both the tag and its editor tool are enabled.
 */
define('BBCODE_INFO_DEFAULTSTATE', 3);

/**
 * BBcode tag description field: an array of allowed arguments.
 * The keys in this array are the arguments that are available.
 * The values are the default values to use for the arguments.
 * If a tag is implemented that allows for assigning a value to the
 * tag name, then this tag name needs to be added to the argument array.
 * For example the [url] tag, which can look like [url=http://www.example.com]
 * needs the argument array ('url' => '') to allow for the URL assignment.
 */
define('BBCODE_INFO_ARGS', 4);

/**
 * BBcode tag description field: an array of arguments that must be replaced
 * in the static replace tags (BBCODE_INFO_REPLACEOPEN and
 * BBCODE_INFO_REPLACECLOSE). In these tags, a string like %argname% can
 * be used to define where the tag should go. The argument value will be
 * HTML escaped before putting it in the tag, so it cannot be abused for XSS.
 */
define('BBCODE_INFO_REPLACEARGS', 5);

/**
 * BBcode tag description field: for simple tags that only need a static
 * replacement, a replacement string for the opening tag can be provided
 * through this field. For example the [b] tag that uses the static
 * HTML open tag "<b>".
 */
define('BBCODE_INFO_REPLACEOPEN', 6);

/**
 * BBcode tag description field: for simple tags that only need a static
 * replacement, a replacement string for the closing tag can be provided
 * through this field. For example the [b] tag that uses the static
 * HTML close tag "</b>".
 */
define('BBCODE_INFO_REPLACECLOSE', 7);

/**
 * BBcode tag description field: for complex tags that need argument parsing
 * or other processing logic, a callback function can be defined. This
 * callback function will be called when the close tag is reached. The callback
 * function will get two arguments: the content that is contained by the
 * tag and the tag information array. The function will have to return the
 * rendered output for the tag.
 */
define('BBCODE_INFO_CALLBACK', 8);

/**
 * BBcod tag description field: whether the tag is a tag that only needs
 * an open tag. For example the [hr] tag, which does not use a closing [/hr].
 */
define('BBCODE_INFO_OPENONLY', 9);

/**
 * BBcode tag description field: whether the tag is a tag that does not use
 * a real argument=value style argment, but for which the full argument part
 * of the tag is used as an argument value for the main tag. This is for
 * example used for the [quote] tag to make both [quote=JohnDoe] and
 * [quote JohnDoe] behave the same way. Note that the value will be stored
 * in the "array" argument field, so you will need to setup the field
 * {@link BBCODE_INFO_ARGS} as an array ('quote' => '') to make it work.
 */
define('BBCODE_INFO_VALUEONLY', 10);

/**
 * BBcode tag description field: whether to strip the first break after
 * the closing tag. For Phorum this means stripping the <phorum break>
 * that is used internally for preserving line breaks.
 */
define('BBCODE_INFO_STRIPBREAK', 11);

/**
 * BBcode tag description field: an array of parent containers for the tag.
 * This will make sure that the tag is always a direct child of one of the
 * provided container tags. This is for example used for formatting lists,
 * where the item [*] tag should always be inside a [list] parent. For this
 * setup, the [*] tag needs the parent array ('list'). If one of the parent
 * tags is enabled in the configuration, the child tag automatically is
 * enabled as well. Note that this makes adding the child tag to the module's
 * settings screen for enabling/disabling futile.
 */
define('BBCODE_INFO_PARENTS', 12);

/**
 * BBcode tag description field: the name of the tag. If the tag [foo] has
 * to be implemented, the value of this field would be "foo". This field
 * is autogenerated by the parse tree preparation code in
 * {@link bbcode_api_buildparser()}.
 */
define('BBCODE_INFO_TAG', 13);

/**
 * BBcode tag description field: this one is used internally by the parser.
 * It is used to parse tag arguments. It is autogenerated by the parse
 * tree preparation code in {@link bbcode_api_buildparser()}.
 */
define('BBCODE_INFO_ARGPARSETREE', 14);

// --------------------------------------------------------------------
// Functions
// --------------------------------------------------------------------

/**
 * Build a list of all available BBcode tags.
 *
 * This will setup a list of all standard BBcode tags that are supported
 * and all tags that are added by other modules that implement them through
 * this module's "bbcode_register" hook.
 *
 * @return array
 *     An array of bbcode tag information arrays, ready for use
 *     by the {@link bbcode_api_buildparser()} function.
 */
function bbcode_api_initparser($force = FALSE)
{
    global $PHORUM;

    /**
     * [hook]
     *     bbcode_register
     *
     * [description]
     *     This hook is implemented by the BBcode module in the file
     *     <literal>mods/bbcode/api.php</literal>. It allows modules to
     *     provide extra or override existing BBcode tag descriptions.<sbr/>
     *     <sbr/>
     *     <b>Warning:</b> do not delete tags from the list, e.g. removing
     *     a tag based on the login status for a user. That would throw off
     *     and invalidate the caching mechanisms. If you need to have some tag
     *     act differently for different users, then override the behavior for
     *     the tag using a callback function and implement the logic in the
     *     callback function.
     *
     * [category]
     *     Module hooks
     *
     * [when]
     *     This hook is called from the function
     *     <literal>bbcode_api_initparser()</literal> in the BBcode
     *     module file <literal>mods/bbcode/api.php</literal>.
     *
     * [input]
     *     An array of tag description arrays.
     *     The keys in this array are tag names. The values are arrays
     *     describing the tags. For examples of what these tag descriptions
     *     look like, please take a look at the file
     *     <literal>mods/bbcode/builtin_tags.php</literal>.
     *
     * [output]
     *     The same array as the one that was used for the hook call
     *     arguments, possibly updated with new or updated tags.
     */
    $tags = array();
    if (isset($PHORUM['hooks']['bbcode_register'])) {
        $tags = phorum_hook('bbcode_register', $tags);
    }

    // Build a cache key for the current state of the tag list
    // and module settings. Include the file modification time for some
    // of the module files to let changes in the module code force a
    // parsing info update.
    $cachekey = md5(
        filemtime(__FILE__) .
        filemtime(dirname(__FILE__) . '/builtin_tags.php') .
        serialize(isset($PHORUM['mod_bbcode'])?$PHORUM['mod_bbcode']:time()) .
        serialize($tags)
    );

    // If no cached parsing data is available for the current state of the
    // tag list, then rebuild this data and store it in the database.
    if ($force ||
        !isset($PHORUM['mod_bbcode_parser']['cachekey']) ||
        $PHORUM['mod_bbcode_parser']['cachekey'] != $cachekey)
    {
        // First, build a full list of tags by merging the builtin tags
        // with the ones that the modules provided.
        require_once('./mods/bbcode/builtin_tags.php');
        $combinedtags = $GLOBALS['PHORUM']['MOD_BBCODE']['BUILTIN'];
        foreach ($tags as $tagname => $tag) {
            $combinedtags[$tagname] = $tag;
        }

        // Build the parser information.
        list ($taginfo, $parsetree) = bbcode_api_buildparser($combinedtags);

        // Store the parser information in the database.
        $PHORUM['mod_bbcode_parser'] = array(
            'cachekey'  => $cachekey,
            'taginfo'   => $taginfo,
            'parsetree' => $parsetree
        );
        phorum_db_update_settings(array(
            'mod_bbcode_parser' => $PHORUM['mod_bbcode_parser']
        ));
    }
}

/**
 * Process tags and prepare them for efficient use by the parser function.
 *
 * @return array
 *     An array containing two elements. The first element is an array
 *     containing all detail information about the available tags,
 *     indexed by tag name. The second element is a parsetree, that is
 *     used by the {@link bbcode_api_tokenize()} function for parsing
 *     tags and their arguments.
 */
function bbcode_api_buildparser($tags)
{
    global $PHORUM;

    // The configured list of activated BBcode tags. For missing configuration
    // settings, the default settings as defined in the builtin_tags.php
    // script will be used.
    $enabled = isset($PHORUM['mod_bbcode']['enabled'])
        ? $PHORUM['mod_bbcode']['enabled'] : array();

    // Prepare the tag information and parsing tree.
    $taginfo = array();
    $parsetree = array('/' => array());
    foreach ($tags as $tagname => $tag)
    {
        // Check for tags that should be enabled, because their container
        // parent is enabled.
        if (!empty($tag[BBCODE_INFO_PARENTS])) {
            $enabled[$tagname] = FALSE;
            foreach ($tag[BBCODE_INFO_PARENTS] as $parent) {
                if (isset($enabled[$parent]) && $enabled[$parent]) {
                    $enabled[$tagname] = TRUE;
                    break;
                }
            }
        }

        // Skip disabled tags.
        if ((isset($enabled[$tagname]) && !$enabled[$tagname]) ||
            (!isset($enabled[$tagname]) && !$tag[BBCODE_INFO_DEFAULTSTATE])) {
            continue;
        }

        // Set default values for missing tag information fields.
        if (!isset($tag[BBCODE_INFO_OPENONLY])) {
            $tag[BBCODE_INFO_OPENONLY] = FALSE;
        }
        if (!isset($tag[BBCODE_INFO_PARENTS])) {
            $tag[BBCODE_INFO_PARENTS] = NULL;
        }
        if (!isset($tag[BBCODE_INFO_REPLACEOPEN])) {
            $tag[BBCODE_INFO_REPLACEOPEN] = '';
        }
        if (!isset($tag[BBCODE_INFO_REPLACECLOSE])) {
            $tag[BBCODE_INFO_REPLACECLOSE] = '';
        }
        if (!isset($tag[BBCODE_INFO_CALLBACK])) {
            $tag[BBCODE_INFO_CALLBACK] = NULL;
        }
        if (!isset($tag[BBCODE_INFO_VALUEONLY])) {
            $tag[BBCODE_INFO_VALUEONLY] = FALSE;
        }
        if (!isset($tag[BBCODE_INFO_REPLACEARGS])) {
            $tag[BBCODE_INFO_REPLACEARGS] = NULL;
        }
        if (!isset($tag[BBCODE_INFO_HASEDITORTOOL])) {
            $tag[BBCODE_INFO_HASEDITORTOOL] = FALSE;
        }
        if (!isset($tag[BBCODE_INFO_STRIPBREAK])) {
            $tag[BBCODE_INFO_STRIPBREAK] = FALSE;
        }

        // Remove fields that are not needed by the parser or module.
        // These are only used by the module's settings screen.
        unset($tag[BBCODE_INFO_DESCRIPTION]);
        unset($tag[BBCODE_INFO_DEFAULTSTATE]);

        // Add fields that are static.
        $tag[BBCODE_INFO_TAG] = $tagname;

        // Add the tag arguments to the argument parse tree for this tag.
        $argparsetree = NULL;
        if (isset($tag[BBCODE_INFO_ARGS]))
        {
            $argparsetree = array();
            foreach ($tag[BBCODE_INFO_ARGS] as $argname => $default) {
                $node =& $argparsetree;
                $arglen = strlen($argname);
                for ($i=0; $i<$arglen; $i++)
                {
                    $l = $argname[$i];
                    if (!isset($node[$l]))
                    {
                        $node[$l] = array();
                    }
                    $node =& $node[$l];
                }
                $node['arg'] = TRUE;
            }
        }
        $tag[BBCODE_INFO_ARGPARSETREE] = $argparsetree;

        // Add the tag name to the tag name parse tree.
        $node =& $parsetree;
        $closenode =& $parsetree['/'];
        $taglen = strlen($tagname);
        for ($i=0; $i<$taglen; $i++)
        {
            $l = $tagname[$i];
            if (!isset($node[$l]))
            {
                $node[$l] = array();
                // Do not add tag closing data for tags that can only have
                // an opening node.
                if (empty($tag[BBCODE_INFO_OPENONLY])) {
                    $closenode[$l] = array();
                }
            }
            $node =& $node[$l];
            // Do not add tag closing data for tags that can only have
            // an opening node.
            if (empty($tag[BBCODE_INFO_OPENONLY])) {
                $closenode =& $closenode[$l];
            }
        }

        // Add the tag to the information array.
        $taginfo[$tagname] = $tag;

        // We add tag token templates as the end points in the tree.
        // These will be used by the tokenizer as the base token that is
        // pushed onto the token array. The tokenizer can update data for
        // this template (i.e. tag arguments and token stack level).

        // Add the open tag token template to the parse tree.
        $node['tag'] = array(
            $tag[BBCODE_INFO_TAG],
            FALSE
        );
        // If the tag takes arguments, add them to the token template as well.
        if (isset($tag[BBCODE_INFO_ARGS])) {
            $node['tag'][] = $tag[BBCODE_INFO_ARGS];
        }

        // For tags that require a close tag, add a closing tag
        // token template to the parse tree.
        if (empty($tag[BBCODE_INFO_OPENONLY])) {
            $closenode['tag'] = array(
                $tag[BBCODE_INFO_TAG],
                TRUE
            );
        }
    }

    return array($taginfo, $parsetree);
}

/**
 * Tokenize the provided text into BBcode tokens.
 *
 * The data that is returned by this function can be processed by the
 * {@link bbcode_api_render()} function to render the final HTML code.
 *
 * @param string $text
 *     The text that has to be parsed. This *must* be a HTML escaped text.
 *     The parser code does not perform any HTML escaping on its own.
 *     This requirement is based on Phorum's defensive nature of fully
 *     HTML escaping the body text before doing any processing on it. This
 *     way, processing code will have to consciously HTML unescape body text
 *     that must be shown as plain HTML code.
 *
 * @return array
 *     An array of tokens. Each item can either be a string, in which a
 *     text token was processed, or an array describing a tag. The tag
 *     description is a tag info array as used internally by the BBcode
 *     code, enriched with an extra field "is_closetag" which tells
 *     whether the opening or close tag is being handled.
 */
function bbcode_api_tokenize($text)
{
    global $PHORUM;

    // Initialize the variables that are used during tokenizing.
    $cursor = 0;
    $maxpos = strlen($text) - 1;
    $state  = 1;
    $is_closetag = 0;
    $current_tag = NULL;
    $current_tagname = NULL;
    $current_arg = NULL;
    $current_val = NULL;
    $current_token = NULL;
    $stack = array();
    $stackidx = 0;
    $opentags = array();
    $autoclosed = array();
    $tokens = array();
    $tokenidx = 0;
    $text_start = 0;
    $text_end = 0;

    $taginfo = $PHORUM['mod_bbcode_parser']['taginfo'];

    // The big outer loop. This one lets the parser run over the
    // full text that has to be parsed.
    for (;;)
    {
        // Leave this loop if we are at the end of the text.
        if ($cursor > $maxpos) break;

        // ------------------------------------------------------------------
        // 1: find the tag starting character "[" in the text
        // ------------------------------------------------------------------

        if ($state == 1)
        {
            // Find the length of the chunk up to the first "[" character.
            $pos = strpos($text, "[", $cursor);
            if ($pos === FALSE) break;

            // Handle BBcode tag escaping if this feature is enabled.
            // Check if the "[" character is escaped by prepending it with
            // a backslash. If it is, then we do not process the bbcode tag.
            // Instead we hide the backslash and continue searching for
            // the next "[" character.
            if (!empty($PHORUM['mod_bbcode']['enable_bbcode_escape']) &&
                $pos > 0 && $text[$pos-1] == '\\')
            {

                $tokens[++$tokenidx] = array(
                    'TEXTNODE', $text_start, $pos - 1 - $text_start
                );
                $text_start = $pos;
                $cursor     = $pos+1;
                $text_end   = $cursor;
                continue;
            }

            // Move on to the possible tagname.
            // +1 is for skipping the "[" character that we found.
            $chunklen = $pos - $cursor;
            $cursor += $chunklen + 1;
            if ($cursor > $maxpos) break;
            $state = 2;

            // Update the pointer to the end of the active text node.
            // Here we move it to the position before the "[" character,
            // because if we are at an actual tag right now, we do not want
            // to include data from the tag into the text node.
            $text_end = $cursor - 1;
        }

        // ------------------------------------------------------------------
        // 2: check for a valid tag name after the "[" char
        // ------------------------------------------------------------------

        if ($state == 2)
        {
            // check if we can find a valid tag name, by walking the tag
            // name parse tree.
            $node = $PHORUM['mod_bbcode_parser']['parsetree'];
            $current_tag = NULL;
            for (;;)
            {
                if ($cursor > $maxpos) break 2;
                $l = strtolower($text[$cursor++]);

                // As long as we find matching nodes in the parse tree,
                // we keep walking it.
                if (isset($node[$l])) {
                    $node = $node[$l];
                }
                // When we hit the end of the tree, we check if there is
                // a separator character after the possible tag name.
                // If that is the case and we can find the token template for
                // a tag in the parse tree, then we go on with the tag that
                // we found. Otherwise, we go back to searching a new tag
                // opening character.
                else
                {
                    // Did we find a tag?
                    if (!isset($node['tag'])) { $state = 1; continue 2; }
                    $is_closetag = !empty($node['tag'][1]); // 1 = close tag?

                    // Find the current character that our cursor is on.
                    $l = $text[--$cursor];

                    // For close tags, we need a closing square bracket here.
                    if ($is_closetag && $l != ']') { $state = 1; continue 2; }

                    // For open tags, we need a space, equal sign or
                    // closing square bracket here.
                    if ($l != ' ' && $l != '=' && $l != ']') {
                        $state = 1; continue 2;
                    }

                    // Checks passed. This might just be a valid tag!
                    $current_tagname = $node['tag'][0]; // 0 = tag name
                    $current_tag = $taginfo[$current_tagname];
                    $current_token = $node['tag'];
                    break;
                }
            }

            // Check if we're not handling an open tag for a tag type that
            // needs to be child of a specific parent tag type, but for which
            // there is no open parent available.
            if (!$is_closetag &&
                !empty($current_tag[BBCODE_INFO_PARENTS]))
            {
                $found_parent = FALSE;
                foreach ($current_tag[BBCODE_INFO_PARENTS] as $p) {
                    if (!empty($opentags[$p])) {
                        $found_parent = TRUE;
                        break;
                    }
                }
                if (!$found_parent) {
                    $state = 1;
                    continue;
                }
            }

            // Check if we're handling a closing tag for a tag that
            // we autoclosed or did not open before. If so, then we
            // want to skip this tag totally.
            if ($is_closetag && (
                !empty($autoclosed[$current_tagname]) ||
                 empty($opentags[$current_tagname])))
            {
                // If there is text in the text node building up to
                // the tag that we are skipping, then add this to
                // the parsed data. The skipped tag string is not
                // included this way.
                if ($text_end != $text_start)
                {
                    $tokens[++$tokenidx] = array(
                        'TEXTNODE', $text_start, $text_end - $text_start
                    );
                }

                // One automatically closed tag is accounted for now.
                if (!empty($autoclosed[$current_tagname])) {
                    $autoclosed[$current_tagname]--;
                }
                // Stale close tag. We include the stale tag string
                // in the current text node.
                else
                {
                    // Create a new textnode or add to the existing one.
                    if ($tokenidx && $tokens[$tokenidx][0] == 'TEXTNODE') {
                        $tokens[$tokenidx][2] += ($cursor - $text_end + 1);
                    } else {
                        $tokens[++$tokenidx] = array(
                            'TEXTNODE', $text_start, $cursor - $text_end + 1
                        );
                    }
                }

                // Continue searching for a new tag, right after the close tag.
                $text_start = ++$cursor;
                $state = 1;
                continue;
            }

            // All checks were okay. We can process the tag. If the next
            // character in the text is an equal sign, then we are looking
            // at a possible [tag=value] tag, where a value is assigned to
            // the tag directly. For value only tags, a space is allowed as
            // well for this ([tag value]).
            if ($text[$cursor] == '=' ||
                ($text[$cursor] == ' ' && $current_tag[BBCODE_INFO_VALUEONLY]))
            {
                $cursor++;
                if ($cursor > $maxpos) break;
                // To accept values that are assigned to the tag name, the
                // argument list definition must contain the name of the tag
                // itself as a possible argument.
                if (isset($current_tag[BBCODE_INFO_ARGS][$current_tagname])) {
                    // Switch to argument value parsing.
                    $current_arg = $current_tagname;
                    $state = 4;
                } else {
                    $state = 1;
                    continue;
                }
            }
            else
            {
                // The start of a tag was found. Continue looking
                // for the closing character or tag arguments.
                $state = 3;
            }
        }

        // ------------------------------------------------------------------
        // 3: find the end of tag char "]" or a new tag argument
        // ------------------------------------------------------------------

        if ($state == 3)
        {
            // Handle closing of a tag.
            if ($text[$cursor] == ']')
            {
                // If there is text in the text node building up to the
                // tag that we just ended, then add this to the parsed data.
                if ($text_end != $text_start)
                {
                    $tokens[++$tokenidx] = array(
                        'TEXTNODE', $text_start, $text_end - $text_start
                    );
                }

                // Handle closing tags.
                if ($current_token[1]) // 1 = is close tag
                {
                    $opentags[$current_tagname]--;

                    // To assure proper tag nesting, we make use of our
                    // tag stack to find the accompanying open tag for
                    // the current close tag. If we find that there are
                    // open tags before the accompanying open tag for our
                    // current tag, then we implicitly close those tags.
                    while ($stackidx > 0)
                    {
                        $toptoken = $stack[$stackidx--];
                        $topname = $toptoken[0]; // 0 = tag name

                        // Keep track if the current top tag matches the
                        // currently processed close tag.
                        $found_matching_open_tag = FALSE;
                        if ($topname == $current_tag[BBCODE_INFO_TAG]) {
                            $found_matching_open_tag = TRUE;
                        }
                        // The current top tag does not match the currently
                        // processed close tag. We'll close the top tag and
                        // flag it as autoclosed.
                        else
                        {
                            $opentags[$topname]--;

                            $autoclosed[$topname] =
                                isset($autoclosed[$topname])
                                ? $autoclosed[$topname] + 1 : 1;
                        }

                        // Add the close tag to the parsed data.
                        $toptoken[1] = TRUE; // 1 = is close tag
                        $tokens[++$tokenidx] = $toptoken;

                        if ($found_matching_open_tag) break;
                    }

                    // Strip trailing break after the close tag, if the tag
                    // is configured to do so.
                    if ($current_tag[BBCODE_INFO_STRIPBREAK])
                    {
                        // First, skip any white space character that we find.
                        $peekcursor = $cursor + 1;
                        while (isset($text[$peekcursor]) &&
                               ($text[$peekcursor] == " " ||
                                $text[$peekcursor] == "\n" ||
                                $text[$peekcursor] == "\r")) {
                            $peekcursor++;
                        }

                        // Check for a Phorum break and strip if we find one.
                        if (isset($text[$peekcursor]) &&
                            substr($text,$peekcursor,14) == '<phorum break>') {
                            $cursor = $peekcursor + 13;
                        }
                    }
                }
                // Handle opening tags.
                else
                {
                    // Take care of parent constraints.
                    if (!empty($current_tag[BBCODE_INFO_PARENTS]))
                    {
                        while (!in_array(
                            $stack[$stackidx][0], // 0 = tag name
                            $current_tag[BBCODE_INFO_PARENTS]
                        )) {
                            $token = $stack[$stackidx--];

                            $opentags[$token[0]]--; // 0 = tag name
                            $autoclosed[$token[0]] =
                                isset($autoclosed[$token[0]])
                                ? $autoclosed[$token[0]] + 1 : 1;

                            // Add the tag close to the parsed data.
                            $token[1] = TRUE; // 1 = is close tag
                            $tokens[++$tokenidx] = $token;
                        }
                    }

                    $opentags[$current_tagname] =
                    isset($opentags[$current_tagname]) ?
                    $opentags[$current_tagname] + 1 : 1;
                    $stack[++$stackidx] = $current_token;
                    if (!empty($autoclosed[$current_tagname])) {
                        $autoclosed[$current_tagname]--;
                    }

                    $tokens[++$tokenidx] = $current_token;

                    // Strip trailing break after the open tag, if the tag
                    // is configured to do so and it is open only.
                    if ($current_tag[BBCODE_INFO_STRIPBREAK] &&
                        $current_tag[BBCODE_INFO_OPENONLY]) {

                        // First, skip any white space character that we find.
                        $peekcursor = $cursor + 1;
                        while (isset($text[$peekcursor]) &&
                               ($text[$peekcursor] == " " ||
                                $text[$peekcursor] == "\n" ||
                                $text[$peekcursor] == "\r")) {
                            $peekcursor++;
                        }

                        // Check for a Phorum break and strip if we find one.
                        if (isset($text[$peekcursor]) &&
                            substr($text,$peekcursor,14) == '<phorum break>') {
                            $cursor = $peekcursor + 13;
                        }
                    }

                }

                $cursor++;
                $text_start = $text_end = $cursor;
                if ($cursor > $maxpos) break;

                $state = 1;
                continue;
            }

            // If the current tag does not take arguments, then it is
            // apparently wrong and we can continue searching for the next tag.
            // We can also continue if there is no space, indicating the start
            // for a new argument.
            elseif ($text[$cursor] != ' ' ||
                    empty($current_tag[BBCODE_INFO_ARGPARSETREE])) {
                $state = 1;
                continue;
            }

            // Skip multiple spaces.
            while (isset($text[$cursor]) && $text[$cursor] == ' ') $cursor++;
            if ($cursor > $maxpos) break;

            // If we ended up at the end of the bbcode tag by now, then
            // restart parsing state 3 to handle this.
            if ($text[$cursor] == ']') {
                $state = 3;
                continue;
            }

            // Check if we can find a valid argument.
            $node = $current_tag[BBCODE_INFO_ARGPARSETREE];
            $current_arg = '';
            for (;;)
            {
                if ($cursor > $maxpos) break 2;
                $l = strtolower($text[$cursor++]);

                // Walk the argument parse tree, until we cannot find
                // a matching character anymore.
                if (isset($node[$l])) {
                    $current_arg .= $l;
                    $node = $node[$l];
                    continue;
                }

                // The arguments must be followed by one of " ", "=" or "]".
                // Also check if we really found the end of an argument here.
                if (($l != ' ' && $l != '=' && $l != ']') ||
                    !isset($node['arg'])) {
                    $state = 1;
                    break 2;
                }

                // Argument found.
                $cursor--;
                break;
            }

            // Check if there is a value assignment for the argument.
            // If not, then we asume it's a boolean flag, which we
            // set to TRUE.
            if ($text[$cursor] == ' ' || $text[$cursor] == ']')
            {
                if ($text[$cursor] == ' ') $cursor++;
                $current_token[2][$current_arg] = TRUE; // 2 = args
                $state = 3;
                continue;
            }
            // There is an equal sign after the argument (checked in
            // earlier code). Read in the argument value.
            else
            {
                $cursor++;
                if ($cursor > $maxpos) break;
                $state = 4;
            }
        }

        // ------------------------------------------------------------------
        // 4: parse a value for a tag argument
        // ------------------------------------------------------------------

        if ($state == 4)
        {
            $current_val = '';

            // Handle &quot; style quotes.
            $forcequote = NULL;
            if ($text[$cursor] == '&') {
                if (substr($text, $cursor, 6) == '&quot;') {
                    $forcequote = '&';
                    $cursor += 5;
                    if ($cursor > $maxpos) break;
                }
            }

            // Handle quoted values.
            if ($forcequote !== NULL ||
                $text[$cursor] == '"' ||
                $text[$cursor] == "'")
            {
                $quote = $forcequote === NULL ? $text[$cursor] : $forcequote;
                $mask = '\\'.$quote;
                $cursor++;
                if ($cursor > $maxpos) break;

                for (;;)
                {
                    $chunklen = strcspn($text, $mask, $cursor);
                    if ($chunklen > 0) {
                        $current_val .= substr($text, $cursor, $chunklen);
                        $cursor += $chunklen;
                    }

                    // Leave the main loop if we are at the end of the text.
                    // We can look ahead one character here. If there are no
                    // more characters after the one we found using the $mask,
                    // then this can in no way become a valid
                    // tag argument value.
                    if (($cursor + 1) > $maxpos) break 2;

                    // Handle escaped characters.
                    if ($text[$cursor] == '\\')
                    {
                        $cursor++;
                        $current_val .= $text[$cursor];
                        $cursor++;
                        if ($cursor > $maxpos) break 2;
                    }
                    // Handle end of quoted arguments.
                    else
                    {
                        // Handle &quot; style quotes.
                        if ($quote == '&') {
                            if (substr($text, $cursor, 6) == '&quot;') {
                                $cursor += 5;
                            } else {
                                $current_val .= $quote;
                                $cursor++;
                                continue;
                            }
                        }

                        // Add the argument to the token arguments.
                        $current_token[2][$current_arg] = $current_val; // 2 = args

                        $cursor++;
                        $state = 3;
                        break;
                    }
                }
                continue;
            }
            // Handle unquoted values.
            else
            {
                // Value only tag arguments run till the closing ] character.
                if ($current_tag[BBCODE_INFO_VALUEONLY]) {
                    $pos = strpos($text, "]", $cursor);
                    if ($pos === FALSE) break;
                    $chunklen = $pos - $cursor;
                } else {
                    // Unquoted arguments end at " " or "]".
                    $chunklen = strcspn($text, " ]", $cursor);
                }
                $current_val = substr($text, $cursor, $chunklen);

                // Add the argument to the token arguments.
                $current_token[2][$current_arg] = $current_val; // 2 = args

                $cursor += $chunklen;
                if ($cursor > $maxpos) break;

                $state = 3;
                continue;
            }
        }
    }

    // Add trailing text node to the parsed data.
    $text_end = $maxpos + 1;
    if ($text_start != $text_end) {
        $tokens[++$tokenidx] = array(
            'TEXTNODE', $text_start, $text_end - $text_start
        );
    }

    // Close tags that weren't explicitly closed.
    while ($stackidx > 0)
    {
        $token = $stack[$stackidx--];

        $opentags[$token[0]]--; // 0 = tag name
        $autoclosed[$token[0]] =
            isset($autoclosed[$token[0]])
            ? $autoclosed[$token[0]] + 1 : 1;

        // Add the tag close to the parsed data.
        $token[1] = TRUE; // 1 = is close tag
        $tokens[++$tokenidx] = $token;
    }

    return $tokens;
}

/**
 * Render the tokens that are returned from {@link bbcode_api_tokenize()}
 * into HTML code.
 *
 * @param string $text
 *     The text that was parsed by {@link bbcode_api_tokenize()}.
 *
 * @param array $tokens
 *     The tokens as returned by the {@link bbcode_api_tokenize()} function.
 *
 * @param array $message
 *     The message that is being parsed. This message is passed on to
 *     tag handling callback functions (BBCODE_INFO_CALLBACK), to provide
 *     context information to the callback.
 *     This argument is treated as a reference argument, making it possible
 *     for the tag callback to do the same. This can be useful for things
 *     like passing information for a later hook (e.g. format_fixup).
 *
 * @return string
 *     The rendered HTML code.
 */
function bbcode_api_render($text, $tokens, &$message)
{
    global $PHORUM;

    $buffers = array(0 => '');
    $bufferidx = 0;
    $taginfo = $PHORUM['mod_bbcode_parser']['taginfo'];
    foreach ($tokens as $token)
    {
        // Add a standard text node.
        if ($token[0] == 'TEXTNODE')
        {
            $buffers[$bufferidx] .= substr($text, $token[1], $token[2]);
        }
        // Add a bbcode tag node.
        else
        {
            // Retrieve the configuration for this token's tag.
            $tag = $taginfo[$token[0]]; // 0 = tag name

            // Handle closing tag.
            if ($token[1]) // 1 = is close tag
            {
                // A callback function is defined for this tag. Now we are
                // at the closing tag, we call this callback function.
                // We provide the content within this tag and the arguments
                // that were used for the tag as the call arguments.
                if (isset($tag[BBCODE_INFO_CALLBACK]))
                {
                    $buffers[$bufferidx-1] .= call_user_func_array(
                        $tag[BBCODE_INFO_CALLBACK],
                        array(
                            $buffers[$bufferidx],
                            isset($token[2]) ? $token[2] : NULL, // 2 = args
                            &$message
                        )
                    );
                    unset($buffers[$bufferidx]);
                    $bufferidx--;
                }
                // This tag has a defined close tag string in its config.
                elseif ($tag[BBCODE_INFO_REPLACECLOSE] !== NULL)
                {
                    // Run argument replacement on the close tag string.
                    if (!empty($tag[BBCODE_INFO_REPLACEARGS]))
                    {
                        foreach ($tag[BBCODE_INFO_REPLACEARGS] as $key) {
                            $tag[BBCODE_INFO_REPLACECLOSE] = str_replace(
                                '%'.$key.'%',
                                htmlspecialchars(
                                    $token[2][$key], // 2 = args
                                    ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]
                                ),
                                $tag[BBCODE_INFO_REPLACECLOSE]
                            );
                        }
                    }
                    $buffers[$bufferidx] .= $tag[BBCODE_INFO_REPLACECLOSE];
                }
                else
                {
                    $buffers[$bufferidx] .=
                        "{No close tag definition for " . $token[0] . "}";
                }
            }
            // Tag open
            else
            {
                // Callbacks are always run at the close tag. We setup a new
                // buffer for this tag's contents, so we can hand the full
                // content over to the tag handling function when we get
                // to the closing tag.
                if (isset($tag[BBCODE_INFO_CALLBACK]))
                {
                    $bufferidx++;
                    $buffers[$bufferidx] = '';
                }
                // This tag has a defined open tag string in its config.
                elseif ($tag[BBCODE_INFO_REPLACEOPEN] !== NULL)
                {
                    // Run argument replacement on the open tag string.
                    if (!empty($tag[BBCODE_INFO_REPLACEARGS]))
                    {
                        foreach ($tag[BBCODE_INFO_REPLACEARGS] as $key) {
                            $tag[BBCODE_INFO_REPLACEOPEN] = str_replace(
                                '%'.$key.'%',
                                htmlspecialchars(
                                    $token[2][$key], // 2 = args
                                    ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]
                                ),
                                $tag[BBCODE_INFO_REPLACEOPEN]
                            );
                        }
                    }
                    $buffers[$bufferidx] .= $tag[BBCODE_INFO_REPLACEOPEN];
                }
                else
                {
                    $buffers[$bufferidx] .=
                        "{No open tag definition for " . $token[0] . "}";
                }
            }
        }
    }

    return $buffers[0];
}

// --------------------------------------------------------------------
// Tag handler functions
// --------------------------------------------------------------------

function bbcode_email_handler($content, $args, $message)
{
    if ($args['email'] == '') {
        if (strpos($content, '<') !== FALSE ||
            strpos($content, '"') !== FALSE ||
            strpos($content, '>') !== FALSE)
            $content = preg_replace('/[<">].*[<">]/', '', $content);
        $args['email'] = $content;
    }

    $append = '';
    if ($args['subject'] != '')
    {
        // Decode the HTML entities in the subject.
        // Use a fallback function for PHP versions prior to 5.1.0.
        if (function_exists('htmlspecialchars_decode')) {
            $subject = htmlspecialchars_decode($args['subject']);
        } else {
            $subject = strtr(
                $args['subject'],
                array_flip(get_html_translation_table(HTML_SPECIALCHARS))
            );
        }

        // Recode it using urlencoding, so we can put it in the URL.
        $append = '?subject='.rawurlencode($subject);
    }

    // Obfuscate against mail address harvesting by spammers.
    $email   = bbcode_html_encode($args['email']);
    $content = bbcode_html_encode($content);

    return "<a href=\"mailto:$email$append\">$content</a>";
}

function bbcode_html_encode($string)
{
    $ret_string = "";
    $len = strlen( $string );
    for( $x = 0;$x < $len;$x++ ) {
        $ord = ord( $string[$x] );
        $ret_string .= "&#$ord;";
    }
    return $ret_string;
}

function bbcode_img_handler($content, $args, $message)
{
    if ($args['img'] == '') {
        if (strpos($content, '<') !== FALSE ||
            strpos($content, '"') !== FALSE ||
            strpos($content, '>') !== FALSE)
            $content = preg_replace('/[<">].*[<">]/', '', $content);
        $args['img'] = $content;
    }
    if (!preg_match('!^\w+://!', $args['img'])) {
        $args['img'] = 'http://'.$args['img'];
    }

    $append = '';
    if ($args['size'] != '') {
        if (strstr($args['size'], 'x'))
        {
            list ($w,$h) = explode('x', $args['size']);
            settype($w, 'int');
            settype($h, 'int');
            $append = "width=\"$w\" height=\"$h\"";
        }
        else
        {
            settype($args['size'], 'int');
            $append = 'width="'.$args['size'].'"';
        }
    }

    return "<img src=\"{$args['img']}\" class=\"bbcode\" border=\"0\" $append/>";
}

function bbcode_url_handler($content, $args, $message)
{
    global $PHORUM;

    // Setup special URL options.
    static $extratags = NULL;
    static $show_full_urls = FALSE;
    
    $settings = $PHORUM['mod_bbcode'];
    if ($extratags === NULL)
    {
        $extratags = '';
        if (!empty($settings['links_in_new_window'])) {
            $extratags .= 'target="_blank" ';
        }

        if (!empty($settings['show_full_urls'])) {
            $show_full_urls = TRUE;
        }
    }

    $strip_url = FALSE;
    if ($args['url'] == '') {
        if (strpos($content, '<') !== FALSE ||
            strpos($content, '"') !== FALSE ||
            strpos($content, '>') !== FALSE)
            $content = preg_replace('/[<">].*[<">]/', '', $content);
        $args['url'] = $content;
        $strip_url = TRUE;
    }
    if (!preg_match('!^\w+://!', $args['url'])) {
        $args['url'] = 'http://'.$args['url'];
    }
    // we need the full url for nofollow handling
    $nofollow='';
    if (!empty($settings['rel_no_follow'])) {
    	if($settings['rel_no_follow'] == 1) {
    		// always add nofollow
    		$nofollow .= ' rel="nofollow"';
    	} else {
    		
    		// check for defined urls
    		$follow = false;
    		$check_urls = array();
    		if(!empty($settings['follow_urls'])) {
    			$check_urls = explode(",",$settings['follow_urls']);
    		}
    		$check_urls[]=$PHORUM['http_path'];

    		foreach($check_urls as $check_url) {
    			// the url has to start with one of these URLs
    			if(stripos($args['url'],$check_url) === 0) {
    				$follow = true;
    				break;
    			}
    		}
    		// we didn't find a matching url, make it nofollow
    		if($follow === false) {
    			$nofollow .= ' rel="nofollow"';
    		}
    	}
    }
    
    if ($strip_url && !$show_full_urls) {
        $parts = @parse_url($args['url']);
        return "[<a href=\"{$args['url']}\" $extratags{$nofollow}>{$parts['host']}</a>]";
    } else {
        return "<a href=\"{$args['url']}\" $extratags{$nofollow}>$content</a>";
    }
}

function bbcode_list_handler($content, $args, $message)
{
    // Fix breaks that are inbetween the rendered contained list tags.
    $content = preg_replace('/\s+/', ' ', $content);
    $content = preg_replace('!^\s*<phorum break>\s*!', '', $content);
    $content = preg_replace('!\s*<phorum break>\s*$!', '', $content);
    $content = preg_replace(
        '!(?:<phorum break>\s*)+(<(?:/ul|/li)>)!',
        '$1', $content
    );
    $content = preg_replace(
        '!(<(?:/?ul|/?li)>)\s*(?:<phorum break>\s*)+(<(?:/?ul|/?li)>)!',
        '$1$2', $content
    );

    if (strpos('iaIA1', $args['list']) !== FALSE) {
        $open = '<ol type="'.$args['list'].'">';
        $close = '</ol>';
    } else {
        $open = '<ul>';
        $close = '</ul>';
    }

    return $open . $content . $close;
}

function bbcode_quote_handler($content, $args, $message)
{
    global $PHORUM;

    $content = preg_replace('/^\s*\<(?:phorum break|br)\s*\/?\>/','', $content);
    $content = preg_replace('/\<(?:phorum break|br)\s*\/?\>\s*$/','', $content);

    return '<blockquote class="bbcode">' .
            '<div>' .
             '<small>' .
              $PHORUM['DATA']['LANG']['mod_bbcode']['quote_title'] . '<br/>' .
             '</small>' .
             '<strong>' . $args['quote'] . '</strong><br/>' .
             $content .
            '</div>' .
           '</blockquote>';
}

function bbcode_size_handler($content, $args, $message)
{
    // Prevent XSS attacks by allowing a strict set of characters.
    if (!preg_match('/^[A-Z0-9.\s-]+$/i', $args['size'])) {
        return $content;
    }

    return '<span style="font-size:' . $args['size'] . '">'.$content.'</span>';
}

function bbcode_color_handler($content, $args, $message)
{
    // Prevent XSS attacks by allowing a strict set of characters.
    if (!preg_match('/^[A-Z0-9#\s]+$/i', $args['color'])) {
        return $content;
    }

    return '<span style="color:' . $args['color'] . '">' . $content . '</span>';
}

?>
