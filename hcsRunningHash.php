<?php

echo '<pre>';
$topicRunningHashVersion = 3;
echo "topicRunningHashVersion $topicRunningHashVersion \n";

// read sample data from mirror node
//$url='http://hbar.live/mirror/hcs/?topicId=0.0.43738&limit=2&unpack=hex&fromSeq=5801&sortBy=asc';
//$json = file_get_contents($url);
//$arr = json_decode($json, true)['data'];

$tx_previous = Array(
    "transactionID" => "00274981601935168799000000",
    "message" => "W3siZXZlbnRJZCI6ImtwYXlsaXRlQDE2MDE5MzUxNzguNjQwNSIsImNoYXJnZSI6W3siaGFzaE1lbWJlciI6Ijg3MTUwOWM1NWNlYzExNThkZTZkNWFjNzVjNmU1NWJlNzA1MWRiMzViZGRkYTc5MDk2Mzk0YWNhYmI1MTI4MjAiLCJoYXNoS3BheUlkIjoiNjlkMzk4Njc0NmYzZjlmM2JhNDM1NDMwZTUwNDNmNWQ1MjNkNWRmZTk4NjkzNjcwN2RmMjFkY2NlNTM4YTA3OCIsImFtb3VudCI6IjAuMSIsInJlY2lwaWVudElkIjoiMC4wLjI3NDk3IiwibWVtbyI6InZlc2F1cnVzLmNvbSAodmlhIGtwYXlsaXRlIGFwaSkifV19XQ==",
    "consensusTime" => "2020-10-05T21:59:39.454+0000",
    "status" => "SUCCESS",
    "topicRunningHash" => "5e75efe15c385d6561f54dec9e7303653a6ce732aca25fb33de3533ade9b903443877837391ab55532cd5ff38272db98",
    "topicSequenceNumber" => "5801",
    "topicID" => "0.0.43738",
    "readableTransactionID" => "0.0.27498@1601935168-799000000"
);
$tx = Array(
    "transactionID" => "00274981601936153164000000",
    "message" => "W3siZXZlbnRJZCI6ImtwYXlsaXRlQDE2MDE5MzYxNjMuMDgyIiwiY2hhcmdlIjpbeyJoYXNoTWVtYmVyIjoiODcxNTA5YzU1Y2VjMTE1OGRlNmQ1YWM3NWM2ZTU1YmU3MDUxZGIzNWJkZGRhNzkwOTYzOTRhY2FiYjUxMjgyMCIsImhhc2hLcGF5SWQiOiIzMDQwYzg4ODI1ZGIwZWMxNjVjMzFkYTBkYjBjNjJjMjE0NTBhMGZlYjhiN2M4YjQ0MDExYTJiNzg4MTc0MTc4IiwiYW1vdW50IjoiMC4xIiwicmVjaXBpZW50SWQiOiIwLjAuMjc0OTciLCJtZW1vIjoidmVzYXVydXMuY29tICh2aWEga3BheWxpdGUgYXBpKSJ9XX1d",
    "consensusTime" => "2020-10-05T22:16:03.442497001Z",
    "status" => "SUCCESS",
    "topicRunningHash" => "c8e0cd943603c415315d0caf77fc322384dd87ba8b3d347b66710e099dc0d87000c6aa3eb35ea6a08a79341855c8fc0f",
    "topicSequenceNumber" => "5802",
    "topicID" => "0.0.43738",
    "readableTransactionID" => "0.0.27498@1601936153-164000000"
);

$input = '';

// 1. The previous running hash of the topic (48 bytes)
//Byte array to hold Java Serilized Object details
//Last byte holds data length
//array(-84, -19, 0, 5, 117, 114, 0, 2, 91, 66, -84, -13, 23, -8, 6, 8, 84, -32, 2, 0, 0, 120, 112, 0, 0, 0, 48)
echo "\n1. Running Hash: ";
$topicRunningHash = [-84, -19, 0, 5, 117, 114, 0, 2, 91, 66, -84, -13, 23, -8, 6, 8, 84, -32, 2, 0, 0, 120, 112, 0, 0, 0, 48];
$strTopicRunningHash = $tx_previous['topicRunningHash'];
$input .= messageHash($strTopicRunningHash, $topicRunningHash);

//print_r(array_merge($strTopicRunningHash,$topicRunningHash));

