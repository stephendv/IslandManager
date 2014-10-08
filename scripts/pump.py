#!/usr/bin/env python

import sys
import serial
from time import sleep
from math import log

devid = "--"                # device id to communicate with
baud = 9600                 # baud rate
port = '/dev/ttyUSB0'       # serial URF port on this computer

ser = serial.Serial(port, baud)
ser.timeout = 0

#-----------------

def request(device, request, retry):
    # sends a message to 'device' with content 'request'
    # returns 'response' from from device
    # retries a number of times, pausing longer between retries each time round
    
    poll = 1
    n = 0
    while (poll == 1 and n < retry):
        sleep(n)            # sleep longer each time I don't get a response
        ser.flushInput()    # clear input buffer
        ser.write('a' + device + request)  # see if our device is online
        sleep(1)            # delay before picking up response
        response = getresponse(devid)
        if len(response) > 1:
            response = response[3:12]
            poll = 0
        n = n + 1
    return(response)

#---------------
def getresponse(devid):     # obtain a llap message from devid
    if ser.inWaiting() >= 12:
        if ser.read() == 'a':  # llap message start
                message = 'a'
                if ser.read(2) == devid:
                            # response is from the targetted node
                    message = message + devid + ser.read(9)
                else:
                    message = '0'   # not a message from devid
        else:
                message = '1'   # not a llap formatted message
    else:
        message = '2'
    return(message)

#----------------

def getstarted(devid):      # wait for the STARTED message from devid
    t = 1
    while t == 1 :
        if ser.inWaiting() >=12:
            if ser.read() == 'a':   # llap message start
                message = 'a'
                if ser.read(2) == devid:    # message is from our device
                    if ser.read(9) == 'STARTED--':  # devid has started
                        t = 0
        ser.flushInput()
        sleep(1)
    return()

#----------------
def Thermistor(ANA):        # calculate the temperature from an ADC value
    beta = 3977             # beta value for the thermistor
    Rtemp = 25.0 + 273.15   # reference temperature (25C)
    Rresi = 10150           # reference resistance at reference temperature - adjust to calibrate
    Rtherm = (2048.0/ANA - 1)*10000  # value of the resistance of the thermistor
    T = Rtemp * beta / (beta + Rtemp * (log(Rtherm/Rresi)))
    T = T - 273.15          # convert from Kelvin to Celsius
    return(T)

#-----------------


def thermostat(tempd):
    # Thermostatic control program using Generic IO firmware on the XRF
    #
    # Attempt to communicate with our device
    # first send a HELLO request to see if the device is online.
    # if there is no suitable response, sit and wait for a STARTED message
    # once there is a response, ensure it is a device that supports Generic IO
    # then read the thermistor raw ADC value, convert it to Celcius
    # send LLAP message on behalf of the generic IO sensor (as if it was the temp sensor)
    # proceed to act as a thermostat and heater control

    reportfreq = 10     # seconds between readings from device
    
    # seek a connection with the device
    response = request(devid,'HELLO----',1)
    if response[0:5] != 'HELLO':
        getstarted(devid)       # wait for device to announce itself with STARTED message

    # check the firmware is Generic IO
    response = request(devid, 'DEVTYPE--',1)
    if response[0:3] != 'RLY':  # we are not dealing with a generic device
        print 'INVALID DEVICE'
        exit()

    # read the raw ADC value at Analog A and convert to temperature
    # do this every reportfreq seconds
    # finish after p readings for now
    p = 5
    n = 0
    heater = 0
    request(devid, 'OUTA0----',1)   # ensure heating is off
    while n < p: 
        response = request(devid,'ANAA-----',1)
        if response[0:4] == 'ANAA':
            ANA = float(response[-4:])
            TempA = Thermistor(ANA)   # convert ADC value to temperature
            if (TempA >= tempd and heater == 1):
                request(devid, 'OUTA0----',1)   # turn heating off
                heater = 0
            if (TempA < tempd and heater == 0):
                request(devid, 'OUTA1----',1)   # turn heating on
                heater = 1
            print '%.2f' %TempA + ' degrees C'
            # and maybe send the proper llap message on behalf of the node
            ser.write('a' + devid + 'TMPA' + '%.2f' %TempA)
        else:   # there was an error in the message
            print 'no or invalid response to ANAA'
        sleep(reportfreq)
        n = n + 1
    request(devid, 'OUTA0----',1)   # ensure heating is off when exiting
    heater = 0
    print 'END'

def relay(relayId,state):
	pad = ''
        for x in range(0, 3 - len(state)):
		pad = pad + '-'		
	cmd = 'RELAY'+relayId+state+pad
	request(devid, cmd,1)

def relayState(relayId):
	return request(devid, 'RELAY'+ relayId +'---',1)

def pumpOn():
	relay('B','ON')

def pumpOff():
	relay('B','OFF')
	
def pumpOnForSeconds(numSeconds):
	pumpOn()
	sleep(numSeconds)
	pumpOff()

# This program is run from the command line like this: > python thermostat.py 21.5
# If you want to run this program from the Python shell, uncomment the previous line
# and comment out the lines below to the end of the file

if __name__ == "__main__":   # run the program from the command line
   import sys
   response = request(devid,'HELLO----',1)
   print "hello response: " + response 
   if response[0:5] != 'HELLO':
       getstarted(devid)       # wait for device to announce itself with STARTED message

   # check the firmware is Generic IO
   response = request(devid, 'DEVTYPE--',1)
   if response[0:3] != 'RLY':  # we are not dealing with a generic device
       print 'INVALID DEVICE'
       print response
       exit()
   pumpOnForSeconds(float(sys.argv[1]))
   print relayState('B')
