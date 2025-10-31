<?php
require_once __DIR__ . '/vendor/autoload.php';

\Slando\core\Telegram::setCredentials('8224108464:AAFgcKg-2cTWooWUF6fwsM7iGwXu4SAELFc');

//$file = \Slando\core\Telegram::downloadFile("AgACAgIAAxkBAAMdaQI6pLb_Ge44hgnR6cF_UBSuWiUAAl0BMhunLxBI0GbUZ2sR5h4BAAMCAAN4AAM2By");

if ($response = \Slando\core\Api::processRequest()) {
    list($chatId, $message) = $response;

    \Slando\core\Telegram::setChatID($response['chatId']);
    $result = \Slando\core\Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    var_dump($result);
}
