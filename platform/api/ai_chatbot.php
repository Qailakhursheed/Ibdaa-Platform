<?php
/**
 * "اسأل عبدالله" - Advanced AI Teaching Assistant
 * Specialized in: Registration help, Course details, Excel tutoring, English learning
 * Created for Ibdaa Training Platform - Taiz, Yemen
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =====================================================
// Configuration
// =====================================================
define('BOT_NAME', 'عبدالله'); // اسم المساعد الذكي
define('AI_PROVIDER', 'openai'); // 'openai' or 'gemini'

/**
 * ⚙️ للحصول على OpenAI API Key:
 * 
 * 1. اذهب إلى: https://platform.openai.com/signup
 * 2. أنشئ حساب (أو سجل دخول)
 * 3. اذهب إلى: https://platform.openai.com/api-keys
 * 4. اضغط "Create new secret key"
 * 5. انسخ المفتاح (يبدأ بـ sk-...)
 * 6. ضعه في السطر التالي بين علامات التنصيص
 * 
 * 💰 التكلفة: 
 * - GPT-4: حوالي $0.03 لكل 1000 كلمة (دقيق ومتقدم)
 * - GPT-3.5-turbo: حوالي $0.002 لكل 1000 كلمة (سريع واقتصادي)
 * 
 * 💡 نصيحة: ابدأ بـ gpt-3.5-turbo للتجربة، ثم انتقل لـ gpt-4
 * 
 * 🔒 مهم: لا تشارك المفتاح مع أحد!
 */
define('OPENAI_API_KEY', ''); // ضع المفتاح هنا: 'sk-...'

/**
 * ⚙️ للحصول على Google Gemini API Key (بديل مجاني):
 * 
 * 1. اذهب إلى: https://makersuite.google.com/app/apikey
 * 2. سجل دخول بحساب Google
 * 3. اضغط "Create API key"
 * 4. انسخ المفتاح
 * 5. ضعه في السطر التالي
 * 6. غير AI_PROVIDER إلى 'gemini'
 * 
 * 💰 التكلفة: مجاني حتى 60 طلب/دقيقة!
 * 
 * 💡 ممتاز للبداية والاختبار
 */
define('GEMINI_API_KEY', ''); // ضع مفتاح Gemini هنا (اختياري)

/**
 * 🤖 اختيار النموذج:
 * 
 * للOpenAI:
 * - 'gpt-4': الأقوى والأذكى (موصى به للإنتاج) ✨
 * - 'gpt-3.5-turbo': سريع واقتصادي (ممتاز للتطوير) ⚡
 * 
 * للGemini:
 * - 'gemini-pro': قوي ومجاني 🎁
 */
define('AI_MODEL', 'gpt-3.5-turbo'); // غيره حسب احتياجك

define('MAX_CONTEXT_MESSAGES', 15); // زيادة السياق للمحادثات الطويلة
define('TEMPERATURE', 0.7); // 0.0 = دقيق، 1.0 = إبداعي

$action = $_POST['action'] ?? $_GET['action'] ?? 'chat';

