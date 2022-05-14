<?php

// v1   13.05.2022
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
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
function makeFlatParamsArray($arrParams, $parent_name = '') {
    $arrFlatParams = [];
    $i = 0;
    foreach ($arrParams as $key => $val) {
        $i++;
        /**
         * Имя делаем вида tag001subtag001
         * Чтобы можно было потом нормально отсортировать и вложенные узлы не запутались при сортировке
         */
        $name = $parent_name . $key . sprintf('%03d', $i);
        if (is_array($val)) {
            $arrFlatParams = array_merge($arrFlatParams, makeFlatParamsArray($val, $name));
            continue;
        }
        $arrFlatParams += array($name => (string)$val);
    }

    return $arrFlatParams;
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

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
}
if ($input["description"] == NULL) {
    $result["state"] = false;
    $result["message"]["description"] = "description is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"]["action"] = "action is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Получение списка товаров в корзине пользователя
$cursor = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=1&limitation=20", $ssToken), true);
if ($cursor["error"] != NULL && $cursor["error"] != 'undefined') {
    $result["state"] = false;
    $result["message"]["error"][] = "Ошибка получения данных из SmartSender";
    if ($cursor["error"]["code"] == 404 || $cursor["error"]["code"] == 400) {
        $result["message"]["user"][] = "Пользователь не найден. Проверте правильность идентификатора пользователя и приналежность токена к текущему проекту.";
    } else if ($cursor["error"]["code"] == 403) {
        $result["message"]["config"][] = "Токен проекта SmartSender указан неправильно. Проверте правильность токена.";
    }
    echo json_encode($result);
    exit;
} else if (empty($cursor["collection"])) {
    $result["state"] = false;
    $result["message"]["checkout"][] = "Корзина пользователя пустая. Для тестирования добавте товар в корзину.";
    echo json_encode($result);
    exit;
}
$pages = $cursor["cursor"]["pages"];
$count = 0;
for ($i = 1; $i <= $pages; $i++) {
    $checkout = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=".$i."&limitation=20", $ssToken), true);
    $essences = $checkout["collection"];
    $data["currency"] = $essences[0]["cash"]["currency"];
    foreach ($essences as $product) {
		$summ[] = $product["pivot"]["quantity"] * $product["cash"]["amount"];
		//$sendData["pg_receipt_positions[".$count."][count]"] = $product["pivot"]["quantity"];
		//$sendData["pg_receipt_positions[".$count."][name]"] = $product["product"]["name"].": ".$product["name"];
		//$sendData["pg_receipt_positions[".$count."][price]"] = $product["cash"]["amount"];
		$count ++;
    }
}

// Формирование данных
$sendData["pg_order_id"] = $input["userId"]."-".mt_rand(1000000, 9999999);
$sendData["pg_merchant_id"] = $pbId;
$sendData["pg_amount"] = array_sum($summ);
settype($sendData["pg_amount"], "string");
$sendData["pg_description"] = $input["description"];
$sendData["pg_salt"] = generateString(20);
$sendData["pg_currency"] = $data["currency"];
$sendData["pg_result_url"] = $url."/callback.php?action=".$input["action"];;
$sendData["pg_request_method"] = "POST";
if ($input["phone"] != NULL) {
    $sendData["pg_user_phone"] = str_replace(array("+", "-", " "), "", $input["phone"]);;
}
if ($input["email"] != NULL) {
    $sendData["pg_user_contact_email"] = $input["email"];
}
if ($input["test"] == true) {
    $sendData["pg_testing_mode"] = "1";
}
$sendData["pg_user_id"] = $input["userId"];

// Генерация подписи
$requestForSignature = makeFlatParamsArray($sendData);
ksort($requestForSignature);
array_unshift($requestForSignature, 'init_payment.php');
array_push($requestForSignature, $pbSecret);
$sendData['pg_sig'] = md5(implode(';', $requestForSignature));

$result = new SimpleXMLElement(send_forward($sendData, "https://api.paybox.money/init_payment.php"));

echo json_encode($result);








