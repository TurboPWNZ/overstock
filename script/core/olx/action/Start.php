<?php
namespace Slando\core\olx\action;

use Slando\core\Bank;
use Slando\core\Configurator;
use Slando\core\Telegram;

class Start extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

//        $paymentLink = Bank::getPaymentLink(7777777, 55);

        $keyboard =  [
            ["text" => "ℹ️ Допомога", "callback_data" => "/help"],
//            ["text" => "💵 Оплата", "url" => $paymentLink],
//                        ["text" => "📋 Мої оголошення", "callback_data" => "/list"]
        ];

        if ($this->isAccountHasSubscription($account)) {
            $keyboard[] = ["text" => "📋 Мої оголошення", "callback_data" => "/list"];
        } else {
            $keyboard[] = ["text" => "📢 Додати підписку", "callback_data" => "/publish"];
        }

        $response['responseMessage'] = "<b>Привет! 👋</b>

Здесь ты можешь оформить <b>подписку на новые объявления с OLX</b>.
Бот будет присылать тебе свежие объявления по твоему запросу — 
<b>в течение 15 минут после их появления</b> на площадке.

🔔 <b>Если ты новый пользователь — тебе доступен бесплатный пробный период 24 часа</b>.
Во время триала можно активировать одну подписку и получать все новые объявления без ограничений.";
        $response['keyboard'] = [
        "inline_keyboard" => [
                    $keyboard
                ]
            ];

        $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }
}