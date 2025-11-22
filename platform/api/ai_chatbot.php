<?php
/**
 * "اسأل عبدالله" - Advanced AI Teaching Assistant
 * Specialized in: Registration help, Course details, Excel tutoring, English learning
 * Created for Ibdaa Training Platform - Taiz, Yemen
 */

// Start output buffering to catch any unwanted output
ob_start();

// Disable error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../db.php';

/**
 * Helper function to send clean JSON response
 */
function sendJsonResponse($data) {
    // Clear any previous output (warnings, notices, whitespace)
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =====================================================
// Configuration
// =====================================================
define('BOT_NAME', 'عبدالله'); // اسم المساعد الذكي
define('AI_PROVIDER', 'gemini'); // 'openai' or 'gemini'

require_once __DIR__ . '/../../includes/config.php';

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
define('AI_MODEL', 'gemini-pro'); // غيره حسب احتياجك

define('MAX_CONTEXT_MESSAGES', 15); // زيادة السياق للمحادثات الطويلة
define('TEMPERATURE', 0.7); // 0.0 = دقيق، 1.0 = إبداعي

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * بيانات احتياطية تُستخدم عندما لا تكون جداول قاعدة البيانات متوفرة.
 */
function getFallbackKnowledgeBaseData() {
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $data = [
        [
            'category' => 'courses',
            'question' => 'ما هي الدورات المتاحة؟',
            'answer' => 'نقدم مجموعة واسعة من الدورات التدريبية مثل ICDL، دبلوم الحاسوب المتكامل، برمجة الويب، التصميم الجرافيكي، التسويق الإلكتروني، واللغة الإنجليزية. يمكن استعراض التفاصيل الكاملة من صفحة الدورات.',
            'keywords' => 'دورات,تدريب,ICDL,دبلوم,برمجة,تصميم,لغة',
            'priority' => 10
        ],
        [
            'category' => 'courses',
            'question' => 'كم مدة الدورة؟',
            'answer' => 'تتراوح مدة الدورات بين أسبوعين وتسعة أشهر حسب نوع الدورة. على سبيل المثال: ICDL غالباً ثلاثة أشهر بينما الدبلومات تصل إلى تسعة أشهر.',
            'keywords' => 'مدة,وقت,فترة,أشهر',
            'priority' => 8
        ],
        [
            'category' => 'courses',
            'question' => 'متى تبدأ الدورات؟',
            'answer' => 'يتم فتح دفعات جديدة بشكل شهري، ويمكن معرفة المواعيد الأحدث من صفحة الدورات أو بالتواصل مع فريق المنصة.',
            'keywords' => 'موعد,بداية,متى تبدأ,تاريخ',
            'priority' => 9
        ],
        [
            'category' => 'scholarships',
            'question' => 'هل توجد منح دراسية؟',
            'answer' => 'نعم، نوفر منحاً جزئية وكاملة للمتفوقين والحالات الخاصة، ويتم الإعلان عن المنح عبر صفحة الإعلانات الرسمية.',
            'keywords' => 'منح,منحة,مجانية,دعم مالي',
            'priority' => 10
        ],
        [
            'category' => 'scholarships',
            'question' => 'كيف أتقدم للمنحة؟',
            'answer' => 'للتقدم للمنحة قم بتسجيل حساب، واختر الدورة المطلوبة، ثم أرفق المستندات واكتب خطاب التحفيز وسيتم الرد خلال خمسة أيام عمل.',
            'keywords' => 'تقديم منحة,طلب منحة,شروط المنحة',
            'priority' => 9
        ],
        [
            'category' => 'registration',
            'question' => 'كيف أسجل في دورة؟',
            'answer' => 'خطوات التسجيل: 1) أنشئ حساباً جديداً، 2) تصفح الدورات، 3) اختر الدورة المناسبة، 4) املأ نموذج التسجيل، 5) أكمل الدفع أو قدم طلب منحة.',
            'keywords' => 'تسجيل,اشتراك,كيف اسجل,خطوات التسجيل',
            'priority' => 10
        ],
        [
            'category' => 'registration',
            'question' => 'ما المستندات المطلوبة؟',
            'answer' => 'عادة نحتاج إلى صورة شخصية، ونسخة من الهوية أو البطاقة الشخصية، وأي مؤهل دراسي متوفر، بالإضافة إلى إيصال الدفع عند الحاجة.',
            'keywords' => 'مستندات,وثائق,ملفات مطلوبة',
            'priority' => 8
        ],
        [
            'category' => 'payments',
            'question' => 'كم رسوم الدورة؟',
            'answer' => 'تختلف الرسوم حسب نوع الدورة: ICDL تقريباً 40,000 ريال، الدبلومات بين 60,000 و80,000 ريال، والدورات القصيرة من 15,000 إلى 30,000 ريال.',
            'keywords' => 'رسوم,سعر,تكلفة,كم',
            'priority' => 9
        ],
        [
            'category' => 'payments',
            'question' => 'طرق الدفع المتاحة؟',
            'answer' => 'يمكنك الدفع نقداً في المركز، أو عبر التحويل البنكي، أو الدفع الإلكتروني، ونوفر خيار التقسيط للدورات الطويلة.',
            'keywords' => 'دفع,طرق الدفع,كيف ادفع,تحويل',
            'priority' => 8
        ],
        [
            'category' => 'general',
            'question' => 'ما هي منصة إبداع؟',
            'answer' => 'منصة إبداع مركز تدريبي في تعز يهدف لتأهيل الشباب في مجالات الحاسوب، البرمجة، التصميم، اللغات، والمهارات المهنية مع شهادات معتمدة.',
            'keywords' => 'ابداع,المنصة,عن المنصة,من نحن',
            'priority' => 10
        ],
        [
            'category' => 'general',
            'question' => 'أين يقع المركز؟',
            'answer' => 'يقع المركز في مدينة تعز - اليمن، ويمكن الحصول على العنوان الكامل من صفحة اتصل بنا أو عبر التواصل الهاتفي.',
            'keywords' => 'موقع,عنوان,مكان,أين',
            'priority' => 8
        ],
        [
            'category' => 'faq',
            'question' => 'هل أحصل على شهادة؟',
            'answer' => 'نعم، يحصل جميع المتدربين على شهادة معتمدة عند اجتياز الدورة بنجاح، وشهادة ICDL معترف بها دولياً.',
            'keywords' => 'شهادة,سيرتفيكيت,معتمدة',
            'priority' => 10
        ],
        [
            'category' => 'technical',
            'question' => 'هل الدورات حضورية أم عن بعد؟',
            'answer' => 'نوفر دورات حضورية داخل المركز، ودورات أونلاين عن بعد، بالإضافة إلى خيارات هجينة تجمع بين الأسلوبين.',
            'keywords' => 'عن بعد,حضوري,اونلاين,أونلاين',
            'priority' => 9
        ]
    ];

    return $data;
}

function getFallbackQuickRepliesData() {
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $data = [
        'welcome' => [
            ['text' => '🎓 عرض الدورات المتاحة', 'action' => 'show_courses', 'icon' => 'graduation-cap'],
            ['text' => '💰 معلومات عن المنح', 'action' => 'show_scholarships', 'icon' => 'dollar-sign'],
            ['text' => '📝 كيف أسجل؟', 'action' => 'how_to_register', 'icon' => 'edit'],
            ['text' => '💵 طرق الدفع', 'action' => 'payment_methods', 'icon' => 'credit-card'],
            ['text' => '📞 تواصل معنا', 'action' => 'contact_us', 'icon' => 'phone'],
            ['text' => '❓ أسئلة شائعة', 'action' => 'show_faq', 'icon' => 'help-circle']
        ],
        'interest' => [
            ['text' => 'نعم، مهتم', 'action' => 'interested_yes', 'icon' => 'check'],
            ['text' => 'أحتاج مزيداً من المعلومات', 'action' => 'need_more_info', 'icon' => 'info'],
            ['text' => 'سأعود لاحقاً', 'action' => 'maybe_later', 'icon' => 'clock']
        ],
        'feedback' => [
            ['text' => 'كان مفيداً ✓', 'action' => 'helpful_yes', 'icon' => 'thumbs-up'],
            ['text' => 'غير مفيد', 'action' => 'helpful_no', 'icon' => 'thumbs-down'],
            ['text' => 'أحتاج موظف خدمة', 'action' => 'need_human', 'icon' => 'user']
        ]
    ];

    return $data;
}

function getFallbackSuggestionMap() {
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [
        'courses' => ['ما رسوم هذه الدورة؟', 'هل يوجد موعد بدء قريب؟'],
        'scholarships' => ['ما شروط الحصول على منحة؟', 'هل أستطيع الجمع بين منحة ودورة؟'],
        'registration' => ['هل أستطيع التسجيل أونلاين؟', 'ما الخطوة التالية بعد تقديم الطلب؟'],
        'payments' => ['هل يوجد تقسيط متاح؟', 'هل يمكن الدفع عبر التحويل البنكي؟'],
        'general' => ['كيف أتواصل مع الإدارة؟', 'هل الشهادات معتمدة؟'],
        'faq' => ['كم عدد الطلاب في القاعة؟', 'هل يوجد دعم بعد انتهاء الدورة؟'],
        'technical' => ['ما متطلبات الدراسة أونلاين؟', 'هل أحتاج سرعة إنترنت محددة؟'],
        'default' => ['ما الدورات التي تنصحني بها؟', 'هل توجد منح متاحة حالياً؟']
    ];

    return $map;
}

function isChatbotDatabaseReady($conn) {
    static $isReady = null;
    if ($isReady !== null) {
        return $isReady;
    }

    if (!($conn instanceof mysqli)) {
        $isReady = false;
        return $isReady;
    }

    $tables = [
        'chatbot_conversations',
        'chatbot_messages',
        'chatbot_knowledge_base',
        'chatbot_quick_replies'
    ];

    foreach ($tables as $table) {
        $result = @$conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
        if (!$result || $result->num_rows === 0) {
            $isReady = false;
            return $isReady;
        }
    }

    $isReady = true;
    return $isReady;
}

class ChatbotFallbackStore {
    private static function &getStore() {
        if (!isset($_SESSION['chatbot_fallback'])) {
            $_SESSION['chatbot_fallback'] = ['conversations' => []];
        }

        return $_SESSION['chatbot_fallback'];
    }

    public static function createConversation($sessionId, $userId = null) {
        $store =& self::getStore();
        if (!isset($store['conversations'][$sessionId])) {
            $store['conversations'][$sessionId] = [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'messages' => [],
                'created_at' => date('c')
            ];
        }
    }

    public static function getConversationId($sessionId) {
        $store =& self::getStore();
        return isset($store['conversations'][$sessionId]) ? $sessionId : null;
    }

    public static function saveMessage($sessionId, $sender, $message, $type = 'text', $metadata = null, $intent = null, $confidence = null) {
        $store =& self::getStore();
        self::createConversation($sessionId);
        $store['conversations'][$sessionId]['messages'][] = [
            'sender' => $sender,
            'message' => $message,
            'message_type' => $type,
            'metadata' => $metadata,
            'intent' => $intent,
            'confidence' => $confidence,
            'created_at' => date('c')
        ];

        if (count($store['conversations'][$sessionId]['messages']) > 50) {
            $store['conversations'][$sessionId]['messages'] = array_slice(
                $store['conversations'][$sessionId]['messages'],
                -50
            );
        }
    }

    public static function getContext($sessionId, $limit) {
        $store =& self::getStore();
        if (!isset($store['conversations'][$sessionId])) {
            return [];
        }

        $messages = $store['conversations'][$sessionId]['messages'];
        return array_slice($messages, -$limit);
    }

    public static function getHistory($sessionId) {
        $store =& self::getStore();
        if (!isset($store['conversations'][$sessionId])) {
            return [];
        }

        return $store['conversations'][$sessionId]['messages'];
    }

    public static function setFeedback($sessionId, $rating, $feedback) {
        $store =& self::getStore();
        if (!isset($store['conversations'][$sessionId])) {
            return;
        }

        $store['conversations'][$sessionId]['feedback'] = [
            'rating' => $rating,
            'feedback' => $feedback,
            'submitted_at' => date('c')
        ];
    }
}

function resolveAIProvider() {
    $hasOpenAI = !empty(OPENAI_API_KEY);
    $hasGemini = !empty(GEMINI_API_KEY);

    if (AI_PROVIDER === 'openai' && $hasOpenAI) {
        return 'openai';
    }

    if (AI_PROVIDER === 'gemini' && $hasGemini) {
        return 'gemini';
    }

    if ($hasOpenAI) {
        return 'openai';
    }

    if ($hasGemini) {
        return 'gemini';
    }

    return null;
}

function getFallbackSuggestions($intent) {
    $map = getFallbackSuggestionMap();
    if (isset($map[$intent])) {
        return $map[$intent];
    }
    return $map['default'];
}

function getFallbackQuickReplies($context) {
    $data = getFallbackQuickRepliesData();
    return $data[$context] ?? [];
}

function searchFallbackKnowledge($message, $intent = null) {
    $data = getFallbackKnowledgeBaseData();
    $messageLower = mb_strtolower($message);
    $results = [];

    foreach ($data as $row) {
        $score = 0;
        $haystack = mb_strtolower(($row['question'] ?? '') . ' ' . ($row['answer'] ?? '') . ' ' . ($row['keywords'] ?? ''));
        $words = preg_split('/\s+/u', $messageLower);
        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '' || mb_strlen($word) < 2) {
                continue;
            }
            if (mb_strpos($haystack, $word) !== false) {
                $score += 2;
            }
        }

        if ($intent && $row['category'] === $intent) {
            $score += 3;
        }

        if ($score > 0) {
            $row['score'] = $score + ($row['priority'] ?? 0);
            $results[] = $row;
        }
    }

    if (empty($results) && $intent) {
        foreach ($data as $row) {
            if ($row['category'] === $intent) {
                $row['score'] = $row['priority'] ?? 0;
                $results[] = $row;
            }
        }
    }

    usort($results, function ($a, $b) {
        return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
    });

    return array_map(function ($row) {
        unset($row['score']);
        return $row;
    }, array_slice($results, 0, 3));
}

