# --- macros
CC=gcc
CFLAGS= -O3 -I../include/ -I../smalib -I../libs -I../projects/generic-cmake/incprj -I../os -I../core -I../protocol -Imysql_incl -DBIG_JOINS=1 -fno-strict-aliasing -DUNIV_LINUX -g 
LIBS= -Wl,-Bsymbolic-functions -rdynamic -lyasdimaster /usr/lib/libmysqlclient.so.16
SOURCES=db.c util.c logger.c
OBJECTS=$(SOURCES:.c=.o)
EXECUTABLE=logger


# --- targets
all:    $(SOURCES) $(EXECUTABLE)

$(EXECUTABLE): $(OBJECTS) 
	$(CC) -o $@ $(OBJECTS) $(LIBS)
        
.c.o:
	$(CC) $(CFLAGS) $< -c -o $@

# --- remove binary and executable files
clean:
	rm -f $(EXECUTABLE) $(OBJECTS)

