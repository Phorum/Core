all:
	(cd ../docbook/developer; ./mkhookdocs)
	(dblatex -T simple -o admin.pdf -x --novalid ../docbook/admin/index.xml)
	(dblatex -T simple -o developer.pdf -x --novalid ../docbook/developer/index.xml)
	(dblatex -T simple -o faq.pdf -x --novalid ../docbook/faq/index.xml)
	(dblatex -T simple -o user.pdf -x --novalid ../docbook/user/index.xml)

clean:
	(rm admin.pdf)
	(rm developer.pdf)
	(rm faq.pdf)
	(rm user.pdf)

