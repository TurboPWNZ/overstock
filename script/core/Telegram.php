<?php
namespace Slando\core;

use Curl\Curl;

class Telegram
{
    private static $_BOT_HASH = '7952044580:AAF36WhcLB9ux0a9MgDphxBbUJMJqG0UUbo';

    private static $_CHAT_ID = '-4833170322';

    public static function setCredentials($botHash)
    {
        self::$_BOT_HASH = $botHash;
    }

    public static function setChatID($chatID)
    {
        self::$_CHAT_ID = $chatID;
    }
    
    public static function sendRequest($data)
    {
        (new self())->sendMessage($data);
    }

    public static function sendMessageWithKeyboard($message, $keyboard = null)
    {
        (new self())->sendMessage($message, $keyboard);
    }

    public static function sendMediaRequest($data, $files)
    {
        (new self())->sendMediaGroup($data, $files);
    }

    /**
     * Отправляет сообщение в чат.
     *
     * @param $text
     */
    protected function sendMessage($text, $replyMarkup = null)
    {
        $sendData = [
            'chat_id' => self::$_CHAT_ID,
            'parse_mode' => 'HTML',
            'text' => $text
        ];

        if ($replyMarkup) {
            $sendData["reply_markup"] = json_encode($replyMarkup);
        }

        $curl = new Curl();
        $curl->setHeader('Content-type', 'application/json');
        $curl->post('https://api.telegram.org/bot' . self::$_BOT_HASH . '/sendMessage',
            json_encode($sendData)
        );
    }

    protected function sendMediaGroup($text, $files)
    {
        $curl = new Curl();

        // формируем массив файлов и media
        $media = [];
        $postFields = [
            'chat_id' => self::$_CHAT_ID
        ];

        foreach ($files['files']['tmp_name'] as $i => $tmpFile) {
            if (!is_uploaded_file($tmpFile)) {
                continue;
            }
            $key = "photo{$i}";
            $postFields[$key] = new \CURLFile(
                $tmpFile,
                mime_content_type($tmpFile),
                $_FILES['files']['name'][$i]
            );
            $media[$i] = [
                'type'  => 'photo',
                'media' => "attach://{$key}"
            ];
            if ($i === 0) {
                $media[$i]['caption'] = $text;
                $media[$i]['parse_mode'] = 'HTML';
            }
        }

        $postFields['media'] = json_encode($media);

        // в multipart заголовок ставить не надо — curl сам поставит
        $result = $curl->post(
            'https://api.telegram.org/bot' . self::$_BOT_HASH . '/sendMediaGroup',
            $postFields
        );

        var_dump($result); exit();
    }

    protected function sendPhoto($botHash, $chatID, $text, $photo, $link)
    {

        $curl = new Curl();
        $curl->setHeader('Content-type', 'application/json');
        $curl->post('https://api.telegram.org/bot' . self::$_BOT_HASH . '/sendPhoto',
            json_encode([
                'chat_id' => self::$_CHAT_ID,
                'parse_mode' => 'HTML',
                'photo' => $photo,
                'text' => $text
            ])
        );
    }
}