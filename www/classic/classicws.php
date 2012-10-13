<?php

require 'midniteclassic.php';

$classic = new MidniteClassic("192.168.0.16");

$all = array(
	'BATT_CUR' => $classic->readRegister(BATT_CUR)/10,
	'BATT_VOLTS' => $classic->readRegister(BATT_VOLTS)/10,
);

//print json_encode($all);

$classic

?>