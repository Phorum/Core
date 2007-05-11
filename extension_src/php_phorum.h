#ifndef PHP_PHORUM_H
#define PHP_PHORUM_H 1

#define PHP_PHORUM_VERSION "1.0"
#define PHP_PHORUM_EXTNAME "phorum"

PHP_MINFO_FUNCTION(phorum);

PHP_FUNCTION(phorum_ext_treesort);
PHP_FUNCTION(phorum_ext_get_url);

extern zend_module_entry phorum_module_entry;

#define phpext_phorum_ptr &phorum_module_entry

#endif
