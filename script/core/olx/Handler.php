<?php
namespace Slando\core\olx;

use Slando\core\olx\action\Common;
use Slando\core\olx\action\Help;
use Slando\core\olx\action\ListAction;
use Slando\core\olx\action\Publish;
use Slando\core\olx\action\Start;
use Slando\core\olx\action\Pay;
use Slando\core\olx\action\Trial;
use Slando\core\olx\action\EditSub;
use Slando\core\olx\action\RemoveSub;
use Slando\core\olx\action\ConfirmRemove;
use Slando\core\olx\action\CancelEdit;
use Slando\core\olx\informer\Sender;

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

    /**
     * @return void
     */
    public static function scheduler()
    {
        Sender::process();
    }

    private static function runAction()
    {
        $requestSubject = self::$_requestData['requestSubject'];
        
        // Проверяем команды с параметрами через regex
        if (preg_match('/^\/edit_sub_\d+$/', $requestSubject)) {
            (new EditSub())->run(self::$_requestData);
            return;
        }
        
        if (preg_match('/^\/remove_sub_\d+$/', $requestSubject)) {
            (new RemoveSub())->run(self::$_requestData);
            return;
        }
        
        if (preg_match('/^\/confirm_remove_\d+$/', $requestSubject)) {
            (new ConfirmRemove())->run(self::$_requestData);
            return;
        }
        
        if (preg_match('/^\/cancel_edit_\d+$/', $requestSubject) || $requestSubject === '/cancel') {
            (new CancelEdit())->run(self::$_requestData);
            return;
        }
        
        // Стандартные команды
        switch ($requestSubject) {
            case '/start':
                (new Start())->run(self::$_requestData);
                break;
            case '/help':
                (new Help())->run(self::$_requestData);
                break;
            case '/publish':
                (new Publish())->run(self::$_requestData);
                break;
            case '/pay':
                (new Pay())->run(self::$_requestData);
                break;
            case '/trial':
                (new Trial())->run(self::$_requestData);
                break;
            case '/list':
                (new ListAction())->run(self::$_requestData);
                break;
            default: (new Common())->run(self::$_requestData);
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