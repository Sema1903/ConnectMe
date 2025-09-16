<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);
$topic = $input['topic'] ?? '';
$tone = $input['tone'] ?? 'casual';

if (empty($topic)) {
    echo json_encode(['success' => false, 'message' => 'Тема не указана']);
    exit;
}

// Ваш API ключ Gemini
$apiKey = "AIzaSyAaR7NckMnmHBijE9SRW7aqIpZm06Z69ZQ";

// Формируем промпт
$prompt = "Напиши короткий пост для социальной сети на тему: \"$topic\". 
Длина: 2-3 предложения. Пост должен быть естественным и интересным.";

// Данные для запроса к Gemini API
$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 150,
        'topP' => 0.8,
        'topK' => 40
    ]
];

// Используем cURL для запроса
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        echo json_encode(['success' => true, 'text' => $text]);
    } else {
        // Если API вернуло неожиданный формат
        echo json_encode(['success' => true, 'text' => generateFallbackPost($topic, $tone)]);
    }
} else {
    // Если API недоступно
    echo json_encode(['success' => true, 'text' => generateFallbackPost($topic, $tone)]);
}

function generateFallbackPost($topic, $tone) {
    $templates = [
        'funny' => [
            "Сегодня думал о $topic... а потом кофе пролил! Кто еще так может?",
            "$topic - это как пытаться собрать IKEA без инструкции! Весело и немного страшно"
        ],
        'serious' => [
            "Размышляя о $topic, понимаю как важно уделять время саморазвитию.",
            "$topic - тема, которая требует глубокого осмысления и внимательного подхода."
        ],
        'inspirational' => [
            "Сегодня прекрасный день чтобы начать заниматься $topic! Мечты сбываются",
            "$topic напоминает мне: никогда не сдавайся и иди к своей цели!"
        ],
        'casual' => [
            "Отличный день чтобы поговорить о $topic! Что вы думаете по этому поводу?",
            "Сегодня утром думал о $topic... интересно, а что об этом думаете вы?"
        ]
    ];
    
    $selectedTemplates = $templates[$tone] ?? $templates['casual'];
    return $selectedTemplates[array_rand($selectedTemplates)];
}
?>