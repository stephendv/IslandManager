<?php
date_default_timezone_set('Europe/Madrid');


class SMADB{
	static $con;
	
	public static function readData($from='01/01/01', $to='01/01/5099') {
		self::$con = mysql_connect("localhost", "sma", "ogmanager");
		if (!self::$con) {
		    die("Error: " . mysql_error());
		}
		mysql_select_db("sma", self::$con);
		$result = mysql_query("SELECT * FROM logged_values");
		return $result;
	} 
	
	public static function convertDateAndTime($date, $time) {
		return date('Y-m-d H-i-s', strtotime($date . " " . $time));
	}
	
	public static function dygraphTimeFormat($line) {
		return date('Y/m/d H:i:s', strtotime($line['logdate'] ." " .$line['logtime']));
	}
	
	public static function close() {
		mysql_close(self::$con);
	}
}


?>