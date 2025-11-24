<?php
namespace Slando\core\olx\informer;

use Curl\Curl;

class Parser
{


    protected function loadRecordsList($url)
    {
        $curl = new Curl();
        $request = $curl->get($url);

        return $request->getResponse();
    }

    private function extractAdsData($content)
    {
        $content = strstr($content, 'window.__PRERENDERED_STATE__');
        $content = strstr($content, 'window.__TAURUS__', true);
        $content = trim($content);
        $content = str_replace('window.__PRERENDERED_STATE__= "', '', $content);
        $content = str_replace('";', '', $content);
        $content = stripslashes($content);

        $json = json_decode($content, true);

        return $json['listing']['listing']['ads'];
    }
}