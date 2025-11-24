<?php
namespace Slando\core\olx\informer;

use Slando\core\olx\db\Subscription;

class Sender
{
    public static function process()
    {
        var_dump(self::loadSubscriptions());
    }

    protected static function loadSubscriptions()
    {
        return (new Subscription())->findAll('validUntil > NOW() AND nextTime < NOW()', []);
    }
}