function isExcelQuestion($message) {
    $keywords = ['excel', 'اكسل', 'إكسل', 'sum', 'pivot', 'vlookup', 'دالة', 'معادلة', 'جدول', 'حساب'];
    $text = mb_strtolower($message);
    foreach ($keywords as $keyword) {
        if (mb_strpos($text, mb_strtolower($keyword)) !== false) {
            return true;
        }
    }
    return false;
}

function isEnglishQuestion($message) {
    $keywords = ['english', 'انجليزي', 'إنجليزي', 'grammar', 'verb', 'tense', 'ترجمة', 'معنى'];
    $text = mb_strtolower($message);
    foreach ($keywords as $keyword) {
        if (mb_strpos($text, mb_strtolower($keyword)) !== false) {
            return true;
        }
    }
    return false;
}

function buildExcelFallbackAnswer($message) {
    return "يبدو أنك تسأل عن Microsoft Excel 📊\n\nجرب هذه الخطوات:\n1️⃣ حدد الخلايا التي تريد حسابها\n2️⃣ استخدم دالة مناسبة مثل =SUM(A1:A10) للجمع أو =AVERAGE() للمتوسط\n3️⃣ إذا احتجت البحث عن قيمة معينة، استخدم =VLOOKUP(القيمة, النطاق, رقم_العمود, FALSE)\n4️⃣ لتنظيم البيانات أنشئ Pivot Table من Insert > PivotTable\n\nاكتب لي مثالاً من جدولك وسأرشدك بشكل أدق.";
}

