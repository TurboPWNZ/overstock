<?php
namespace Slando\core\olx\informer;

use Slando\core\Configurator;
use Slando\core\olx\db\Account;
use Slando\core\olx\db\Ads;
use Slando\core\olx\db\Subscription;
use Slando\core\Telegram;

class Sender
{
    public static function process()
    {
        $config = Configurator::load();

        Telegram::setCredentials($config['params']['secrets']['olx']['bot']);

        $subscriptions = self::loadSubscriptions();

        foreach ($subscriptions as $subscription) {
            self::processSubscription($subscription);
        }
    }

    protected static function processSubscription($subscription)
    {
        $account = self::getAccount($subscription['userId']);

        Telegram::setChatID($account['telegramUserId']);

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

            if (!$isNew)
                continue;

            Telegram::sendPhotoAds(implode(' ', $data),
                $ad['photos'][0],
                $ad['url']);

            echo "->";
            sleep(rand(1, 3));

            break;
        }
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

    protected static function getAccount($id)
    {
        return (new Account())
            ->find('id = :id', ['id' => $id]);
    }
}