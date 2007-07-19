FILES="include/db/mysql.php"

all:
	(cd ../../..; phpdoc \
	       -f $(FILES) \
	       -t docs/html/db \
	       -ti "Phorum DB Layer Documentation" \
	       -o HTML:frames:phphtmllib -dn PhorumDBLayer)
	cp index.html.custom index.html

clean:
	/bin/rm -f *.html
	/bin/rm -fR media
	/bin/rm -fR docs
	/bin/rm -fR PhorumDBLayer/
	/bin/rm -fR __examplesource

