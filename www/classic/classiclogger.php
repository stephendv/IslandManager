#!/usr/bin/php

<?php

require 'midniteclassic.php';
require '../cosm.php';
require '../constants.php';

define('LOGDB',false);
define('LOGCOSM',false);
define('DEBUG', 1);
define('APIKEY','<insert key>');
define('FEED',75889);
define('DATASTREAM1',"Current");
define('DATASTREAM2',"BatteryVoltage");
define('DATASTREAM3',"PVVoltage");


global $db;
require '../db.php';
$db = new DB("localhost",DBUNAME,DBPASSWORD);
$db->createChargerDB();
require_once '../guzzle.phar';


$classic = new MidniteClassic("192.168.0.16");

$cosm = new Cosm(APIKEY);
	set_time_limit(30);
	$classic->readKeyValues();
	$curr = $classic->battCurrent;
	$battvolts = $classic->battVoltage;
	$pvvolts =  $classic->pvVoltage;	
	if (DEBUG) {
		print "Got values from Classic: $curr A,  $battvolts V,  $pvvolts V\n";
	}
	
	
	$db->updateCharger($curr, $battvolts ,$pvvolts );

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
	
	if (LOGDB)





?>
