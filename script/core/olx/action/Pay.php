<?php
namespace Slando\core\olx\action;

use Slando\core\Bank;
use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\Telegram;

class Pay extends AAction
{
    public function run($requestData)
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($requestData['chatId']);

        $account = $this->loadAccount($requestData);

        $subscription = $this->loadSubscriptionInEdit($account);

        // Если нету редактируемой подписки - оплачевать нечего.
        if (empty($subscription)) {
            $response['responseMessage'] = Translation::text('Не указано обьявление для оплаты');
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

        $keyboard = [
            ["text" => Translation::text("🔄️"), "callback_data" => "/start"],
            ["text" => "20 грн", "url" => $this->createTransactionLink($account, $subscription, 20)],
            ["text" => "50 грн", "url" => $this->createTransactionLink($account, $subscription, 50)],
            ["text" => "100 грн", "url" => $this->createTransactionLink($account, $subscription, 100)],
            ["text" => "200 грн", "url" => $this->createTransactionLink($account, $subscription, 200)],
//                        ["text" => "📋 Мої оголошення", "callback_data" => "/list"]
        ];

        if ($account['trial'] == 1) {
            $keyboard[] = ["text" => Translation::text("🆓"),
                "callback_data" => "/trial"];
        }

        $response['responseMessage'] = Translation::text("Оплата подписки :subName выберите желаемый вариант: \n\n
- 🆓бесплатно - 24 часа (*пробный период)\n
- 💵20 грн - 24 часа\n
- 💵50 грн - 3 дня\n
- 💵100 грн - неделя\n
- 💵200 грн - месяц\n\n

<b>Внимание!!! Не меняйте реквизиты или примечание платежа</b>, в противном случае платеж не будет обработан автоматически!\n
При возникновении проблем обратитесь в тех. поддержку
        ");
        $response['keyboard'] = [
            "inline_keyboard" => [
                $keyboard
            ]
        ];

        Telegram::sendMessageWithKeyboard($response['responseMessage'], $response['keyboard']);
    }

    protected function createTransactionLink($account, $subscription, $amount)
    {
        $transactionId = implode(';', [
            $account['telegramUserId'],
            $subscription['id'],
            $amount,
        ]);

        return Bank::getPaymentLink($transactionId, $amount);
    }
}