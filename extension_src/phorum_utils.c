#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"
#include "phorum_utils.h"

long
get_constant_long(char *key)
{
    zval  zkey;
    zval  zvalue;

    ZVAL_STRING(&zkey, key, 0);

    if (!zend_get_constant(Z_STRVAL(zkey), Z_STRLEN(zkey), &zvalue TSRMLS_CC)) {
        zend_error(E_ERROR, "get_constant_long(): Cannot find symbol %s, is this function called inside an initialized Phorum environment?", key);
    }

    convert_to_long(&zvalue);
    return Z_LVAL(zvalue);
}

char *
get_constant_string(char *key)
{
    zval  zkey;
    zval  zvalue;

    ZVAL_STRING(&zkey, key, 0);

    if (!zend_get_constant(Z_STRVAL(zkey), Z_STRLEN(zkey), &zvalue TSRMLS_CC)) {
        zend_error(E_ERROR, "get_constant_long(): Cannot find symbol %s, is this function called inside an initialized Phorum environment?", key);
    }

    convert_to_string(&zvalue);
    return Z_STRVAL(zvalue);
}

/* Lookup a key from the global $PHORUM array. */
zval *
get_PHORUM(char *key)
{
    HashTable *PHORUM = NULL;

    zval **P;

    /* Lookup the global $PHORUM variable. */
    if (zend_hash_find(&EG(symbol_table), "PHORUM", sizeof("PHORUM"), (void**)&P) == FAILURE) {
        zend_error(E_ERROR, "get_PHORUM(): Cannot find symbol $PHORUM, is this function called inside an initialized Phorum environment?");
    }
    PHORUM = Z_ARRVAL_PP(P);

    /* Lookup the key in $PHORUM. */
    if (zend_hash_find(PHORUM, key, strlen(key)+1, (void**)&P) == FAILURE) {
        return NULL;
    }

    return *P;
}

char *
get_PHORUM_string(char *key) {
    zval *P = get_PHORUM(key);
    if (P == NULL) {
        zend_error(E_ERROR, "PHORUM(): Cannot find symbol $PHORUM[%s]", key);
    }
    convert_to_string(P);
    return Z_STRVAL_P(P);
}

long
get_PHORUM_long(char *key) {
    zval *P = get_PHORUM(key);
    if (P == NULL) {
        zend_error(E_ERROR, "PHORUM(): Cannot find symbol $PHORUM[%s]", key);
    }
    convert_to_long(P);
    return Z_LVAL_P(P);
}

/* Lookup a key from the $PHORUM["args"] array. */
zval *
get_PHORUM_args(char *key)
{
    HashTable *args = NULL;
    zval   zkey;
    zval **P;

    /* Lookup the $PHORUM["args"] variable. */
    zval *A = get_PHORUM("args");
    if (A == NULL) {
        zend_error(E_ERROR, "PHORUM(): Cannot find symbol $PHORUM[args]");
    }
    args = Z_ARRVAL_P(A);

    /* Lookup the key in the args table. */
    ZVAL_STRING(&zkey, key, 1);
    if (zend_hash_find(args, Z_STRVAL(zkey), Z_STRLEN(zkey)+1, (void**)&P) == FAILURE) {
        convert_to_long(&zkey);
        if (zend_hash_index_find(args, Z_LVAL(zkey), (void**)&P) == FAILURE) {
          return NULL;
        }
    }

    return *P;
}

/* Lookup a key from the $PHORUM["DATA"] array. */
zval *
get_PHORUM_DATA(char *key)
{
    HashTable *data = NULL;
    zval   zkey;
    zval **P;

    /* Lookup the $PHORUM["DATA"] variable. If there is no such variable,
     * then return NULL. */
    zval *A = get_PHORUM("DATA");
    if (A == NULL) return NULL;
    data = Z_ARRVAL_P(A);

    /* Lookup the key in the DATA table. */
    ZVAL_STRING(&zkey, key, 1);
    if (zend_hash_find(data, Z_STRVAL(zkey), Z_STRLEN(zkey)+1, (void**)&P) == FAILURE) {
        convert_to_long(&zkey);
        if (zend_hash_index_find(data, Z_LVAL(zkey), (void**)&P) == FAILURE) {
          return NULL;
        }
    }

    return *P;
}
