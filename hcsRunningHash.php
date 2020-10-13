<?php
// DRAFT - NOT WORKING
// http://kpay.live/hash/hcsRunningHash.php
// https://github.com/topshef/hash/blob/master/hcsRunningHash.php

// calculate HCS running hash
// ref https://github.com/hashgraph/hedera-services/issues/88


	echo '<pre>';
	$topicRunningHashVersion = 3;
	echo "topicRunningHashVersion $topicRunningHashVersion \n";

// read sample data from mirror node
	$url='http://hbar.live/mirror/hcs/?topicId=0.0.43738&limit=2&unpack=hex&fromSeq=5801&sortBy=asc';
	$json = file_get_contents($url);
	$arr = json_decode($json, true)['data'];

	$tx_previous = $arr[0];
	$tx = $arr[1];

	$input = '';

// 1. The previous running hash of the topic (48 bytes)
	$input .= pack('H*', $tx_previous['topicRunningHash']);  

// 2. The topicRunningHashVersion below (8 bytes) 
	$input .= pack('Q', $topicRunningHashVersion);      

// 3. The payer account's shard (8 bytes) 
// 4. The payer account's realm (8 bytes) 
// 5. The payer account's number (8 bytes)
	$readableTransactionID = $tx['readableTransactionID'];
	$payerID = substr($readableTransactionID, 0, strpos($readableTransactionID,'@'));
	foreach (explode('.',$payerID) as $v) $input .=  pack('Q',intval($v));

// 6. The topic's shard (8 bytes) 
// 7. The topic's realm (8 bytes) 
// 8. The topic's number (8 bytes) 
	$topicID = $tx['topicID'];
	foreach (explode('.',$topicID) as $v) $input .=  pack('Q', intval($v));
  
// 9. The number of seconds since the epoch before the ConsensusSubmitMessage reached consensus (8 bytes) 	
	//$input .= pack('Q',1 * consensusTimeToEpoch($consensusAt));
	$consensusTime = $tx['consensusTime'];
	echo "DG consensusTime =$consensusTime\n";
	
	$consensusSeconds = consensusTimeToEpochSeconds(substr($tx['consensusTime'],0,19));
	echo "DG consensusSeconds =$consensusSeconds\n";
	$dateConsensus =  gmdate("Y-m-d H:i:s", $consensusSeconds);
	echo "check $dateConsensus\n";
	$input .= pack('Q',1 * $consensusSeconds);
 
// 10. The number of nanoseconds since 9. before the ConsensusSubmitMessage reached consensus (4 bytes) 
	
	// we need the consensus time in nanoseconds - get this from Kabuto
	$kabutoTxId = str_replace('-','.', $readableTransactionID);
	$url_kabuto = "https://api.kabuto.sh/v1/transaction?q={%22id%22:%20%22{$kabutoTxId}%22}";
	$response_kabuto = file_get_contents($url_kabuto);
	$arr_kabuto = json_decode($response_kabuto, true);
	$consensusAt = $arr_kabuto['transactions'][0]['consensusAt'];
	
	echo "kabuto consensusAt =$consensusAt\n";
	
	$consensusSeconds = consensusTimeToEpochSeconds(substr($consensusAt,0,19));
	$consensusNanos = 1 * substr($consensusAt,20,9);
	echo "kabuto consensusSeconds =$consensusSeconds\n";  
	echo "kabuto consensusNanos =$consensusNanos\n";
	
	//consensus time
	//2020-10-05T22:16:03.442+0000   <-- DG (summary API)
	//2020-10-05T22:16:03.442497001Z <-- kabuto raw tx
	
	$input .=  pack('L', $consensusNanos);
	
// 11. The topicSequenceNumber from above (8 bytes) 
	$input .=  pack('Q', $tx['topicSequenceNumber']);
  
// 12. The output of the SHA-384 digest of the message bytes from the consensusSubmitMessage (48 bytes) 
	$hashMsg = hash('sha384', $tx['message']);               // https://www.php.net/manual/en/function.hash.php
	$input .=  pack('H*', $hashMsg);						 // https://www.php.net/manual/en/function.pack.php
	//echo strlen(pack('H*', $hashMsg));

test:

// compare and report results
	$expected = hash('sha384', $input);
	echo "expected runningHash: $expected \n";	
	$actual = $tx['topicRunningHash'];
	echo "actual runningHash: $actual \n";

	echo ($expected==$actual) ? 'pass' : 'fail';

	echo "\n\nsample data:\n";
	print_r($arr);

// functions
	function consensusTimeToEpoch($consensusTime) {
		//eg 2020-08-16T00:56:02.232+0000
		return strtotime($consensusTime) . substr($consensusTime, 20, 3) ;
	}

	function consensusTimeToEpochSeconds($consensusTime) {
		//eg 2020-08-16T00:56:02.232+0000
		return strtotime($consensusTime) ;
	}


?>
