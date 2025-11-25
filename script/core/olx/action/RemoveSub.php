<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class RemoveSub extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        // Извлекаем ID подписки из команды /remove_sub_123
        $subId = $this->extractSubId($requestData['requestSubject']);

        if (!$subId) {
            $this->sendError("Невірний формат команди");
            return false;
        }

        // Проверяем, принадлежит ли подписка пользователю
        $subscription = $this->getSubscription($subId, $account['id']);

        if (empty($subscription)) {
            $this->sendError("Підписка не знайдена або не належить вам");
            return false;
        }

        // Отправляем подтверждение удаления
        $this->sendConfirmation($subscription);
    }

    protected function extractSubId($requestSubject)
    {
        // Извлекаем ID из строки вида "/remove_sub_123"
        if (preg_match('/\/remove_sub_(\d+)/', $requestSubject, $matches)) {
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

    protected function sendConfirmation($subscription)
    {
        $response['responseMessage'] = Translation::text(
            "⚠️ Підтвердження видалення\n\n" .
            "Ви дійсно хочете видалити підписку?\n\n" .
            "📌 #:subId <b>:subName</b>\n" .
            "🔗 :subUrl\n" .
            "📅 Активна до: :subValidUntil\n\n" .
            "❗️ Ця дія незворотна!",
            [
                ':subId' => $subscription['id'],
                ':subName' => $subscription['name'],
                ':subUrl' => $subscription['url'],
                ':subValidUntil' => $subscription['validUntil'],
            ]
        );

        $response['keyboard'] = [
            "inline_keyboard" => [
                [
                    ["text" => Translation::text("✅ Так, видалити"), "callback_data" => "/confirm_remove_" . $subscription['id']],
                ],
                [
                    ["text" => Translation::text("❌ Ні, скасувати"), "callback_data" => "/list"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
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
