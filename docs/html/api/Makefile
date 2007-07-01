FILES="include/api/file_storage.php,include/api/custom_profile_fields.php,include/api/base.php,include/api/user.php"

all:
	(cd ../../..; phpdoc \
	       -f $(FILES) \
	       -t docs/html/api \
	       -ti "Phorum API Documentation" \
	       -o HTML:frames:DOM/earthli -dn PhorumAPI)

clean:
	/bin/rm -f *.html
	/bin/rm -fR media

