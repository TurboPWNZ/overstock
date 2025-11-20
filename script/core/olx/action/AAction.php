<?php
namespace Slando\core\olx\action;

use Slando\core\olx\db\Account;
use Slando\core\olx\db\Subscription;

abstract class AAction
{
    protected function loadAccount($requestData)
    {
        $account = (new Account())
            ->find('telegramUserId = :telegramUserId', ['telegramUserId' => $requestData['senderId']]);

        if (empty($account)) {
            $account = (new Account())->insert([
                'telegramUserId' => $requestData['senderId'],
                'username' => $requestData['username'],
            ]);
        }

        return $account;
    }

    protected function isAccountHasSubscription($account)
    {
        return (new Subscription())->find('userId = :userId', ['userId' => $account['id']]);
    }
}