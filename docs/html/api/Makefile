FILES="include/api.php"
DIRECTORIES="include/api"

all:
	(cd ../../..; phpdoc \
	       -f $(FILES) \
	       -d $(DIRECTORIES) \
         -i include/api/examples/file_delete.php,include/api/examples/file_store.php,include/api/examples/user_auth_module.php,include/api/examples/user_login.php \
	       -t docs/html/api \
	       -ti "Phorum API Documentation" \
	       -o HTML:frames:phphtmllib -dn PhorumAPI)
	cp index.html.custom index.html

clean:
	/bin/rm -f *.html
	/bin/rm -fR media
	/bin/rm -fR docs
	/bin/rm -fR PhorumAPI
	/bin/rm -fR __examplesource

