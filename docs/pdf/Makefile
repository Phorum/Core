all:
	(cd ../docbook/developer; ./mkhookdocs)
	(dblatex -T simple -o admin.pdf ../docbook/admin/index.xml)
	(dblatex -T simple -o developer.pdf ../docbook/developer/index.xml)
	(dblatex -T simple -o faq.pdf ../docbook/faq/index.xml)
	(dblatex -T simple -o user.pdf ../docbook/user/index.xml)

clean:
	(rm admin.pdf)
	(rm developer.pdf)
	(rm faq.pdf)
	(rm user.pdf)

