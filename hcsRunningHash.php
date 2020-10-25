<?php

// DRAFT - works in most cases, but fails for some messages
// See hardcoding in steps 1 & 2
// http://kpay.live/hash/hcsRunningHash.php
// https://github.com/topshef/hash/blob/master/hcsRunningHash.php

// calculate HCS running hash
// ref https://github.com/hashgraph/hedera-services/issues/88


echo '<pre>';
$arrout =[]; // output array

$topicRunningHashVersion = 3;
$arrout['input']['topicRunningHashVersion'] = $topicRunningHashVersion;

// read sample data from mirror node
	//$url='http://hbar.live/mirror/hcs/?topicId=0.0.43738&limit=2&unpack=hex&fromSeq=5819&sortBy=asc';
	$topic = $_GET['topic']; $topic = ($topic==null) ? '0.0.43738' : $topic;
	$seq = $_GET['seq']; $seq = ($seq==null) ? '5801' : $seq;
	$url="http://hbar.live/mirror/hcs/?topicId={$topic}&limit=2&unpack=hex&fromSeq={$seq}&sortBy=asc";
	$json = file_get_contents($url);
	$arr = json_decode($json, true)['data'];
	
	$arrout['input']['url'] = $url;
	$arrout['input']['data'] = $arr;
	
	$tx_previous = $arr[0];
	$tx = $arr[1];

$input = '';

// 1. The previous running hash of the topic (48 bytes)
//Byte array to hold Java Serialized Object details
//Last byte holds data length
$arrJavaSuffix = [-84, -19, 0, 5, 117, 114, 0, 2, 91, 66, -84, -13, 23, -8, 6, 8, 84, -32, 2, 0, 0, 120, 112, 0, 0, 0, 48];
$strTopicRunningHash = $tx_previous['topicRunningHash'];
$input .= messageHash($strTopicRunningHash, $arrJavaSuffix);

// 2. The topicRunningHashVersion below (8 bytes) 
//Byte array to hold Java Serialized Object details
//First two bytes hold Serialization details, rest of them are 8 byts for data
//array(119, 76, 0, 0, 0, 0, 0, 0, 0)    
/// TODO implement another method to use all 8 bytes instead of hardcoding last byte only
$arrHashVersion = array(119, 76, 0, 0, 0, 0, 0, 0, 0, $topicRunningHashVersion);
$input .= numberHash($arrHashVersion);

// 3. The payer account's shard (8 bytes) 
// 4. The payer account's realm (8 bytes) 
// 5. The payer account's number (8 bytes)
$readableTransactionID = $tx['readableTransactionID'];
$payerID = substr($readableTransactionID, 0, strpos($readableTransactionID, '@'));
foreach (explode('.', $payerID) as $v) {
    $vals = decToByte(intval($v));
    $input .= numberHash($vals);
}

// 6. The topic's shard (8 bytes) 
// 7. The topic's realm (8 bytes) 
// 8. The topic's number (8 bytes) 
$topicID = $tx['topicID'];
foreach (explode('.', $topicID) as $v) {
    $vals = decToByte(intval($v));
    $input .= numberHash($vals);
}

// 9. The number of seconds since the epoch before the ConsensusSubmitMessage reached consensus (8 bytes) 	
$consensusTime = $tx['consensusTime'];
$consensusSeconds = consensusTimeToEpochSeconds(substr($tx['consensusTime'], 0, 19));
$dateConsensus = gmdate("Y-m-d H:i:s", $consensusSeconds);
$arrCS = decToByte(intval($consensusSeconds));
$input .= numberHash($arrCS);

// 10. The number of nanoseconds since 9. before the ConsensusSubmitMessage reached consensus (4 bytes) 
// we need the consensus time in nanoseconds - get this from Kabuto
$kabutoTxId = str_replace('-', '.', $readableTransactionID);
$url_kabuto = "https://api.kabuto.sh/v1/transaction?q={%22id%22:%20%22{$kabutoTxId}%22}";
$response_kabuto = file_get_contents($url_kabuto);
$arr_kabuto = json_decode($response_kabuto, true);
$consensusAt = $arr_kabuto['transactions'][0]['consensusAt'];

$arrout['debug']['kabutoTxId'] = $kabutoTxId;
$arrout['debug']['url_kabuto'] = $url_kabuto;
//$arrout['debug']['arr_kabuto'] = $arr_kabuto;

