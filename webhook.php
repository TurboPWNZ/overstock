<?php
require_once __DIR__ . '/vendor/autoload.php';

\Slando\core\Telegram::setCredentials('8224108464:AAFgcKg-2cTWooWUF6fwsM7iGwXu4SAELFc');

if ($response = \Slando\core\Api::processRequest()) {
    list($chatId, $message) = $response;

    \Slando\core\Telegram::setChatID($response['chatId']);
    \Slando\core\Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
}
