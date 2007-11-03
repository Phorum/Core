#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"

/**
 * Initialize all available Phorum URL handlers.
 *
 * This function is called once per request form the MINIT code
 * to setup the hashed mapping of URL types to their URL generation
 * handler functions.
 */
void
initialize_get_url_handlers()
{
    register_url_handler(
     PHORUM_BASE_URL,              &basic_url, "",           NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_CSS_URL,               &basic_url, "css",        FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_JAVASCRIPT_URL,        &basic_url, "javascript", FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_LIST_URL,              &list_url,  "list",       NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_READ_URL,              &read_url,  "read",       FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_FOREIGN_READ_URL,      &read_url,  "read",       NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_CHANGES_URL,           &basic_url, "changes",    FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_REPLY_URL,             &reply_url, "posting",    FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_POSTING_URL,           &basic_url, "posting",    FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_POSTING_ACTION_URL,    &basic_url, "posting",    NO_FORUM_ID, NO_GET_VARS);
    register_url_handler(
     PHORUM_REDIRECT_URL,          &basic_url, "redirect",   NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_SEARCH_URL,            &basic_url, "search",     FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_SEARCH_ACTION_URL,     &basic_url,"search",      NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_INDEX_URL,             &basic_url, "index",      NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_LOGIN_URL,             &basic_url, "login",      FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_LOGIN_ACTION_URL,      &basic_url, "login",      NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_REGISTER_URL,          &basic_url, "register",   FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_REGISTER_ACTION_URL,   &basic_url, "register",   NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_PROFILE_URL,           &basic_url, "profile",    FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_SUBSCRIBE_URL,         &basic_url, "subscribe",  FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_MODERATION_URL,        &basic_url, "moderation", FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_MODERATION_ACTION_URL, &basic_url, "moderation", NO_FORUM_ID, NO_GET_VARS);
    register_url_handler(
     PHORUM_CONTROLCENTER_URL,     &basic_url, "control",    FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_CONTROLCENTER_ACTION_URL, &basic_url, "control", FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_PM_URL,                &basic_url, "pm",         FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_PM_ACTION_URL,         &basic_url, "pm",         NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_FILE_URL,              &file_url,  "file",       FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_FOLLOW_URL,            &basic_url, "follow",     FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_FOLLOW_ACTION_URL,     &basic_url, "follow",     NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_REPORT_URL,            &basic_url, "report",     FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_FEED_URL,              &basic_url, "feed",       NO_FORUM_ID, GET_VARS);
    register_url_handler(
     PHORUM_ADDON_URL,             &basic_url, "addon",      FORUM_ID,    GET_VARS);
    register_url_handler(
     PHORUM_CUSTOM_URL,            &custom_url,"",           NO_FORUM_ID, GET_VARS);
}

/**
 * Implementation of the phorum_get_url() function.
 *
 * This function acts mainly as a dispatcher, which calls the
 * URL handling functions based on the requested URL type.
 */
