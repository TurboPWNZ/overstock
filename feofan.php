<?php
use Slando\core\Logger;

require_once __DIR__ . '/vendor/autoload.php';

\Slando\core\Telegram::setCredentials('8253837427:AAHYJR5-cU0zC7FWscCpjCo5piqsnEVTAZ8');

$content = file_get_contents("php://input");

Logger::log($content);

$update = json_decode($content, true);

$question = $update['message']['text'];

if (stripos($question, '@feofan_slavian_bot') === false) {
    exit();
}

\Slando\core\Telegram::setChatID($update['message']['chat']['id']);

$question = trim(str_replace('@feofan_slavian_bot', '', $question));

$sender = $update['message']['from']['username'];

if (hasSergeyMention($question) && $sender != 'turboplay1989') {
    \Slando\core\Telegram::sendRequest('Иди нахуй смерд, я не намерен обсуждать с тобой создателя 🖕🖕🖕');
    exit();
}

$apiKey = "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"; // 🔒 ключ в переменной окружения

// Подготавливаем данные
$url = "https://api.openai.com/v1/responses";
$data = [
    "model" => "gpt-4o-mini",
    "input" => $question
];

// Отправляем запрос
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    \Slando\core\Telegram::sendRequest('Мой ум не ясен пока. ' . curl_error($ch));
//    echo json_encode(['error' => curl_error($ch)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Парсим ответ
$decoded = json_decode($response, true);
$text = '';

if (isset($decoded["output"][0]["content"][0]["text"])) {
    $text = $decoded["output"][0]["content"][0]["text"];
} elseif (isset($decoded["output_text"])) {
    $text = $decoded["output_text"];
} else {
    $text = "Я в замешательстве. " . $response;
}

\Slando\core\Telegram::sendRequest($text);

function hasSergeyMention($text) {
    // Список возможных форм имени
    $patterns = [
        'сергей',
        'серёг',    // серёга, серёжка
        'серега',
        'сергій',
        'сергійко',
        'сергейко',
        'серож',    // редкие уменьшительные
        'серг',
        'turboplay1989',
        'turboplay',
    ];

    // Приводим к нижнему регистру
    $text = mb_strtolower($text, 'UTF-8');

    // Проверяем по всем вариантам
    foreach ($patterns as $pattern) {
        if (mb_strpos($text, $pattern, 0, 'UTF-8') !== false) {
            return true;
        }
    }

    return false;
}