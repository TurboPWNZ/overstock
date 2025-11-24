<?php
namespace Slando\core\olx\informer;

use Slando\core\olx\db\Ads;
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

        foreach ($adsList as $ad) {
//            if ($ad['isPromoted'] === true)
//                continue;
            $data = [];

            $data['name'] = '🔈 <i>' . $ad['title'] . '</i>';
            $data['price'] = ' 🆓 <b>' . $ad['price']['displayValue'] . '</b>' . "\n\n";
            $data['description'] = strip_tags($ad['description']) . "\n\n";

            $data['place'] = '📍' . $ad['location']['pathName'] . " \n";

            $isNew = self::isNewRecord($ad['id'], $subscription['id']);

            var_dump($isNew);
        }
        var_dump($adsList);
    }

    private static function isNewRecord($adID, $subscriptionID)
    {
        $ad =  (new Ads())->find('subscriptionId = :subscriptionId AND adId = :adId', [
            'subscriptionId' => $subscriptionID,
            'adId' => $adID,
        ]);

        if (!$ad)
            (new Ads())->insert(['subscriptionId' => $subscriptionID, 'adId' => $adID]);

        return !$ad;
    }

    protected static function loadSubscriptions()
    {
        return (new Subscription())->findAll('validUntil > NOW() AND nextTime < NOW()', []);
    }
}