PHP_FUNCTION(phorum_get_url)
{
    zval       ***argv = NULL;
    int           argc = 0;
    long          type = 0;
    char         *urlstr = NULL;
    url_handler **handler;
    url_info     *url = NULL;

    /* Check if we have at least one parameter (the URL type). */
    argc = ZEND_NUM_ARGS();
    if (argc < 1) {
        zend_error(
            E_WARNING,
            "phorum_get_url() takes at least one argument."
        );
        goto error;
    }

    /* Retrieve the function call parameters. */
    argv = (zval ***)emalloc(argc * sizeof(**argv));
    if (!argv) zend_error(E_ERROR, "Out of memory");
    if(zend_get_parameters_array_ex(argc, argv) != SUCCESS)
        WRONG_PARAM_COUNT;

    /* argv[0] should be an integer describing the URL type. */
    if (Z_TYPE_P(*argv[0]) != IS_LONG) {
        zend_error(
            E_WARNING,
            "phorum_get_url(): the first argument needs to be "
            "an integer value, describing the type of URL to create."
        );
        goto error;
    }
    type = Z_LVAL_P(*argv[0]);

    /* Lookup the url handler to call. */
    if (zend_hash_index_find(&PHORUMG(url_handlers), type, (void **)&handler) == FAILURE) {
        zend_error(
            E_WARNING,
            "phorum_get_url(): URL type \"%ld\" unknown", type
        );
        goto error;
    }

    /* Initialize the url info, which will be used to keep all
     * url related info together during the URL building calls. */
    url = (url_info *)emalloc(sizeof(url_info));
    if (!url) zend_error(E_ERROR, "Out of memory");
    bzero(url, sizeof(url_info));
    url->add_forum_id = (*handler)->add_forum_id;
    url->add_get_vars = (*handler)->add_get_vars;
    url->page         = estrdup((*handler)->page);
    if (!url->page) zend_error(E_ERROR, "Out of memory");

    /* Call the URL handler function for the requested URL type.
     * The url type is stripped from the argument list. */
    urlstr = (*(*handler)->func)(*handler, url, argc-1, (argc==1 ? NULL : &argv[1]));

    error:

    /* Cleanup work data. */
    if (argv != NULL) efree(argv);
    destroy_url(&url);

    /* Return the URL string or FALSE in case some error occurred. */
    if (urlstr != NULL) {
        RETURN_STRING(urlstr, 0);
    } else {
        RETURN_FALSE;
    }
}

/**
 * Register a Phorum URL handler.
 *
 * This adds the URL type to the internal hash table, which will be used
 * for dispatching phorum_get_url() requests to the appropriate URL
 * formatting function.
 */
void
register_url_handler(long type, url_handler_func *func, char *page, int add_forum_id, int add_get_vars)
{
    /* Create a new url handler description. */
    url_handler *handler = (url_handler *)pemalloc(sizeof(url_handler), 1);
    if (!handler) zend_error(E_ERROR, "Out of memory");
    bzero(handler, sizeof(url_handler));
    handler->type         = type;
    handler->func         = func;
    handler->page         = page;
    handler->add_forum_id = add_forum_id ? 1 : 0;
    handler->add_get_vars = add_get_vars ? 1 : 0;

    /* Add the description to the url handlers hash. */
    if (zend_hash_index_update(&PHORUMG(url_handlers), type, (void *)&handler, sizeof(void *), NULL) == SUCCESS) {
    }
}

/**
 * Destroy a Phorum URL handler.
 */
void destroy_url_handler(url_handler **handler) {
    pefree(*handler, 1);
}

/* ====================================================================== */
/* Helper functions for URL handlers                                      */
/* ====================================================================== */

/**
 * Destroy all memory that is related to a url_info struct.
 */
void destroy_url(url_info **urlp)
{
    url_info *url = *urlp;
    url_arg  *arg;

    if (url != NULL) {
        if (url->page != NULL) {
            efree(url->page);
            url->page = NULL;
        }
        arg = url->arg_first;
        while (arg) {
             url_arg *next = arg->next;
             destroy_url_arg(&arg);
             arg = next;
        }
        if (url->suffix != NULL) {
            efree(url->suffix);
            url->suffix = NULL;
        }
        if (url->pathinfo != NULL) {
            efree(url->pathinfo);
            url->pathinfo = NULL;
        }
        efree(url);
    }
    *urlp = NULL;
}

/**
 * Destroy all memory that is related to a url_arg struct.
 */
void destroy_url_arg(url_arg **argp)
{
    url_arg *arg = *argp;

    if (arg != NULL) {
        if (arg->str != NULL) efree(arg->str);
        efree(arg);
    }

    *argp = NULL;
}

/**
 * Allocate memory and format a string using vsnprintf().
 * Return the result as an url_arg struct.
 */
