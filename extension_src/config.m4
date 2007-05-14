PHP_ARG_ENABLE(phorum, whether to enable Phorum support,
[  --enable-phorum         Enable Phorum support], no)

if test "$PHP_PHORUM" = "yes"; then
  AC_DEFINE(HAVE_PHORUM, 1, [Has Phorum extension])

  EXT_PHORUM_SOURCES="\
      phorum.c \
      phorum_utils.c \
      phorum_get_url.c \
      phorum_ext_treesort.c";

  PHP_NEW_EXTENSION(phorum, $EXT_PHORUM_SOURCES, $ext_shared)
fi

