<?php

require '/var/www/classic/midniteclassic.php';


define('LOGDB',false);
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
global $pachube;


if (LOGDB) {
	require '/var/www/db.php';
	$db = new DB("localhost",DBUNAME,DBPASSWORD);
	$db->createChargerDB();
}

if (LOGCOSM) {
	require_once '/var/www/guzzle.phar';
}

function updateCosm($json) {
	$client = new Guzzle\Service\Client("http://api.cosm.com/", array(
	    'curl.CURLOPT_SSL_VERIFYHOST' => false,
	    
	   // 'curl.CURLOPT_PROXY'          => '192.168.0.5:8080',
	   // 'curl.CURLOPT_PROXYTYPE'      => 'CURLPROXY_HTTP',
		'curl.CURLOPT_SSL_VERIFYPEER' => false
	));
	
	$request = $client->put("/v2/feeds/". FEED);
	$request->setHeader('X-ApiKey', APIKEY);
	//print $json;
	
	$request->setBody($json);
	$response = $request->send();
}


$classic = new MidniteClassic("192.168.0.16");

while (true) {
	set_time_limit(15);
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
		updateCosm($json);
	}	
	
	//Cosm free account has a 10 requests pre minute limit
	sleep(7);
}




?>