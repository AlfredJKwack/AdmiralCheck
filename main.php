<?php

// more efficient implementation of curl_multi()
// see https://github.com/PEPSIASAS/RollingCurl
require 'RollingCurl/src/RollingCurlService/RollingCurl.php';
require 'RollingCurl/src/RollingCurlService/RollingCurlRequest.php';

$input_file = "AdmiraList/AdmiraList.txt";
$output_analysis = "AlCheckOut_analysis.txt";
$output_pr_dead = "AlCheckOut_pr_dead.txt";
$output_unique_hashes = "AlCheckOut_pr_dead.txt";

/**
 * Creates an output file where all dead domains are removed.
 * This is useful to generate a PR at some point
 * @param  String $output_file Path to output file. It will be overwritten
 * @param  Array  $data        Input data in the format used throughout
 * @return none
 */
function generateDeadPrFile($output_file, $data){

	function liveDomain($theDomain){
		// remove all domains where http_code was 0
		return !($theDomain['return_code'] === 0);
	}

	$filteredArray = array_keys(array_filter($data, "liveDomain"));
	sort($filteredArray);

	file_put_contents($output_file, implode(PHP_EOL, $filteredArray));
};

/**
 * Creates a tab delimited output file ready for further analysis in your 
 * favorite data manipulation tool.
 * @param  String $output_file Path to output file. It will be overwritten
 * @param  Array  $data        Input data in the format used throughout.
 * @return none
 */
function generateAnalysisFile($output_file, $data){

	$output = "";
	foreach ($data as $k => $v){
		$output .= $v['return_code']."\t";
		$output .= $v['txt_match']."\t";
		$output .= $v['md5']."\t";
		$output .= $k."\n";
	}

	$ob_file = fopen($output_file, "w") or die("Unable to open file!");
	fwrite($ob_file, $output);
	fclose($ob_file);	
};

/**
 * Takes an array of domain names as input and generates a new 
 * multi-dimensional array with the format as follows.
 *
 *   ["theInputDomainName"]=>
 *     array(4) {
 *         ["url"]=> "https://advertisementafterthought.com/"
 *         ["return_code"]=> ''
 *         ["txt_match"]=> ''
 *         ["md5"]=> ''
 *     }
 * 
 * @param  array $inputArr An array of domain names
 * @return array           The array as described 
 */
function generateDataArray($inputArr){
	$tmpArr = array();
	array_walk($inputArr, function ($value, $key) use (&$tmpArr) {
		$tmpArr[$value] = array(
			'url' => "https://".$value."/",		// url we will query
			'return_code' => '',				// http_code returned by query
			'txt_match' => '',					// is there a magic text match?
			'md5' => ''							// an md5 hash of the webpage
		);
	});
	return $tmpArr;
};

// transform input file into a useful array.
$theInput = file($input_file, FILE_IGNORE_NEW_LINES);
$domains = generateDataArray($theInput);

$rollingCurl = new \RollingCurlService\RollingCurl();

// queue up each of the domains for Curl requests to be generated
foreach ($domains as $key => $domain) {
    $request = new \RollingCurlService\RollingCurlRequest($domain["url"]);
    $request->setAttributes(['requestId'=>$key]); // for callback goodness
    $rollingCurl->addRequest($request);
}

// exectue the Curl request and update the data array
$rollingCurl->execute(function ($output, $info, $request) use (&$domains){

	$lookfor = "This domain is used by digital publishers to control access to copyrighted content ";

	// clean up the output a little
	$cleanOutput = strip_tags($output);

    $requestAttributes = $request->getAttributes(); // callback goodness
    $domains[$requestAttributes['requestId']]["return_code"] = $info['http_code'];
    $domains[$requestAttributes['requestId']]["md5"] = md5($cleanOutput);
    $domains[$requestAttributes['requestId']]["url"] = $info['url'];

	if (strpos($cleanOutput, $lookfor) !== false) {
		$domains[$requestAttributes['requestId']]["txt_match"] =  "txt";
	} else {
		$domains[$requestAttributes['requestId']]["txt_match"] = "n/a";
	}

});
generateDeadPrFile($output_pr_dead, $domains);
generateAnalysisFile($output_analysis, $domains);

?>