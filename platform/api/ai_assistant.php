<?php
// Real API for the "Ask Abdullah" AI Assistant

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

// Get the user's prompt from the request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$prompt = isset($data['prompt']) ? trim($data['prompt']) : '';

if (empty($prompt)) {
    echo json_encode([
        'success' => false,
        'message' => 'No prompt provided.',
        'reply' => 'عفواً، لم أستلم أي رسالة. الرجاء كتابة سؤالك.'
    ]);
    exit;
}

// Predefined answers for platform-specific questions
$platform_questions = [
    'منصة إبداع' => 'منصة إبداع هي منصة للتدريب والتأهيل في اليمن، تعز.',
    'دورات' => 'نقدم مجموعة متنوعة من الدورات في مجالات البرمجة، التصميم، واللغات.',
    'التسجيل' => 'يمكنك التسجيل في الدورات من خلال صفحة الدورة نفسها.',
    'الأسعار' => 'تختلف الأسعار باختلاف الدورة، يمكنك الاطلاع على سعر كل دورة في صفحتها.',
];

foreach ($platform_questions as $key => $value) {
    if (strpos($prompt, $key) !== false) {
        echo json_encode([
            'success' => true,
            'reply' => $value,
            'received_prompt' => $prompt
        ]);
        exit;
    }
}

// Use Google Search for other questions
try {
    $gemini_api_key = getenv('GEMINI_API_KEY');
    if (!$gemini_api_key) {
        throw new Exception('Gemini API key not found.');
    }

    $client = new \Google\Client();
    $client->setApiKey($gemini_api_key);
    $gemini = new \Google\Service\AIPlatform($client);

    $response = $gemini->projects_locations_publishers_models->generateContent(
        'projects/ibdaa-taiz/locations/us-central1/publishers/google/models/gemini-1.0-pro',
        new \Google\Service\AIPlatform\GenerateContentRequest([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ])
    );

    $reply = $response['candidates'][0]['content']['parts'][0]['text'];

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'received_prompt' => $prompt
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error communicating with the AI model.',
        'reply' => 'عفواً، حدث خطأ أثناء محاولة الإجابة على سؤالك. الرجاء المحاولة مرة أخرى.',
        'error' => $e->getMessage()
    ]);
}