url_arg *format_url_arg(char *fmt, ...)
{
    va_list  args;
    char     lentest[2];
    long     len;
    char    *str;
    url_arg *arg;

    /* First, find out how much space is needed for the result string. */
    va_start(args, fmt);
    len = vsnprintf((char *)&lentest, 1, fmt, args);
    va_end(args);

    /* Allocate a buffer that is large enough to hold the result. */
    if ((str = (char *)emalloc(len + 1)) == NULL)
        zend_error(E_ERROR, "Out of memory");

    /* Restart argument scanning to actually fill the result string. */
    va_start(args, fmt);
    vsnprintf(str, len+1, fmt, args);
    va_end(args);

    /* Create the url_arg. */
    arg = (url_arg *)emalloc(sizeof(url_arg));
    if (!arg) zend_error(E_ERROR, "Out of memory");
    bzero(arg, sizeof(url_arg));
    arg->str = str;
    arg->length = len;

    return arg;
}

/**
 * Add a url_arg struct to the linked list of url arguments in a
 * url_info struct. The "prepend" argument is used to tell the function
 * whether to prepend (1) or append (0) the new url_arg.
 */
void add_url_arg(url_info **urlp, url_arg *arg, int prepend)
{
    url_info *url = *urlp;

    url->arg_length += arg->length;
    url->arg_count ++;

    if (url->arg_first == NULL) {
        url->arg_first = arg;
        url->arg_last = arg;
    } else {
        if (prepend) {
            /* prepend */
            arg->next = url->arg_first;
            url->arg_first = arg;
        } else {
            /* append */
            url_arg *last;
            last = url->arg_last;
            last->next = arg;
            url->arg_last = arg;
        }
    }
}

/**
 * Handle the standard url_info building for Phorum URLs.
 *
 * This includes:
 *
 * - adding the forum_id in the arguments;
 * - adding the arguments that were passed on to phorum_get_url();
 * - adding the arguments that are stored in $PHORUM["GET_VARS"].
 */
void
default_url_build(void *h, void *u, int argc, zval ***argv)
{
    url_info    *url     = (url_info *)u;
    url_arg     *arg;
    int          i;

    /* Add the forum id to the argument list. */
    if (url->add_forum_id)
    {
        arg = format_url_arg("%ld", get_PHORUM_long("forum_id"));
        add_url_arg(&url, arg, 0);
    }

    /* Add the rest of argv to the argument list. */
    for (i=0; i<argc; i++)
    {
        zval **a = argv[i];
        convert_to_string(*a);
        arg = format_url_arg("%s", Z_STRVAL_PP(a));
        add_url_arg(&url, arg, 0);
    }

    /* Add $PHORUM["GET_VARS"] to the argument list (used for passing the
     * URI authentication session id). */
    if (url->add_get_vars)
    {
        zval *get_vars = get_PHORUM_DATA("GET_VARS");
        if (get_vars != NULL && Z_TYPE_P(get_vars) == IS_ARRAY) {
            HashTable    *t;
            HashPosition  p;
            zval        **v;
            t = HASH_OF(get_vars);
            for (zend_hash_internal_pointer_reset_ex(t, &p);
                 zend_hash_get_current_data_ex(t, (void**)&v, &p) == SUCCESS;
                 zend_hash_move_forward_ex(t, &p)) {
                 if (Z_TYPE_PP(v) == IS_STRING) {
                     arg = format_url_arg("%s", Z_STRVAL_PP(v));
                     add_url_arg(&url, arg, 0);
                 }
            }
        }
    }
}

/**
 * Format a URL, based on the data in a url_info struct.
 *
 * If the function phorum_custom_get_url() is defined, then that function
 * will be called instead of creating the URL ourselves. This is for example
 * used by portable and embedded code for generating correct URLs for the
 * system that Phorum runs in.
 */
