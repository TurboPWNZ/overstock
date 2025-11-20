<?php
namespace Slando\core\olx;

use Slando\core\Configurator;
use Slando\core\olx\action\Help;
use Slando\core\olx\action\Publish;
use Slando\core\olx\action\Start;
use Slando\core\Telegram;

class Handler
{
    private static $_requestData = [
        'chatId' => '',
        'senderId' => '',
        'username' => '',
        'first_name' => '',
        'requestSubject' => ''
    ];

    public static function request()
    {
        $content = file_get_contents("php://input");

        $update = json_decode($content, true);

        self::extractRequestData($update);

        self::runAction();
    }

    private static function runAction()
    {
        switch (self::$_requestData['requestSubject']) {
            case '/help':
                (new Help())->run(self::$_requestData);
                break;
            case '/publish':
                (new Publish())->run(self::$_requestData);
                break;
            default: (new Start())->run(self::$_requestData);
        }
    }

    private static function extractRequestData($update)
    {
        if (!empty($update['message']['from']['id'])) {
            self::$_requestData['chatId'] = $update['message']['chat']['id'];
            self::$_requestData['senderId'] = $update['message']['from']['id'];
            self::$_requestData['username'] = $update['message']['from']['username'];
            self::$_requestData['first_name'] = $update['message']['from']['first_name'];
            self::$_requestData['requestSubject'] = $update['message']['text'];
        } elseif (!empty($update['callback_query']['from']['id'])) {
            self::$_requestData['chatId'] = $update['callback_query']['message']['chat']['id'];
            self::$_requestData['senderId'] = $update['callback_query']['from']['id'];
            self::$_requestData['username'] = $update['callback_query']['from']['username'];
            self::$_requestData['first_name'] = $update['callback_query']['from']['first_name'];
            self::$_requestData['requestSubject'] = $update['callback_query']['data'];
        }
    }
}