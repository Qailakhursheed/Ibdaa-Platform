<?php
require_once __DIR__ . '/../database/db.php';

// --- Gemini API Configuration ---
function get_gemini_response($message, $conn) {
    $apiKey = 'AIzaSyC7KZFp8t6FAyXq3L0sjOTxpvJo4do_NwY';
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    // --- 1. Fetch course data for context ---
    $courseContext = "لا توجد معلومات عن الدورات حالياً.";
    try {
        $stmt = $conn->prepare("SELECT name, description, cost, duration_hours, status FROM courses WHERE status = 'active' ORDER BY created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($courses)) {
            $courseContext = "هذه هي الدورات المتاحة حالياً في منصة إبداع تعز:\n\n";
            foreach ($courses as $course) {
                $courseContext .= "- الدورة: " . htmlspecialchars($course['name']) . "\n";
                $courseContext .= "  - الوصف: " . htmlspecialchars($course['description']) . "\n";
                $courseContext .= "  - التكلفة: " . htmlspecialchars($course['cost']) . " ريال\n";
                $courseContext .= "  - المدة: " . htmlspecialchars($course['duration_hours']) . " ساعة\n\n";
            }
        }
    } catch (Exception $e) {
        error_log("Ask Abdullah - DB Context Error: " . $e->getMessage());
        $courseContext = "حدث خطأ أثناء جلب بيانات الدورات. يرجى إعلام المستخدم بالخطأ ومطالبته بالمحاولة لاحقاً.";
    }

    // --- 2. Construct the prompt for the AI ---
    $system_prompt = "
        أنت 'عبدالله'، مساعد ذكي ومتخصص لمنصة 'إبداع تعز' التعليمية.
        مهمتك هي الإجابة على أسئلة المستخدمين بأسلوب احترافي وودود.

        معلومات أساسية عنك وعن المنصة:
        - اسمك: عبدالله.
        - المنصة: إبداع تعز، وهي منصة تعليمية رائدة.
        - خبراتك: أنت خبير في مجالات متعددة مثل تصميم الويب، تحليل البيانات (Excel)، البرمجة، والتسويق.
        - هدفك: مساعدة المستخدمين، والإجابة على استفساراتهم حول الدورات، وتقديم شروحات مبسطة في مجالات خبرتك.

        معلومات الدورات الحالية (استخدمها عند الإجابة على الأسئلة المتعلقة بالدورات):
        {$courseContext}

        تعليمات:
        - كن ودوداً ومساعداً دائماً.
        - عندما تسأل عن الدورات، استخدم المعلومات المذكورة أعلاه.
        - إذا كان السؤال في مجال خبرتك (مثل 'اشرح لي دالة VLOOKUP في Excel' أو 'ما هي أساسيات HTML؟')، قدم إجابة واضحة ومفيدة.
        - إذا كان السؤال خارج نطاقك تماماً، اعتذر بلطف وأخبر المستخدم أنك متخصص في المجالات التقنية والتعليمية المتعلقة بالمنصة.
        - استخدم اللغة العربية الفصحى المبسطة.
    ";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $system_prompt],
                    ['text' => "سؤال المستخدم: " . $message]
                ]
            ]
        ]
    ];

    // --- 3. Make the cURL request to the Gemini API ---
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Use with caution

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // --- 4. Process the API response ---
    if ($curl_error) {
        error_log("Gemini API cURL Error: " . $curl_error);
        return 'عفواً، يبدو أن هناك مشكلة في الاتصال بمساعد الذكاء الاصطناعي. يرجى المحاولة مرة أخرى لاحقاً.';
    }

    if ($http_code !== 200) {
        error_log("Gemini API HTTP Error: Code " . $http_code . " | Response: " . $response);
        return 'عفواً، واجه المساعد الذكي خطأ. ربما يكون مفتاح API غير صحيح أو أن الخدمة تخضع للصيانة.';
    }

    $responseData = json_decode($response, true);
    $generated_text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (empty($generated_text)) {
        // Check for safety blocks
        if (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] === 'SAFETY') {
            return 'عفواً، لا يمكنني الإجابة على هذا السؤال لأنه قد يخالف سياسات السلامة.';
        }
        error_log("Gemini API - Empty Response: " . $response);
        return 'لم أتمكن من توليد إجابة. هل يمكنك إعادة صياغة سؤالك?';
    }

    return $generated_text;
}


// --- Main API Router ---

$action = $_REQUEST['action'] ?? 'chat';
$request_data = json_decode(file_get_contents('php://input'), true) ?? [];
$user_message = $request_data['message'] ?? '';

switch ($action) {
    case 'start':
        session_start();
        $session_id = session_id();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'session_id' => $session_id,
                'message' => 'مرحباً بك في منصة إبداع! أنا عبدالله، مساعدك الذكي. كيف يمكنني مساعدتك اليوم?',
                'quick_replies' => [
                    ['text' => 'ما هي الدورات المتاحة?', 'action' => 'ask_courses'],
                    ['text' => 'كيف يمكنني التسجيل?', 'action' => 'ask_registration'],
                    ['text' => 'اشرح لي أساسيات HTML', 'action' => 'ask_html'],
                ]
            ]
        ]);
        break;

    case 'chat':
        if (empty($user_message)) {
            echo json_encode(['success' => false, 'error' => 'No message provided.']);
            exit;
        }

        // The $conn variable comes from 'database/db.php'
        $bot_reply = get_gemini_response($user_message, $conn);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => $bot_reply
            ]
        ]);
        break;

    case 'history':
        echo json_encode(['success' => true, 'data' => []]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        break;
}

?>
