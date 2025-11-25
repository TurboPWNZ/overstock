<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class EditSub extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        // Извлекаем ID подписки из команды /edit_sub_123
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

        // Проверяем, не редактируется ли уже другая подписка
        $editInProgress = $this->loadSubscriptionInEdit($account);
        
        if (!empty($editInProgress) && $editInProgress['id'] != $subId) {
            $this->sendError("У вас вже є незавершене редагування іншої підписки. Завершіть його або скасуйте.");
            return false;
        }

        // Устанавливаем флаг редактирования для выбранной подписки
        $this->setEditMode($subId, true);

        $response['responseMessage'] = Translation::text(
            "🔄 Редагування підписки #:subId <b>:subName</b>\n\n" .
            "Поточні дані:\n" .
            "📌 Назва: :subName\n" .
            "🔗 Посилання: :subUrl\n" .
            "📅 Активна до: :subValidUntil\n\n" .
            "Вкажіть нову назву підписки або відправте /cancel для скасування",
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
                    ["text" => Translation::text("❌ Скасувати"), "callback_data" => "/cancel_edit_" . $subId],
                    ["text" => Translation::text("🔄️ До списку"), "callback_data" => "/list"],
                ]
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function extractSubId($requestSubject)
    {
        // Извлекаем ID из строки вида "/edit_sub_123"
        if (preg_match('/\/edit_sub_(\d+)/', $requestSubject, $matches)) {
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

    protected function setEditMode($subId, $isEdit)
    {
        (new Subscription())->update(
            'id = :id',
            [
                'id' => $subId,
                'isEditInProgress' => $isEdit ? 1 : 0
            ]
        );
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
