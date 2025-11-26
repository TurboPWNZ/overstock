<?php
namespace Slando\core\payment\privat;

use Curl\Curl;
use Slando\core\Configurator;

class Transaction
{
    public static function process()
    {
        $transactions = self::loadTransactionList();

        var_dump($transactions);
    }

    protected static function loadTransactionList()
    {
        $config = Configurator::load();

        $curl = new Curl();
        $curl->setHeader('Content-type', 'application/json;charset=utf8');
        $curl->setHeader('token', $config['params']['secrets']['bank']['apiToken']);
        $request = $curl->post('https://acp.privatbank.ua/api/statements/transactions/interim?acc=' .
            $config['params']['secrets']['bank']['account']);

        return $request->getResponse();
    }
}