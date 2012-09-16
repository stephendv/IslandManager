#!/usr/bin/php

<?php

require_once '/var/www/guzzle.phar';

class Cosm {
	
	function __construct($apikey) {
	   $this->api = $apikey;
	}
	
	function update($feed, $json) {
		$client = new Guzzle\Service\Client("http://api.cosm.com/", array(
		    'curl.CURLOPT_SSL_VERIFYHOST' => false,	    
		   // 'curl.CURLOPT_PROXY'          => '192.168.0.5:8080',
		   // 'curl.CURLOPT_PROXYTYPE'      => 'CURLPROXY_HTTP',
			'curl.CURLOPT_SSL_VERIFYPEER' => false
		));

		$request = $client->put("/v2/feeds/$feed");
		$request->setHeader('X-ApiKey', $this->api);
		//print $json;

		$request->setBody($json);
		$response = $request->send();
	}
}


?>