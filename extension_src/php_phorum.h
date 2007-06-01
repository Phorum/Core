#include "phorum_ext_treesort.h"
#include "phorum_get_url.h"
#include "phorum_utils.h" 
#include "phorum_constants.h"

#ifndef PHP_PHORUM_H
#define PHP_PHORUM_H 1

#define PHP_PHORUM_VERSION "20070522"
#define PHP_PHORUM_EXTNAME "phorum"

ZEND_BEGIN_MODULE_GLOBALS(phorum)
    HashTable url_handlers;
ZEND_END_MODULE_GLOBALS(phorum)

ZEND_DECLARE_MODULE_GLOBALS(phorum)

#ifdef ZTS
#define PHORUMG(v) TSRMG(phorum_globals_id, zend_phorum_globals*, v)
#else
#define PHORUMG(v) (phorum_globals.v)
#endif

PHP_MINIT_FUNCTION(phorum);
PHP_MSHUTDOWN_FUNCTION(phorum);

PHP_MINFO_FUNCTION(phorum);

PHP_FUNCTION(phorum_ext_version);
PHP_FUNCTION(phorum_ext_treesort);
PHP_FUNCTION(phorum_get_url);

extern zend_module_entry phorum_module_entry;

#define phpext_phorum_ptr &phorum_module_entry

#endif
