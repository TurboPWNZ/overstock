<?php
header('Content-Type: application/json; charset=utf-8');

// Проверяем, пришёл ли файл
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Файл не получен']);
    exit;
}

// Пути
$python = '/usr/bin/python3.12';
$script = '/home/eg550228/voice.py';

// Сохраняем файл во временную папку
$tmpName = '/home/eg550228/tmp_' . uniqid() . '.wav';
move_uploaded_file($_FILES['file']['tmp_name'], $tmpName);

// Команда
$cmd = "$python " . escapeshellarg($script) . ' ' . escapeshellarg($tmpName) . ' 2>/dev/null';

// Выполняем Python-скрипт
exec($cmd, $out, $ret);

// Удаляем временный файл
//unlink($tmpName);

// Собираем результат
$text = trim(implode("\n", $out));

// Если нет текста — ошибка
if ($ret !== 0 || $text === '') {
    echo json_encode(['error' => 'Не удалось распознать речь']);
    exit;
}

$question = $text;

$apiKey = ""; //

// Подготавливаем данные
$url = "https://api.openai.com/v1/responses";
$data = [
    "model" => "gpt-4o-mini",
    "input" => $text
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
    echo json_encode(['error' => curl_error($ch)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
    $text = "Ошибка или пустой ответ: " . $response;
}

// --- Очистка текста для TTS ---
$text = strip_tags($text);
$text = preg_replace('/\s+/', ' ', $text);
$text = trim($text);
// убираем все кавычки, знаки препинания, скобки и прочие спецсимволы
$text = preg_replace('/[\"\'\(\)\[\]\{\},;:!?.<>\/\\\\|@#$%^&*_+=~`]/u', '', $text);

// Возвращаем JSON
echo json_encode(['message' => $text, 'question' => $question], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);