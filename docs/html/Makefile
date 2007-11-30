# Can be overridden using:
# make TPLSET=.....
TEMPLATE=default

all:
	(cd ../docbook/developer; ./mkhookdocs)
	(cd user; make)
	(cd admin; make)
	(cd developer; make)
	(cd api; make)
	(cd faq; make)
	(cd db; make)
	cp index.html-$(TEMPLATE) index.html
	cp navigation.html-$(TEMPLATE) navigation.html

template:
	cp index.html-$(TEMPLATE) index.html
	cp navigation.html-$(TEMPLATE) navigation.html

clean:
	(cd user; make clean)
	(cd admin; make clean)
	(cd developer; make clean)
	(cd api; make clean)
	(cd faq; make clean)
	(cd db; make clean)

