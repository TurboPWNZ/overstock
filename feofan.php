<?php
use Slando\core\Logger;

require_once __DIR__ . '/vendor/autoload.php';

\Slando\core\Telegram::setCredentials('8253837427:AAHYJR5-cU0zC7FWscCpjCo5piqsnEVTAZ8');

$content = file_get_contents("php://input");

Logger::log($content);