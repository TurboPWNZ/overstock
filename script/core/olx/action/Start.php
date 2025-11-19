<?php
namespace Slando\core\olx\action;

use Slando\core\Bank;
use Slando\core\Configurator;
use Slando\core\Telegram;

class Start
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $paymentLink = Bank::getPaymentLink(7777777, 55);

        $response['responseMessage'] = "Привіт! 👋 Обери дію";
        $response['keyboard'] = [
        "inline_keyboard" => [
                    [
                        ["text" => "📢 Добавить запрос", "callback_data" => "/publish"],
                        ["text" => "💵 Оплата", "url" => $paymentLink],
//                        ["text" => "📋 Мої оголошення", "callback_data" => "/list"]
                    ]
                ]
            ];

        $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }
}