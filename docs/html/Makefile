XSLTPROC=xsltproc
XSLTPROCOPTS=\
    --novalid \
    --nonet \
    --stringparam toc.section.depth 3 \
    --stringparam section.autolabel 1 \
    --stringparam section.label.includes.component.label 1 \
    --stringparam html.stylesheet docbook.css
XSLT=/usr/local/xml/docbook-xsl/html/onechunk.xsl

all:
	$(XSLTPROC) $(XSLTPROCOPTS) $(XSLT) ../docbook/index.xml

