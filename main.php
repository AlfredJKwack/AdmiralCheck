<?php

// more efficient implementation of curl_multi()
// see https://github.com/PEPSIASAS/RollingCurl
require 'RollingCurl/src/RollingCurlService/RollingCurl.php';
require 'RollingCurl/src/RollingCurlService/RollingCurlRequest.php';

$input_file = "testData/PR.txt";
$output_analysis = "AlCheckOut_analysis.txt";
$output_pr_dead = "AlCheckOut_pr_dead.txt";
$output_unique_hashes = "AlCheckOut_Abstracts.txt";
$output_json = "AlCheckOut.json";

// a string of text we know is present on the Admiral sites
$lookfor = "This domain is used by digital publishers to control access to copyrighted content ";

// $input_file = "testData/testList.txt";

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

/**
 * Takes a multi-dimentional input array and returns a new array without 
 * duplicate values for a given key value. The first equal element will be 
 * retained,
 *
 * See: https://www.php.net/manual/en/function.array-unique.php#116302
 * 
 * @param  Array  $multiArr The array that we wish to reduce from
 * @param  String $key      The key we will make unique
 * @return Array            The array containing only unique keys
 */
function array_multidim_unique($multiArr, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();
   
    foreach($multiArr as $arrSrcKey => $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$arrSrcKey] = $val;
        }
        $i++;
    }
    return $temp_array;
}

/**
 * Genrates a file containing only entries where the hash is unique
 * @param  String $output_file Path to output file. It will be overwritten
 * @param  Array  $data        Input data in the format used throughout
 * @return none
 */
function generateAbstractsFile($output_file, $data){

	$output = array_multidim_unique($data,'md5');
	generateAnalysisFile($output_file, $output);

}

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
$rollingCurl->execute(function ($output, $info, $request) use (&$domains, $lookfor){

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
file_put_contents($output_json,json_encode($domains));
generateDeadPrFile($output_pr_dead, $domains);
generateAnalysisFile($output_analysis, $domains);
generateAbstractsFile($output_unique_hashes,$domains);
?>