$consensusSeconds = consensusTimeToEpochSeconds(substr($consensusAt, 0, 19));
$arrout['debug']['consensusAt'] = $consensusAt;
//2020-10-05T22:22:40.814490Z 
//$consensusNanos = 1 * substr($consensusAt, 20, 9);
$lenNanos =  strlen($consensusAt) - 21;
$consensusNanos = substr($consensusAt, 20, $lenNanos);
$consensusNanos = $consensusNanos . str_repeat('0', 9 - $lenNanos);  // fill out zeros if needed (kabuto oddity?)
//eg seq 5808 nanos 814490 --> 814490000  
$arrout['debug']['consensusNanos'] = $consensusNanos;

//fail 5803
//135405004

//consensus time
//2020-10-05T22:16:03.442+0000   <-- DG (summary API)
//2020-10-05T22:16:03.442497001Z <-- kabuto raw tx
//Use only 4 bytes for this
$arrNanos = decToByte(intval($consensusNanos), 4);

$input .= numberHash($arrNanos);

// 11. The topicSequenceNumber from above (8 bytes) 
$vals = decToByte(intval($tx['topicSequenceNumber']));
$input .= numberHash($vals);

// 12. The output of the SHA-384 digest of the message bytes from the consensusSubmitMessage (48 bytes) 
// Convert the Message into Hex values first
$hex = strToHex($tx['message']);
// Use pack() method to creae bytes arry
$strHex = messageHash($hex);
// Use bytes array to create has of the Message
$strHash = hash('sha384', "$strHex");

//Byte array to hold Java Serialized Object i.e. Hashed Message in this case
//Last byte holds data length
//array(117, 113, 0, 126, 0, 0, 0, 0, 0, 48);
$hashArr = [117, 113, 0, 126, 0, 0, 0, 0, 0, 48];         
$input .= messageHash($strHash, $hashArr);

test:

// compare and report results

$expected = $tx['topicRunningHash'];
$actual = hash('sha384', "$input");

$arrout['output']['expected'] = $expected;
$arrout['output']['actual'] = $actual;
$arrout['output']['result'] = ($expected == $actual) ? 'pass' : 'fail';

print_r($arrout);


// functions
function consensusTimeToEpochSeconds($consensusTime) {
    //eg 2020-08-16T00:56:02.232+0000
	//https://stackoverflow.com/questions/7924663/force-php-strtotime-to-use-utc
	date_default_timezone_set('UTC');
    return strtotime($consensusTime);
}

/**
 * Convert a Number to Bytes array as per Java notation
 *
 * @param Number    $number  Number to convert
 * @param Bytes     $totalbytes Number of bytes to convert the number to.
 * 
 * @author Muhammad Saqib
 * @return BytesArray
 */ 
function decToByte($number, $totalbytes = 8) {    
    $bytes = array();
    $hex = dechex($number);
    $length = strlen($hex);
    if ($length > 1) {
        for ($i = 0; $i < $length - 1; $i += 2) {
            $a = hexdec($hex[$i]) << 4;
            $b = hexdec($hex[$i + 1]);
            array_push($bytes, (($a + $b + 128) % 256) - 128);
        }
    }

    if (count($bytes) < $totalbytes) {
        $len = $totalbytes - count($bytes);
        for ($i = 0; $i < $len; $i++) {
            array_unshift($bytes, 0);
        }
    }

    return $bytes;
}

/**
 * Convert a String to Hex number 
 *
 * @param String    $string  String to convert
 * 
 * @author Muhammad Saqib
 * @return HexString
 */ 
function strToHex($string) {
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

/**
 * Convert a String to Binary String
 *
 * @param Number    $str  String to convert
 * @param Bytes     $bytearraytoappend Array to append to returned array
 * 
 * @author Muhammad Saqib
 * @return Binary String
 */ 
function messageHash($str, $bytearraytoappend=array()) {
    $input = '';
    $hashMsg1 = array_merge($bytearraytoappend,array()); 
    for ($pos = 0; $pos < strlen($str); $pos += 2) {
        $a = hexdec($str[$pos]) << 4;
        $b = hexdec($str[$pos + 1]);
        array_push($hashMsg1, (($a + $b + 128) % 256) - 128);
        $a = 0;
        $b = 0;
    }

    foreach ($hashMsg1 as $i => $value) {
        $input .= pack('c*', $hashMsg1[$i]);
    }
    //print_r($hashMsg1);

    return $input;
}

/**
 * Convert a Byte Array to Binary String
 *
 * @param ByteArray    $byteArray  String to convert
 * 
 * @author Muhammad Saqib
 * @return Binary String
 */ 
function numberHash($byteArray){
    $input = '';
    foreach ($byteArray as $i => $value) {
        $input .= pack("c", $byteArray[$i]);
    }
    return $input;
}

?>