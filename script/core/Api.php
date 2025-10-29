<?php
namespace Slando\core;

use Slando\core\db\User;
use Slando\core\db\UserRequest;

class Api
{
    const WELCOME_STEP = 0;

    const ADD_ADS_STEP = 1;

    private static $_user;
    private static $step;
    private static $_chatId;

    private static $_responseMessage;
    private static $_keyboard;

    public static function processRequest()
    {
        // ====== ПОЛУЧАЕМ ВХОДЯЩИЕ ДАННЫЕ ======
        $content = file_get_contents("php://input");
        Logger::log($content);
        $update = json_decode($content, true);

        self::$step = self::checkProcessedRequest($update);

        return self::runStep(self::$step, $update);
    }

    private static function checkProcessedRequest($update)
    {
        if (!empty($update['message']['from']['id'])) {
            $telegramUserID = $update['message']['from']['id'];
        } elseif (!empty($update['callback_query']['from']['id'])) {
            $telegramUserID = $update['callback_query']['from']['id'];
        } else {
            return self::WELCOME_STEP;
        }

        $user = (new User())->find('telegramUserId = :telegramUserId', ['telegramUserId' => $telegramUserID]);

        if (empty($user)) {
            $user = (new User())->insert(['telegramUserId' => $telegramUserID]);
        }

        self::$_user = $user;

        $request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_user['id']]);

        if (!empty($request['step'])) {
            return $request['step'];
        }

        return self::WELCOME_STEP;
    }

    private static function runStep($step, $data)
    {
        switch ($step) {
            case 0:
                return self::welcome($data);
            case 1:
                return self::selectAddOrDrop($data);
        }
    }

    private static function welcome($update)
    {
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

            self::setNextStep(self::ADD_ADS_STEP);

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
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

        return self::runStep(self::WELCOME_STEP, $update);
    }

    private static function processAction($action)
    {
        if ($action == "/publish") {
            if (!self::isCanPostAds()) {
                $lastPublishTime = strtotime(self::$_user['lastPost']);

                self::$_responseMessage =
                    "Публікація безкоштовного оголошення можлива після " .
                    date('Y-m-d H:i:s', $lastPublishTime + 60 * 60 * 12);
                self::$_keyboard = [
                    "inline_keyboard" => [
                        [
                            ["text" => "💵 Оплатити публікацію 10 грн", "callback_data" => "/publish_pay"]
                        ]
                    ]
                ];
            } else {
                self::$_responseMessage = "Окей, пришли текст своего объявления ✍️";
            }
        } elseif ($action == "/delete") {
            self::$_responseMessage = "Пришли ID объявления, которое нужно удалить ❌";
        }
    }

    private static function isCanPostAds()
    {
        $lastPublishTime = strtotime(self::$_user['lastPost']);

        return (time() - $lastPublishTime - 60 * 60 * 12) > 0;
    }

    private static function setNextStep($step)
    {
        $request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_user['id']]);

        if (empty($request)) {
            (new UserRequest())->insert(['userId' => self::$_user['id'], 'step' => 1]);
        }

        (new UserRequest())->update('id = :id', [
            'id' => $request['id'],
            'step' => $step
        ]);
    }
}