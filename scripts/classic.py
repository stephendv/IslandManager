#!/usr/bin/python

from pymodbus.client.sync import ModbusTcpClient
import sys
import logging
import time
import xively
import datetime

logging.basicConfig()
log = logging.getLogger()
log.setLevel(logging.DEBUG)

SERIAL1 = 0					# The first part of the serial number
SERIAL2 = 194				# The second part of the serial number
HOST = '192.168.0.16'
LIMIT = 30

# Monitoring
XIVELY_API = "insert your xively api key"
XIVELY_FEED = your feed id
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
	global BATTV, KWH, WATT, AH, TEMP
	print "Reading..."
	global client
	base = 4114
	rq = client.read_holding_registers(base,40)
	assert(rq.function_code < 0x80)
	BATTV = rq.registers[4114-base]/10.0
	KWH = rq.registers[4117-base]/10.0
	WATT = rq.registers[4118-base]/10.0
	AH = rq.registers[4124-base]/10.0
	TEMP = rq.registers[4131-base]/10.0

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

def monitor():
	readAll()
	print("Battery voltage: "+str(BATTV))
	print("kWh today: "+str(KWH))
	print("Watts: "+str(WATT))
	print("Ah today: "+str(AH))
	print("Temperature: "+str(TEMP))
	api = xively.XivelyAPIClient(XIVELY_API)
	now = datetime.datetime.utcnow()
	
	feed = api.feeds.get(XIVELY_FEED)
	feed.datastreams = [
    	xively.Datastream(id='batts', current_value=BATTV, at=now),
		xively.Datastream(id='watts', current_value=WATT, at=now),
		xively.Datastream(id='kwh', current_value=KWH, at=now),
		xively.Datastream(id='ah', current_value=AH, at=now),
		xively.Datastream(id='temp', current_value=TEMP, at=now),
    ]
	feed.update()
		
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
		limitCurrent(LIMIT)
		forceeq(int(float(sys.argv[2])), int(sys.argv[3]))	
		close()
		time.sleep(int(sys.argv[3])+60)
		connect()
		unlock(SERIAL1, SERIAL2)
		limitCurrent(86)
		forcebulk()
		close()

	if (sys.argv[1] == 'readall'): 
		readAll()	
		
	if (sys.argv[1] == 'monitor'): 
		monitor()		  

	print "Done."

if __name__ == "__main__":
	main(sys.argv[1:])
