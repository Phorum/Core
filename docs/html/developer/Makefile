XSLTPROC=xsltproc
XSLTPROCOPTS=--novalid

all:
	$(XSLTPROC) $(XSLTPROCOPTS) \
		../phorum_docbook.xsl \
		../../docbook/developer/index.xml

clean:
	/bin/rm -f *.html