char *
default_url_format(void *u)
{
    url_info    *url = (url_info *)u;
    url_arg     *arg;
    int          i;
    char        *http_path;
    char        *extension = NULL;
    char        *urlstr = NULL;

    /* ------------------------------------------------------------- */
    /* Build the URL using phorum_custom_get_url(), if defined.      */
    /* ------------------------------------------------------------- */

    zend_function *func;

    /* Check if we have a custom URL function,
     * the first time that this code is run. */
    if (get_url_do_custom_url == -1) {
        get_url_do_custom_url =(zend_hash_find(
            EG(function_table),
            "phorum_custom_get_url", sizeof("phorum_custom_get_url"),
            (void **)&func
        ) == SUCCESS) ? 1 : 0;
    }

    if (get_url_do_custom_url == 1)
    {
        zval *retval = NULL;
        zval *func, *page, *query_items, *suffix, *pathinfo;
        zval **params[4];

        /* Setup the function call parameters. */

        MAKE_STD_ZVAL(func);
        ZVAL_STRING(func, "phorum_custom_get_url", 1);

        MAKE_STD_ZVAL(page);
        ZVAL_STRING(page, url->page, 1);
        params[0] = &page;

        MAKE_STD_ZVAL(query_items);
        array_init(query_items);
        for (arg = url->arg_first; arg; arg = arg->next) {
            add_next_index_string(query_items, arg->str, 1);
        }
        params[1] = &query_items;

        MAKE_STD_ZVAL(suffix);
        if (url->suffix != NULL) {
            url_arg *arg = url->suffix;
            ZVAL_STRING(suffix, arg->str, 1);
        } else {
            ZVAL_STRING(suffix, "", 1);
        }
        params[2] = &suffix;

        MAKE_STD_ZVAL(pathinfo);
        if (url->pathinfo != NULL) {
            ZVAL_STRING(pathinfo, url->pathinfo, 1);
            } else {
            ZVAL_STRING(pathinfo, "", 1);
        }
        params[3] = &pathinfo;

        /* Call the phorum_custom_get_url() function. */

        if (call_user_function_ex(
            EG(function_table), NULL, func,
            &retval, 4, params, 0, NULL TSRMLS_CC
        ) == SUCCESS) {
            urlstr = estrdup(Z_STRVAL_P(retval));
            efree(Z_STRVAL_P(retval));
            FREE_ZVAL(retval);
        }

        /* Free the memory that we used. */

        efree(Z_STRVAL_P(func));
        FREE_ZVAL(func);
        efree(Z_STRVAL_P(page));
        FREE_ZVAL(page);
        efree(Z_STRVAL_P(suffix));
        FREE_ZVAL(suffix);
        zend_hash_destroy(HASH_OF(query_items));
        FREE_HASHTABLE(HASH_OF(query_items));
        FREE_ZVAL(query_items);

        /* Return the resulting URL. */

        if (urlstr) return urlstr;

        /* No success? Then do not do anything about it here. Just
         * let the standard code below handle the URL generation. */
    }

    /* ------------------------------------------------------------- */
    /* Build the URL.                                                */
    /* ------------------------------------------------------------- */

    /* -- compute the memory that is needed for the URL -- */

    /* The start of the URL. */
    http_path = get_PHORUM_string("http_path");
    url->url_length = strlen(http_path);

    /* Add a slash if the http_path doesn't have one at the end. */
    if (http_path[url->url_length-1] != '/') {
        url->url_length ++;
        url->add_slash = 1;
    }

    /* Add the script page name.extension, pathinfo, arguments and suffix.
     * If the page name is empty, then none of these are added
     * (used for PHORUM_BASE_URL). */
    if (strlen(url->page))
    {
        url->url_length += strlen(url->page) + 1 +
                           strlen(PHORUM_FILE_EXTENSION) + 1;

        /* Add arguments (plus "?" and space for a comma between
         * each argument). */
        if (url->arg_count > 0) {
            url->url_length += 1 + url->arg_length + url->arg_count;
        }

        if (url->pathinfo != NULL) {
            url->url_length += strlen(url->pathinfo);
        }

        /* Add suffix. */
        if (url->suffix != NULL) {
            arg = url->suffix;
            url->url_length += arg->length;
        }
    }

    /* -- allocate and build the URL -- */

    urlstr = (char *)emalloc(url->url_length + 1);
    if (!urlstr) zend_error(E_ERROR, "Out of memory");
    *urlstr = '\0';
    strcat(urlstr, http_path);
    if (url->add_slash) strcat(urlstr, "/");
    if (strlen(url->page)) {
        strcat(urlstr, url->page);
        strcat(urlstr, ".");
        strcat(urlstr, PHORUM_FILE_EXTENSION);
        if (url->pathinfo != NULL) {
            strcat(urlstr, url->pathinfo);
        }
        if (url->arg_count > 0) {
            strcat(urlstr, "?");
            i = url->arg_count;
            for (arg = url->arg_first; arg; arg = arg->next) {
                 strcat(urlstr, arg->str);
                 if (--i) strcat(urlstr, ",");
            }
        }
        if (url->suffix != NULL) {
            arg = url->suffix;
            strcat(urlstr, arg->str);
        }
    }

    return urlstr;
}

