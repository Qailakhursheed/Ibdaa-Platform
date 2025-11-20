<?php
// Mock API for the "Ask Abdullah" AI Assistant

header('Content-Type: application/json');

// Simulate a network delay
sleep(1);

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

// Basic, static responses based on keywords
$reply = 'أهلاً بك! أنا عبدالله، مساعدك الذكي في منصة إبداع. حالياً ما زلت في مرحلة التطوير، ولكنني سأكون جاهزاً لمساعدتك قريباً في كل ما يخص الدورات والتسجيل. شكراً لتفهمك!';

if (strpos($prompt, 'الدورات') !== false || strpos($prompt, 'دورات') !== false) {
    $reply = 'يمكنك الاطلاع على جميع دوراتنا المتاحة من خلال صفحة الدورات. نقدم مجموعة واسعة من البرامج في مجالات مثل تطوير الويب، التصميم، والبرمجة.';
} elseif (strpos($prompt, 'التسجيل') !== false) {
    $reply = 'للتسجيل في إحدى دوراتنا، يمكنك زيارة صفحة الدورة التي تهمك والضغط على زر "التسجيل الآن". إذا واجهت أي صعوبة، فريق الدعم الفني جاهز لمساعدتك.';
} elseif (strpos($prompt, 'أسعار') !== false || strpos($prompt, 'سعر') !== false) {
    $reply = 'تختلف أسعار الدورات حسب المجال والمدة. تجد سعر كل دورة في صفحتها الخاصة. نقدم أيضاً خصومات مميزة للطلاب والمجموعات.';
}

// Send the response
echo json_encode([
    'success' => true,
    'reply' => $reply,
    'received_prompt' => $prompt
]);
