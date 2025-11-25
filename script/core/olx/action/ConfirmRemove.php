<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class ConfirmRemove extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        // Извлекаем ID подписки из команды /confirm_remove_123
        $subId = $this->extractSubId($requestData['requestSubject']);

//        if (!$subId) {
            $this->sendError("Невірний формат команди");
            return false;
//        }

        // Проверяем, принадлежит ли подписка пользователю
        $subscription = $this->getSubscription($subId, $account['id']);

        if (empty($subscription)) {
            $this->sendError("Підписка не знайдена або не належить вам");
            return false;
        }

        // Удаляем подписку
        $this->deleteSubscription($subId);

        // Отправляем уведомление об успешном удалении
        $response['responseMessage'] = Translation::text(
            "✅ Підписка #:subId <b>:subName</b> успішно видалена",
            [
                ':subId' => $subscription['id'],
                ':subName' => $subscription['name'],
            ]
        );

        $response['keyboard'] = [
            "inline_keyboard" => [
                [
                    ["text" => Translation::text("📋 Мої підписки"), "callback_data" => "/list"],
                    ["text" => Translation::text("🏠 На початок"), "callback_data" => "/start"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function extractSubId($requestSubject)
    {
        // Извлекаем ID из строки вида "/confirm_remove_123"
        if (preg_match('/\/confirm_remove_(\d+)/', $requestSubject, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    protected function getSubscription($subId, $userId)
    {
        return (new Subscription())->find(
            'id = :id AND userId = :userId',
            ['id' => $subId, 'userId' => $userId]
        );
    }

    protected function deleteSubscription($subId)
    {
        // Можно использовать soft delete, добавив поле isDeleted
        // Или жесткое удаление через delete метод
        (new Subscription())->delete('id = :id', ['id' => $subId]);
    }

    protected function sendError($message)
    {
        $response['responseMessage'] = "❌ " . Translation::text($message);
        $response['keyboard'] = [
            "inline_keyboard" => [
                [
                    ["text" => Translation::text("🔄️ До списку"), "callback_data" => "/list"],
                    ["text" => Translation::text("🏠 На початок"), "callback_data" => "/start"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }
}
