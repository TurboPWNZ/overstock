<?php
namespace Slando\core;

class Api
{
    private static $_chatId;

    private static $_responseMessage;
    private static $_keyboard;

    public static function processRequest()
    {
        // ====== ПОЛУЧАЕМ ВХОДЯЩИЕ ДАННЫЕ ======
        $content = file_get_contents("php://input");
        Logger::log($content);
        $update = json_decode($content, true);

        if (isset($update["message"])) {
            self::$_chatId = $update["message"]["chat"]["id"];
            $text = trim($update["message"]["text"]);

            self::processAction($text);

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
        }

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