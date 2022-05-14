<?php

// Данные интеграции с paybox.money
$pbId = "";
$pbSecret = "";
$ssToken = "";

// Сервисные данные
//$logUrl = "https://log.mufiksoft.com/smartsender-paybox-".$_SERVER["HTTP_HOST"];
$dir = dirname($_SERVER["PHP_SELF"]);
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
$url = explode("?", $url);
$url = $url[0];