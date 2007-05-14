typedef struct url_arg {
    char             *str;
    long              length;
    void             *next;
} url_arg;

typedef char *(url_handler_func)(void *, void *, int, zval ***);

typedef struct url_info {
    url_arg          *arg_first;
    url_arg          *arg_last;
    int               add_forum_id;
    int               add_get_vars;
    int               add_slash;
    int               arg_count;
    int               arg_length;
    int               url_length;
    char             *page;
} url_info;

typedef struct url_handler {
    char             *typename;
    long              type;
    url_handler_func *func;
    char             *page;
    int               add_forum_id;
    int               add_get_vars;
} url_handler;

static HashTable url_handlers;
static int get_url_initialized = 0;

url_arg *format_url_arg (char *, ...);
void     append_url_arg(url_info **, url_arg *);
void     register_url_handler (char *, url_handler_func *, char *, int, int);

void     default_url_build (void *, void *, int, zval ***);
char    *default_url_format (void *);

char    *basic_url   (void *, void *, int, zval ***);
char    *list_url    (void *, void *, int, zval ***);
char    *read_url    (void *, void *, int, zval ***);
char    *feed_url    (void *, void *, int, zval ***);
char    *prepost_url (void *, void *, int, zval ***);

/* For _phorum_register_url_handler() calls. */
#define FORUM_ID     1
#define NO_FORUM_ID  0
#define GET_VARS     1
#define NO_GET_VARS  0

