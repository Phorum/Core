/* Phorum Extension utility functions */

/* Retrieve $PHORUM[...] data. */
zval *get_PHORUM(char *);
char *get_PHORUM_string(char *key);
long  get_PHORUM_long(char *key);

/* Retrieve $PHORUM[args][...] data. */
zval *get_PHORUM_args(char *key);

/* Retrieve $PHORUM[DATA][...] data. */
zval *get_PHORUM_DATA(char *key);

/* Retrieve defined constants. */
long  get_constant_long(char *);
char *get_constant_string(char *);

