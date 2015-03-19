#!/usr/bin/php5 -q
<?php
// auteur : EtienneLaplane

//Arguments -------------------------
//appelant
$numberSender = $argv[1]; 
//appele - subscriber dynamease
$numberSub = $argv[2];
$numberAsterisk = $argv[2];
$isUrgent = false;
//-----------------------------------

//required include for agi commands
require('/var/lib/asterisk/agi-bin/phpagi.php');


//remplir le JSON
$data = array(
        'callerNumber' => $numberSender,
        'calledNumber' => $numberSub);

$encodeddata = json_encode($data);

// Create the context for the request

$context = stream_context_create(array(
    'http' => array(
        'method' => "POST",
        'header' => "Content-Type: application/json",
        'content' => $encodeddata 
    )
));


//$response = file_get_contents("http:/"."/services.dynamease.net/appless/communication", FALSE, $context);
$response = file_get_contents("http:/"."/localhost:9090/communication", FALSE, $context);
//---------------------------------------------------------------------------------------------------------



// Decode the response
$responseData = json_decode($response, TRUE);
echo($response);

// Print the date from the response
$autorise = $responseData["canContact"];
//echo($autorise);
//echo("\n");
$address = $responseData["address"];
if($address == "0123456789"){$address="0001";}
if($address == "0223456789"){$address="0002";}

//echo($address);
//echo("\n");
$corpid = $responseData["corp_id"];
$subid = $responseData["sub_id"];
$subName = $responseData["subName"];
$agi = new AGI();
$agi->NoOp("noop, seriously ?");
if ($response == null || $autorise=="" || $autorise==null){
                                $agi->exec('AGI','googletts.agi,"votre correspondant nest pas joignable pour le moment",fr');
                                $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');

return 0;
}

if ($autorise == "REFUSED")
        {
        if($corpid != 0){
                if($subid == 0){
                        if(file_exists("/var/lib/asterisk/sounds/$corpid.refused.ulaw")){
				$agi->exec('Playback',"$corpid.refused");
                        } else {
                                $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
                                $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
                                $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');

                        }
                } else {
                        if(file_exists("/var/lib/asterisk/sounds/$subid.refused.ulaw")){
                                //echo("playback du fichier subid.refused");
                                $agi->exec('Playback',"$subid.refused");
                        } else if(file_exists("/var/lib/asterisk/sounds/$corpid.refused.ulaw")){
                                $agi->exec('Playback',"$corpid.refused");
                        } else {
		                $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
		 	        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
    				$agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');
			}
                }
        } else {
                if($subid != 0){
		    if(file_exists("/var/lib/asterisk/sounds/$subid.refused.ulaw")){
                                $agi->exec('Playback',"$subid.refused");
                        }
                        else {
			        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        			$agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
        			$agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');
			}
                } else {
	        	$agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
		        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
       			$agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');
		}
        }

        $agi->exec('Hangup',16);
        return 0;
        }


if ($autorise == "DIRECT")
        {

	$agi->exec('Playtones',440/1500,0/3500);
	$agi->exec('NoOp',"call ACCEPTED");
        $agi->exec('Dial',"SIP/".$address, 20, m);
        $agi->exec('Hangup','16');
        return 0;
        }
if ($autorise == "URGENCY")
        {
        if($corpid != 0){
                if($subid == 0){
                        if(file_exists("/var/lib/asterisk/sounds/$corpid.urgency.ulaw")){
                                $agi->exec('Playback',"$corpid.urgency");
                        } else {
			        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        			$agi->exec('AGI','googletts.agi,"est actuellement occupé",fr');
        			$agi->exec('AGI','googletts.agi,"Si vous souhaitez le déranger tapez dièse",fr');
                        }
                } else {
                        if(file_exists("/var/lib/asterisk/sounds/$subid.urgency.ulaw")){
                                $agi->exec('Playback',"$subid.urgency");
                        } else if(file_exists("/var/lib/asterisk/sounds/$corpid.urgency.ulaw")){
                                $agi->exec('Playback',"$corpid.urgency");
                        } else {
			        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        			$agi->exec('AGI','googletts.agi,"est actuellement occupé",fr');
  			        $agi->exec('AGI','googletts.agi,"Si vous souhaitez le déranger tapez dièse",fr');
                        }
                }
        } else {
                if($subid != 0){
   			 if(file_exists("/var/lib/asterisk/sounds/$subid.urgency.ulaw")){
                                $agi->exec('Playback',"$subid.urgency");
                         } else {
         			$agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        			$agi->exec('AGI','googletts.agi,"est actuellement occupé",fr');
        			$agi->exec('AGI','googletts.agi,"Si vous souhaitez le déranger tapez dièse",fr');
			}
                } else {

        	$agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        	$agi->exec('AGI','googletts.agi,"est actuellement occupé",fr');
        	$agi->exec('AGI','googletts.agi,"Si vous souhaitez le déranger tapez dièse",fr');
		}
	}

//$data = array('subId' => "".$subid);

//$encodeddata = json_encode($data);

// Create the context for the request

//$context = stream_context_create(array(
//    'http' => array(
//        'method' => "POST",
//        'header' => "Content-Type: application/json-urgentcallcontact",
//        'content' => $encodeddata 
//    )
//));
//$response = file_get_contents("http:/"."/monastir.dynamease.net/appless/communication", FALSE, $context);
//$response = file_get_contents("http:/"."/localhost:9090/communication", FALSE, $context);

//if ($response == "Error" || $response == "error" || $response == null){
//                                $agi->exec('AGI','googletts.agi,"votre correspondant nest pas joignable pour le moment",fr');
//                                $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');
//
//return 0;

//}
//	$ret = $agi->exec(WaitExten(100000));

//	if($ret == 0){
$agi2 = new AGI();
//	$agi2->wait_for_digit(200000000000000);

        	   $agi2->exec('Dial',"SIP/".$address."@".$numberAsterisk, 20, g);

        return 0;

	

        }        
if ($autorise == "DELAYED")
        {

        if($corpid != 0){
                if($subid == 0){
                        if(file_exists("/var/lib/asterisk/sounds/$corpid.delayed.ulaw")){
                                //echo("playback du fichier corpid.refused");
                                $agi->exec('Playback',"$corpid.delayed");
                        } else {
                                        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
        $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');

                        }
                } else {
//			echo("truc");
                        if(file_exists("/var/lib/asterisk/sounds/$subid.delayed.ulaw")){
//                                echo("playback du fichier $subid.refused");
                                $agi->exec('Playback',"$subid.delayed");
                        } else if(file_exists("/var/lib/asterisk/sounds/$corpid.delayed.ulaw")){
                                //echo("playback du fichier corpid.refused");
                                $agi->exec('Playback',"$corpid.delayed");
                        } else {
                                //echo("$subName n'est pas joignable pour le moment, mais nous serons$
        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
        $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');
                        }
                }
        } else {
		if($subid != 0){
    if(file_exists("/var/lib/asterisk/sounds/$subid.delayed.ulaw")){
                                $agi->exec('Playback',"$subid.delayed");
			}else {
        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
        $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');}



		} else {
        $agi->exec("AGI","googletts.agi,\"{$subName}\",fr");
        $agi->exec('AGI','googletts.agi,"nest pas joignable pour le moment",fr');
        $agi->exec('AGI','googletts.agi,"Mais nous serons prevenus de votre appel",fr');}
        }

        $agi->exec('Hangup',16);
        return 0;
        }

?>