/* Check if a string contains only numbers. */
int string_is_numeric(char *string)
{
    char *p;
    for (p = string; *p; p++) {
        if (*p < '0' || *p > '9') return 0;
    }
    return 1;
}

/* ====================================================================== */
/* URL handlers                                                           */
/* ====================================================================== */

/**
 * Default URL handler.
 */
char *
basic_url(void *h, void *u, int argc, zval ***argv)
{
    default_url_build(h, u, argc, argv);
    return default_url_format(u);
}

/**
 * Reply URL handler.
 *
 * If we have replies on the read page, then create a URL to show the
 * reply form there. Else create a URL to the posting page.
 */
char *
reply_url(void *h, void *u, int argc, zval ***argv)
{
    url_info    *url = (url_info *)u;
    int          reply_on_read_page = 0;
    zval        *z;

    /* Check if "reply on read page" is configured. */
    z = get_PHORUM("reply_on_read_page");
    if (z != NULL) {
        convert_to_long(z);
        if (Z_LVAL_P(z) > 0) reply_on_read_page = 1;
    }

    if (reply_on_read_page) {
        efree(url->page);
        url->page = estrdup("read");
        url->suffix = format_url_arg("#REPLY");
        return basic_url(h, u, argc, argv);
    }
    else /* reply on separate page */
    {
        default_url_build(h, u, argc, argv);

        /* For reply on a separate page, we call posting.php on its own.
         * In that case the first argument should be the editor mode we
         * want to use ("reply" in this case). Currently, the thread id
         * is the first argument. We don't need that one for the posting.php
         * script though, so we simply replace that argument with the
         * editor mode argument.
         */
        if (url->arg_first != NULL) {
            url_arg *first, *next, *newarg;
            first = url->arg_first; next  = first->next;
            newarg = format_url_arg("reply");
            newarg->next = next;
            url->arg_first = newarg;
            destroy_url_arg(&first);
        }
        return default_url_format(u);
    }
}

/**
 * List URL handler.
 *
 * If there are no url arguments in the argv array,
 * then we add the active forum_id to the arguments.
 */
char *
list_url(void *h, void *u, int argc, zval ***argv)
{
    url_info *url = (url_info *)u;
    if (argc == 0) url->add_forum_id = 1;
    default_url_build(h, u, argc, argv);
    return default_url_format(u);
}

/**
 * File URL handler.
 *
 * If a filename=... parameter is set, then change that parameter into
 * pathinfo, unless this feature is not enabled in the Phorum settings.
 */
