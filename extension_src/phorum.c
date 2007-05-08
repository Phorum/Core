#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_phorum.h"

static function_entry phorum_functions[] = {
    PHP_FE(phorum_treesort, NULL)
    {NULL, NULL, NULL}
};

zend_module_entry phorum_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    PHP_PHORUM_EXTNAME,
    phorum_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
#if ZEND_MODULE_API_NO >= 20010901
    PHP_PHORUM_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PHORUM
ZEND_GET_MODULE(phorum)
#endif

PHP_FUNCTION(phorum_treesort)
{
    zval            *nodes;
    zval           **data;
    HashTable       *nodes_hash;
    HashPosition     node_pointer;
    long             treepos;
    phorum_tree      tree;
    phorum_treenode *cur_node;
    phorum_treenode *new_node;

    tree.node_first = NULL;
    tree.node_last = NULL;

    /* ================================================================== */
    /* Walk over the array and describe the tree as a linked lists system */
    /* ================================================================== */

    /* Retrieve our function call parameter, which should be an array. */
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &nodes) == FAILURE) {
        RETURN_NULL();
    }
    nodes_hash = Z_ARRVAL_P(nodes);

    /* Walk over the array. */
    for(zend_hash_internal_pointer_reset_ex(nodes_hash, &node_pointer);
        zend_hash_get_current_data_ex(nodes_hash, (void**) &data, &node_pointer) == SUCCESS;
        zend_hash_move_forward_ex(nodes_hash, &node_pointer)) {

        zval     **z_parent_id;
        zval     **z_message_id;
        long       parent_id = 0;
        long       message_id = 0;
        int        found = 0;
        zval      *node = *data;
        HashTable *node_hash;

        /* Get the hash table for the current node (nodes are arrays too). */
        if (Z_TYPE_P(*data) != IS_ARRAY) {
            zend_error(E_ERROR, "The nodes array argument contains a non-array element");
        }
        node_hash = Z_ARRVAL_P(node);

        if (zend_hash_find(node_hash, "parent_id", 10, &z_parent_id) == SUCCESS) {
            convert_to_long(*z_parent_id);
            parent_id = Z_LVAL_P(*z_parent_id);
            found ++;
        }

        if (zend_hash_find(node_hash, "message_id", 11, &z_message_id) == SUCCESS) {
            convert_to_long(*z_message_id);
            message_id = Z_LVAL_P(*z_message_id);
            found ++;
        }

        if (found != 2) {
            zend_error(E_ERROR, "One of the nodes does not contain both a \"message_id\" and a \"parent_id\" field.");
        }

        /* Create a new phorum tree node. */
        new_node = (phorum_treenode *)emalloc(sizeof(phorum_treenode));
        if (! new_node) zend_error(E_ERROR, "Out of memory");
        bzero(new_node, sizeof(phorum_treenode));
        new_node->id = message_id;
        new_node->parent_id = parent_id;
        new_node->hp = node_pointer;

        /* Add the node to the tree. */
        /* First node is really easy. */
        if (tree.node_first == NULL) {
            if (new_node->parent_id != 0) {
                zend_error(E_ERROR, "First tree node in the array (id %ld) has parent_id != 0", new_node->id);
            }
            tree.node_first = tree.node_last = new_node;
        }
        /* Follow up nodes require some more work. */
        else
        {
            /* Find the parent node for the new node. */
            for (cur_node = tree.node_first; cur_node; cur_node = cur_node->next) {
                if (cur_node->id == new_node->parent_id) break;
            }
            if (! cur_node) {
                zend_error(E_ERROR, "No parent found for tree node (id %ld)\n", new_node->id);
            }

            new_node->indent = cur_node->indent + 1;

            /* Append the node to the linear list. */
            tree.node_last->next = new_node; 
            new_node->prev = tree.node_last;
            tree.node_last = new_node;

            /* Place the node in the tree. */
            new_node->parent = cur_node;
            if (cur_node->child_first == NULL) {
                cur_node->child_first = cur_node->child_last = new_node;
            }
            else {
                phorum_treenode *n = cur_node->child_last;
                n->sibling = new_node;
                cur_node->child_last = new_node;
            }
        }

        /* Set the indent level in the node. */
        if (add_assoc_long(*data, "indent_cnt", new_node->indent) == FAILURE) {
          // TODO error handling
        }
    }

    /* ================================================== */
    /* Reorder the array buckets to match the tree order. */
    /* ================================================== */

    nodes_hash->pListHead = NULL;
    nodes_hash->pListTail = NULL;
    nodes_hash->pInternalPointer = NULL;

    cur_node = tree.node_first;
    while (cur_node != NULL)
    {
        phorum_treenode *next = NULL;
        phorum_treenode *free = NULL;

        /* If we did not process this node before, then add it 
         * to the resulting sorted tree hashtable.
         */
        if (cur_node->seen == 0)
        {
            /* The first node. */
            if (nodes_hash->pListHead == NULL) {
                nodes_hash->pListHead = cur_node->hp;
                nodes_hash->pListTail = cur_node->hp;
                nodes_hash->pInternalPointer = cur_node->hp;
                cur_node->hp->pListLast = NULL;
                cur_node->hp->pListNext = NULL;
            /* Follow up nodes. */
            } else {
                nodes_hash->pListTail->pListNext = cur_node->hp;
                cur_node->hp->pListLast = nodes_hash->pListTail;
                cur_node->hp->pListNext = NULL;
                nodes_hash->pListTail = cur_node->hp;
            }

            cur_node->seen = 1;
        }

        /* Some tree traversal logic (draw it out on paper if it
         * does not make sense ;-):
         *
         * Go to the first child node of the current node if there is one. */
        if (cur_node->child_first != NULL) {
            next = cur_node->child_first;
            /* Block the child road for when we return here. */
            cur_node->child_first = NULL;
        /* If there's no first child node, then go to our sibling. */
        } else if (cur_node->sibling != NULL) {
            next = cur_node->sibling;
            efree(cur_node); /* we'll never return to this one. */
        /* If there's no first child or sibling, then return to our parent. */
        } else if (cur_node->parent != NULL) {
            next = cur_node->parent;
            efree(cur_node); /* we'll never return to this one. */
        }

        cur_node = next;
    }
}

