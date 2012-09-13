/*
 *      MySQL Logger for SMA devices by Vicne
 *
 *      Based on YASDI - Copyright(C) 2001-2008 SMA Solar Technology AG
 *
 */

// TODO : make this a daemon
// TODO : standardize logging (only if -verbose ? only if not daemon ? use stderr or stdout ?)
 
/*************************************************************************
*   I N C L U D E
*************************************************************************/
#include <stdio.h>

#ifdef __cplusplus
extern "C" {
#endif

#include "os.h"
#include "smadef.h"
#include "chandef.h"
#include "libyasdi.h"
#include "libyasdimaster.h"
#include "smadata_layer.h"
#include "tools.h"
#include "db.h"
#include "consts.h"
#include "util.h"

#ifdef __cplusplus
}
#endif

/**************************************************************************
*   G L O B A L
**************************************************************************/

#define MAXDRIVERS 10   // for simplicity, we reserve 10 YASDI Bus drivers

/**************************************************************************
*   S T A T I C
**************************************************************************/

// This is the list of the inverter properties that will be fetched and stored in DB (aka "Channels").
// The values below are based on a SMC4600 inverter.
// You can remove some if you don't need them to keep the DB lighter, but please make sure to edit the 3 arrays consistently.
// Channel names used by the inverter (!case sensitive !)
char channelNames[][MAX_CHANNEL_NAME_LEN] = 
{"Soh","TotInvPwrAt","TotInvCur","BatSoc","BatVtg","BatChrgVtg","AptTmRmg","TotBatCur","BatTmp","RmgTmFul","RmgTmEqu","BatSocErr","BatChrgOp","AptPhs","Error","InvPwrAt","InvVtg","InvFrq","ExtPwrAt","ExtVtg","TotExtCur","ExtFrq","GdRmgTm","GnStt","Rly1Stt","Rly2Stt","GnRnStt","InvCur"};

char dbColumns[][MAX_COLUMN_NAME_LEN] = 
{"Soh","TotInvPwrAt","TotInvCur","BatSoc","BatVtg","BatChrgVtg","AptTmRmg","TotBatCur","BatTmp","RmgTmFul","RmgTmEqu","BatSocErr","BatChrgOp","AptPhs","Error","InvPwrAt","InvVtg","InvFrq","ExtPwrAt","ExtVtg","TotExtCur","ExtFrq","GdRmgTm","GnStt","Rly1Stt","Rly2Stt","GnRnStt","InvCur"};

// Types of the database columns to store the values in
char dbTypes[][MAX_COLUMN_TYPE_LEN] = 
{"FLOAT","DOUBLE","DOUBLE","FLOAT","FLOAT","FLOAT","DOUBLE","DOUBLE","FLOAT","DOUBLE","DOUBLE","FLOAT","VARCHAR(10)","VARCHAR(10)","VARCHAR(20)","FLOAT","FLOAT","FLOAT","FLOAT","FLOAT","FLOAT","FLOAT","DOUBLE","VARCHAR(10)","VARCHAR(5)","VARCHAR(5)","VARCHAR(5)","FLOAT"};

/* maximum age of the channel values, in seconds...*/
DWORD maxChannelValueAge; 

/* interval between loops, in seconds */	
int requestedInterval; 

