<?php
namespace Slando\core\olx\informer;

use Slando\core\olx\db\Subscription;

class Sender
{
    public static function process()
    {
        $subscriptions = self::loadSubscriptions();

        foreach ($subscriptions as $subscription) {
            self::processSubscription($subscription);
        }
    }

    protected static function processSubscription($subscription)
    {
        $adsList = (new Parser())->loadRecordsList($subscription['url']);

        var_dump($adsList);
    }

    protected static function loadSubscriptions()
    {
        return (new Subscription())->findAll('validUntil > NOW() AND nextTime < NOW()', []);
    }
}