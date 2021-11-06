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

    	$output_file = "results.txt";
        $this->rc = new RollingCurl(array($this, 'processPage'));
        $this->ob_file = fopen($output_file, "w") or die("Unable to open file!");

    }

    function processPage($response, $info){

    	$this->strout .= $info['http_code']."\t";


    	// find our nice Admiral string
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

    function run($input_file){

	    function domainToUrl($domain){
	    	return ("https://".$domain."/");
	    }

    	$urls = file($input_file, FILE_IGNORE_NEW_LINES);
    	$urls = array_map('domainToUrl', $urls);

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
$collector->run("AdmiraList/AdmiraList.txt");
?>