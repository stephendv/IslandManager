
#ifndef __util_h__
#define __util_h__

#define LEVEL_DEBUG 0
#define LEVEL_DETAIL 1
#define LEVEL_INFO 2
#define LEVEL_IMPORTANT 3
#define LEVEL_WARNING 4
#define LEVEL_ERROR 5
#define LEVEL_FATAL 6

/**************************************************************************
initLog
**************************************************************************/
int initLog(int loggingLevel, FILE * logfile);

/**************************************************************************
printLog
**************************************************************************/
int printLog(int level, const char *format, ...);

/**************************************************************************
lightLog - doesn't add date before given string. Just adds result to the log
**************************************************************************/
int lightLog(int level, const char *format, ...);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the end of the given string
 * The string is modified and returned
 */
char* trimEnd (char* str);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the start of the given string
 * The string is modified and returned
 */
char* trimStart (char* str);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the both ends of the given string
 * The string is modified and returned
 */
char* trim (char* str);

#endif

