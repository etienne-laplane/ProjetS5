#!/usr/bin/php5 -q
//$response = file_get_contents("http:/"."/ns3313891.ip-5-135-166.eu/appless/communication", FALSE, $context);<?php

// auteur : EtienneLaplane

//Arguments -------------------------
//appelant
$numberSender = "0687593706"; 
//appele - subscriber dynamease
$numberSub = "0278990114";
$isUrgent = false;
//-----------------------------------

//required include for agi commands
//require('/var/lib/asterisk/agi-bin/phpagi.php');

//remplir le JSON
$data = array(
	'sender' => array('firstName' => "",
				'lastName' => "",
				'info' => $numberSender),
	'receiver' => array('firstName' => "",
				'lastName' => "",
				'info' => $numberSub),
	'urgentCall' => $isUrgent);

$encodeddata = json_encode($data);

// Create the context for the request
$context = stream_context_create(array(
    'http' => array(
        'method' => "POST",
        'header' => "Content-Type: application/json",
        'content' => $encodeddata 
    )
));

// Send the request
$response = file_get_contents("http:/"."/localhost:9090/communication", FALSE, $context);
//$response = file_get_contents("http:/"."/ns3313891.ip-5-135-166.eu/appless/communication", FALSE, $context);
//---------------------------------------------------------------------------------------------------------

//traitement de la réponse

// Check for errors
//if($response === FALSE){
//    die('Error');
//}

// Decode the response
$responseData = json_decode($response, TRUE);

// Print the date from the response
$autorise = $responseData["canContact"];
$address = $responseData["address"];
echo $autorise;
echo "SIP/".$address."@forfait-ovh";

// AGI START

$agi = new AGI();
switch ($autorise){
	case "REFUSED":
		$agi->hangup();
		echo "a";
	case "URGENCY":
		$agi->exec('Dial',"SIP/".$address."@forfaitovh-ovh", 20);
	case "DELAYED":
		$agi->hangup();
	case "DIRECT" :
		$agi->exec('Dial',"SIP/".$address."@forfaitovh-ovh", 20);
	case "MESSAGE":
		$agi->hangup();
}
?>
