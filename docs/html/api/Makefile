DIRECTORIES="include/api"
#FILES="include/api/ban.php,include/api/buffer.php,include/api/constants.php,include/api/custom_field.php,include/api/custom_profile_fields.php,include/api/diff.php,include/api/feed.php,include/api/file.php,include/api/format.php,include/api/forums.php,include/api/generate.php,include/api/http_get.php,include/api/image.php,include/api/json.php,include/api/mail.php,include/api/message.php,include/api/modules.php,include/api/newflags.php,include/api/output.php,include/api/profiler.php,include/api/redirect.php,include/api/tree.php,include/api/url.php,include/api/user.php,include/api/write_file.php"


all:
	(cd ../../..; phpdoc \
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

