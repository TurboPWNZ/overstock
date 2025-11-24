<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class ListAction extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $subscriptions = $this->getSubscriptions($account['id']);

        $list = '';
        foreach ($subscriptions as $subscription) {
            $list.= Translation::text(" - #:subId <b>:subName</b> Активна до :subValidUntil \n", [
                ':subId' => $subscription['id'],
                ':subName' => $subscription['name'],
                ':subUrl' => $subscription['url'],
                ':subValidUntil' => $subscription['validUntil'],
            ]);
            $list.= Translation::text("/edit_sub_:subId - изменить /remove_sub_:subId - удалить",
                [':subId' => $subscription['id']]);
        }

        $response['responseMessage'] = Translation::text("Список ваших подписок: \n\n");
        $response['responseMessage'].= $list;
//        $response['responseMessage'].= Translation::text("\n Для того что бы изменить подписку пришлите комманду
//        /edit_sub id  где id номер вашей подписки.\n Для того что бы удалить подписку пришлите комманду
//        /remove_sub id  где id номер вашей подписки.");

        $response['keyboard'] = [
            "inline_keyboard" => [
                [
                    ["text" => Translation::text("🔄️ Вернуться"), "callback_data" => "/start"],
                    ["text" => Translation::text("📢 Добавить подписку"), "callback_data" => "/publish"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function getSubscriptions($userId)
    {
        return (new Subscription())->findAll('userId = :userId', ['userId' => $userId]);
    }
}