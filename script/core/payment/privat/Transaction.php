<?php
namespace Slando\core\payment\privat;

use Curl\Curl;
use Slando\core\Configurator;
use Slando\core\olx\db\Transactions;

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
        list($telegramID, $subscriptionID, $amount, $desc) = explode(';', $transaction['OSND']);

        var_dump($telegramID, $subscriptionID, $amount, $desc);
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