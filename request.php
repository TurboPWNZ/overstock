<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/script/core/Telegram.php';

$data['name'] = '🗣 <i>'. strip_tags($_REQUEST["name"]) . '</i>' . "\n\n";

if (!empty($_REQUEST["company"])) {
    $data['company'] = ' 🏢 <b>' . strip_tags($_REQUEST["company"]) . '</b>' . "\n\n";
}

$data['phone'] =  "📞" . strip_tags($_REQUEST["phone"]) . "\n\n";
$data['problem'] =  strip_tags($_REQUEST["problem"]) . "\n\n";

Telegram::sendRequest(implode($data));