/**************************************************************************
checkArrayConsistency
**************************************************************************/
int checkArrayConsistency() {
	int channelCount = sizeof(channelNames)/MAX_CHANNEL_NAME_LEN;
	int columnCount = sizeof(dbColumns)/MAX_COLUMN_NAME_LEN;
	int typeCount = sizeof(dbTypes)/MAX_COLUMN_TYPE_LEN;
	int i;

	printLog(LEVEL_DETAIL, "List of requested channels:\n");
    lightLog(LEVEL_DETAIL, "        channel_name -> db_column (db_type)\n");
    lightLog(LEVEL_DETAIL, "------------------------------------------------------------------------------------\n");
	for (i = 0; i < max(channelCount, max(columnCount, typeCount)); i++) {
		lightLog(LEVEL_DETAIL, "%20s -> %s (%s)\n", (i < channelCount ? channelNames[i]:"?"), (i < columnCount ? dbColumns[i]:"?"), (i < typeCount ? dbTypes[i]:"?"));
	}

	// Check size consistency
	if (channelCount != columnCount) { 
		printLog(LEVEL_FATAL, "Error : inconsistent number of channel_names (%d) and db_columns (%d)!\n", channelCount, columnCount);
		exit(-20);
	}
	if (columnCount != typeCount) { 
		printLog(LEVEL_FATAL, "Error : inconsistent number of db_columns (%d) and db_types (%d)!\n", columnCount, typeCount);
		exit(-21);
	}
	
	return channelCount;
}


/**************************************************************************
logValues
**************************************************************************/
BOOL logValues(int deviceCount, DWORD deviceHandles[], int channelCount) {
	int res;

	char channelValues[channelCount][MAX_CHANNEL_VALUE_LEN];
	char chanName[MAX_CHANNEL_NAME_LEN];
	DWORD channelHandles[deviceCount][channelCount]; 
	double value;
	char textValue[MAX_CHANNEL_VALUE_LEN];
	time_t loopStart, dayStart, now;
	double elapsedSecs;
	int deviceNr, channelNr, delay;

	/* init channel handles */
	for (deviceNr = 0; deviceNr < deviceCount; deviceNr++) {
		for (channelNr = 0; channelNr < channelCount; channelNr++) {
			channelHandles[deviceNr][channelNr] = FindChannelName(deviceHandles[deviceNr], channelNames[channelNr]);
			if (channelHandles[deviceNr][channelNr] <= 0) {
				// Throw in some help for debugging purpose...
				DWORD chanHandle[300]; 
				char deviceName[50];
				char cUnit[17];
				int realChannelNr;

				printLog(LEVEL_FATAL, "Error : cannot determine handle of channel name '%s' for device %d\n", channelNames[channelNr], deviceHandles[deviceNr]);
				int realChannelCount = GetChannelHandlesEx(deviceHandles[deviceNr], chanHandle, 300, SPOTCHANNELS);
				GetDeviceName(deviceHandles[deviceNr], deviceName, sizeof(deviceName)-1);
				lightLog(LEVEL_ERROR, "Here are the %d available channels for device '%s':\n", realChannelCount, deviceName);
				lightLog(LEVEL_ERROR, "---------------------------------------\n");
				lightLog(LEVEL_ERROR, "|   Channel name   |   Channel Unit   |\n");
				lightLog(LEVEL_ERROR, "---------------------------------------\n");
				for(realChannelNr = 0; realChannelNr < realChannelCount; realChannelNr++)
				{
					GetChannelName(chanHandle[realChannelNr], chanName, sizeof(chanName)-1);
					cUnit[0]=0;
					GetChannelUnit(chanHandle[realChannelNr], cUnit, sizeof(cUnit)-1);
					lightLog(LEVEL_ERROR, "| %16s | %-16s |\n", chanName, cUnit);
				}
				lightLog(LEVEL_ERROR, "---------------------------------------\n");

				exit(-50);
			}
			GetChannelName(channelHandles[deviceNr][channelNr], chanName, sizeof(chanName)-1);
			printLog(LEVEL_DETAIL, "Channel '%s' has handle %d on device %d\n", channelNames[channelNr], channelHandles[deviceNr][channelNr], deviceHandles[deviceNr]);
		}
	}

	// TODO : add a way to exit gracefully
	BOOL loop = true;
	BOOL readOK = true;
	
	// Determine first schedule
	now = time(NULL);
	struct tm *cal = localtime(&now);
	cal->tm_hour = 0;
	cal->tm_min = 0;
	cal->tm_sec = 0;
	dayStart = mktime(cal);
	elapsedSecs = difftime(now, dayStart);

	delay = requestedInterval - ((int)(elapsedSecs) % requestedInterval); 
	printLog(LEVEL_INFO, "Waiting for %d seconds...\n", delay);
	// Sleep a bit now.	
	sleep (delay);

	while (loop) {
		loopStart = time(NULL);
		
		// Iterate on devices
		for (deviceNr = 0; deviceNr < deviceCount; deviceNr++) {

            // Iterate on channels for this device
			for (channelNr=0; channelNr<channelCount; channelNr++) {
				res = GetChannelValue(channelHandles[deviceNr][channelNr], deviceHandles[deviceNr], &value, textValue, sizeof(textValue)-1, maxChannelValueAge);
				if (res == 0) {
					if (strlen(textValue) == 0) {
						sprintf(textValue, "%.6f", value);
					}
				}
				else {                    
                    // means connection with at least one device was lost. 
                    if (res != -3) {
                        printLog(LEVEL_WARNING, "Error reading channel value....error code=%d\n", res);
                    }
                    readOK = false;
                    loop = false;
					break;
				}
				strcpy(channelValues[channelNr], textValue);
			}
			
			if (readOK) {
				// Insert row
				insertValues(channelCount, dbColumns, channelValues);
				lightLog(LEVEL_INFO, ".");
			}
		}

		if (loop) {
            // determine processing duration to schedule next loop turn
            now = time(NULL);
            elapsedSecs = difftime(now, loopStart);

            if (elapsedSecs < requestedInterval) {
                // Compute theoretic start of next loop
                struct tm *cal = localtime(&loopStart);
                cal->tm_sec += requestedInterval;
                loopStart = mktime(cal);
                delay = requestedInterval - elapsedSecs;

                // Must sleep a bit now.
                sleep(delay);
            }
            else {
                printLog(LEVEL_WARNING, "Warning : Processing took %f seconds. Can't keep up with the %d seconds requested interval. Please choose a higher value.\n", elapsedSecs, requestedInterval);
                loopStart = time(NULL);
            }
        }
	}
    return readOK;
}

