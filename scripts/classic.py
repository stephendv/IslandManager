#!/usr/bin/python

from pymodbus.client.sync import ModbusTcpClient
import sys
import logging

logging.basicConfig()
log = logging.getLogger()
log.setLevel(logging.DEBUG)

SERIAL1 = 0                 # The first part of the serial number
SERIAL2 = 1234              # The second part of the serial number
HOST = '192.168.0.16'

client = ModbusTcpClient(HOST)

def unlock(serial1,serial2):
	global client
	rq = client.write_registers(20491, [serial1,serial2])
	assert(rq.function_code < 0x80) 

def forceeq(volts, time=1):
	global client
	rq = client.write_register(4150,int(volts*10))
	assert(rq.function_code < 0x80) 
	rq = client.write_register(4161,time)
	assert(rq.function_code < 0x80) 
	rq = client.write_register(4159,0x80)
	assert(rq.function_code < 0x80) 

def forcefloat():
	print "Forcing float"
	global client
	rq = client.write_register(4159,0x20)
	assert(rq.function_code < 0x80) 

def forcebulk():
	global client
	client.write_register(4159,0x40)

def main(argv):
	global client		
	global SERIAL1
	global SERIAL2

	client.connect()
	if (sys.argv[1] == 'forceeq'): 
		unlock(SERIAL1, SERIAL2)
		forceeq(float(sys.argv[2]), int(sys.argv[3]))

	if (sys.argv[1] == 'forcefloat'): 
		unlock(SERIAL1, SERIAL2)
		forcefloat()

	client.close()
	print "Done."

if __name__ == "__main__":
	main(sys.argv[1:])
