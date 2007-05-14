/* TODO:
 * - cleanup created handler map on request finish.
 * - finish all URL types
 * - add GET args
 * - make sure that array format is nowhere called in the core code.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"
#include "phorum_ext_get_url.h"
#include "phorum_utils.h"

initialize_get_url_handlers()
{
    zend_hash_init(&url_handlers, 0, NULL, NULL, 0);
  
    register_url_handler(
     "PHORUM_INDEX_URL",             &basic_url, "index",      NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_LIST_URL",              &list_url,  "list",       NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_READ_URL",              &read_url,  "read",       FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_FEED_URL",              &feed_url,  "feed",       NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_CHANGES_URL",           &basic_url, "changes",    FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_POSTING_URL",           &basic_url, "posting",    FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_REDIRECT_URL",          &basic_url, "redirect",   NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_SEARCH_URL",            &basic_url, "search",     FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_SEARCH_ACTION_URL",     &basic_url,"search",      NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_DOWN_URL",              &basic_url, "down",       FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_VIOLATION_URL",         &basic_url, "violation",  FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_LOGIN_URL",             &basic_url, "login",      FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_REGISTER_URL",          &basic_url, "register",   FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_REGISTER_ACTION_URL",   &basic_url, "register",   NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_PROFILE_URL",           &basic_url, "profile",    FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_SUBSCRIBE_URL",         &basic_url, "subscribe",  FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_MODERATION_URL",        &basic_url, "moderation", FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_MODERATION_ACTION_URL", &basic_url, "moderation", NO_FORUM_ID, NO_GET_VARS);
    register_url_handler(
     "PHORUM_PROFILE_URL",           &basic_url, "profile",    FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_PM_URL",                &basic_url, "pm",         FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_CONTROLCENTER_URL",     &basic_url, "control",    FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_CONTROLCENTER_ACTION_URL", &basic_url, "control", NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_BASE_URL",              &basic_url, "",           NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_PREPOST_URL",           &prepost_url, "",         NO_FORUM_ID, GET_VARS);
  
    get_url_initialized = 1;
}

/* Destroy all memory that is related to a url_info struct. */
void destroy_url(url_info **urlp)
{
    url_info *url = *urlp;
    url_arg  *arg;

    if (url != NULL) {
        if (url->page != NULL) {
            efree(url->page);
            url->page = NULL;
        }
        for (arg = url->arg_first; arg; arg = arg->next) {
             efree(arg->str);
             efree(arg);
        }
        efree(url);
    }
    *urlp = NULL;
}

/* Destroy all memory that is related to a url_arg struct. */
void destroy_url_arg(url_arg **argp)
{
    url_arg *arg = *argp; 
   
    if (arg != NULL) {
        if (arg->str != NULL) {
            efree(arg->str);
        }
        efree(arg);
    }

    *argp = NULL;
}


