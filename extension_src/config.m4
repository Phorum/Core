PHP_ARG_ENABLE(phorum, whether to enable Phorum support,
[ --enable-phorum   Enable Phorum support])

if test "$PHP_PHORUM" = "yes"; then
  AC_DEFINE(HAVE_PHORUM, 1, [Whether you have Phorum])
  PHP_NEW_EXTENSION(phorum, phorum.c, $ext_shared)
fi

