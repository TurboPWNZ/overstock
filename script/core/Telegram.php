<?php

class Telegram
{
    const BOT_HASH = '7952044580:AAF36WhcLB9ux0a9MgDphxBbUJMJqG0UUbo';

    const CHAT_ID = '-4833170322';

    public static function sendRequest($data)
    {
        (new self())->sendMessage($data);
    }

    /**
     * Отправляет сообщение в чат.
     *
     * @param $text
     */
    protected function sendMessage($text)
    {
        $curl = new Curl\Curl();
        $curl->setHeader('Content-type', 'application/json');
        $curl->post('https://api.telegram.org/bot' . self::BOT_HASH . '/sendMessage',
            json_encode([
                'chat_id' => self::CHAT_ID,
                'parse_mode' => 'HTML',
                'text' => $text
            ])
        );
    }
}