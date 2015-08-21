# This Makefile can be used to build the Phorum JavaScript library.

TARGET=../phorum-javascript-library.php

all: $(TARGET)

$(TARGET): *.src Makefile
    @rm $(TARGET)
    @touch $(TARGET)
    @cat *.src >> $(TARGET)
    @echo Compiled $(TARGET)

test: $(TARGET)
    @for F in `ls *src | grep -v 001.base.src`; do ./jslint_wrapper $$F; done | cat

