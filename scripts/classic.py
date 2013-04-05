#!/usr/bin/python

from pymodbus.client.sync import ModbusTcpClient
import sys
import logging
import time

logging.basicConfig()
log = logging.getLogger()
log.setLevel(logging.DEBUG)

SERIAL1 = 0                 # The first part of the serial number
SERIAL2 = 1334              # The second part of the serial number
HOST = '192.168.0.16'

client = ModbusTcpClient(HOST)

def connect():
	global client
	connected = 0 
	count = 0
	while (not connected):
		resp = client.connect()
		if (resp == False):
			time.sleep(10)
		else:
			connected = 1 
		count = count + 1

def close():
	global client
	client.close()
	
def unlock(serial1,serial2):
	global client
	rq = client.write_registers(20491, [serial1,serial2])
	assert(rq.function_code < 0x80) 

def forceeq(volts, delay):
	global client
	print "Forcing EQ"
	rq = client.write_register(4150,int(volts*10))
	assert(rq.function_code < 0x80) 
	rq = client.write_register(4161,delay)
	assert(rq.function_code < 0x80) 
	rq = client.write_register(4159,0x80)
	assert(rq.function_code < 0x80) 

def limitCurrent(current):
	global client
	print "Limiting current to: "+str(current)
	rq = client.write_register(4147,int(current*10))
	assert(rq.function_code < 0x80) 

def readAll():
        global client
	base = 4147
        rq = client.read_holding_registers(base,20)
        assert(rq.function_code < 0x80)
        print "EQ time (s):\t" +  str(rq.registers[4161-base])
        print "EQ V (/10):\t" +  str(rq.registers[4150-base])
        print "Current limit (/10):\t" +  str(rq.registers[4147-base])

def forcefloat():
	print "Forcing float"
	global client
	rq = client.write_register(4159,0x20)
	assert(rq.function_code < 0x80) 

def forcebulk():
	print "Forcing bulk"
	global client
	rq = client.write_register(4159,0x40)
	assert(rq.function_code < 0x80) 

def pulsedEQ(volts, pulseLength, delay, count):
	print "Pulsing EQ"
	global client
	for i in range(1,count):
		connect()
		unlock(SERIAL1, SERIAL2)
		limitCurrent(31)
		forceeq(volts, pulseLength)
		close()
		time.sleep(delay) 
	connect()
	unlock(SERIAL1, SERIAL2)
	limitCurrent(80)
	forcebulk()
	close()

def main(argv):
	global client		
	global SERIAL1
	global SERIAL2

	if (sys.argv[1] == 'forceeq'): 
		connect()
		unlock(SERIAL1, SERIAL2)
		forceeq(float(sys.argv[2]), int(sys.argv[3]))
		close()

	if (sys.argv[1] == 'forcefloat'): 
		connect()
		unlock(SERIAL1, SERIAL2)
		forcefloat()
		close()

	if (sys.argv[1] == 'forcebulk'): 
		connect()
		unlock(SERIAL1, SERIAL2)
		forcebulk()
		close()
	
	if (sys.argv[1] == 'limit'): 
		connect()
		unlock(SERIAL1, SERIAL2)
		limitCurrent(int(sys.argv[2]))
		close()

	if (sys.argv[1] == 'pulseeq'): 
	  	pulsedEQ(float(sys.argv[2]), int(sys.argv[3]), int(sys.argv[4]), int(sys.argv[5]))	

	if (sys.argv[1] == 'finishcharge'): 
		connect()
		unlock(SERIAL1, SERIAL2)
	  	forceeq(int(float(sys.argv[2])), int(sys.argv[3]))	
		close()
		time.sleep(int(sys.argv[3])+60)
		connect()
		unlock(SERIAL1, SERIAL2)
		forcebulk()
		close()

	if (sys.argv[1] == 'readall'): 
	  	readAll()	

	print "Done."

if __name__ == "__main__":
	main(sys.argv[1:])
