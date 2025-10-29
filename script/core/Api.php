<?php
namespace Slando\core;

use Slando\core\db\Ads;
use Slando\core\db\User;
use Slando\core\db\UserRequest;

class Api
{
    const WELCOME_STEP = 0;

    const ADD_ADS_STEP = 1;

    const ADS_NAME_STEP = 2;
    const ADD_PHONE_STEP = 3;

    private static $_user;
    private static $_request;
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

        self::$_request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_user['id']]);

        if (!empty($_request['step'])) {
            return $_request['step'];
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
            case 2:
                return self::setAdsUserName($data);
            default:
                return self::welcome($data);
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

            if ($data == "/publish") {
                if (!self::isCanPostAds()) {
                    $lastPublishTime = strtotime(self::$_user['lastPost']);

                    self::$_responseMessage =
                        "Публікація безкоштовного оголошення можлива після " .
                        date('d.m.Y H:i:s', $lastPublishTime + 60 * 60 * 12);
                    self::$_keyboard = [
                        "inline_keyboard" => [
                            [
                                ["text" => "💵 Оплатити публікацію 10 грн", "callback_data" => "/publish_pay"]
                            ]
                        ]
                    ];
                } else {
//                self::$_responseMessage = "Окей, вкажи заголовок свого оголошення ✍️";
                    self::$_responseMessage = "Вкажи як можна до тебе звертатись ✍️";

                    if (
                        !empty($update["callback_query"]['from']['first_name']) ||
                        !empty($update["callback_query"]['from']['username'])
                    ) {
                        $keyboard = [[]];

                        if (!empty($update["callback_query"]['from']['first_name']))
                            array_push($keyboard[0], ["text" => $update["callback_query"]['from']['first_name']]);

                        if (!empty($update["callback_query"]['from']['username']))
                            array_push($keyboard[0], ["text" => $update["callback_query"]['from']['username']]);

                        self::$_keyboard = [
                            "keyboard" => $keyboard,
                            "resize_keyboard" => true, // чтобы не занимала весь экран
                            "one_time_keyboard" => false
                        ];
                    }

                    self::setNextStep(self::ADS_NAME_STEP);
                }
            } elseif ($data == "/delete") {
                self::$_responseMessage = "Пришли ID объявления, которое нужно удалить ❌";
            }

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
        }

        return self::runStep(self::WELCOME_STEP, $update);
    }

    private static function setAdsUserName($data)
    {
        self::$_chatId = $data["message"]["chat"]["id"];
        $name = $data["message"]["text"];

        self::$_responseMessage = "Добре " . $name . " вкажіть контактний номер для зв'язку 📲";

        self::updateAds(['name' => $name]);

        self::setNextStep(self::ADD_PHONE_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage,
            'keyboard' => []
        ];
    }

    private static function updateAds($params)
    {
        $ads = self::getCurrentAds();

        (new Ads())->update('id = :id', array_merge([
            'id' => $ads['id']
        ], $params));
    }

    private static function getCurrentAds()
    {
        if (empty(self::$_request['adsId'])) {
            $ads = (new Ads())->insert([
                'userId' => self::$_user['id']
            ]);

            (new UserRequest())->update('id = :id', [
                'id' => self::$_request['id'],
                'adsId' => $ads['id']
            ]);

            return $ads;
        }

        return (new Ads())->findByPk(self::$_request['adsId']);
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