/**************************************************************************
* startDeviceLogging
* Start synchronous device detection (blocks until device detection is done)
* then launches logging
**************************************************************************/
void startDeviceLogging(int deviceCount, DWORD deviceHandles[], int channelCount) {
	int iErrorCode, deviceNr;
	char nameBuffer[50];

	printLog(LEVEL_INFO, "Starting detection of %d device(s). Please wait...\n", deviceCount);

	/* start searching for devices... */
	BOOL deviceOK = false;
	while (!deviceOK) {
		iErrorCode = DoStartDeviceDetection(deviceCount, TRUE /*blocking*/ );
		switch(iErrorCode) {
			case YE_NOT_ALL_DEVS_FOUND:
				lightLog(LEVEL_INFO, "x");
				sleep(requestedInterval);
				deviceOK = false;	
				break;
			 
			case YE_DEV_DETECT_IN_PROGRESS:
				printLog(LEVEL_FATAL, "\nError: there is already an running device detection.\n");
				exit(-40);
		  
			case YE_OK:
				deviceOK = true;	
				break;
			 
			default:
				printLog(LEVEL_FATAL, "\nUnknown error : %d\n", iErrorCode);
				exit(-41);
		}
        
        if (deviceOK) {
			lightLog(LEVEL_INFO, "\n");
            /* get all device handles...*/
            DWORD devCount = GetDeviceHandles(deviceHandles, deviceCount);
                
            if (devCount != deviceCount) {
                printLog(LEVEL_FATAL, "Error : GetDeviceHandles returned %d device(s) while we expect %d\n", devCount, deviceCount);
                exit(-42);
            }

            // Debug output
            lightLog(LEVEL_DEBUG, "-------------------------------------------\n");
            lightLog(LEVEL_DEBUG, "Device handle | Device Name  \n");
            lightLog(LEVEL_DEBUG, "-------------------------------------------\n");
            for (deviceNr = 0; deviceNr < deviceCount; deviceNr++) {
                GetDeviceName(deviceHandles[deviceNr], nameBuffer, sizeof(nameBuffer)-1);
                lightLog(LEVEL_DEBUG, "   %3lu        | '%s'\n", (unsigned long)deviceHandles[deviceNr], nameBuffer);
            }
            lightLog(LEVEL_DEBUG, "-------------------------------------------\n\n");
            
            deviceOK = logValues(deviceCount, deviceHandles, channelCount);
        }
    }
}

