#!/usr/bin/php

<?php

require '/var/www/classic/midniteclassic.php';
require '/var/www/cosm.php';

define('LOGDB',true);
define('LOGCOSM',true);
define('DEBUG', 1);
define('APIKEY','<insert key>');
define('FEED',75889);
define('DATASTREAM1',"Current");
define('DATASTREAM2',"BatteryVoltage");
define('DATASTREAM3',"PVVoltage");
define('DBUNAME','sma');
define('DBPASSWORD','ogmanager');

global $db;

if (LOGDB) {
	require '/var/www/db.php';
	$db = new DB("localhost",DBUNAME,DBPASSWORD);
	$db->createChargerDB();
}

if (LOGCOSM) {
	require_once '/var/www/guzzle.phar';
}

$classic = new MidniteClassic("192.168.0.16");
$cosm = new Cosm(APIKEY);

while (true) {
	set_time_limit(30);
	$curr = $classic->readRegister(BATT_CUR)/10;
	$battvolts = $classic->readRegister(BATT_VOLTS)/10;
	$pvvolts =  $classic->readRegister(PV_VOLTS)/10;	
	if (DEBUG) {
		print "Got values from Classic: $curr A,  $battvolts V,  $pvvolts V\n";
	}
	
	if (LOGDB) {
		$db->updateCharger($curr, $battvolts ,$pvvolts );
	}
	
	if (LOGCOSM) {
		$json = array(
			"version" => "1.0.0",
			"datastreams" => array(
				array(
					"id" => DATASTREAM1,
					"current_value" => $curr
				),
				array(
					"id" => DATASTREAM2,
					"current_value" => $battvolts
				),
				array(
					"id" => DATASTREAM3,
					"current_value" => $pvvolts
				),	
			)
		);
		$json = json_encode($json);
		$cosm->update(FEED, $json);
	}	
	
	//Cosm free account has a 10 requests pre minute limit
	sleep(7);
}




?>
