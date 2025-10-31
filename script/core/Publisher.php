<?php
namespace Slando\core;
use Slando\core\db\Ads;
use Slando\core\db\User;

class Publisher
{
    public static function run()
    {
        $ads = (new Ads())->findAll(
            'publishCount > 0 AND isReady = 1 AND (publishTime <= NOW() - INTERVAL 1 HOUR OR publishTime IS NULL)', []);

        if (empty($ads))
            return false;

        var_dump($ads); exit();
        foreach ($ads as $ad) {
            self::publishAd($ad);
        }
    }

    private static function publishAd($ad)
    {
        $config = Configurator::load();

        $user = (new User())->findByPk($ad['userId']);

        $adsDir = __DIR__ . '/../../uploads/' . $user['telegramUserId'] . '/' . $ad['id'];

        $data['subject'] = '<i>' . $ad['subject'] . '</i>' . " \n";
        $data['price'] = 'Ціна: <b>' . $ad['price'] . ' грн</b>' . "\n\n";
        $data['description'] =  strip_tags($ad["description"]) . "\n\n";
        $data['place'] =  '📍' . $ad['place'] . " \n\n";
        $data['user'] =  '👤' . ' <b>' . $ad['name'] . '</b>' . " \n\n";
        $data['contact'] =  '📱<tg-spoiler>' . $ad['phone'] . "</tg-spoiler> \n";

        Telegram::setChatID($config['params']['publish_ads_chanel_id']);
        Telegram::sendAdsPreview(implode($data), $adsDir);

        (new Ads())->update('id = :id', [
            'id' => $ad['id'],
            'publishTime' => date('Y-m-d H:i:s', time()),
            'publishCount' => $ad['publishCount'] - 1
        ]);
    }
}