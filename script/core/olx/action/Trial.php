<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Account;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class Trial extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $subscription = $this->loadSubscriptionInEdit($account);

        // Если у парня нет триала то идет в хер
        if ($account['trial'] != 1) {
            $response['responseMessage'] = Translation::text('У вас нет пробного периода');
            $response['keyboard'] = [
                "inline_keyboard" => [
                    [
                        ["text" => Translation::text("🔄️ Вернуться"), "callback_data" => "/start"],
                    ]
                ]
            ];
            Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);

            return false;
        }

        $this->activateTrial($subscription);

        $response['responseMessage'] = Translation::text('Спасибо подписка <b>:subName</b> продлена на 24 часа!',
            [':subName' => $subscription['name']]);
        $response['keyboard'] = [
            "inline_keyboard" => [
                [
                    ["text" => Translation::text("🔄️ Вернуться"), "callback_data" => "/start"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function activateTrial($subscription)
    {
        (new Subscription())->update('id = :id', [
            'id' => $subscription['id'],
            'nextTime' => date('Y-m-d H:i:s', time() + 60 * 15),
            'validUntil' => date('Y-m-d H:i:s', time() + 60 * 60 * 24),
            'isEditInProgress' => 0
        ]);

        (new Account())->update('id = :id',
        [
            'id' => $subscription['userId'],
            'trial' => 0,
        ]);
    }
}