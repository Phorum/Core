#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"
#include "phorum_ext_get_url.h"


PHP_FUNCTION(phorum_ext_get_url)
{
    zval ***argv = NULL;
    int     argc = 0;
    long    type = 0;
    char   *url;
    phorum_get_url_handler **handler;

    if (phorum_get_url_initialized == 0)
    {
      zend_hash_init(&phorum_get_url_handlers, 0, NULL, NULL, 0);

      _phorum_register_get_url_handler(
       "PHORUM_INDEX_URL", &hello_world, "index", NO_FORUM_ID, NO_GET_VARS);
      _phorum_register_get_url_handler(
       "PHORUM_LIST_URL",  &hello_world, "list",  FORUM_ID,    NO_GET_VARS);

      phorum_get_url_initialized = 1;
    }

    /* Check if we have at least the URL type parameter. */
    argc = ZEND_NUM_ARGS();
    if (argc < 1) {
        zend_error(
            E_WARNING,
            "phorum_ext_get_url() takes at least one argument."
        );
        RETURN_FALSE;
    }

    /* Retrieve the function call parameters. */
    argv = (zval ***)emalloc(argc * sizeof(**argv));
    if(zend_get_parameters_array_ex(argc, argv) != SUCCESS)
        WRONG_PARAM_COUNT;

    /* argv[0] should be an integer describing the URL type. */
    if (Z_TYPE_P(*argv[0]) != IS_LONG) {
        zend_error(
            E_WARNING,
            "phorum_ext_get_url(): the first argument needs to be "
            "an integer value, describing the type of URL to create."
        );
        RETURN_FALSE;
    }
    type = Z_LVAL_P(*argv[0]);

    /* Lookup the handler. */
    type = 12; /* TODO, for now use this one for all calls. */
    if (zend_hash_index_find(&phorum_get_url_handlers, type, (void **)&handler) == FAILURE) {
        zend_error(
            E_WARNING,
            "phorum_ext_get_url(): URL type \"%ld\" unknown", type
        );
        RETURN_FALSE;
    }

    /* Call the URL handler function for the requested URL type. */
    url = (*(*handler)->func)(*handler, argc, argv);

    if (url != NULL) {
        RETURN_STRING(url, 0);
    } else {
        RETURN_FALSE;
    }
}

char *hello_world(void *h, int argc, zval ***argv)
{
    phorum_get_url_handler *handler = (phorum_get_url_handler *)h; 
    char *buffer = emalloc(1000);

    sprintf(buffer, "http://phorum.site.com/%s.php?...", handler->page);
    return buffer;
}

long *
_phorum_get_constant(char *key)
{
    zval  zkey;
    long *value;
    zval  zvalue;

    ZVAL_STRING(&zkey, key, 0);

    if (!zend_get_constant(Z_STRVAL(zkey), Z_STRLEN(zkey), &zvalue TSRMLS_CC)) {
        return NULL;
    }

    value = emalloc(sizeof(long));
    convert_to_long(&zvalue);
    *value = Z_LVAL(zvalue);

    return value;
}

void
_phorum_register_get_url_handler(char *typename, phorum_get_url_handler_func *func, char *page, int add_forum_id, int add_get_vars)
{
    long *type = _phorum_get_constant(typename);
    if (type == NULL) return;

    /* Create a new url handler description. */
    phorum_get_url_handler *handler =
        (phorum_get_url_handler *)emalloc(sizeof(phorum_get_url_handler));
    bzero(handler, sizeof(phorum_get_url_handler));
    handler->type         = *type;
    handler->func         = func;
    handler->page         = page; 
    handler->add_forum_id = add_forum_id ? 1 : 0;
    handler->add_get_vars = add_get_vars ? 1 : 0;

    if (zend_hash_index_update(&phorum_get_url_handlers, *type, (void *)&handler, sizeof(void *), NULL) == SUCCESS) {
    }
}

