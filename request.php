<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/script/core/Telegram.php';

//var_dump($_REQUEST);
//var_dump($_FILES);
//
//exit;

$data['name'] = '🗣 <i>'. strip_tags($_REQUEST["name"]) . '</i>' . "\n\n";

if (!empty($_REQUEST["company"])) {
    $data['company'] = ' 🏢 <b>' . strip_tags($_REQUEST["company"]) . '</b>' . "\n\n";
}

$data['phone'] =  "📞" . strip_tags($_REQUEST["phone"]) . "\n\n";
$data['description'] =  strip_tags($_REQUEST["description"]) . "\n\n";

//Telegram::sendRequest(implode($data));
Telegram::sendMediaRequest(implode($data), $_FILES);