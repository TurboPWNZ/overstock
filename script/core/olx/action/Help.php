<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\Telegram;

class Help
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $keyboard =  [
            ["text" => "🔄️ Повернутись", "callback_data" => "/start"],
//            ["text" => "💵 Оплата", "url" => $paymentLink],
//                        ["text" => "📋 Мої оголошення", "callback_data" => "/list"]
        ];

        $response['responseMessage'] = "Страница помощи";
        $response['keyboard'] = [
            "inline_keyboard" => [
                $keyboard
            ]
        ];

        $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }
}