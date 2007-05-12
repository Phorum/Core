typedef struct treenode {
    long  id;          /* The id of the node. */
    long  parent_id;   /* The parent id of the node; 0 (zero) for root node. */
    long  indent_lvl;  /* The indent level of the node. */
    void *prev;        /* Previous node in linear mode. */
    void *next;        /* Next node in linear mode. */
    void *parent;      /* Parent node in tree mode. */
    void *child_first; /* First child in tree mode. */
    void *child_last;  /* Last child in tree mode. */
    void *sibling;     /* Next sibling child. */
    int   seen;        /* Keep track if the node was seen during tree walk. */
    HashPosition hp;   /* The Zend hash position. */
} treenode;

typedef struct tree {
    treenode *node_first;
    treenode *node_last;
} tree;

