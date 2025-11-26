<?php
namespace Slando\core\payment\privat;

use Curl\Curl;
use Slando\core\Configurator;
use Slando\core\i18n\Translation;
use Slando\core\Logger;
use Slando\core\olx\db\Account;
use Slando\core\olx\db\Subscription;
use Slando\core\olx\db\Transactions;
use Slando\core\Telegram;

class Transaction
{

    public static function process()
    {
        $transactions = self::loadTransactionList();

        foreach ($transactions as $transaction) {
            if (self::isTransactionSuccess($transaction) === false)
                continue;

            if(self::isTransactionNotProcessed($transaction) === false)
                continue;

            self::processTransaction($transaction);
        }

//        var_dump($transactions);
    }

    protected static function processTransaction($transaction)
    {
        $transactionAmount = (int) $transaction['SUM'];

        list($telegramID, $subscriptionID, $amount, $desc) = explode(';', $transaction['OSND']);

        $subscription = (new Subscription())->findByPk($subscriptionID);

        if (empty($subscription)) {
            Logger::log('Error payment: ' . var_export($transaction, true));

            return false;
        }

        $paymentTime = self::calculatePaymentTime($transactionAmount);

        if ($paymentTime === false) {
            Logger::log('Error payment: ' . var_export($transaction, true));

            return false;
        }

        self::applyTimeToSubscription($subscription, $paymentTime, $transaction);

        self::sendAccountNotification($subscription, $transactionAmount);

//        var_dump($telegramID, $subscriptionID, $amount, $desc);
    }

    protected static function sendAccountNotification($subscription, $transactionAmount)
    {
        $config = Configurator::load();

        $subscription = (new Subscription())->findByPk($subscription['id']);

        $account = (new Account())->findByPk($subscription['userId']);

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        Telegram::setChatID($account['telegramUserId']);

        $response['responseMessage'] = Translation::text("<b>Платёж успешно получен! 💳✔️</b>\n\n" .
            "Спасибо за оплату — <b>:amount</b>, и ваша подписка <b>«:subName»</b> была успешно продлена.\n\n" .
            "⏳ Новый срок действия подписки:\n" .
            "<b>:expireDate</b>\n\n" .
            "Подписка активна, и вы продолжите получать новые объявления без задержек.\n\n" .
            "Спасибо, что выбираете наш сервис! ❤️", [
                ':amount' => $transactionAmount,
                ':subName' => $subscription['name'],
                ':expireDate' => $subscription['validUntil'],
        ]);

        var_dump($response);
//        $response['keyboard'] = [
//            "inline_keyboard" => [
//                [
//                    ["text" => Translation::text("📋 Мої підписки"), "callback_data" => "/list"],
//                    ["text" => Translation::text("🏠 На початок"), "callback_data" => "/start"],
//                ]
//            ]
//        ];
        Telegram::sendMessageWithKeyboard($response['responseMessage'], []);
    }

    protected static function applyTimeToSubscription($subscription, $time, $transaction)
    {
        if (strtotime($subscription['validUntil']) > time()) {
            $newTime = strtotime($subscription['validUntil']) +  (60 * 60 * $time);
        } else {
            $newTime = time() + (60 * 60 * $time);
        }
        (new Subscription())->update('id = :id', [
            'id' => $subscription['id'],
            'nextTime' => date('Y-m-d H:i:s', time() + 60 * 15),
            'validUntil' => date('Y-m-d H:i:s', $newTime),
            'isEditInProgress' => 0,
        ]);

        (new Transactions())->insert([
            'paymentTransactionId' => $transaction['ID'],
            'subscriptionId' => $subscription['id'],
            'amount' => $transaction['SUM'],
            'date' => date('Y-m-d H:i:s', time())
        ]);
    }

    protected static function calculatePaymentTime($amount)
    {
        switch($amount) {
            case 20: return 24;
            case 50: return 24 * 3;
            case 100: return 24 * 7;
            case 200: return 24 * 30;
            default: return false;
        }
    }

    protected static function isTransactionNotProcessed($transaction)
    {
        $dbTransaction = (new Transactions())->find('paymentTransactionId = :paymentTransactionId', [
            'paymentTransactionId' => $transaction['ID']
        ]);

        if (empty($dbTransaction))
            return true;

        return false;
    }

    protected static function isTransactionSuccess($transaction)
    {
        if ($transaction['FL_REAL'] != 'r') {
            return false;
        }

        if ($transaction['PR_PR'] != 'r') {
            return false;
        }

        if ($transaction['TRANTYPE'] != 'C') {
            return false;
        }

        return true;
    }

    protected static function loadTransactionList()
    {
        $config = Configurator::load();

        $curl = new Curl();
        $curl->setHeader('Content-type', 'application/json;charset=utf8');
        $curl->setHeader('token', $config['params']['secrets']['bank']['apiToken']);
        $request = $curl->post('https://acp.privatbank.ua/api/statements/transactions/interim?acc=' .
            $config['params']['secrets']['bank']['account']);

        $response = json_decode($request->getResponse(), true);
        return $response['transactions'];
    }
}