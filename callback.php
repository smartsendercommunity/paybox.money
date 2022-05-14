<?php

// v1   13.05.2022
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/xml; charset=utf-8');

http_response_code(200);

//--------------

$input = $_POST;
include ('config.php');

// Functions
{
function send_forward($inputJSON, $link){
	
$request = 'POST';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function send_bearer($url, $token, $type = "GET", $param = []){
	
		
$descriptor = curl_init($url);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('User-Agent: M-Soft Integration', 'Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function generateString($strength = 16) {
    $inputString = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $inputLength = strlen($inputString);
    $randomString = '';
    for($i = 0; $i < $strength; $i++) {
        $randomCharacter = $inputString[mt_rand(0, $inputLength - 1)];
        $randomString .= $randomCharacter;
    }
    return $randomString;
}
}

$sign = $input["pg_sig"];
$checkSign = $input;
unset($checkSign["pg_sig"]);
ksort($checkSign);
array_unshift($checkSign, 'callback.php');
$checkSign[] = $pbSecret;
$valideSign = md5(implode(";", $checkSign));

if ($sign == $valideSign && $input["pg_result"] == "1") {
    // Все ок
    $userId = (explode("-", $input["pg_order_id"]))[0];
    $trigger["name"] = $input["action"];
    $log["SmartSender"] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/fire", $ssToken, "POST", $trigger), true);
}

$randString = generateString(20);
echo '<?xml version="1.0" encoding="utf-8"?>
<response>
    <pg_status>ok</pg_status>
    <pg_description>Payment ok</pg_description>
    <pg_salt>'.$randString.'</pg_salt>
    <pg_sig>'.md5("callback.php;Payment ok;".$randString.";ok;",$pbSecret).'</pg_sig>
</response>';



$log["checkSign"] = $checkSign;
$log["valideSign"] = $valideSign;
$log["post"] = $_POST;

//send_forward(json_encode($log), $logUrl);

