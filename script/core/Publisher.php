<?php
namespace Slando\core;
use Slando\core\db\Ads;

class Publisher
{
    public static function run()
    {
        $ads = (new Ads())->findAll('publishCount > 0', []);

        var_export($ads);
    }
}