// 2. The topicRunningHashVersion below (8 bytes) 
//Byte array to hold Java Serilized Object details
//First two bytes hold Serialization details, rest of them are 8 byts for data
//array(119, 76, 0, 0, 0, 0, 0, 0, 0)    
/// TODO implement another method to use all 8 bytes instead of hardcoding last byte only
$arrHashVersion = array(119, 76, 0, 0, 0, 0, 0, 0, 0, $topicRunningHashVersion);
$input .= numberHash($arrHashVersion);
echo "\n2. Running Hash Version: ";
print_r($arrHashVersion);
// 3. The payer account's shard (8 bytes) 
// 4. The payer account's realm (8 bytes) 
// 5. The payer account's number (8 bytes)
$readableTransactionID = $tx['readableTransactionID'];
$payerID = substr($readableTransactionID, 0, strpos($readableTransactionID, '@'));
echo "\n3,4,5 Payer: ";
foreach (explode('.', $payerID) as $v) {
    $vals = decToByte(intval($v));
    $input .= numberHash($vals);
    print_r($vals);
}

// 6. The topic's shard (8 bytes) 
// 7. The topic's realm (8 bytes) 
// 8. The topic's number (8 bytes) 
$topicID = $tx['topicID'];
echo "\n5,6,7. TopicID: ";
foreach (explode('.', $topicID) as $v) {
    $vals = decToByte(intval($v));
    $input .= numberHash($vals);
    print_r($vals);    
}


// 9. The number of seconds since the epoch before the ConsensusSubmitMessage reached consensus (8 bytes) 	
$consensusTime = $tx['consensusTime'];
//echo "DG consensusTime =$consensusTime\n";
$consensusSeconds = consensusTimeToEpochSeconds(substr($tx['consensusTime'], 0, 19));
//echo "DG consensusSeconds =$consensusSeconds\n";
$dateConsensus = gmdate("Y-m-d H:i:s", $consensusSeconds);
//echo "check $dateConsensus\n";
//$arrCS = decToByte(intval($consensusSeconds));
$arrCS = decToByte(intval(1601936163));
echo "\n9. consensusSeconds: $consensusSeconds\n";
$input .= numberHash($arrCS);
print_r($arrCS); 
// 10. The number of nanoseconds since 9. before the ConsensusSubmitMessage reached consensus (4 bytes) 
// we need the consensus time in nanoseconds - get this from Kabuto
$kabutoTxId = str_replace('-', '.', $readableTransactionID);
$url_kabuto = "https://api.kabuto.sh/v1/transaction?q={%22id%22:%20%22{$kabutoTxId}%22}";
$response_kabuto = file_get_contents($url_kabuto);
$arr_kabuto = json_decode($response_kabuto, true);
$consensusAt = $arr_kabuto['transactions'][0]['consensusAt'];


$consensusSeconds = consensusTimeToEpochSeconds(substr($consensusAt, 0, 19));
$consensusNanos = 1 * substr($consensusAt, 20, 9);
//consensus time
//2020-10-05T22:16:03.442+0000   <-- DG (summary API)
//2020-10-05T22:16:03.442497001Z <-- kabuto raw tx
//User only 4 bytes for this
$arrNanos = decToByte(intval($consensusNanos), 4);


echo "\n10. consensusNanos: $consensusNanos\n";
$input .= numberHash($arrNanos);
print_r($arrNanos);


// 11. The topicSequenceNumber from above (8 bytes) 
$vals = decToByte(intval($tx['topicSequenceNumber']));
$input .= numberHash($vals);
echo "\n11. Squence: ";
print_r($vals);

// 12. The output of the SHA-384 digest of the message bytes from the consensusSubmitMessage (48 bytes) 
// Convert the Message into Hex values first
echo "\n12. Message: ";
$hex = strToHex($tx['message']);
// Use pack() method to creae bytes arry
$strHex = messageHash($hex);
// Use bytes array to create has of the Message
$strHash = hash('sha384', "$strHex");
//print_r($strHash);
//Byte array to hold Java Serilized Object i.e. Hashed Message in this case
//Last byte holds data length
//array(117, 113, 0, 126, 0, 0, 0, 0, 0, 48);
$hashArr = [117, 113, 0, 126, 0, 0, 0, 0, 0, 48];         
$input .= messageHash($strHash, $hashArr);

test:

// compare and report results
//$dd = array(-84,-19,0,5,119,8,0,0,0,0,0,0,0,3);
//$byte_array = $dd;//unpack('C*', $dd);
//var_dump($byte_array);
//$binarydata = pack("c*", -84,-19,0,5,119,8,0,0,0,0,0,0,0,$topicRunningHashVersion);
//var_dump($input);
$got = hash('sha384', "$input");
$actual = $tx['topicRunningHash'];
echo "\nExpected Hash: $actual \n";
echo "Got Hash: $got \n";


echo ($got == $actual) ? 'pass' : 'fail';

echo "\n\nsample data:\n";
print_r($tx_previous);
print_r($tx);

// functions
function consensusTimeToEpoch($consensusTime) {
    //eg 2020-08-16T00:56:02.232+0000
    return strtotime($consensusTime) . substr($consensusTime, 20, 3);
}

function consensusTimeToEpochSeconds($consensusTime) {
    //eg 2020-08-16T00:56:02.232+0000
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