<?php

use Slando\core\Telegram;

require_once __DIR__ . '/vendor/autoload.php';

Telegram::setCredentials('8224108464:AAFgcKg-2cTWooWUF6fwsM7iGwXu4SAELFc');

//$file = \Slando\core\Telegram::downloadFile("AgACAgIAAxkBAAMdaQI6pLb_Ge44hgnR6cF_UBSuWiUAAl0BMhunLxBI0GbUZ2sR5h4BAAMCAAN4AAM2By");

//Telegram::setChatID('-1002254357315');
//
//$result = Telegram::sendMessageWithKeyboard('test', []);
//
//var_dump($result->response);
//
//exit();
if ($response = \Slando\core\Api::processRequest()) {
    list($chatId, $message) = $response;

    Telegram::setChatID($response['chatId']);
    $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);

    \Slando\core\Logger::log($result->response);
}