/**************************************************************************
main
**************************************************************************/
int main(int argv, char **argc)
{
	int driverCount, driverNr;
	DWORD drivers[MAXDRIVERS]; 
	char driverName[30];
	BOOL bOnDriverOnline = FALSE; //Is at least one driver online?
	char iniFile[]="/home/pi/yasdi/islandlogger/yasdi.ini";	
    int logLevel;

  	// Read config params
	logLevel = GetPrivateProfileInt_("Log", "level", 3, iniFile);
	requestedInterval = GetPrivateProfileInt_("Params", "requestedInterval", 300, iniFile);
    maxChannelValueAge = GetPrivateProfileInt_("Params", "maxChannelValueAge", 5, iniFile);

    // TODO make log destination a config param (detect stdout & stderr as constants, everything else is a path of a file to open)
    initLog(logLevel, stdout);
    printLog(LEVEL_DETAIL, "SMySqLogger was started\n");

	/* init Yasdi- and Yasdi-Master-Library */
	if (0 > yasdiMasterInitialize(iniFile, &driverCount)) {
		printLog(LEVEL_FATAL, "ERROR: ini file '%s' was not found or is unreadable!\n", iniFile);
		exit(-1);
	}

	/* get List of all supported drivers...*/
	driverCount = yasdiMasterGetDriver(drivers, MAXDRIVERS);

	/* Switch all drivers online (you should only do one of them online!)...*/
	for(driverNr = 0; driverNr < driverCount; driverNr++) {
		/* The name of the driver */
		yasdiGetDriverName(drivers[driverNr], driverName, sizeof(driverName));

		printLog(LEVEL_INFO, "Switching on driver '%s'... ", driverName);

		if (yasdiSetDriverOnline(drivers[driverNr])) {
			lightLog(LEVEL_INFO, "Done\n");
			bOnDriverOnline = TRUE;
		}
		else {
			lightLog(LEVEL_FATAL, "Failure ! Please check config file\n");
			exit(-2);
		}
	}
	lightLog(LEVEL_INFO, "\n");
   
	//Check that at least one driver is online...
	if (FALSE == bOnDriverOnline) {
		printLog(LEVEL_FATAL, "Error: No drivers are online! YASDI can't communicate with devices!\n");
		exit(-3);
	}
	
	// Check arrays consistency 
	int channelCount = checkArrayConsistency();
	
	// Init DB
	dbInit(iniFile, channelCount, dbColumns, dbTypes);

	// Autodetect devices when started
    int deviceCount = GetPrivateProfileInt_("Plant", "deviceCount", -1, iniFile);
	if (deviceCount == -1) {
		printLog(LEVEL_FATAL, "Error: 'deviceCount=xx' must be specified under section '[Plant]' of %s !\n", iniFile);
		exit(-4);
	}

   	DWORD deviceHandles[deviceCount];

	/* Detect devices and start logging */
	startDeviceLogging(deviceCount, deviceHandles, channelCount);
	
	/* Shutdown all yasdi drivers... */
	for (driverNr = 0; driverNr < driverCount; driverNr++) {
		yasdiGetDriverName(drivers[driverNr], driverName, sizeof(driverName));
		printLog(LEVEL_INFO, "Switching off driver '%s'...\n", driverName);
		yasdiSetDriverOffline(drivers[driverNr]);
	}

	/* Shutdown DB */
	dbShutdown();

	/* Shutdown YASDI..., bye, bye */
	yasdiMasterShutdown();
	return 0;
}
