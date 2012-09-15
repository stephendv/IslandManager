
#ifndef __db_h__
#define __db_h__

#include <my_global.h>
#include <mysql.h>

#include "consts.h"

void dbInit(char * iniFilename, int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char dbTypes[][MAX_COLUMN_TYPE_LEN]);

int insertValues(int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char channelValues[][MAX_CHANNEL_VALUE_LEN]);

void dbShutdown();

#endif

