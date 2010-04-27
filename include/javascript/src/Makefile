# This Makefile can be used to build the Phorum JavaScript library.

TARGET=../phorum-javascript-library.php

all: $(TARGET)

$(TARGET): *.src
	rm $(TARGET)
	touch $(TARGET)
	cat *.src >> $(TARGET)

