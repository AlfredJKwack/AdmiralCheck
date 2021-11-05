<?php

	// all output goes to to a file
	$ob_file = fopen("results.txt", "w") or die("Unable to open file!");

	global $strout;

	// more efficient implementation of curl_multi()
	// see https://github.com/LionsAd/rolling-curl
    require 'rolling-curl/RollingCurl.php';
    require 'rolling-curl/RollingCurlGroup.php';

	// an array of URL's to fetch
	$urls = array("http://www.google.com",
              "http://www.facebook.com",
              "http://www.yahoo.com",
          	  "http://bandonedaction.com", 
          	  "http://aboardamusement.com",
          	  "http://decisivebase.com"
          	);    

	// a function that will process the returned responses
	function request_callback($response, $info) 
	{

		$strout .= $info['url']."\t".$info['http-code']."\r";
		echo $strout;

	}

	// create a new RollingCurl object and pass it the name of your custom callback function
	$rc = new RollingCurl("request_callback");

	// the window size determines how many simultaneous requests to allow.  
	$rc->window_size = 20;

	foreach ($urls as $url) 
	{
	    // add each request to the RollingCurl object
	    $request = new RollingCurlRequest($url);
	    $rc->add($request);
	}
	$rc->execute(); 

	fwrite($ob_file, $strout);
	fclose($ob_file);

?>