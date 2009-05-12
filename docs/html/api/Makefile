FILES="include/api/ban.php,include/api/buffer.php,include/api/custom_fields.php,include/api/diff.php,include/api/file.php,include/api/file_storage.php,include/api/format.php,include/api/forums.php,include/api/generate.php,include/api/http_get.php,include/api/image.php,include/api/json.php,include/api/mail.php,include/api/message.php,include/api/modules.php,include/api/newflags.php,include/api/profiler.php,include/api/redirect.php,include/api/tree.php,include/api/url.php,include/api/user.php,include/api/write_file.php"

all:
	(cd ../../..; phpdoc \
	       -f $(FILES) \
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

