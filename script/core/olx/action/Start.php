<?php
namespace Slando\core\olx\action;

use Slando\core\Bank;
use Slando\core\Configurator;
use Slando\core\olx\db\Account;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class Start
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $paymentLink = Bank::getPaymentLink(7777777, 55);

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

        $response['responseMessage'] = "Привіт! 👋 Обери дію";
        $response['keyboard'] = [
        "inline_keyboard" => [
                    $keyboard
                ]
            ];

        $result = Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function loadAccount($requestData)
    {
        $account = (new Account())->findByPk($requestData['senderId']);

        if (empty($account)) {
            $account = (new Account())->insert([
                'telegramUserId' => $requestData['senderId'],
                'username' => $requestData['username'],
            ]);
        }

        return $account;
    }

    protected function isAccountHasSubscription($account)
    {
        return (new Subscription())->find('userId = :userId', ['userId' => $account['id']]);
    }
}