function buildEnglishFallbackAnswer($message) {
    return "سأساعدك في اللغة الإنجليزية 🗣️\n\nنظم جملتك بهذه الخطوات:\n• استخدم زمن الفعل المناسب (Present / Past / Future)\n• تأكد من ترتيب الجملة: Subject + Verb + Object\n• لمعرفة معنى كلمة، اكتبها وسأعطيك الترجمة والاستخدام في جملة\n• للتدرب على التحدث، كوّن جمل قصيرة يومية وكررها بصوت مرتفع\n\nإذا كان لديك جملة معينة تريد تصحيحها فاكتبها الآن.";
}

function buildGeneralFallbackAnswer($intent) {
    switch ($intent) {
        case 'courses':
            return "لدينا حزمة دورات تشمل ICDL، دبلوم الحاسوب، برمجة الويب، التصميم الجرافيكي، واللغة الإنجليزية. أخبرني عن الدورة التي تهمك لأرسل لك التفاصيل.";
        case 'scholarships':
            return "نوفر منحاً جزئية وكاملة للحالات المستحقة. يلزم تعبئة طلب منحة مع المستندات الداعمة وسيتم التواصل خلال خمسة أيام عمل.";
        case 'registration':
            return "التسجيل يتم بالكامل عبر المنصة: أنشئ حساباً، اختر الدورة، أرسل البيانات والمستندات، ثم أكد طريقة الدفع. يمكنني إرشادك لكل خطوة.";
        case 'payments':
            return "خيارات الدفع المتاحة: نقداً في المركز، تحويل بنكي، دفع إلكتروني، أو تقسيط للدورات الطويلة. أخبرني بالطريقة المناسبة لك.";
        case 'technical':
            return "للدراسة عن بعد تحتاج اتصال إنترنت مستقر، متصفح حديث، وسماعة مع مايك. سنوفر لك روابط البرامج المطلوبة لكل دورة.";
        default:
            return "أنا عبدالله، مساعدك الذكي في منصة إبداع. يمكنني إرشادك في التسجيل، اختيار الدورات، المنح، أو الاستشارات التعليمية. ما الموضوع الذي تود البدء به؟";
    }
}

