<?php

// more efficient implementation of curl_multi()
// see https://github.com/LionsAd/rolling-curl
require 'rolling-curl/RollingCurl.php';
require 'rolling-curl/RollingCurlGroup.php';

class MyInfoCollector {
    private $rc;
	private $ob_file;
	private $strout; 

    function __construct(){
        $this->rc = new RollingCurl(array($this, 'processPage'));
        $this->ob_file = fopen("results.txt", "w") or die("Unable to open file!");
    }

    function processPage($response, $info){

    	$this->strout .= $info['http_code']."\t";


    	// find our nice string
    	$lookfor = "This domain is used by digital publishers to control access to copyrighted content ";
    	$string = str_replace("\r\n", "\n", $response); // windows -> unix
		$string = str_replace("\r", "\n", $string);   // remaining -> unix     

		if (strpos($string, $lookfor) !== false) {
			
			$this->strout .= "txt"."\t";

		} else {

			$this->strout .= "n/a"."\t";
		}

		$this->strout .= md5($string)."\t";

		$this->strout .= $info['url']."\r";

    }

    function run($urls){
        foreach ($urls as $url){
            $request = new RollingCurlRequest($url);
            $this->rc->add($request);
        }
        $this->rc->execute();

		fwrite($this->ob_file, $this->strout);
		fclose($this->ob_file);        
    }
}

$collector = new MyInfoCollector();
$collector->run(array("http://www.google.com",
      "http://www.facebook.com",
      "http://www.yahoo.com",
  	  "http://bandonedaction.com", 
  	  "http://aboardamusement.com",
  	  "http://decisivebase.com"
  	));
?>