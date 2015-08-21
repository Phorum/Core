FILES="./include/api.php,./include/db/mysql.php"
DIRECTORIES="./include/api"
IGNORE="compile.php"

all:
    (cd ../../..; phpdoc \
           -f $(FILES) \
           -d $(DIRECTORIES) \
           -i $(IGNORE) \
           -t docs/html/api \
           -ti "Phorum API Documentation" \
           -o HTML:frames:phphtmllib \
         -dn PhorumAPI)

    cat index.html | sed -e 's/120,\*/90,\*/' -e 's/25%,\*/320,\*/' > index2.html
    mv index2.html index.html

clean:
    /bin/rm -f *.html
    /bin/rm -fR media
    /bin/rm -fR docs
    /bin/rm -fR PhorumAPI
    /bin/rm -fR PhorumDBLayer
    /bin/rm -fR __examplesource

