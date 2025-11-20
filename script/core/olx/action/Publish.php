<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class Publish extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $subscription = $this->loadSubscriptionInEdit($account);

        if (!empty($subscription)) {
            return false;
        }

        $this->createNewSubscription($account);

        $keyboard =  [
            ["text" => "🔄️ Повернутись", "callback_data" => "/start"],
//            ["text" => "💵 Оплата", "url" => $paymentLink],
//                        ["text" => "📋 Мої оголошення", "callback_data" => "/list"]
        ];

        $response['responseMessage'] = "Вкажіть назву нової підписки";
        $response['keyboard'] = [];

        $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function createNewSubscription($account)
    {
        $subscription = (new Subscription())->insert([
            'userId' => $account['id'],
            'isEditInProgress' => 1,
        ]);
    }
}