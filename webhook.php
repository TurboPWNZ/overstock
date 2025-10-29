<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/script/core/Telegram.php';
require_once __DIR__ . '/script/core/Api.php';

Telegram::setCredentials('8224108464:AAFgcKg-2cTWooWUF6fwsM7iGwXu4SAELFc');

if ($response = Api::processRequest()) {
    list($chatId, $message) = $response;

    Telegram::setChatID($chatId);
    Telegram::sendRequest($message);
}
