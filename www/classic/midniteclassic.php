<?php


require_once dirname(__FILE__) . '/../Phpmodbus/ModbusMaster.php';

// Because of the way the Phpmodbus library works, all register values are -1 from the documented midnite registers
define('BASE',4101);
define('END',4283);
define('DAILY_AH',4124);
define('BATT_TEMP',4132);
define('ABSORB_COUNTER', 4138);
define('EQ_REMAINING',4142);
define('BATT_CUR',4116);
define('BATT_VOLTS',4114);
define('PV_VOLTS',4115);
define('ABSORB_V',4148);
define('CHARGE_STAGE',4119);

/*
	Battery stage
		0 = Resting
		3 = Absorb
		4 = BulkMPPT
		5 = Float
		6 = FloatMPPT
		7 = EQ
		10 = VyperVOC
		18 = EQMPPT
*/

class MidniteClassic {
		
	function __construct($address) {
	   $this->modbus = new ModbusMaster($address, "TCP");
	}
	
	// Reading all at once caused errors, might be the modbus PHP library, so reading one at a time instead.
	function readAll() {
		for ($i=BASE;$i<= END;$i++) {
			try {
			    $temp = $this->modbus->readMultipleRegisters(1, $i, 1);
				print " [". $i ."]:";
				print " MSB=".$temp[0]; 				
				print "   LSB=".$temp[1];
				$converted = self::convertBytes($temp[0],$temp[1]);
				print "  converted value=" . $converted . "\n";
			} catch (Exception $e) {
			    // Print error information if any
			    echo "Error reading :" . $i;		    
			}
		}
	}
	
	function writeRegister($reg,$data,$type="INT") {
		$val[0] = $data;
		$datatype[0] = $type;
		try {
		    $temp = $this->modbus->writeMultipleRegister(1, $reg, $val, $datatype);
			return $temp;
		} catch (Exception $e) {
		    echo "Error :" . $e;    		    
		}
	}
	
	function writeRegisters($reg,$data,$type) {
		try {
		    $temp = $this->modbus->writeMultipleRegister(1, $reg, $data, $type);
			return $temp;
		} catch (Exception $e) {
		    echo "Error :" . $e;    		    
		}
	}
	
	function readRegister($reg) {
		try {
		    $temp = $this->modbus->readMultipleRegisters(1, $reg, 1);			
			return self::convertBytes($temp[0],$temp[1]);
		} catch (Exception $e) {
		    // Print error information if any
		    echo "Error reading :" . $reg;	
			echo $e;
		}
	}
	
	function readKeyValues() {
		$this->chargeStage = $this->readChargeStage();
		$this->battCurrent = $this->readRegister(BATT_CUR)/10;
		$this->battVoltage = $this->readRegister(BATT_VOLTS)/10;
		$this->pvVoltage = $this->readRegister(PV_VOLTS)/10;
	}
	
	function forceEQ() {
		$this->writeRegister(4159,0x80);
	}
	
	function forceBulk($volts) {
		$this->writeRegister(4159,0x40);
	}
	
	function forceFloat($volts) {
		$this->writeRegister(4159,0x20);
	}	
	
	function readChargeStage() {
		return $this->readRegister(CHARGE_STAGE);
	}
	
	function convertBytes($msb,$lsb) {
		return (($msb << 8) + $lsb);
	}
}

// Create Modbus object
$classic = new MidniteClassic("192.168.0.16");
//echo $classic->readRegister(BATT_CUR)/10;
//echo $classic->readRegister(BATT_VOLTS)/10;
//echo $classic->readRegister(PV_VOLTS)/10;


?>