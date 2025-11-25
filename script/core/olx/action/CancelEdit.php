<?php
namespace Slando\core\olx\action;

use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class CancelEdit extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        // Извлекаем ID подписки из команды /cancel_edit_123
        $subId = $this->extractSubId($requestData['requestSubject']);

        if ($subId) {
            // Сбрасываем флаг редактирования
            $this->setEditMode($subId, false);
        } else {
            // Если ID не указан, сбрасываем все подписки в режиме редактирования
            $this->resetAllEditModes($account['id']);
        }

        $response['responseMessage'] = Translation::text("✅ Редагування скасовано");

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
        // Извлекаем ID из строки вида "/cancel_edit_123" или "/cancel"
        if (preg_match('/\/cancel_edit_(\d+)/', $requestSubject, $matches)) {
            return (int)$matches[1];
        }
        return null;
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

    protected function resetAllEditModes($userId)
    {
        (new Subscription())->update(
            'userId = :userId AND isEditInProgress = 1',
            [
                'userId' => $userId,
                'isEditInProgress' => 0
            ]
        );
    }
}