PHP_FUNCTION(phorum_get_url)
{
    zval       ***argv = NULL;
    int           argc = 0;
    long          type = 0;
    char         *urlstr = NULL;
    url_handler **handler;
    url_info     *url = NULL;

    /* Initialize the function map hash. */
    if (get_url_initialized == 0) initialize_get_url_handlers();

    /* Check if we have at least one parameter (the URL type). */
    argc = ZEND_NUM_ARGS();
    if (argc < 1) {
        zend_error(
            E_WARNING,
            "phorum_ext_get_url() takes at least one argument."
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
            "phorum_ext_get_url(): the first argument needs to be "
            "an integer value, describing the type of URL to create."
        );
        goto error;
    }
    type = Z_LVAL_P(*argv[0]);

    /* Lookup the url handler to call. */
    if (zend_hash_index_find(&url_handlers, type, (void **)&handler) == FAILURE) {
        zend_error(
            E_WARNING,
            "phorum_ext_get_url(): URL type \"%ld\" unknown", type
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

    /* Call the URL handler function for the requested URL type. */
    urlstr = (*(*handler)->func)(*handler, url, argc, argv);

    error:

    if (argv != NULL) efree(argv);
    destroy_url(&url);

    if (urlstr != NULL) {
        RETURN_STRING(urlstr, 0);
    } else {
        RETURN_FALSE;
    }
}


void
register_url_handler(char *typename, url_handler_func *func, char *page, int add_forum_id, int add_get_vars)
{
    long type = get_constant_long(typename);

    /* Create a new url handler description. */
    url_handler *handler = (url_handler *)emalloc(sizeof(url_handler));
    if (!handler) zend_error(E_ERROR, "Out of memory");
    bzero(handler, sizeof(url_handler));
    handler->type         = type;
    handler->func         = func;
    handler->page         = page; 
    handler->add_forum_id = add_forum_id ? 1 : 0;
    handler->add_get_vars = add_get_vars ? 1 : 0;

    if (zend_hash_index_update(&url_handlers, type, (void *)&handler, sizeof(void *), NULL) == SUCCESS) {
    }
}

/* ====================================================================== */
/* Helper functions for URL handlers                                      */
/* ====================================================================== */

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

void append_url_arg(url_info **urlp, url_arg *arg)
{
    url_info *url = *urlp;

    url->arg_length += arg->length;
    url->arg_count ++;

    if (url->arg_first == NULL) {
        url->arg_first = arg;
        url->arg_last = arg;
    } else {
        url_arg *last;
        last = url->arg_last;
        last->next = arg;
        url->arg_last = arg;
    }
}

void
default_url_build(void *h, void *u, int argc, zval ***argv)
{
    url_handler *handler = (url_handler *)h;
    url_info    *url     = (url_info *)u;
    url_arg     *arg;
    zval       **data;
    int          i;
    char        *http_path;
    char        *extension;
    char        *urlstr;

    /* Add the forum id to the argument list. */
    if (url->add_forum_id)
    {
        arg = format_url_arg("%ld", get_PHORUM_long("forum_id"));
        append_url_arg(&url, arg);
    }

    /* Add the rest of argv to the argument list.
     * Starts with i=1, because argv[0] is the URL type constant. */
    for (i=1; i<argc; i++)
    {
        zval **a = argv[i];
        convert_to_string(*a);
        arg = format_url_arg("%s", Z_STRVAL_PP(a));
        append_url_arg(&url, arg);
    }

    /* Add GET vars to the argument list. */
    /* TODO */
}

char *
default_url_format(void *u)
{
    url_info    *url     = (url_info *)u;
    url_arg     *arg;
    zval       **data;
    int          i;
    char        *http_path;
    char        *extension = NULL;
    char        *urlstr;

    /* ------------------------------------------------------------- */
    /* Build the URL. First compute the memory that we need.         */
    /* ------------------------------------------------------------- */

    /* The start of the URL. */
    http_path = get_PHORUM_string("http_path");
    url->url_length = strlen(http_path);

    /* Add a slash if the http_path doesn't have one at the end. */
    if (http_path[url->url_length-1] != '/') {
        url->url_length ++;
        url->add_slash = 1;
    }

    /* Add the script page name.extension. */
    if (strlen(url->page)) {
        extension = get_constant_string("PHORUM_FILE_EXTENSION");
        url->url_length += strlen(url->page) + 1 + strlen(extension) + 1;
    }

    /* Add arguments (plus "?" and space for a comma between each argument). */
    if (url->arg_count > 0) {
        url->url_length += 1 + url->arg_length + url->arg_count;
    }

    /* ------------------------------------------------------------- */
    /* The needed amount of memory is known. Allocate and build URL. */
    /* ------------------------------------------------------------- */

    urlstr = (char *)emalloc(url->url_length + 1); 
    if (!urlstr) zend_error(E_ERROR, "Out of memory");
    *urlstr = '\0';
    strcat(urlstr, http_path);
    if (url->add_slash) strcat(urlstr, "/");
    if (strlen(url->page)) {
        strcat(urlstr, url->page);
        strcat(urlstr, ".");
        strcat(urlstr, extension);
    }
    if (url->arg_count > 0) { 
        strcat(urlstr, "?");
        i = url->arg_count;
        for (arg = url->arg_first; arg; arg = arg->next) {
             strcat(urlstr, arg->str);
             if (--i) strcat(urlstr, ",");
        }
    }

    /* apparently zend_get_constant() returns allocated memory. */
    if (extension != NULL) efree(extension);

    return urlstr;
}

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

/* Default URL handler. */
char *
basic_url(void *h, void *u, int argc, zval ***argv)
{
    default_url_build(h, u, argc, argv);
    return default_url_format(u);
}

/* List URL handler.
 * If there are no url arguments in the argv array (except for the
 * URL type to generate), then we add the active forum_id to the arguments.
 */ 
char *
list_url(void *h, void *u, int argc, zval ***argv)
{
    url_info *url = (url_info *)u;
    if (argc == 1) url->add_forum_id = 1;
    return basic_url(h, u, argc, argv);
}

/* Read URL handler.
 * If argv[2] (message_id) is set, the anchor #msg-<message_id> is
 * added to the formatted URL.
 */ 
char *
read_url(void *h, void *u, int argc, zval ***argv)
{
    url_handler *handler = (url_handler *)h;
    url_info    *url     = (url_info *)u;
    
    char *urlstr = basic_url(h, u, argc, argv);

    /* Add #msg-<msgid> to the URL, if a message id is known. */
    if (argc >= 3) {
        zval **zarg = argv[2]; /* message id */
        convert_to_string(*zarg);

        if (string_is_numeric(Z_STRVAL_PP(zarg))) {
            url_arg *anchor = format_url_arg("#msg-%s", Z_STRVAL_PP(zarg));
            int newlen = strlen(urlstr) + anchor->length;
            urlstr = erealloc(urlstr, newlen + 1);
            if (!urlstr) zend_error(E_ERROR, "Out of memory");
            strcat(urlstr, anchor->str);
            urlstr[newlen] = '\0';
            efree(anchor->str);
            efree(anchor);
        }
    }

    return urlstr;
}

/* Prepost URL handler.
 * panel=messages is added as an url argument.
 */
char *prepost_url(void *h, void *u, int argc, zval ***argv)
{
    url_info *url = (url_info *)u;
    url_arg  *arg = format_url_arg("panel=messages"); 
    append_url_arg(&url, arg);
    return basic_url(h, u, argc, argv);
}

/* Feed URL handler. */
char *feed_url(void *h, void *u, int argc, zval ***argv)
{
    url_handler *handler = (url_handler *)h;
    url_info    *url     = (url_info *)u;
    char        *phorum_page = get_constant_string("phorum_page");

    default_url_build(h, u, argc, argv);

    if (!strcmp(phorum_page, "list"))
    {
        url->add_forum_id = 1; 
    }

    if (!strcmp(phorum_page, "read"))
    {
        /* Add $PHORUM["args"]["1"] (the thread id on a read page) 
         * as the first URL argument. */
        zval *arg = get_PHORUM_args("1");
        if (arg != NULL) {
            convert_to_string(arg);
            if (string_is_numeric(Z_STRVAL_P(arg)))
            {
                url_arg *newarg = format_url_arg("%s", (char *)Z_STRVAL_P(arg));
                
                url->arg_length += newarg->length;
                url->arg_count ++;
                if (url->arg_first == NULL) {
                    url->arg_first = newarg;
                    url->arg_last = newarg;
                } else {
                    newarg->next = url->arg_first;
                    url->arg_first = newarg;
                }
            }
        }

        url->add_forum_id = 1; 
    }

    /* apparently zend_get_constant() returns allocated memory. */
    efree(phorum_page);

    return default_url_format(u);
}

