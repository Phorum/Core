all:
	(cd ../docbook/developer; ./mkhookdocs)
	(cd user; make)
	(cd admin; make)
	(cd developer/api; make)
	(cd developer; make)

clean:
	(cd user; make clean)
	(cd admin; make clean)
	(cd developer/api; make clean)
	(cd developer; make clean)
	
