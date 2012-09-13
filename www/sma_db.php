<?php
date_default_timezone_set('Europe/Madrid');


class SMADB{
	static $con;
	
	public static function readData($from, $to) {
		if (empty($from)) {
			$from = "2000-01-01";
		}
		if (empty($to)) {
			$to = "2200-01-01";
		} 
		$from = mysql_real_escape_string($from);
		$to = mysql_real_escape_string($to);
		self::$con = mysql_connect("localhost", "sma", "ogmanager");
		if (!self::$con) {
		    die("Error: " . mysql_error());
		}
		mysql_select_db("sma", self::$con);
		$result = mysql_query("SELECT * FROM logged_values where logdate between '". $from ."' AND '". $to . "'");
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