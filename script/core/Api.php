<?php

class Api
{
    private static $_chatId;

    private static $_responseMessage;
    private static $_keyboard;

    public static function processRequest()
    {
        // ====== ПОЛУЧАЕМ ВХОДЯЩИЕ ДАННЫЕ ======
        $content = file_get_contents("php://input");

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
        } elseif ($action == "1" || stripos($action, "опубликовать") !== false) {
            self::$_responseMessage = "Окей, пришли текст своего объявления ✍️";
        } elseif ($action == "2" || stripos($action, "удалить") !== false) {
            self::$_responseMessage = "Пришли ID объявления, которое нужно удалить ❌";
        } else {
            self::$_responseMessage = "Не понял 😅 Напиши /start";
        }
    }
}