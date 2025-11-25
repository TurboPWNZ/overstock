<?php
namespace Slando\core\olx\action;

use Composer\Util\Svn;
use Slando\core\Configurator;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class Common extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $subscription = $this->loadSubscriptionInEdit($account);

        if (empty($subscription)) {
            $keyboard =  [
                ["text" => "🔄️ Повернутись", "callback_data" => "/start"]
            ];

            $response['responseMessage'] = "Помилка";
            $response['keyboard'] = [
                "inline_keyboard" => [
                    $keyboard
                ]
            ];
            Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);

            return false;
        }

        $this->updateSubscription($subscription, $requestData);

        if (empty($subscription['name'])) {
            $responseMessage = 'Вкажіть ссилку на пошук оголошень';
            $keyboard = [];
        } else {
            $responseMessage = 'Підписка сформована';
            $keyboard = [["text" => "🔀 Запустити", "callback_data" => "/pay"]];
        }


        $response['responseMessage'] = $responseMessage;
        $response['keyboard'] = [
            "inline_keyboard" => [
                $keyboard
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function updateSubscription($subscription, $requestData)
    {
        $updateData = [
            'id' => $subscription['id']
        ];

        if (empty($subscription['name'])) {
            $updateData['name'] = $requestData['requestSubject'];
        } elseif (empty($subscription['url'])) {
            if (strpos($requestData['requestSubject'], 'www.olx.ua/uk') === false) {
                $requestData['requestSubject'] = str_replace('olx.ua', 'olx.ua/uk', $requestData['requestSubject']);
            }

            $updateData['url'] = $requestData['requestSubject'];
            // Сбрасываем флаг редактирования после завершения
            $updateData['isEditInProgress'] = 0;
        }

        (new Subscription())->update('id = :id', $updateData);
    }
}