<?php
namespace Slando\core;

use Slando\core\db\Ads;
use Slando\core\db\User;
use Slando\core\db\UserRequest;

class Api
{
    const WELCOME_STEP = 0;

    const ADD_ADS_STEP = 1;

    const ADS_NAME_STEP = 2;
    const ADD_PHONE_STEP = 3;

    const ADD_SUBJECT_STEP = 4;
    const ADD_DESCRIPTION_STEP = 5;
    const ADD_PLACE_STEP = 6;
    const ADD_PRICE_STEP = 7;
    const ADD_PHOTO_STEP = 8;
    const SET_PUBLISH_TYPE_STEP = 9;

    private static $_user;
    private static $_request;
    private static $step;
    private static $_chatId;
    private static $_currentAds;

    private static $_responseMessage;
    private static $_keyboard;

    public static function processRequest()
    {
        // ====== ПОЛУЧАЕМ ВХОДЯЩИЕ ДАННЫЕ ======
        $content = file_get_contents("php://input");
        Logger::log($content);
        $update = json_decode($content, true);

        self::$step = self::checkProcessedRequest($update);

        return self::runStep(self::$step, $update);
    }

    private static function checkProcessedRequest($update)
    {
        if (!empty($update['message']['from']['id'])) {
            $telegramUserID = $update['message']['from']['id'];
        } elseif (!empty($update['callback_query']['from']['id'])) {
            $telegramUserID = $update['callback_query']['from']['id'];
        } else {
            return self::WELCOME_STEP;
        }

        $user = (new User())->find('telegramUserId = :telegramUserId', ['telegramUserId' => $telegramUserID]);

        if (empty($user)) {
            $user = (new User())->insert(['telegramUserId' => $telegramUserID]);
        }

        self::$_user = $user;

        self::$_request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_user['id']]);

        if (!empty(self::$_request['step'])) {
            return self::$_request['step'];
        }

        return self::WELCOME_STEP;
    }

    private static function runStep($step, $data)
    {
        switch ($step) {
            case 0:
                return self::welcome($data);
            case self::ADD_ADS_STEP:
                return self::selectAddOrDrop($data);
            case 2:
                return self::setAdsUserName($data);
            case 3:
                return self::setAdsPhone($data);
            case 4:
                return self::setAdsSubject($data);
            case self::ADD_DESCRIPTION_STEP:
                return self::setAdsDescription($data);
            case self::ADD_PLACE_STEP:
                return self::setAdsPlace($data);
            case self::ADD_PRICE_STEP:
                return self::setAdsPrice($data);
            case self::ADD_PHOTO_STEP:
                return self::setAdsPhoto($data);
            case self::SET_PUBLISH_TYPE_STEP:
                return self::setPublishType($data);
            default:
                return self::welcome($data);
        }
    }

    private static function welcome($update)
    {
            self::$_chatId = $update["message"]["chat"]["id"];

            self::$_responseMessage = "Привіт! 👋 Обери дію";
            self::$_keyboard = [
                "inline_keyboard" => [
                    [
                        ["text" => "📢 Опублікувати", "callback_data" => "/publish"],
                        ["text" => "❌ Видалити", "callback_data" => "/delete"]
                    ]
                ]
            ];

            self::setNextStep(self::ADD_ADS_STEP);

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
    }

    private static function selectAddOrDrop($update)
    {
        if (isset($update["callback_query"])) {
            self::$_chatId = $update["callback_query"]["message"]["chat"]["id"];
            $data = $update["callback_query"]["data"];

            if (in_array($data, ["/publish", "/reset_ads"])) {
                if (!self::isCanPostAds()) {
                    $lastPublishTime = strtotime(self::$_user['lastPost']);

                    self::$_responseMessage =
                        "Публікація безкоштовного оголошення можлива після " .
                        date('d.m.Y H:i:s', $lastPublishTime + 60 * 60 * 12);
                    self::$_keyboard = [
                        "inline_keyboard" => [
                            [
                                ["text" => "💵 Оплатити публікацію 10 грн", "callback_data" => "/publish_pay"]
                            ]
                        ]
                    ];
                } else {
//                self::$_responseMessage = "Окей, вкажи заголовок свого оголошення ✍️";
                    self::$_responseMessage = "Вкажи як можна до тебе звертатись ✍️";

                    if (
                        !empty($update["callback_query"]['from']['first_name']) ||
                        !empty($update["callback_query"]['from']['username'])
                    ) {
                        $keyboard = [[]];

                        if (!empty($update["callback_query"]['from']['first_name']))
                            array_push($keyboard[0], ["text" => $update["callback_query"]['from']['first_name']]);

                        if (!empty($update["callback_query"]['from']['username']))
                            array_push($keyboard[0], ["text" => $update["callback_query"]['from']['username']]);

                        self::$_keyboard = [
                            "keyboard" => $keyboard,
                            "resize_keyboard" => true, // чтобы не занимала весь экран
                            "one_time_keyboard" => true
                        ];
                    }

                    self::setNextStep(self::ADS_NAME_STEP);
                }
            } elseif ($data == "/delete") {
                self::$_responseMessage = "Пришли ID объявления, которое нужно удалить ❌";
            }

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage,
                'keyboard' => self::$_keyboard
            ];
        }

        return self::runStep(self::WELCOME_STEP, $update);
    }

    private static function setPublishType($data) {

    }
    private static function adsPreview()
    {
        \Slando\core\Telegram::setChatID(self::$_chatId);

        $currentAds = self::getCurrentAds();

        $adsDir = __DIR__ . '/../../uploads/' . self::$_user['telegramUserId'] . '/' . $currentAds['id'];

        $data['subject'] = '<i>' . $currentAds['subject'] . '</i>' . " \n";
        $data['price'] = 'Ціна: <b>' . $currentAds['price'] . ' грн</b>' . "\n\n";
        $data['description'] =  strip_tags($currentAds["description"]) . "\n\n";
        $data['place'] =  '📍' . $currentAds['place'] . " \n\n";
        $data['user'] =  '👤' . ' <b>' . $currentAds['name'] . '</b>' . " \n\n";
        $data['contact'] =  '📱<tg-spoiler>' . $currentAds['phone'] . "</tg-spoiler> \n";
/**
        $data['name'] = '🗣 <i>'. strip_tags($_REQUEST["name"]) . '</i>' . "\n\n";
<tg-spoiler>смерд</tg-spoiler>
        if (!empty($_REQUEST["company"])) {
            $data['company'] = ' 🏢 <b>' . strip_tags($_REQUEST["company"]) . '</b>' . "\n\n";
        }

        $data['phone'] =  "📞" . strip_tags($_REQUEST["phone"]) . "\n\n";
        $data['description'] =  strip_tags($_REQUEST["description"]) . "\n\n";

        $data['name'] = '🔈 <i>' . $ad['title'] . '</i>';
        $data['price'] = ' 🆓 <b>' . $ad['price']['displayValue'] . '</b>' . "\n\n";
        $data['description'] =  strip_tags($ad['description']) . "\n\n";

        $data['place'] =  '📍' . $ad['location']['pathName'] . " \n";
//            $data['image'] = "[ ](" . $ad['photos'][0] . ") \n";
//            $data['link'] = '🔗 <a href="'.$ad['url'].'">Забрати</a>' . " \n";
**/

        Telegram::sendAdsPreview(implode($data), $adsDir);

        self::$_responseMessage =
            "🔎 Такий вигляд буде мати твое оголошення";
        self::$_keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "✔️Публікувати", "callback_data" => "/publish_ads"],
                    ["text" => "✍️Змінити", "callback_data" => "/reset_ads"],
                    ["text" => "❌Видалити", "callback_data" => "/remove_ads"]
                ]
            ]
        ];

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage,
            'keyboard' => self::$_keyboard
        ];
    }

    private static function setAdsPhoto($data) {
        if (isset($data["callback_query"])) {
            self::$_chatId = $data["callback_query"]["message"]["chat"]["id"];
            $action = $data["callback_query"]["data"];

            if ($action == "/publish_ads") {
                self::setNextStep(self::SET_PUBLISH_TYPE_STEP);

                self::$_responseMessage = "Обери бажаний варіант публікації: <br />
                    <ul>
                        <li>🆓Безкоштовно - 1 публікація оголошення на день</li>
                        <li>💵20 грн - 3 публікації оголошення на день</li>
                        <li>💵50 грн - 10 публікацій оголошення на день</li>
                    </ul>
                 ";

                self::$_keyboard = [
                    "inline_keyboard" => [
                        [
                            ["text" => "💵 50 грн", "callback_data" => "/50_publish"],
                            ["text" => "💵20 грн", "callback_data" => "/20_publish"],
                            ["text" => "🆓Безкоштовно", "callback_data" => "/free_publish"]
                        ]
                    ]
                ];

                return [
                    'chatId' => self::$_chatId,
                    'responseMessage' => self::$_responseMessage,
                    'keyboard' => self::$_keyboard
                ];
                //return self::adsPreview();
            }

            if ($action == "/preview_ads") {
                return self::adsPreview();
            }

            if ($action == "/reset_ads") {
                self::removeAdsImages();

                return self::runStep(self::ADD_ADS_STEP, $data);
            }

            if ($action == "/remove_ads") {
                $remove = self::removeCurrentAds();

                if ($remove) {
                    self::setNextStep(self::ADD_ADS_STEP);

                    self::$_responseMessage = "Оголошення видалено! 👋 Обери дію";
                    self::$_keyboard = [
                        "inline_keyboard" => [
                            [
                                ["text" => "📢 Опублікувати", "callback_data" => "/publish"],
                                ["text" => "❌ Видалити", "callback_data" => "/delete"]
                            ]
                        ]
                    ];

                    return [
                        'chatId' => self::$_chatId,
                        'responseMessage' => self::$_responseMessage,
                        'keyboard' => self::$_keyboard
                    ];
                }
            }
        }

        self::$_chatId = $data["message"]["chat"]["id"];

        if (empty($data["message"]['photo'])) {
            self::$_responseMessage =
                "‼️Будьласка, завантажте фотографію товару";

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage
            ];
        }

        $photo = end($data["message"]['photo']);

        $file = Telegram::downloadFile($photo['file_id']);

        $fileName = self::createImageFileName($file);

        if (empty($fileName)) {
            self::$_responseMessage =
                "‼️Будьласка, завантажте фотографію товару. Завантаженний файл не є картинкою";

            return [
                'chatId' => self::$_chatId,
                'responseMessage' => self::$_responseMessage
            ];
        }

        $userDir = __DIR__ . '/../../uploads/' . self::$_user['telegramUserId'];

        if (!is_dir($userDir)) {
            mkdir($userDir, 0777, true);
        }

        if (!is_writable($userDir)) {
            chmod($userDir, 0777);
        }

        $currentAds = self::getCurrentAds();

        $adsDir = $userDir . '/' . $currentAds['id'];

        if (!is_dir($adsDir)) {
            mkdir($adsDir, 0777, true);
        }

        if (!is_writable($adsDir)) {
            chmod($adsDir, 0777);
        }

        file_put_contents($adsDir . '/' . $fileName, $file);

        self::$_responseMessage =
            "✔️Фотографія завантажена. Відправите ще фотографію?";
        self::$_keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "✔️Публікувати оголошення", "callback_data" => "/preview_ads"]
                ]
            ]
        ];

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage,
            'keyboard' => self::$_keyboard
        ];
    }

    private static function setAdsPrice($data) {
        self::$_chatId = $data["message"]["chat"]["id"];
        $price = $data["message"]["text"];

        self::updateAds(['price' => $price]);

        self::$_responseMessage =
            "✔️Дякуемо тепер, завантажте фотографію товару";

        self::setNextStep(self::ADD_PHOTO_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage
        ];
    }

    private static function setAdsPlace($data) {
        self::$_chatId = $data["message"]["chat"]["id"];
        $place = $data["message"]["text"];

        self::updateAds(['place' => $place]);

        self::$_responseMessage =
            "✔️Дякуемо тепер, вкажіть ціну 💵 вашого товару Наприклад (1000 грн)";

        self::setNextStep(self::ADD_PRICE_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage
        ];
    }
    private static function setAdsDescription($data)
    {
        self::$_chatId = $data["message"]["chat"]["id"];
        $description = $data["message"]["text"];

        self::updateAds(['description' => $description]);

        self::$_responseMessage =
            "✔️Дякуемо тепер, вкажіть місце вашого розташування 📍 Наприклад (Київ, Деснянський р-н)";

        self::setNextStep(self::ADD_PLACE_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage
        ];
    }
    private static function setAdsSubject($data)
    {
        self::$_chatId = $data["message"]["chat"]["id"];
        $subject = $data["message"]["text"];

        self::updateAds(['subject' => $subject]);

        self::$_responseMessage = "✔️Дякуемо тепер, вкажіть <b>опис</b> вашого оголошення ";

        self::setNextStep(self::ADD_DESCRIPTION_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage
        ];
    }

    private static function setAdsPhone($data)
    {
        self::$_chatId = $data["message"]["chat"]["id"];
        $phone = $data["message"]["text"];

        self::updateAds(['phone' => $phone]);

        self::$_responseMessage = "✔️Додано номер " . $phone . ", вкажіть <b>заголовок</b> оголошення";

        self::setNextStep(self::ADD_SUBJECT_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage
        ];
    }

    private static function setAdsUserName($data)
    {
        self::$_chatId = $data["message"]["chat"]["id"];
        $name = $data["message"]["text"];

        self::$_responseMessage = "Добре " . $name . " вкажіть контактний номер для зв'язку 📲";

        self::updateAds(['name' => $name]);

        self::setNextStep(self::ADD_PHONE_STEP);

        return [
            'chatId' => self::$_chatId,
            'responseMessage' => self::$_responseMessage,
            'keyboard' => []
        ];
    }

    private static function updateAds($params)
    {
        $ads = self::getCurrentAds();

        (new Ads())->update('id = :id', array_merge([
            'id' => $ads['id']
        ], $params));
    }

    private static function removeAdsImages()
    {
        $ads = self::getCurrentAds();

        $adsDir = __DIR__ . '/../../uploads/' . self::$_user['telegramUserId'] . '/' . $ads['id'];

        self::deleteDirectory($adsDir);
    }

    private static function removeCurrentAds()
    {
        $ads = self::getCurrentAds();

        $adsDir = __DIR__ . '/../../uploads/' . self::$_user['telegramUserId'] . '/' . $ads['id'];

        self::deleteDirectory($adsDir);

        return (new Ads())->removeFromPk($ads['id']);
    }

    private static function getCurrentAds()
    {
        if (empty(self::$_request['adsId'])) {
            $ads = (new Ads())->insert([
                'userId' => self::$_user['id']
            ]);

            (new UserRequest())->update('id = :id', [
                'id' => self::$_request['id'],
                'adsId' => $ads['id']
            ]);

            return $ads;
        }

        if (empty(self::$_currentAds))
            self::$_currentAds = (new Ads())->findByPk(self::$_request['adsId']);

        return self::$_currentAds;
    }

    private static function deleteDirectory($dir) {
        // Проверяем, существует ли директория
        if (!is_dir($dir)) {
            return false;
        }

        // Получаем все файлы и папки в директории
        $items = scandir($dir);

        foreach ($items as $item) {
            // Пропускаем специальные директории "." и ".."
            if ($item == '.' || $item == '..') {
                continue;
            }

            // Формируем полный путь к элементу
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            // Если элемент - директория, вызываем функцию рекурсивно
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                // Если элемент - файл, удаляем его
                unlink($path);
            }
        }

        // Удаляем саму директорию
        return rmdir($dir);
    }

    private static function createImageFileName($fileData)
    {
        // Определяем MIME-тип по контенту
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($fileData);

        // Карта mime → расширение
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (empty($map[$mime])) {
            return false;
        }

        $ext = $map[$mime];

        return date('YmdHis') . '.' . $ext;
    }

    private static function isCanPostAds()
    {
        $lastPublishTime = strtotime(self::$_user['lastPost']);

        return (time() - $lastPublishTime - 60 * 60 * 12) > 0;
    }

    private static function setNextStep($step)
    {
        $request = (new UserRequest())->find('userId = :userId', ['userId' => self::$_user['id']]);

        if (empty($request)) {
            (new UserRequest())->insert(['userId' => self::$_user['id'], 'step' => 1]);
        }

        (new UserRequest())->update('id = :id', [
            'id' => $request['id'],
            'step' => $step
        ]);
    }
}