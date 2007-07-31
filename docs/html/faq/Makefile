XSLTPROC=xsltproc
XSLTPROCOPTS=--novalid

all:
	$(XSLTPROC) $(XSLTPROCOPTS) \
		../phorum_docbook.xsl \
		../../docbook/faq/index.xml

clean:
	/bin/rm -f *.html

