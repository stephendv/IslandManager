#!/usr/bin/php

<?php

require_once __DIR__ . '/midniteclassic.php';
require_once __DIR__ . '/../cosm.php';
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../KLogger.php';
require_once __DIR__ . '/../settings.php';

define('DEBUG', 1);
define('APIKEY','3e402e999ed708f1a08da7aeb9457f09bf037a7b2ebe946d99532be9d6afa4d5');
define('FEED',75889);
define('DATASTREAM1',"Current");
define('DATASTREAM2',"BatteryVoltage");
define('DATASTREAM3',"PVVoltage");
define('DATASTREAM4',"Power");

$log = KLogger::instance(__DIR__ . '/../', KLogger::DEBUG);

require_once __DIR__ . '/../db.php';

$db = new DB("localhost",DBUNAME,DBPASSWORD);
$db->createChargerDB();

$settings = new Settings();
$result = $db->readLatestInverterData(1);
$latestInverterData = mysql_fetch_array($result); 

$classic = new MidniteClassic("192.168.0.16");

set_time_limit(20);
$classic->readKeyValues();
$curr = $classic->battCurrent;
$battvolts = $classic->battVoltage;
$pvvolts =  $classic->pvVoltage;
$midniteChargeStage = $classic->chargeStage;
$chargePhase = $settings->chargePhase;

function performEQ() {
	$classic->setCurrentLimit($settings->EQCurrent);
	$classic->forceEQ();
	$settings->saveLastDateDSEQ(time());	
	$settings->saveDateStartedDSEQ(time());
}


if (DEBUG) {    
	print "Got values from Classic: $curr A,  $battvolts V,  $pvvolts V\n";
	$log->logInfo("Classic: $curr A,  $battvolts V,  $pvvolts V");
}                 
                          
$db->updateCharger($curr, $battvolts ,$pvvolts );
$log->logInfo("ChargerDB updated: ".$curr . "A, " . $battvolts . "V");
     
 
$log->logInfo("Midnite charge stage: " . $midniteChargeStage);

if ($db->isUpToDate()) {
	$startDSEQ = 0;
	$log->logInfo("lastDateDSEQ: " . $settings->lastDateDSEQ . " DSEQinterval: " . $settings->DSEQInterval . " DSEQSoc: " . $settings->DSEQSoC . " chargePhase: " . $settings->chargePhase);
	if ($settings->chargePhase == 0 and time() - $settings->lastDateDSEQ > ($settings->DSEQInterval * 60 * 60 * 24)) {
		$log->logInfo("Starting destratification EQ because interval");
		$startDSEQ = 1; //Do a destratification EQ
	}
	if ($settings->chargePhase == 0 and $latestInverterData['BatSoc'] < $settings->DSEQSoC) {
		$log->logInfo("Starting destratification EQ because SoC");
		$startDSEQ = 1; //Do a destratification EQ
	}
	if ($startDSEQ) {
		$log->logInfo("Saving charge phase = 1");
		$settings->saveChargePhase(1);
	}	
	if ($settings->useRealEndAmps && $midniteChargeStage == 3 ) {	
		$result = $db->readLatestInverterData(ENDAMPSCOUNT);
		$endAmpsReached = TRUE;
		for ($i=0; $i < ENDAMPSCOUNT; $i++) {
			$line = mysql_fetch_array($result); 
			$chargeCurrent = -1 * $line['TotBatCur'];
			$log->logInfo("Charge current row ". $i . ": " . $chargeCurrent);
			if ($chargeCurrent > $settings->endAmps) {
				$endAmpsReached = FALSE;
				$log->logInfo("Charge current: "+$chargeCurrent . " > " . $settings->endAmps . " continuing absorb.");
			}		
		}
		//TODO check minimum absorb time
		if ($endAmpsReached) {
			if ($settings->chargePhase == 0) { 
				$log->logInfo("Forcing float because end amps reached");
				$classic->ForceFloat();
				$log->logInfo("Float forced");
			} else if ($settings->chargePhase == 1) {
				$log->logInfo("End of absorb reach, starting destratification EQ");
				performEQ();
			}
		}
	} 
	if ($settings->chargePhase == 1 and ($classic->battVoltage > $settings->absorbVoltage*24+1) and (time() - $settings->dateStartedDSEQ > ($settings->DSEQMaxTime * 24 * 60 * 60))) {
		$log->logInfo("Stopping DSEQ, maxtime reached");
		$classic->setCurrentLimit(90);
		$classic->forceFloat();
		$settings->saveChargePhase(0);
	}


	/*if ((time() - $settings->lastDateFull) > ($settings->fullInterval * 24 * 60 * 60)) {
		$log->logInfo("Starting full charge, setting absorb V: " . $settings->fullAbsorbVoltage . " time: " . $settings->fullAbsorbTime);
		$classic
	}
	*/
	
} else {
	$log->logError("Database is not up to date");
}

if ($settings->useCosm) {
	$cosm = new Cosm(APIKEY);
	$json = array("version" => "1.0.0",
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
			array(
				"id" => DATASTREAM4,
				"current_value" => $battvolts * $curr
			)
		)
	);            
	$json = json_encode($json);
	$cosm->update(FEED, $json);
}	   

?>