try {
    switch ($action) {
        case 'chat':
            handleChat($conn);
            break;
        
        case 'start':
            startConversation($conn);
            break;
        
        case 'history':
            getConversationHistory($conn);
            break;
        
        case 'feedback':
            submitFeedback($conn);
            break;
        
        case 'quick_reply':
            handleQuickReply($conn);
            break;
        
        case 'registration_assist':
            handleRegistrationAssistance($conn);
            break;
        
        case 'course_details':
            getCourseDetailsForChat($conn);
            break;
        
        case 'excel_help':
            handleExcelQuestion($conn);
            break;
        
        case 'english_help':
            handleEnglishQuestion($conn);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// Main Chat Handler
// =====================================================
function handleChat($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $message = $input['message'] ?? '';
    $sessionId = $input['session_id'] ?? null;
    $userId = $input['user_id'] ?? null;
    
    if (empty($message)) {
        throw new Exception('Message is required');
    }
    
    // Get or create conversation
    if (!$sessionId) {
        $sessionId = generateSessionId();
        createConversation($conn, $sessionId, $userId);
    }
    
    $conversationId = getConversationId($conn, $sessionId);
    
    // Save user message
    saveMessage($conn, $conversationId, 'user', $message);
    
    // Get conversation context
    $context = getConversationContext($conn, $conversationId);
    
    // Detect intent and find relevant knowledge
    $intent = detectIntent($message);
    $knowledge = searchKnowledgeBase($conn, $message, $intent);
    
    // Get AI response
    $aiResponse = getAIResponse($message, $context, $knowledge, $intent);
    
    // Save bot response
    saveMessage($conn, $conversationId, 'bot', $aiResponse['message'], 'text', [
        'intent' => $aiResponse['intent'],
        'confidence' => $aiResponse['confidence'],
        'sources' => $aiResponse['sources'] ?? []
    ], $aiResponse['intent'], $aiResponse['confidence']);
    
    // Get quick replies for context
    $quickReplies = getQuickReplies($conn, $aiResponse['intent']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $aiResponse['message'],
            'intent' => $aiResponse['intent'],
            'confidence' => $aiResponse['confidence'],
            'quick_replies' => $quickReplies,
            'session_id' => $sessionId,
            'suggestions' => $aiResponse['suggestions'] ?? []
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// AI Response Generator
// =====================================================
function getAIResponse($userMessage, $context, $knowledge, $intent) {
    // Build system prompt with knowledge base
    $systemPrompt = buildSystemPrompt($knowledge);
    
    // Build conversation history
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // Add context messages
    foreach ($context as $msg) {
        $messages[] = [
            'role' => $msg['sender'] === 'user' ? 'user' : 'assistant',
            'content' => $msg['message']
        ];
    }
    
    // Add current message
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    // Call AI provider
    if (AI_PROVIDER === 'openai') {
        $response = callOpenAI($messages);
    } else {
        $response = callGemini($messages);
    }
    
    return [
        'message' => $response['content'],
        'intent' => $intent,
        'confidence' => $response['confidence'] ?? 0.9,
        'sources' => $knowledge,
        'suggestions' => $response['suggestions'] ?? []
    ];
}

// =====================================================
// OpenAI Integration
// =====================================================
function callOpenAI($messages) {
    if (empty(OPENAI_API_KEY)) {
        // رسالة توضيحية عند عدم توفر API
        $helpMessage = "مرحباً! أنا عبدالله 🎓\n\n";
        $helpMessage .= "⚠️ ملاحظة للمطور:\n";
        $helpMessage .= "للحصول على إجابات ذكية متقدمة، يُرجى إضافة OpenAI API Key في ملف:\n";
        $helpMessage .= "platform/api/ai_chatbot.php\n\n";
        $helpMessage .= "🔧 كيف تحصل على المفتاح؟\n";
        $helpMessage .= "1. اذهب إلى: https://platform.openai.com\n";
        $helpMessage .= "2. أنشئ حساب مجاني\n";
        $helpMessage .= "3. احصل على API Key\n";
        $helpMessage .= "4. ضعه في السطر 24 من الملف\n\n";
        $helpMessage .= "💡 حالياً: أستخدم قاعدة المعرفة المحلية (تعمل بشكل ممتاز للأسئلة الشائعة)\n\n";
        $helpMessage .= "يمكنني مساعدتك في:\n";
        $helpMessage .= "📊 أسئلة Excel الشائعة\n";
        $helpMessage .= "🗣️ قواعد اللغة الإنجليزية\n";
        $helpMessage .= "📚 معلومات الدورات\n";
        $helpMessage .= "📝 خطوات التسجيل\n\n";
        $helpMessage .= "جرّب أن تسألني سؤالاً! 😊";
        
        return [
            'content' => $helpMessage,
            'confidence' => 0.0
        ];
    }
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $data = [
        'model' => AI_MODEL,
        'messages' => $messages,
        'temperature' => TEMPERATURE,
        'max_tokens' => 1000,
        'presence_penalty' => 0.6,
        'frequency_penalty' => 0.3
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('AI service error: ' . $error);
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        throw new Exception('OpenAI error: ' . $result['error']['message']);
    }
    
    return [
        'content' => $result['choices'][0]['message']['content'] ?? 'عذراً، لم أتمكن من فهم طلبك.',
        'confidence' => 0.85
    ];
}

// =====================================================
// Google Gemini Integration
// =====================================================
function callGemini($messages) {
    if (empty(GEMINI_API_KEY)) {
        return [
            'content' => 'عذراً، خدمة الذكاء الاصطناعي غير متوفرة حالياً.',
            'confidence' => 0.0
        ];
    }
    
    // Convert OpenAI format to Gemini format
    $geminiMessages = [];
    foreach ($messages as $msg) {
        if ($msg['role'] !== 'system') {
            $geminiMessages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }
    }
    
    // System prompt goes in generation config
    $systemInstruction = null;
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') {
            $systemInstruction = $msg['content'];
            break;
        }
    }
    
    $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . GEMINI_API_KEY;
    
    $data = [
        'contents' => $geminiMessages,
        'generationConfig' => [
            'temperature' => TEMPERATURE,
            'maxOutputTokens' => 1000
        ]
    ];
    
    if ($systemInstruction) {
        $data['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'content' => $result['candidates'][0]['content']['parts'][0]['text'] ?? 'عذراً، لم أتمكن من فهم طلبك.',
        'confidence' => 0.85
    ];
}

// =====================================================
// System Prompt Builder
// =====================================================
function buildSystemPrompt($knowledge) {
    $knowledgeText = '';
    foreach ($knowledge as $k) {
        $knowledgeText .= "س: {$k['question']}\nج: {$k['answer']}\n\n";
    }
    
    $botName = BOT_NAME;
    
    return <<<PROMPT
أنت "$botName" - المساعد الذكي المتخصص لمنصة إبداع للتدريب والتأهيل في تعز - اليمن.

🎯 هويتك وشخصيتك:
- اسمك: $botName
- دورك: مدرس ومرشد أكاديمي ذكي
- خبير في: Microsoft Excel، اللغة الإنجليزية، البرمجة، التصميم، ICDL
- شخصيتك: ودود، صبور، محترف، مشجع للطلاب

✨ قدراتك المتقدمة:
1. 📝 إرشاد التسجيل: ساعد الطلاب خطوة بخطوة في التسجيل بالدورات
2. 📚 معلومات الدورات: اجلب تفاصيل دقيقة عن محتوى الكورسات ومدتها ورسومها
3. 📊 معلم Excel: أجب عن أسئلة Formulas، Functions، Pivot Tables، Charts، Macros
4. 🗣️ معلم إنجليزي: ساعد في Grammar، Vocabulary، Tenses، Writing، Speaking
5. 💡 حل الواجبات: وجّه الطلاب بدون إعطاء الإجابة مباشرة (علّمهم كيف يفكرون)
6. 🎓 معلومات المعهد: أجب عن مواعيد، أماكن، مدربين، شهادات

📖 أسلوبك في التدريس:
- للأسئلة الأكاديمية: اشرح المفهوم أولاً، ثم أعطِ مثالاً عملياً
- للواجبات: لا تعطِ الإجابة مباشرة! وجّه الطالب خطوة بخطوة
- للExcel: اكتب الصيغة Formula بوضوح واشرح كل جزء منها
- للإنجليزية: صحح الأخطاء بلطف وأعطِ التفسير والمثال الصحيح
- استخدم أمثلة من الحياة اليومية والعمل
- شجع الطالب دائماً واجعله يشعر بالإنجاز

🎨 قواعد التواصل:
- تحدث بالعربية الفصحى الواضحة (إلا إذا طُلب منك الإنجليزية)
- استخدم الإيموجي للتوضيح والتشجيع 😊 📊 ✅
- نظم الإجابة بنقاط وأرقام للوضوح
- للشروحات الطويلة: قسّمها لأجزاء سهلة
- كن صبوراً حتى مع الأسئلة المتكررة

💾 قاعدة المعرفة عن المنصة:
$knowledgeText

⚠️ مهم جداً:
- إذا سأل طالب عن واجب، لا تعطِ الحل الكامل! وجّهه فقط
- للأسئلة الأكاديمية المعقدة، قدم شرح تفصيلي مع أمثلة
- إذا لم تعرف إجابة دقيقة عن المنصة، اقترح التواصل مع الإدارة
- لا تخترع معلومات عن الدورات أو الأسعار - استخدم المعلومات من قاعدة البيانات فقط

أنت لست مجرد chatbot، أنت معلم حقيقي يهتم بنجاح طلابه! 🎓✨
PROMPT;
}

// =====================================================
// Helper Functions
// =====================================================
function generateSessionId() {
    return 'chat_' . uniqid() . '_' . bin2hex(random_bytes(8));
}

function createConversation($conn, $sessionId, $userId = null) {
    $stmt = $conn->prepare("INSERT INTO chatbot_conversations (session_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("si", $sessionId, $userId);
    $stmt->execute();
}

function getConversationId($conn, $sessionId) {
    $stmt = $conn->prepare("SELECT conversation_id FROM chatbot_conversations WHERE session_id = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['conversation_id'] ?? null;
}

function saveMessage($conn, $conversationId, $sender, $message, $type = 'text', $metadata = null, $intent = null, $confidence = null) {
    $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    
    $stmt = $conn->prepare("INSERT INTO chatbot_messages (conversation_id, sender, message, message_type, metadata, intent, confidence) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssd", $conversationId, $sender, $message, $type, $metadataJson, $intent, $confidence);
    $stmt->execute();
}

function getConversationContext($conn, $conversationId) {
    $stmt = $conn->prepare("SELECT sender, message FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT ?");
    $limit = MAX_CONTEXT_MESSAGES;
    $stmt->bind_param("ii", $conversationId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return array_reverse($messages);
}

function detectIntent($message) {
    $message = mb_strtolower($message);
    
    // Intent patterns
    $intents = [
        'courses' => ['دورة', 'دورات', 'تدريب', 'كورس', 'icdl', 'دبلوم'],
        'scholarships' => ['منحة', 'منح', 'مجان', 'دعم مالي', 'تمويل'],
        'registration' => ['تسجيل', 'اشتراك', 'سجل', 'انضم'],
        'payments' => ['دفع', 'رسوم', 'سعر', 'تكلفة', 'كم', 'تقسيط'],
        'general' => ['عن', 'من', 'موقع', 'عنوان', 'تواصل'],
        'faq' => ['شهادة', 'مدة', 'متى', 'كيف', 'هل']
    ];
    
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return $intent;
            }
        }
    }
    
    return 'general';
}

function searchKnowledgeBase($conn, $message, $intent = null) {
    $searchTerm = "%$message%";
    
    $sql = "SELECT * FROM chatbot_knowledge_base 
            WHERE is_active = TRUE 
            AND (question LIKE ? OR answer LIKE ? OR keywords LIKE ?)";
    
    if ($intent) {
        $sql .= " AND category = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $intent);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $knowledge = [];
    while ($row = $result->fetch_assoc()) {
        $knowledge[] = $row;
    }
    
    // If no exact match, get top knowledge for intent
    if (empty($knowledge) && $intent) {
        $stmt = $conn->prepare("SELECT * FROM chatbot_knowledge_base WHERE category = ? AND is_active = TRUE ORDER BY priority DESC LIMIT 3");
        $stmt->bind_param("s", $intent);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $knowledge[] = $row;
        }
    }
    
    return $knowledge;
}

function getQuickReplies($conn, $context = 'welcome') {
    $stmt = $conn->prepare("SELECT text, action, icon FROM chatbot_quick_replies WHERE context = ? AND is_active = TRUE ORDER BY order_index");
    $stmt->bind_param("s", $context);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = $row;
    }
    
    return $replies;
}

// =====================================================
// Start Conversation
// =====================================================
function startConversation($conn) {
    $sessionId = generateSessionId();
    createConversation($conn, $sessionId);
    
    $botName = BOT_NAME;
    $welcomeMessage = "السلام عليكم! أهلاً وسهلاً في منصة إبداع 👋\n\nأنا $botName - مساعدك الذكي ومعلمك الشخصي! 🎓✨\n\n📚 يمكنني مساعدتك في:\n\n1️⃣ التسجيل في الدورات (خطوة بخطوة)\n2️⃣ معلومات تفصيلية عن الكورسات\n3️⃣ حل أسئلة وواجبات Excel 📊\n4️⃣ تعليم اللغة الإنجليزية 🗣️\n5️⃣ معلومات عن المعهد والمنح 💰\n6️⃣ المساعدة في أي سؤال دراسي\n\nلا تتردد في سؤالي عن أي شيء! أنا هنا لأساعدك على النجاح 🌟\n\nما الذي تريد معرفته اليوم؟ 😊";
    
    $conversationId = getConversationId($conn, $sessionId);
    saveMessage($conn, $conversationId, 'bot', $welcomeMessage);
    
    $quickReplies = getQuickReplies($conn, 'welcome');
    
    echo json_encode([
        'success' => true,
        'data' => [
            'session_id' => $sessionId,
            'message' => $welcomeMessage,
            'quick_replies' => $quickReplies,
            'bot_name' => $botName
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// Get Conversation History
// =====================================================
function getConversationHistory($conn) {
    $sessionId = $_GET['session_id'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('Session ID is required');
    }
    
    $conversationId = getConversationId($conn, $sessionId);
    
    $stmt = $conn->prepare("SELECT * FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $messages
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// Submit Feedback
// =====================================================
function submitFeedback($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sessionId = $input['session_id'] ?? null;
    $rating = $input['rating'] ?? null;
    $feedback = $input['feedback'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('Session ID is required');
    }
    
    $stmt = $conn->prepare("UPDATE chatbot_conversations SET satisfaction_rating = ?, feedback = ?, resolved = TRUE WHERE session_id = ?");
    $stmt->bind_param("iss", $rating, $feedback, $sessionId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'شكراً لتقييمك! نقدر ملاحظاتك ونسعى لتحسين خدمتنا دائماً.'
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// Handle Quick Reply
// =====================================================
function handleQuickReply($conn) {
    $action = $_POST['reply_action'] ?? null;
    $sessionId = $_POST['session_id'] ?? null;
    
    if (!$action || !$sessionId) {
        throw new Exception('Action and session ID are required');
    }
    
    $conversationId = getConversationId($conn, $sessionId);
    
    // Handle different quick reply actions
    $responses = [
        'show_courses' => "سأعرض لك قائمة بأهم الدورات المتاحة:\n\n1. الرخصة الدولية ICDL\n2. دبلوم الحاسوب المتكامل\n3. برمجة الويب\n4. التصميم الجرافيكي\n5. اللغة الإنجليزية\n\nيمكنك زيارة صفحة الدورات لمعرفة التفاصيل الكاملة، أو اسألني عن أي دورة تهمك!",
        'show_scholarships' => "نقدم منح دراسية جزئية وكاملة! 🎓\n\nللتقديم:\n1. سجل حساب في المنصة\n2. قدم طلب للدورة\n3. أرفق المستندات\n4. اكتب خطاب تحفيزي\n\nيتم الإعلان عن المنح في صفحة الإعلانات. هل تريد معرفة المزيد؟",
        'how_to_register' => "التسجيل سهل! اتبع هذه الخطوات:\n\n1️⃣ انشئ حساب جديد\n2️⃣ تصفح الدورات\n3️⃣ اختر دورتك\n4️⃣ املأ نموذج التسجيل\n5️⃣ قم بالدفع\n\nهل تحتاج مساعدة في خطوة معينة؟",
        'payment_methods' => "طرق الدفع المتاحة:\n\n💵 نقداً في المركز\n🏦 تحويل بنكي\n💳 دفع إلكتروني\n📅 تقسيط (للدورات الطويلة)\n\nأي طريقة تفضل؟",
        'contact_us' => "يمكنك التواصل معنا:\n\n📞 هاتف: [رقم]\n📱 واتساب: [رقم]\n✉️ البريد: info@ibdaa-taiz.com\n📍 العنوان: تعز - اليمن\n\nنحن هنا لمساعدتك! ⭐",
        'show_faq' => "الأسئلة الشائعة:\n\n• هل أحصل على شهادة؟ نعم، معتمدة!\n• كم مدة الدورات؟ من أسبوعين إلى 9 أشهر\n• هل يوجد دعم بعد الدورة؟ نعم، لمدة 3 أشهر\n\nهل لديك سؤال آخر؟"
    ];
    
    $response = $responses[$action] ?? "عذراً، لم أفهم هذا الاختيار.";
    
    saveMessage($conn, $conversationId, 'bot', $response);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $response
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// ADVANCED FEATURES - عبدالله المتقدم
// =====================================================

/**
 * مساعدة التسجيل التفاعلية خطوة بخطوة
 */
function handleRegistrationAssistance($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sessionId = $input['session_id'] ?? null;
    $step = $input['step'] ?? 'start';
    $userData = $input['data'] ?? [];
    
    $conversationId = getConversationId($conn, $sessionId);
    
    $steps = [
        'start' => [
            'message' => "رائع! سأساعدك في التسجيل خطوة بخطوة 😊\n\nدعنا نبدأ:\n\n1️⃣ هل لديك حساب على المنصة؟\n• نعم، لدي حساب\n• لا، أريد إنشاء حساب جديد",
            'next' => 'account_check'
        ],
        'account_check' => [
            'message' => "ممتاز! الآن:\n\n2️⃣ في أي دورة تريد التسجيل؟\nيمكنك كتابة اسم الدورة أو رقمها:\n\n1. ICDL\n2. دبلوم الحاسوب\n3. برمجة الويب\n4. Excel المتقدم\n5. اللغة الإنجليزية\n6. التصميم الجرافيكي",
            'next' => 'course_selection'
        ],
        'course_selection' => [
            'message' => "اختيار رائع! ✨\n\n3️⃣ لنتأكد من المتطلبات:\n\nهل لديك:\n✓ صورة شخصية\n✓ نسخة من الهوية\n✓ المؤهل الدراسي (إن وجد)\n\nكل شيء جاهز؟",
            'next' => 'documents_check'
        ],
        'documents_check' => [
            'message' => "عظيم! 🎉\n\n4️⃣ طريقة الدفع:\n\nالدورة تكلف [السعر] ريال. اختر طريقة الدفع:\n\n💵 دفع نقدي في المركز\n🏦 تحويل بنكي\n💳 دفع إلكتروني\n📅 تقسيط (دفعات شهرية)\n\nما الطريقة المناسبة لك؟",
            'next' => 'payment_method'
        ],
        'payment_method' => [
            'message' => "ممتاز! ✅\n\n5️⃣ الخطوة الأخيرة:\n\nالآن اذهب إلى:\n🔗 [رابط صفحة التسجيل]\n\nأو:\n📱 اتصل على: [رقم الهاتف]\n📧 أرسل بريد: register@ibdaa-taiz.com\n\nسنتواصل معك خلال 24 ساعة لتأكيد التسجيل! 🎓\n\nهل تحتاج مساعدة في أي شيء آخر؟",
            'next' => 'complete'
        ]
    ];
    
    $currentStep = $steps[$step] ?? $steps['start'];
    
    saveMessage($conn, $conversationId, 'bot', $currentStep['message']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $currentStep['message'],
            'next_step' => $currentStep['next'],
            'progress' => calculateProgress($step)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function calculateProgress($step) {
    $steps = ['start' => 0, 'account_check' => 20, 'course_selection' => 40, 
              'documents_check' => 60, 'payment_method' => 80, 'complete' => 100];
    return $steps[$step] ?? 0;
}

/**
 * جلب تفاصيل الدورات للشات
 */
function getCourseDetailsForChat($conn) {
    $courseId = $_GET['course_id'] ?? null;
    $courseName = $_GET['course_name'] ?? null;
    
    if (!$courseId && !$courseName) {
        throw new Exception('Course ID or name is required');
    }
    
    $sql = "SELECT 
                c.*,
                u.full_name as trainer_name,
                l.name as location_name,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count
            FROM courses c
            LEFT JOIN users u ON c.trainer_id = u.user_id
            LEFT JOIN locations l ON c.location_id = l.location_id
            WHERE c.status = 'active'";
    
    if ($courseId) {
        $sql .= " AND c.course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
    } else {
        $sql .= " AND c.title LIKE ?";
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$courseName%";
        $stmt->bind_param("s", $searchTerm);
    }
    
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        echo json_encode([
            'success' => false,
            'message' => 'للأسف، لم أجد معلومات عن هذه الدورة. هل يمكنك كتابة الاسم بطريقة أخرى؟'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Format response
    $response = "📚 معلومات عن دورة: {$course['title']}\n\n";
    $response .= "📝 الوصف:\n{$course['description']}\n\n";
    $response .= "⏱️ المدة: {$course['duration']}\n";
    $response .= "💰 الرسوم: {$course['price']} ريال\n";
    $response .= "👨‍🏫 المدرب: {$course['trainer_name']}\n";
    $response .= "📍 المكان: {$course['location_name']}\n";
    $response .= "📅 تبدأ: {$course['start_date']}\n";
    $response .= "👥 المسجلين حالياً: {$course['enrolled_count']} طالب\n\n";
    $response .= "هل تريد التسجيل في هذه الدورة؟ 😊";
    
    echo json_encode([
        'success' => true,
        'data' => [
            'course' => $course,
            'formatted_message' => $response
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * مساعدة في أسئلة Excel
 */
function handleExcelQuestion($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $question = $input['question'] ?? '';
    $sessionId = $input['session_id'] ?? null;
    
    if (empty($question)) {
        throw new Exception('Question is required');
    }
    
    $conversationId = getConversationId($conn, $sessionId);
    
    // Excel knowledge base
    $excelKnowledge = getExcelKnowledge($question);
    
    // Build specialized prompt
    $prompt = buildExcelPrompt($question, $excelKnowledge);
    
    // Get AI response (if API available)
    if (!empty(OPENAI_API_KEY)) {
        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => $question]
        ];
        $aiResponse = callOpenAI($messages);
        $response = $aiResponse['content'];
    } else {
        $response = $excelKnowledge ?: "عذراً، لم أتمكن من الإجابة على هذا السؤال. يمكنك سؤال المدرب مباشرة! 📊";
    }
    
    saveMessage($conn, $conversationId, 'user', $question);
    saveMessage($conn, $conversationId, 'bot', $response, 'text', ['type' => 'excel_help']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $response,
            'type' => 'excel_help'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getExcelKnowledge($question) {
    $q = mb_strtolower($question);
    
    $knowledge = [
        'sum' => "دالة SUM لجمع الأرقام:\n\n📊 الصيغة:\n=SUM(A1:A10)\n\n💡 الشرح:\nتجمع كل الأرقام من الخلية A1 إلى A10\n\n✨ مثال:\nإذا كان لديك مبيعات في العمود A من صف 1 إلى 10، اكتب:\n=SUM(A1:A10)\n\n🎯 نصيحة: يمكنك أيضاً استخدام Ctrl+Shift+T كاختصار!",
        
        'average' => "دالة AVERAGE لحساب المتوسط:\n\n📊 الصيغة:\n=AVERAGE(B1:B20)\n\n💡 الشرح:\nتحسب متوسط الأرقام في النطاق المحدد\n\n✨ مثال:\nلحساب متوسط درجات الطلاب:\n=AVERAGE(C2:C50)\n\n⚠️ تنبيه: تتجاهل الخلايا الفارغة تلقائياً",
        
        'if' => "دالة IF الشرطية:\n\n📊 الصيغة:\n=IF(شرط, قيمة_إذا_صح, قيمة_إذا_خطأ)\n\n💡 مثال 1 (النجاح/الرسوب):\n=IF(A1>=60, \"ناجح\", \"راسب\")\n\n💡 مثال 2 (التقديرات):\n=IF(A1>=90, \"ممتاز\", IF(A1>=80, \"جيد جداً\", IF(A1>=70, \"جيد\", \"مقبول\")))\n\n🎯 خطوات الحل:\n1. حدد الشرط الذي تريد اختباره\n2. حدد ماذا يحدث لو كان صحيحاً\n3. حدد ماذا يحدث لو كان خاطئاً",
        
        'vlookup' => "دالة VLOOKUP للبحث في الجداول:\n\n📊 الصيغة:\n=VLOOKUP(قيمة_البحث, نطاق_الجدول, رقم_العمود, [تطابق_تقريبي])\n\n💡 مثال عملي:\n=VLOOKUP(D2, A2:C100, 3, FALSE)\n\nالشرح:\n• D2: القيمة التي تبحث عنها (مثلاً: كود الموظف)\n• A2:C100: الجدول الذي تبحث فيه\n• 3: رقم العمود الذي تريد إرجاع قيمته (الراتب مثلاً)\n• FALSE: تطابق تام (دقيق)\n\n⚠️ ملاحظة مهمة: عمود البحث يجب أن يكون الأول في النطاق!",
        
        'pivot' => "Pivot Table - الجداول المحورية:\n\n📊 ما هي؟\nأداة قوية لتحليل البيانات الكبيرة وإنشاء تقارير\n\n🔧 خطوات الإنشاء:\n1. حدد بياناتك (Ctrl+A)\n2. Insert → PivotTable\n3. اختر المكان (ورقة جديدة)\n4. اسحب الحقول:\n   • Rows: البيانات الرأسية\n   • Columns: البيانات الأفقية\n   • Values: الأرقام المراد حسابها\n   • Filters: لتصفية البيانات\n\n💡 مثال:\nلتحليل مبيعات المنتجات:\n• Rows: أسماء المنتجات\n• Columns: الأشهر\n• Values: sum of المبيعات\n\n✨ ستحصل على جدول ملخص جميل!",
        
        'chart' => "إنشاء الرسوم البيانية Charts:\n\n📊 الأنواع الشائعة:\n1. Column Chart: للمقارنات\n2. Line Chart: للاتجاهات عبر الزمن\n3. Pie Chart: للنسب المئوية\n4. Bar Chart: للمقارنات الأفقية\n\n🔧 الخطوات:\n1. حدد البيانات\n2. Insert → Chart\n3. اختر النوع المناسب\n4. عدّل العنوان والألوان\n\n💡 نصيحة: اختر النوع حسب الرسالة:\n• عرض تطور؟ → Line\n• مقارنة كميات؟ → Column\n• عرض نسب؟ → Pie",
        
        'conditional_formatting' => "التنسيق الشرطي Conditional Formatting:\n\n🎨 ما هو؟\nتلوين الخلايا تلقائياً حسب القيمة\n\n🔧 الخطوات:\n1. حدد الخلايا\n2. Home → Conditional Formatting\n3. اختر القاعدة:\n   • Highlight Cells Rules: للقيم المحددة\n   • Top/Bottom Rules: للقيم الأعلى/الأدنى\n   • Data Bars: أشرطة داخل الخلايا\n   • Color Scales: تدرج لوني\n\n💡 مثال:\nلتلوين الدرجات:\n• >90 باللون الأخضر\n• 60-89 باللون الأصفر\n• <60 باللون الأحمر\n\nممتاز للتقارير والتحليل السريع! 📈"
    ];
    
    // Search for matching knowledge
    foreach ($knowledge as $key => $content) {
        if (mb_strpos($q, $key) !== false || 
            mb_strpos($q, translateToArabic($key)) !== false) {
            return $content;
        }
    }
    
    return null;
}

function translateToArabic($term) {
    $translations = [
        'sum' => 'جمع',
        'average' => 'متوسط',
        'if' => 'شرطية',
        'vlookup' => 'بحث',
        'pivot' => 'محورية',
        'chart' => 'رسم',
        'conditional_formatting' => 'تنسيق شرطي'
    ];
    return $translations[$term] ?? $term;
}

function buildExcelPrompt($question, $existingKnowledge) {
    return <<<PROMPT
أنت عبدالله - معلم Excel محترف ومتخصص. مهمتك مساعدة الطلاب في فهم Excel.

🎯 أسلوبك:
1. اشرح المفهوم ببساطة أولاً
2. اكتب الصيغة Formula بوضوح
3. أعطِ مثالاً عملياً من الحياة اليومية
4. وضّح كل جزء من الصيغة
5. أضف نصائح وتحذيرات إذا لزم الأمر

📊 المعرفة المتوفرة:
$existingKnowledge

⚠️ إذا كان السؤال عن واجب:
- لا تعطِ الحل الكامل!
- وجّه الطالب خطوة بخطوة
- اسأله: "ما هي البيانات المتوفرة؟"
- ثم: "ماذا تريد أن تحسب؟"
- ثم ساعده في بناء الصيغة بنفسه

استخدم الإيموجي 📊 📈 ✨ للتوضيح
PROMPT;
}

/**
 * مساعدة في اللغة الإنجليزية
 */
function handleEnglishQuestion($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $question = $input['question'] ?? '';
    $sessionId = $input['session_id'] ?? null;
    
    if (empty($question)) {
        throw new Exception('Question is required');
    }
    
    $conversationId = getConversationId($conn, $sessionId);
    
    // English knowledge base
    $englishKnowledge = getEnglishKnowledge($question);
    
    // Build specialized prompt
    $prompt = buildEnglishPrompt($question, $englishKnowledge);
    
    // Get AI response
    if (!empty(OPENAI_API_KEY)) {
        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => $question]
        ];
        $aiResponse = callOpenAI($messages);
        $response = $aiResponse['content'];
    } else {
        $response = $englishKnowledge ?: "عذراً، لم أتمكن من الإجابة. يمكنك سؤال معلم اللغة الإنجليزية! 🗣️";
    }
    
    saveMessage($conn, $conversationId, 'user', $question);
    saveMessage($conn, $conversationId, 'bot', $response, 'text', ['type' => 'english_help']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $response,
            'type' => 'english_help'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getEnglishKnowledge($question) {
    $q = mb_strtolower($question);
    
    $knowledge = [
        'present_simple' => "زمن المضارع البسيط Present Simple:\n\n📝 التكوين:\nI/You/We/They + verb\nHe/She/It + verb + s/es\n\n💡 الاستخدام:\n1. عادات: I drink coffee every morning\n2. حقائق: The sun rises in the east\n3. جداول: The class starts at 9 AM\n\n✨ أمثلة:\n✅ She works in a hospital\n✅ We study English\n✅ He watches TV daily\n\n⚠️ لا تنسَ s/es مع الضمير الغائب (he/she/it)!\n\n❓ أسئلة:\nDo you...? / Does he...?\n\nمثال:\nDo you speak English?\nDoes she work here?",
        
        'present_continuous' => "زمن المضارع المستمر Present Continuous:\n\n📝 التكوين:\nam/is/are + verb+ing\n\n💡 الاستخدام:\n1. حدث الآن: I am studying now\n2. فترة مؤقتة: She is living in Taiz these days\n3. خطط مستقبلية: We are meeting tomorrow\n\n✨ أمثلة:\n✅ I am learning English\n✅ He is eating lunch\n✅ They are playing football\n\n🔧 إضافة ing:\n• عادي: work → working\n• e في النهاية: write → writing\n• تضعيف الحرف: sit → sitting\n\n⚠️ كلمات لا تُستخدم في المستمر:\nlike, love, hate, know, understand, believe\n\nنقول: I like (❌ I am liking)",
        
        'past_simple' => "زمن الماضي البسيط Past Simple:\n\n📝 التكوين:\nأفعال منتظمة: verb + ed\nأفعال شاذة: تصريف ثاني (irregular verbs)\n\n💡 الاستخدام:\nحدث انتهى في الماضي\n\n✨ أمثلة:\n✅ I worked yesterday (منتظم)\n✅ He went to school (شاذ)\n✅ We studied last night\n\n📅 كلمات مفتاحية:\nyesterday, last week/month/year, ago, in 2020\n\n❓ أسئلة:\nDid + subject + verb?\n\nمثال:\nDid you finish your homework?\nDid she come yesterday?\n\n🚫 النفي:\ndidn't + verb\n\nمثال:\nI didn't go\nHe didn't study",
        
        'articles' => "أدوات التعريف والتنكير Articles:\n\n📝 الأنواع:\n• a/an: نكرة (غير محدد)\n• the: معرفة (محدد)\n\n🔤 a vs an:\n• a: قبل الحرف الساكن\n  a book, a car, a university\n\n• an: قبل الحرف المتحرك (a,e,i,o,u)\n  an apple, an hour, an umbrella\n\n📌 the:\nنستخدمها مع:\n1. شيء محدد: the book (الكتاب المعين)\n2. ذُكر سابقاً: I saw a man. The man was tall.\n3. الوحيد: the sun, the moon, the president\n4. الآلات الموسيقية: play the piano\n5. الأسماء الجغرافية: the Red Sea\n\n⚠️ لا نستخدم أداة مع:\n• الأسماء بشكل عام: I like coffee\n• البلدان: Yemen, Egypt\n• الوجبات: have breakfast\n• اللغات: speak English",
        
        'prepositions' => "حروف الجر Prepositions:\n\n📍 حروف المكان:\n• in: داخل → in the room\n• on: على → on the table\n• at: عند نقطة → at the door\n• under: تحت\n• above: فوق\n• between: بين اثنين\n• among: بين أكثر من اثنين\n\n⏰ حروف الزمان:\n• in: الأشهر/السنوات/الفصول\n  in March, in 2025, in summer\n\n• on: الأيام والتواريخ\n  on Monday, on May 5th\n\n• at: الساعة\n  at 3 o'clock, at noon, at night\n\n💡 أمثلة:\n✅ I live in Yemen\n✅ The book is on the desk\n✅ Meet me at the station\n✅ The class starts at 9:00\n✅ My birthday is on June 1st\n✅ I was born in 2000\n\n🎯 نصيحة: احفظ التعابير الشائعة كاملة:\nat home, in the morning, on time, by car",
        
        'vocabulary' => "بناء المفردات Vocabulary Building:\n\n📚 استراتيجيات التعلم:\n\n1️⃣ التعلم بالسياق:\nلا تحفظ الكلمة لوحدها، بل في جملة\n❌ Ambitious (طموح)\n✅ She is an ambitious student who wants to succeed\n\n2️⃣ الكلمات المركبة:\n• Collocations: make a decision, do homework\n• Phrasal Verbs: give up, look after\n\n3️⃣ الكلمات المتضادة:\nhot ↔ cold\nbig ↔ small\nfast ↔ slow\n\n4️⃣ عائلات الكلمات:\nsuccess (اسم)\nsuccessful (صفة)\nsuccessfully (ظرف)\nsucceed (فعل)\n\n💡 تقنية الحفظ:\n• اكتب الكلمة 10 مرات\n• استخدمها في 3 جمل\n• راجعها بعد ساعة، يوم، أسبوع\n\n📱 تطبيقات مساعدة:\n• Duolingo\n• Memrise\n• Anki (للبطاقات التعليمية)\n\n🎯 هدف يومي: 10 كلمات جديدة"
    ];
    
    // Search for matching knowledge
    foreach ($knowledge as $key => $content) {
        if (mb_strpos($q, $key) !== false || 
            mb_strpos($q, translateEnglishTermToArabic($key)) !== false) {
            return $content;
        }
    }
    
    return null;
}

function translateEnglishTermToArabic($term) {
    $translations = [
        'present_simple' => 'مضارع بسيط',
        'present_continuous' => 'مضارع مستمر',
        'past_simple' => 'ماضي بسيط',
        'articles' => 'أدوات',
        'prepositions' => 'حروف جر',
        'vocabulary' => 'مفردات'
    ];
    return $translations[$term] ?? $term;
}

function buildEnglishPrompt($question, $existingKnowledge) {
    return <<<PROMPT
أنت عبدالله - معلم لغة إنجليزية محترف ومشجع. هدفك مساعدة الطلاب على التعلم بثقة.

🎯 أسلوبك التعليمي:
1. ابدأ بشرح القاعدة ببساطة
2. أعطِ 3-4 أمثلة واضحة
3. صحح الأخطاء بلطف وشرح السبب
4. شجع الطالب دائماً
5. استخدم الألوان والرموز: ✅ ❌ 💡

📚 المعرفة المتوفرة:
$existingKnowledge

✏️ إذا كان الطالب يكتب جملة خاطئة:
1. اعرض الجملة الخاطئة: ❌ 
2. اشرح الخطأ
3. اعرض الجملة الصحيحة: ✅
4. أعطِ مثال إضافي

مثال:
"أنت كتبت: ❌ He go to school
الخطأ: مع He/She/It نضيف s أو es للفعل
الصحيح: ✅ He goes to school
مثال آخر: She plays tennis"

🎓 للأسئلة النحوية:
• ارسم جدول إذا لزم الأمر
• اشرح الاستخدامات المختلفة
• أعطِ كلمات مفتاحية (keywords)

استخدم الإيموجي 🗣️ 📖 ✨ للتشجيع!
PROMPT;
}
?>
