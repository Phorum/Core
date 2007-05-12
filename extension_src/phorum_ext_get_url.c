#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"
#include "phorum_ext_get_url.h"
#include "phorum_utils.h"

/* TODO cleanup creatd handler map */

initialize_get_url_handlers()
{
    zend_hash_init(&url_handlers, 0, NULL, NULL, 0);
  
    register_url_handler(
     "PHORUM_INDEX_URL", &basic_url, "index", NO_FORUM_ID, GET_VARS);
    register_url_handler(
     "PHORUM_LIST_URL",  &basic_url, "list",  FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_READ_URL",  &read_url,  "read",  FORUM_ID,    GET_VARS);
    register_url_handler(
     "PHORUM_FEED_URL",  &feed_url,  "feed",  NO_FORUM_ID, GET_VARS);
  
    get_url_initialized = 1;
}

PHP_FUNCTION(phorum_ext_get_url)
{
    zval       ***argv = NULL;
    int           argc = 0;
    long          type = 0;
    char         *urlstr;
    url_handler **handler;
    url_info     *url;

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

    if (urlstr != NULL) {
        efree(argv);
        efree(url);
        RETURN_STRING(urlstr, 0);
    }

    error:
    if (argv != NULL) efree(argv);
    if (url  != NULL) efree(url);
    RETURN_FALSE;
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

zval *format_url_arg(char *fmt, ...)
{
    va_list  args;
    char     lentest[2];
    long     len;
    char    *str;
    zval    *arg;

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

    /* Create the zval for this arg. */
    MAKE_STD_ZVAL(arg);
    ZVAL_STRING(arg, str, 0);

    return arg;
}

void
default_url_build(void *h, void *u, int argc, zval ***argv)
{
    url_handler *handler = (url_handler *)h;
    url_info    *url     = (url_info *)u;
    zval        *arg;
    zval       **data;
    int          i;
    char        *http_path;
    char        *extension;
    char        *urlstr;

    /* Initialize the argument list. */
    zend_hash_init(&url->args, 0, NULL, ZVAL_PTR_DTOR, 0);

    /* Add the forum id to the argument list. */
    if (url->add_forum_id) {
        arg = format_url_arg("%ld", get_PHORUM_long("forum_id"));
        url->arg_length += Z_STRLEN_P(arg);
        zend_hash_index_update(&url->args, url->arg_count++, (void*)&arg, sizeof(zval*), NULL);
    }

    /* Add the rest of argv to the argument list.
     * Starts with i=1, because argv[0] is the URL type constant. */
    for (i=1; i<argc; i++) {
        zval **a = argv[i];
        convert_to_string(*a);
        arg = format_url_arg("%s", Z_STRVAL_PP(a));
        url->arg_length += Z_STRLEN_P(arg);
        zend_hash_index_update(&url->args, url->arg_count++, (void*)&arg, sizeof(zval*), NULL);
    }

    /* Add GET vars to the argument list. */
    /* TODO */
}

char *
default_url_format(void *u)
{
    url_info    *url     = (url_info *)u;
    zval        *arg;
    zval       **data;
    int          i;
    char        *http_path;
    char        *extension;
    char        *urlstr;

    /* Build the URL. First compute the memory that we need. */

    /* The start of the URL. */
    http_path = get_PHORUM_string("http_path");
    url->url_length = strlen(http_path);

    /* Add a slash if the http_path doesn't have one at the end. */
    if (http_path[url->url_length-1] != '/') {
        url->url_length ++;
        url->add_slash = 1;
    }

    /* Add the script page name, extension and "?". */
    extension = get_constant_string("PHORUM_FILE_EXTENSION");
    url->url_length += strlen(url->page) + 1 + strlen(extension) + 2;

    /* Add arguments (and space for a comma between each argument). */
    url->url_length += url->arg_length + url->arg_count;

    /* Allocate and build the URL. */
    urlstr = (char *)emalloc(url->url_length + 1); 
    if (!urlstr) zend_error(E_ERROR, "Out of memory");
    *urlstr = '\0';
    strcat(urlstr, http_path);
    if (url->add_slash) strcat(urlstr, "/");
    strcat(urlstr, url->page);
    strcat(urlstr, ".");
    strcat(urlstr, extension);
    strcat(urlstr, "?");
    for (zend_hash_internal_pointer_reset_ex(&url->args, &url->p);
         zend_hash_get_current_data_ex(&url->args, (void**) &data, &url->p) == SUCCESS;
         zend_hash_move_forward_ex(&url->args, &url->p)) {
         strcat(urlstr, (char *)Z_STRVAL_PP(data));
         if (--url->arg_count) strcat(urlstr, ","); /* TODO private counter */
    }

    /* Free our work data. */
    zend_hash_destroy(&url->args);

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

/* Read URL handler.
 * argv[0] = URL type
 * argv[1] = thread
 * argv[2] = message_id
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
            zval *anchor = format_url_arg("#msg-%s", Z_STRVAL_PP(zarg));
            int newlen = strlen(urlstr) + strlen(Z_STRVAL_P(anchor)) + 1;
            urlstr = erealloc(urlstr, newlen + 1);
            if (!urlstr) zend_error(E_ERROR, "Out of memory");
            strcat(urlstr, Z_STRVAL_P(anchor));
            urlstr[newlen] = '\0';
            FREE_ZVAL(anchor);
        }
    }

    return urlstr;
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
        zval      *arg;

        /* Add $PHORUM["args"]["1"] to the URL (thread id on read page) */
        arg = get_PHORUM_args("1");
        if (arg != NULL) {
            convert_to_string(arg);
            if (string_is_numeric(Z_STRVAL_P(arg)))
            {
                HashTable   *newhash;
                HashPosition p;
                zval        *newval, **data;

                MAKE_STD_ZVAL(newval);
                ZVAL_STRING(newval, Z_STRVAL_P(arg), 1);
                url->arg_length += Z_STRLEN_P(newval);
                url->arg_count ++;

                ALLOC_HASHTABLE(newhash);
                zend_hash_init(newhash, 0, NULL, ZVAL_PTR_DTOR, 0);
                zend_hash_next_index_insert(newhash, &newval, sizeof(zval*), NULL);
                for (zend_hash_internal_pointer_reset_ex(&url->args, &url->p);
                     zend_hash_get_current_data_ex(&url->args, (void**) &data, &url->p) == SUCCESS;
                     zend_hash_move_forward_ex(&url->args, &url->p)) {
                    MAKE_STD_ZVAL(newval);
                    ZVAL_STRING(newval, Z_STRVAL_PP(data), 1);
                    zend_hash_next_index_insert(newhash, &newval, sizeof(zval*), NULL);
                }
                zend_hash_destroy(&url->args);
                url->args = *newhash;
                zend_hash_internal_pointer_reset_ex(&url->args, &url->p);
            }
        }

        url->add_forum_id = 1; 
    }

    return default_url_format(u);
}