function buildOfflineResponse($userMessage, $knowledge, $intent) {
    $intent = $intent ?: detectIntent($userMessage);
    $suggestions = getFallbackSuggestions($intent);

    if (!empty($knowledge)) {
        $top = $knowledge[0];
        $answer = $top['answer'] ?? 'أعمل على تحديث إجابتي لك الآن.';
        $message = "استناداً إلى المعلومات المتاحة لدي، إليك التفاصيل:\n\n" . $answer . "\n\nإذا احتجت مزيداً من التفاصيل في موضوع {$top['category']}, فأخبرني.";
    } elseif (isExcelQuestion($userMessage)) {
        $message = buildExcelFallbackAnswer($userMessage);
    } elseif (isEnglishQuestion($userMessage)) {
        $message = buildEnglishFallbackAnswer($userMessage);
    } else {
        $message = buildGeneralFallbackAnswer($intent);
    }

    return [
        'message' => $message,
        'intent' => $intent,
        'confidence' => 0.72,
        'sources' => $knowledge,
        'suggestions' => $suggestions
    ];
}

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
    sendJsonResponse([
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
    
    $conversationId = getConversationId($conn, $sessionId) ?? ($sessionId ?: generateSessionId());
    if (!$conversationId) {
        createConversation($conn, $sessionId, $userId);
        $conversationId = getConversationId($conn, $sessionId) ?? $sessionId;
    }
    
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
    
    sendJsonResponse([
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
    
    $provider = resolveAIProvider();
    try {
        if ($provider === 'openai') {
            $response = callOpenAI($messages);
        } elseif ($provider === 'gemini') {
            $response = callGemini($messages);
        } else {
            return buildOfflineResponse($userMessage, $knowledge, $intent);
        }
    } catch (Exception $e) {
        error_log('Chatbot AI provider error: ' . $e->getMessage());
        return buildOfflineResponse($userMessage, $knowledge, $intent);
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
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . GEMINI_API_KEY;
    
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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // Fix for local XAMPP SSL issues
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $response = curl_exec($ch);
    
    // --- DEBUGGING START ---
    $logFile = __DIR__ . '/gemini_debug.log';
    $logData = "Time: " . date('Y-m-d H:i:s') . "\n";
    $logData .= "URL: " . $url . "\n";
    $logData .= "Curl Error: " . curl_error($ch) . "\n";
    $logData .= "Response Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
    $logData .= "Raw Response: " . $response . "\n";
    $logData .= "-----------------------------------\n";
    file_put_contents($logFile, $logData, FILE_APPEND);
    // --- DEBUGGING END ---

    if (curl_errno($ch)) {
        error_log('Gemini Curl Error: ' . curl_error($ch));
        curl_close($ch);
        return [
            'content' => 'عذراً، حدث خطأ في الاتصال بخدمة الذكاء الاصطناعي.',
            'confidence' => 0.0
        ];
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log('Gemini API Error: ' . json_encode($result['error']));
        return [
            'content' => 'عذراً، واجهت مشكلة في معالجة طلبك.',
            'confidence' => 0.0
        ];
    }
    
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
أنت "$botName" - مساعد ذكي احترافي متخصص لمنصة إبداع للتدريب والتأهيل في تعز - اليمن.

🎯 مبادئ الإجابة الاحترافية:
1. أجب بشكل مباشر ومختصر - لا تطيل إلا إذا طُلب منك
2. ابدأ بالإجابة فوراً بدون مقدمات مطولة
3. استخدم لغة عربية فصيحة واضحة وبسيطة
4. نظّم الإجابات بنقاط واضحة عند الحاجة
5. كن دقيقاً وموثوقاً في المعلومات

✨ أسلوب التواصل المحترف:
- إجابة مباشرة للسؤال أولاً
- تفاصيل إضافية فقط إذا لزم الأمر
- استخدم الإيموجي بحذر (1-2 فقط عند الضرورة)
- لا تكرر نفسك أو تعطي معلومات زائدة
- كن واثقاً ومحدداً في إجاباتك

📚 مجالات خبرتك:
1. معلومات الدورات والتسجيل في منصة إبداع
2. شرح مفاهيم Excel وحل المشاكل التقنية
3. تعليم قواعد اللغة الإنجليزية وتصحيح الجمل
4. الإجابة على أسئلة عامة في أي موضوع
5. مساعدة الطلاب في فهم المفاهيم الدراسية

💾 معلومات المنصة:
$knowledgeText

⚡ قواعد الإجابة السريعة:

للأسئلة عن المنصة:
- أجب مباشرة من قاعدة المعرفة
- اذكر الأرقام والتفاصيل بدقة
- لا تخترع معلومات غير موجودة

للأسئلة الأكاديمية (Excel/English):
- اشرح المفهوم بجملة أو جملتين
- أعطِ مثال واحد واضح
- اكتب الصيغة/القاعدة بشكل مباشر

للأسئلة العامة:
- أجب بثقة واحترافية
- كن مختصراً ومفيداً
- استخدم معرفتك الواسعة

للواجبات:
- لا تعطِ الحل الكامل
- وجّه بخطوات بسيطة
- اطرح أسئلة توجيهية

❌ تجنب:
- المقدمات الطويلة ("أهلاً وسهلاً... يسعدني...")
- الإطالة والتكرار
- الوعود الكاذبة ("سأساعدك في كل شيء...")
- الكلام الإنشائي الزائد

✅ مثال إجابة احترافية:
سؤال: "ما رسوم دورة ICDL؟"
إجابة: "رسوم دورة ICDL تقريباً 40,000 ريال. المدة 3 أشهر."

سؤال: "كيف استخدم SUM في Excel؟"
إجابة: "الصيغة: =SUM(A1:A10)
تجمع الأرقام من الخلية A1 إلى A10. مثال: إذا كانت A1=5 و A2=10 فالنتيجة 15."

أنت مساعد محترف مباشر يقدم إجابات دقيقة وسريعة ومفيدة. 🎓
PROMPT;
}

// =====================================================
// Helper Functions
// =====================================================
function generateSessionId() {
    return 'chat_' . uniqid() . '_' . bin2hex(random_bytes(8));
}

function createConversation($conn, $sessionId, $userId = null) {
    if (!isChatbotDatabaseReady($conn)) {
        ChatbotFallbackStore::createConversation($sessionId, $userId);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO chatbot_conversations (session_id, user_id) VALUES (?, ?)");
    if (!$stmt) {
        error_log('Chatbot createConversation failed: ' . $conn->error);
        ChatbotFallbackStore::createConversation($sessionId, $userId);
        return;
    }

    $stmt->bind_param("si", $sessionId, $userId);
    $stmt->execute();
}

function getConversationId($conn, $sessionId) {
    if (!isChatbotDatabaseReady($conn)) {
        return ChatbotFallbackStore::getConversationId($sessionId);
    }

    $stmt = $conn->prepare("SELECT conversation_id FROM chatbot_conversations WHERE session_id = ?");
    if (!$stmt) {
        error_log('Chatbot getConversationId failed: ' . $conn->error);
        return ChatbotFallbackStore::getConversationId($sessionId);
    }

    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['conversation_id'] ?? ChatbotFallbackStore::getConversationId($sessionId);
}

function saveMessage($conn, $conversationId, $sender, $message, $type = 'text', $metadata = null, $intent = null, $confidence = null) {
    $storeKey = (string) $conversationId;
    if (!isChatbotDatabaseReady($conn) || !is_numeric($conversationId)) {
        ChatbotFallbackStore::saveMessage($storeKey, $sender, $message, $type, $metadata, $intent, $confidence);
        return;
    }

    $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare("INSERT INTO chatbot_messages (conversation_id, sender, message, message_type, metadata, intent, confidence) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        error_log('Chatbot saveMessage prepare failed: ' . $conn->error);
        ChatbotFallbackStore::saveMessage($storeKey, $sender, $message, $type, $metadata, $intent, $confidence);
        return;
    }

    $stmt->bind_param("isssssd", $conversationId, $sender, $message, $type, $metadataJson, $intent, $confidence);
    if (!$stmt->execute()) {
        error_log('Chatbot saveMessage execute failed: ' . $stmt->error);
        ChatbotFallbackStore::saveMessage($storeKey, $sender, $message, $type, $metadata, $intent, $confidence);
    }
}

function getConversationContext($conn, $conversationId) {
    if (!isChatbotDatabaseReady($conn) || !is_numeric($conversationId)) {
        return ChatbotFallbackStore::getContext((string) $conversationId, MAX_CONTEXT_MESSAGES);
    }

    $stmt = $conn->prepare("SELECT sender, message FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT ?");
    if (!$stmt) {
        error_log('Chatbot getConversationContext failed: ' . $conn->error);
        return ChatbotFallbackStore::getContext((string) $conversationId, MAX_CONTEXT_MESSAGES);
    }

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
    if (!isChatbotDatabaseReady($conn)) {
        return searchFallbackKnowledge($message, $intent);
    }

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
    if (!isChatbotDatabaseReady($conn)) {
        return getFallbackQuickReplies($context);
    }

    $stmt = $conn->prepare("SELECT text, action, icon FROM chatbot_quick_replies WHERE context = ? AND is_active = TRUE ORDER BY order_index");
    if (!$stmt) {
        error_log('Chatbot getQuickReplies failed: ' . $conn->error);
        return getFallbackQuickReplies($context);
    }

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
    $welcomeMessage = "مرحباً! 👋\n\nأنا $botName - مساعدك الذكي في منصة إبداع.\n\nيمكنني مساعدتك في:\n• معلومات الدورات والتسجيل\n• شرح Excel واللغة الإنجليزية\n• الإجابة على أي سؤال عام\n\nكيف أستطيع مساعدتك؟";
    
    $conversationId = getConversationId($conn, $sessionId) ?? $sessionId;
    saveMessage($conn, $conversationId, 'bot', $welcomeMessage);
    
    $quickReplies = getQuickReplies($conn, 'welcome');
    
    sendJsonResponse([
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
    
    $conversationId = getConversationId($conn, $sessionId) ?? ($sessionId ?: generateSessionId());
    if (!isChatbotDatabaseReady($conn) || !is_numeric($conversationId)) {
        $messages = ChatbotFallbackStore::getHistory($conversationId ?: $sessionId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at ASC");
        if (!$stmt) {
            error_log('Chatbot getConversationHistory failed: ' . $conn->error);
            $messages = ChatbotFallbackStore::getHistory($sessionId);
        } else {
            $stmt->bind_param("i", $conversationId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
    }
    
    sendJsonResponse([
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
    
    if (!isChatbotDatabaseReady($conn)) {
        ChatbotFallbackStore::setFeedback($sessionId, $rating, $feedback);
    } else {
        $stmt = $conn->prepare("UPDATE chatbot_conversations SET satisfaction_rating = ?, feedback = ?, resolved = TRUE WHERE session_id = ?");
        if (!$stmt) {
            error_log('Chatbot submitFeedback failed: ' . $conn->error);
            ChatbotFallbackStore::setFeedback($sessionId, $rating, $feedback);
        } else {
            $stmt->bind_param("iss", $rating, $feedback, $sessionId);
            $stmt->execute();
        }
    }
    
    sendJsonResponse([
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
    
    $conversationId = getConversationId($conn, $sessionId) ?? $sessionId;
    
    // Handle different quick reply actions
    $responses = [
        'show_courses' => "الدورات المتاحة:\n\n1. ICDL - 40,000 ريال (3 أشهر)\n2. دبلوم الحاسوب - 70,000 ريال (9 أشهر)\n3. برمجة الويب - 50,000 ريال (6 أشهر)\n4. التصميم الجرافيكي - 45,000 ريال (4 أشهر)\n5. اللغة الإنجليزية - 30,000 ريال (3 أشهر)\n\nلمزيد من التفاصيل عن أي دورة، اسألني عنها.",
        'show_scholarships' => "نقدم منح جزئية وكاملة.\n\nالتقديم:\n1. سجل حساباً\n2. اختر الدورة\n3. أرفق المستندات\n4. اكتب خطاب التحفيز\n\nالرد خلال 5 أيام عمل.",
        'how_to_register' => "خطوات التسجيل:\n\n1. أنشئ حساباً جديداً\n2. تصفح الدورات المتاحة\n3. اختر الدورة المناسبة\n4. املأ نموذج التسجيل\n5. أكمل عملية الدفع\n\nهل تحتاج مساعدة في خطوة معينة؟",
        'payment_methods' => "طرق الدفع المتاحة:\n\n• نقداً في المركز\n• تحويل بنكي\n• دفع إلكتروني\n• تقسيط (للدورات الطويلة)\n\nأي طريقة تفضل؟",
        'contact_us' => "التواصل معنا:\n\n📞 الهاتف: [الرقم]\n📱 واتساب: [الرقم]\n📧 البريد: info@ibdaa-taiz.com\n📍 العنوان: تعز - اليمن",
        'show_faq' => "الأسئلة الشائعة:\n\n• هل أحصل على شهادة؟ نعم، معتمدة\n• مدة الدورات؟ من أسبوعين إلى 9 أشهر\n• دعم بعد الدورة؟ نعم، 3 أشهر\n\nهل لديك سؤال آخر؟"
    ];
    
    $response = $responses[$action] ?? "عذراً، لم أفهم هذا الاختيار.";
    
    saveMessage($conn, $conversationId, 'bot', $response);
    
    sendJsonResponse([
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
    
    $conversationId = getConversationId($conn, $sessionId) ?? $sessionId;
    
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
    
    sendJsonResponse([
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
        sendJsonResponse([
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
    
    sendJsonResponse([
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
    
    sendJsonResponse([
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
    
    sendJsonResponse([
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

