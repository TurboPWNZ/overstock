<?php
//https://api.telegram.org/bot{HASH}/setWebhook?url=https://ithelp.uno/olx_bot.php

use Slando\core\Logger;

require_once __DIR__ . '/vendor/autoload.php';

$content = file_get_contents("php://input");

Logger::log($content);