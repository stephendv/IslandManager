<?php


require_once dirname(__FILE__) . '/../Phpmodbus/ModbusMaster.php';

// Because of the way the Phpmodbus library works, all register values are -1 from the documented midnite registers
define('BASE',4101);
define('END',4283);
define('DAILY_AH',4124);
define('BATT_TEMP',4132);
define('ABSORB_COUNTER', 4138);
define('EQ_REMAINING',4142);
define('BATT_CUR',4271);
define('BATT_VOLTS',4275);
define('PV_VOLTS',4276);

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
	
	//Not working, don't call
	function forceEQ() {
		$this->writeRegister(4159,0x80);
	}
	
	function unlock($data) {
		$types = array("INT");
		$this->writeRegisters(20491,$data,$types);
	}
	
	function convertBytes($msb,$lsb) {
		return (($msb << 8) + $lsb);
	}
}

// Create Modbus object
//$classic = new MidniteClassic("192.168.0.16");
//echo $classic->readRegister(BATT_CUR)/10;
//echo $classic->readRegister(BATT_VOLTS)/10;
//echo $classic->readRegister(PV_VOLTS)/10;

?>