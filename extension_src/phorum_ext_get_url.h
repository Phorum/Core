static HashTable phorum_get_url_handlers;

static int phorum_get_url_initialized = 0;

typedef char *(phorum_get_url_handler_func)(void *, int, zval ***);

typedef struct phorum_get_url_handler {
    char                        *typename;
    long                         type;
    phorum_get_url_handler_func *func;
    char                        *page;
    int                          add_forum_id;
    int                          add_get_vars;
} phorum_get_url_handler;


long *_phorum_get_constant(char *);
void  _phorum_register_get_url_handler(char *, phorum_get_url_handler_func *, char *, int, int);

char  *hello_world(void *, int, zval ***);

/* For _phorum_register_get_url_handler() calls. */
#define FORUM_ID     1
#define NO_FORUM_ID  0
#define GET_VARS     1
#define NO_GET_VARS  0

