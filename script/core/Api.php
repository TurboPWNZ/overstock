<?php
namespace Slando\core;

use Slando\core\db\User;
use Slando\core\db\UserRequest;

class Api
{
    private static $_userID;
    private static $_chatId;

    private static $_responseMessage;
    private static $_keyboard;

    public static function processRequest()
    {
        // ====== ПОЛУЧАЕМ ВХОДЯЩИЕ ДАННЫЕ ======
        $content = file_get_contents("php://input");
        Logger::log($content);
        $update = json_decode($content, true);

        $step = self::checkProcessedRequest($update);

        return self::runStep($step, $update);
    }

    private static function checkProcessedRequest($update)
    {
        if (!empty($update['message']['from']['id'])) {
            $telegramUserID = $update['message']['from']['id'];
        } elseif (!empty($update['callback_query']['from']['id'])) {
            $telegramUserID = $update['callback_query']['from']['id'];
        } else {
            return 0;
        }

        $user = (new User())->find('telegramUserId = :telegramUserId', ['telegramUserId' => $telegramUserID]);

        if (empty($user)) {
            $user = (new User())->insert(['telegramUserId' => $telegramUserID]);
        }

        self::$_userID = $user['id'];

        $request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_userID]);

        if (!empty($request['step'])) {
            return $request['step'];
        }

        return 0;
    }

    private static function runStep($step, $data)
    {
        switch ($step) {
            case 0:
                return self::welcome($data);
        }
    }

    private static function welcome($update)
    {
        if (isset($update["message"])) {
            self::$_chatId = $update["message"]["chat"]["id"];

            self::$_responseMessage = "Привіт! 👋 Обери дію";
            self::$_keyboard = [
                "inline_keyboard" => [
                    [
                        ["text" => "📢 Опублікувати", "callback_data" => "/publish"],
                        ["text" => "❌ Видалити", "callback_data" => "/delete"]
                    ]
                ]
            ];

            (new UserRequest())->insert(['userId' => self::$_userID, 'step' => 1]);

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
        }

        return false;
    }

    private static function selectAddOrDrop($update)
    {
        if (isset($update["callback_query"])) {
            self::$_chatId = $update["callback_query"]["message"]["chat"]["id"];
            $data = $update["callback_query"]["data"];

            self::processAction($data);

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
        }

        return false;
    }

    private static function processAction($action)
    {
        if ($action == "/start") {
            self::$_responseMessage = "Привет! 👋 Что хочешь сделать?";
            self::$_keyboard = [
                "inline_keyboard" => [
                    [
                        ["text" => "📢 Опубликовать", "callback_data" => "/publish"],
                        ["text" => "❌ Удалить", "callback_data" => "/delete"]
                    ]
                ]
            ];
        } elseif ($action == "/publish") {
            self::$_responseMessage = "Окей, пришли текст своего объявления ✍️";
        } elseif ($action == "/delete") {
            self::$_responseMessage = "Пришли ID объявления, которое нужно удалить ❌";
        } else {
            self::$_responseMessage = "Не понял 😅 Напиши /start";
        }
    }
}