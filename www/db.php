<?php
date_default_timezone_set('Europe/Madrid');


class DB {
	var $con;
	
	function __construct($address,$user,$password) {
	   $this->con = mysql_connect($address, $user, $password);
	   mysql_select_db("sma", $this->con);
	}	
	
	public function readInverterData($from, $to) {
		if (empty($from)) {
			$from = "2000-01-01";
		}
		if (empty($to)) {
			$to = "2200-01-01";
		} 
		$from = mysql_real_escape_string($from);
		$to = mysql_real_escape_string($to);
		
		if (!$this->con) {
		    die("Error: " . mysql_error());
		}
		
		$result = mysql_query("SELECT * FROM logged_values where logdate between '". $from ."' AND '". $to . "'");
		
		return $result;
	} 
	
	public function readLatestInverterData($limit) {
		$result = mysql_query("select * from logged_values order by logdate DESC,logtime DESC limit " . $limit);
		return $result;
	}	
	
	public function isUpToDate() {
		//TODO check that the latest record from SI matches the current time
		return true;
	}	
	
	public function createChargerDB() {
		$result = mysql_query("CREATE TABLE IF NOT EXISTS solarcharger (date DATETIME, current FLOAT,volts FLOAT,pvvolts FLOAT)")  or print(mysql_error());
		return $result;
	}
	
	public function updateCharger($current,$volts,$pvvolts) {
		$date = date("Y-m-d H:i:s");
		$current = mysql_real_escape_string($current);
		$volts = mysql_real_escape_string($volts);
		$pvvolts = mysql_real_escape_string($pvvolts);
		$query = "INSERT INTO solarcharger values ('$date',$current,$volts,$pvvolts)";
		//print $query;
	 	$result = mysql_query($query) or print(mysql_error());
	 	return $result;
	}
	
	
	public static function convertDateAndTime($date, $time) {
		return date('Y-m-d H-i-s', strtotime($date . " " . $time));
	}
	
	public static function dygraphTimeFormat($line) {
		return date('Y/m/d H:i:s', strtotime($line['logdate'] ." " .$line['logtime']));
	}
	
	public function close() {
		mysql_close($this->con);
	}
}


?>