char *
file_url(void *h, void *u, int argc, zval ***argv)
{
    url_info *url                    = (url_info *)u;
    int       file_url_uses_pathinfo = 0;
    zval     *z;
    int       i;

    z = get_PHORUM("file_url_uses_pathinfo");
    if (z != NULL) {
        convert_to_long(z);
        if (Z_LVAL_P(z) > 0) file_url_uses_pathinfo = 1;
    }

    // If the file_url_uses_pathinfo option is disabled, then the file
    // URL behaves exactly like the standard URL.
    if (file_url_uses_pathinfo == 0) {
        default_url_build(h, u, argc, argv);
        return default_url_format(u);
    }

    /* Check if there is a filename parameter in the arguments. */
    for (i=0; i<argc; i++)
    {
        zval **zarg = argv[i];
        convert_to_string(*zarg);

        if (strlen(Z_STRVAL_PP(zarg)) > 9 &&
            strncmp(Z_STRVAL_PP(zarg), "filename=", 9) == 0)
        {
            int   srcpos, dstpos = 0, len, prev_is_special = 0;
            char *pathinfo       = NULL;

            pathinfo = estrdup(Z_STRVAL_PP(zarg) + 8);

            /* Pathinfo starts with a slash. */
            pathinfo[dstpos++] = '/';

            /* Generate safe pathinfo, unless the filename is "%file_name%".
             * We should not mangle that one, because it is used as a
             * replacable string inside file URL templates. */
            if (strcmp(pathinfo, "/%file_name%")) {
                len = strlen(pathinfo);
                for (srcpos=1; srcpos<len; srcpos++) {
                    if ((pathinfo[srcpos] >= 'a' && pathinfo[srcpos] <= 'z') ||
                        (pathinfo[srcpos] >= 'A' && pathinfo[srcpos] <= 'Z') ||
                        (pathinfo[srcpos] >= '0' && pathinfo[srcpos] <= '9') ||
                        pathinfo[srcpos] == '-' || pathinfo[srcpos] == '.') {
                        prev_is_special = 0;
                        pathinfo[dstpos++] = pathinfo[srcpos];
                        continue;
                    } else {
                        if (!prev_is_special) {
                            pathinfo[dstpos++] = '_';
                        }
                        prev_is_special = 1;
                    }
                }
                pathinfo[dstpos] = '\0';
            }

            /* In case there was more than one filename argument. Should
             * not really happen, but still, let's keep it in mind. */
            if (url->pathinfo != NULL) {
                efree(url->pathinfo);
            }

            url->pathinfo = pathinfo;

            /* Remove the filename argument. */
            dstpos = i;
            srcpos = i + 1;
            while (srcpos <= argc) {
                argv[dstpos++] = argv[srcpos++];
            }
            argc --;
            i--;
        }
    }

    default_url_build(h, u, argc, argv);
    return default_url_format(u);
}

/**
 * Read URL handler.
 *
 * If argv[1] (for read url) or argv[2] (for foreign read url) is set
 * (which is the message_id), the anchor #msg-<message_id> is added to
 * the formatted URL.
 */
char *
read_url(void *h, void *u, int argc, zval ***argv)
{
    url_handler *handler = (url_handler *)h;
    url_info    *url     = (url_info *)u;
    int          idx;

    /* The argv field to look at depends on the type of URL. For
     * PHORUM_READ_URL it is field 1 and for PHORUM_FOREIGN_READ_URL
     * it is field 2.  */
    idx = handler->type == PHORUM_READ_URL ? 1 : 2;

    /* Create a #msg-<msgid> suffix, if a message id is known. */
    if (argc >= (idx+1)) {
        zval **zarg = argv[idx]; /* message id */
        convert_to_string(*zarg);

        if (string_is_numeric(Z_STRVAL_PP(zarg))) {
            url_arg *anchor = format_url_arg("#msg-%s", Z_STRVAL_PP(zarg));
            url->suffix = anchor;
        }
    }

    return basic_url(h, u, argc, argv);
}

/**
 * Custom URL handler.
 *
 * argv[0] = page name
 * argv[1] = whether to add the forum id (true / false)
 * argv[n] = URL argument(s)
 */
char *custom_url(void *h, void *u, int argc, zval ***argv)
{
    url_info    *url     = (url_info *)u;
    int          skip    = 1;
    char        *page;

    /* We need at least the page name argument. */
    if (argc < 1) {
        zend_error(
            E_WARNING,
            "phorum_get_url(PHORUM_CUSTOM_URL, ..) takes at least "
            "two arguments."
        );
        return NULL;
    }

    /* Set the page name. */
    page = Z_STRVAL_PP(argv[0]);
    efree(url->page);
    url->page = estrdup(page);

    /* See if we have to add the forum id. */
    if (argc >= 2) {
        convert_to_boolean(*argv[1]);
        if (Z_LVAL_PP(argv[1])) {
            url->add_forum_id = 1;
        }
        skip ++;
    }

    return basic_url(h, u, (argc-skip), &argv[skip]);
}


