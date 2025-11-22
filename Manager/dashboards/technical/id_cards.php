<?php
/**
 * Technical Dashboard - ID Cards Management System
 * نظام إدارة البطاقات الشخصية الكامل
 * Features: Create, Display, Download, Email, WhatsApp
 */

// Get ID Cards statistics from users table
$cardsStats = [
    'total_cards' => 0,
    'active_cards' => 0,
    'expired_cards' => 0,
    'pending_cards' => 0
];

try {
    // Total Cards (all users who can have ID cards)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('student', 'trainer')");
    $stmt->execute();
    $result = $stmt->get_result();
    $cardsStats['total_cards'] = $result->fetch_assoc()['total'];
    
    // Active Cards (active users)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('student', 'trainer') AND status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    $cardsStats['active_cards'] = $result->fetch_assoc()['total'];
    
    // Expired Cards (inactive users)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('student', 'trainer') AND status != 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    $cardsStats['expired_cards'] = $result->fetch_assoc()['total'];
    
    // Pending Cards (users without profile photo)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('student', 'trainer') AND (photo IS NULL OR photo = '')");
    $stmt->execute();
    $result = $stmt->get_result();
    $cardsStats['pending_cards'] = $result->fetch_assoc()['total'];
    
} catch(Exception $e) {
    error_log("ID Cards Stats Error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="credit-card" class="w-10 h-10"></i>
                إدارة البطاقات الشخصية
            </h1>
            <p class="text-cyan-100 text-lg">إنشاء وإصدار وإدارة البطاقات الشخصية للطلاب والمدربين</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openCreateCardModal()" class="bg-white text-cyan-600 px-6 py-3 rounded-xl font-bold hover:bg-cyan-50 transition-all flex items-center gap-2 shadow-lg">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                إنشاء بطاقة جديدة
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Cards -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-cyan-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-cyan-100 flex items-center justify-center">
                <i data-lucide="credit-card" class="w-6 h-6 text-cyan-600"></i>
            </div>
            <span class="text-sm font-semibold text-cyan-600 bg-cyan-50 px-3 py-1 rounded-full">الإجمالي</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $cardsStats['total_cards']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي البطاقات</p>
    </div>

    <!-- Active Cards -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">نشطة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $cardsStats['active_cards']; ?></h3>
        <p class="text-slate-500 text-sm">بطاقات نشطة</p>
    </div>

    <!-- Expired Cards -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-sm font-semibold text-red-600 bg-red-50 px-3 py-1 rounded-full">منتهية</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $cardsStats['expired_cards']; ?></h3>
        <p class="text-slate-500 text-sm">بطاقات منتهية</p>
    </div>

    <!-- Pending Cards -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">معلقة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $cardsStats['pending_cards']; ?></h3>
        <p class="text-slate-500 text-sm">بطاقات معلقة</p>
    </div>
</div>

<!-- View Toggle and Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex gap-3">
            <button onclick="switchView('grid')" id="gridViewBtn" class="px-4 py-2 rounded-lg font-bold transition-all bg-cyan-100 text-cyan-700 flex items-center gap-2">
                <i data-lucide="grid" class="w-5 h-5"></i>
                شبكة
            </button>
            <button onclick="switchView('list')" id="listViewBtn" class="px-4 py-2 rounded-lg font-bold transition-all text-slate-600 hover:bg-slate-100 flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i>
                قائمة
            </button>
        </div>
        
        <div class="flex gap-3 flex-wrap">
            <input type="text" id="searchCards" placeholder="بحث بالاسم أو رقم البطاقة..." class="border border-slate-300 rounded-lg px-4 py-2 w-80" oninput="searchCards()">
            <select id="filterCardType" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCards()">
                <option value="all">كل الأنواع</option>
                <option value="student">طالب</option>
                <option value="trainer">مدرب</option>
                <option value="staff">موظف</option>
            </select>
            <select id="filterCardStatus" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCards()">
                <option value="all">كل الحالات</option>
                <option value="active">نشطة</option>
                <option value="expired">منتهية</option>
                <option value="pending">معلقة</option>
            </select>
        </div>
    </div>
</div>

<!-- Cards Grid View -->
<div id="cardsGridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="col-span-full text-center py-12">
        <i data-lucide="loader" class="w-12 h-12 mx-auto mb-3 animate-spin text-cyan-600"></i>
        <p class="text-slate-500 text-lg">جاري تحميل البطاقات...</p>
    </div>
</div>

<!-- Cards List View -->
<div id="cardsListView" class="bg-white rounded-xl shadow-lg p-6 hidden">
    <div id="cardsTableContainer" class="overflow-x-auto">
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 mx-auto mb-3 animate-spin text-cyan-600"></i>
            <p class="text-slate-500 text-lg">جاري تحميل البطاقات...</p>
        </div>
    </div>
</div>

<!-- Create Card Modal -->
<div id="createCardModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-7 h-7"></i>
                    إنشاء بطاقة جديدة
                </h3>
                <button onclick="closeCreateCardModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="createCardForm" class="p-6 space-y-4" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">نوع البطاقة *</label>
                    <select name="card_type" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" onchange="handleCardTypeChange(this.value)">
                        <option value="">اختر النوع</option>
                        <option value="student">بطاقة طالب</option>
                        <option value="trainer">بطاقة مدرب</option>
                        <option value="staff">بطاقة موظف</option>
                    </select>
                </div>
                
                <div id="personSelectContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-2">اختر الشخص *</label>
                    <select name="person_id" id="personSelect" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        <option value="">اختر...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">رقم البطاقة *</label>
                    <input type="text" name="card_number" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="مثال: ID-2025-001">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تاريخ الإصدار *</label>
                    <input type="date" name="issue_date" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تاريخ الانتهاء *</label>
                    <input type="date" name="expiry_date" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الحالة *</label>
                    <select name="status" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        <option value="active">نشطة</option>
                        <option value="pending">معلقة</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">صورة البطاقة (اختياري)</label>
                <input type="file" name="card_image" accept="image/*" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <p class="text-xs text-slate-500 mt-1">صورة شخصية للبطاقة (PNG, JPG - حد أقصى 2MB)</p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="أي ملاحظات إضافية..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-cyan-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-cyan-700 transition-all">
                    إنشاء البطاقة
                </button>
                <button type="button" onclick="closeCreateCardModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Card Modal -->
<div id="viewCardModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="eye" class="w-7 h-7"></i>
                    عرض البطاقة
                </h3>
                <button onclick="closeViewCardModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <div id="cardPreviewContent" class="p-6">
            <!-- Loaded dynamically -->
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div id="sendEmailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="mail" class="w-7 h-7"></i>
                    إرسال بالبريد
                </h3>
                <button onclick="closeSendEmailModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="sendEmailForm" class="p-6 space-y-4">
            <input type="hidden" name="card_id" id="email_card_id">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">البريد الإلكتروني *</label>
                <input type="email" name="email" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="example@email.com">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الموضوع *</label>
                <input type="text" name="subject" required value="بطاقتك الشخصية من مركز إبداع تعز" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الرسالة</label>
                <textarea name="message" rows="4" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="رسالة مخصصة...">مرحباً،

تجد مرفقاً بطاقتك الشخصية من مركز إبداع تعز للتدريب.

نتمنى لك التوفيق.</textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-purple-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    إرسال
                </button>
                <button type="button" onclick="closeSendEmailModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Send WhatsApp Modal -->
<div id="sendWhatsAppModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-7 h-7"></i>
                    إرسال عبر واتساب
                </h3>
                <button onclick="closeSendWhatsAppModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="sendWhatsAppForm" class="p-6 space-y-4">
            <input type="hidden" name="card_id" id="whatsapp_card_id">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">رقم الواتساب *</label>
                <input type="tel" name="phone" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="967xxxxxxxxx" pattern="[0-9]+">
                <p class="text-xs text-slate-500 mt-1">أدخل الرقم بصيغة دولية (مثال: 967777123456)</p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الرسالة</label>
                <textarea name="message" rows="4" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="رسالة مخصصة...">السلام عليكم،

تجد مرفقاً بطاقتك الشخصية من مركز إبداع تعز للتدريب.

للاستفسار يرجى التواصل معنا.</textarea>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-700 text-sm">
                    <i data-lucide="info" class="w-4 h-4 inline-block ml-2"></i>
                    سيتم فتح واتساب مع الرسالة والملف جاهزين للإرسال
                </p>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    فتح واتساب
                </button>
                <button type="button" onclick="closeSendWhatsAppModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentView = 'grid';

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Load cards
    loadCards();
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="issue_date"]').value = today;
    
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    document.querySelector('[name="expiry_date"]').value = nextYear.toISOString().split('T')[0];
});

// Load Cards
async function loadCards(cardType = 'all', status = 'all', searchTerm = '') {
    try {
        let url = '<?php echo $managerBaseUrl; ?>/api/id_cards.php?action=get_all';
        if (cardType !== 'all') url += `&card_type=${cardType}`;
        if (status !== 'all') url += `&status=${status}`;
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            if (currentView === 'grid') {
                displayCardsGrid(data.data);
            } else {
                displayCardsList(data.data);
            }
        } else {
            const emptyMessage = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="credit-card" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                    <p class="text-slate-500 text-lg">لا توجد بطاقات</p>
                </div>
            `;
            if (currentView === 'grid') {
                document.getElementById('cardsGridView').innerHTML = emptyMessage;
            } else {
                document.getElementById('cardsTableContainer').innerHTML = emptyMessage;
            }
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading cards:', error);
        const errorMessage = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
        if (currentView === 'grid') {
            document.getElementById('cardsGridView').innerHTML = errorMessage;
        } else {
            document.getElementById('cardsTableContainer').innerHTML = errorMessage;
        }
    }
}

// Display Cards as Grid
function displayCardsGrid(cards) {
    const container = document.getElementById('cardsGridView');
    container.innerHTML = cards.map(card => `
        <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all overflow-hidden border-2 ${getBorderColor(card.status)}">
            <!-- Card Preview -->
            <div class="relative bg-gradient-to-br ${getGradientColor(card.card_type)} p-6 text-white">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs opacity-75 mb-1">${getCardTypeLabel(card.card_type)}</p>
                        <h4 class="text-lg font-bold">${card.full_name}</h4>
                    </div>
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center">
                        <i data-lucide="${getCardTypeIcon(card.card_type)}" class="w-8 h-8"></i>
                    </div>
                </div>
                <p class="text-sm opacity-90 mb-2">${card.card_number}</p>
                <div class="flex items-center justify-between text-xs">
                    <span>صالحة حتى: ${formatDate(card.expiry_date)}</span>
                    <span class="px-2 py-1 rounded-full bg-white/20">${getStatusLabel(card.status)}</span>
                </div>
            </div>
            
            <!-- Card Actions -->
            <div class="p-4 bg-slate-50">
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="viewCard(${card.card_id})" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        عرض
                    </button>
                    <button onclick="downloadCard(${card.card_id})" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        تحميل
                    </button>
                    <button onclick="openSendEmailModal(${card.card_id}, '${card.email}')" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        بريد
                    </button>
                    <button onclick="openSendWhatsAppModal(${card.card_id}, '${card.phone}')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        واتساب
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Display Cards as List
function displayCardsList(cards) {
    const container = document.getElementById('cardsTableContainer');
    container.innerHTML = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b-2 border-slate-200">
                <tr>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">الاسم</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">رقم البطاقة</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">النوع</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">تاريخ الإصدار</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">تاريخ الانتهاء</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">الحالة</th>
                    <th class="text-center py-4 px-6 font-bold text-slate-700">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                ${cards.map(card => `
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-semibold text-slate-800">${card.full_name}</div>
                            <div class="text-sm text-slate-500">${card.email}</div>
                        </td>
                        <td class="py-4 px-6 font-mono text-cyan-600 font-semibold">${card.card_number}</td>
                        <td class="py-4 px-6">
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${getTypeClass(card.card_type)}">
                                ${getCardTypeLabel(card.card_type)}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-slate-600">${formatDate(card.issue_date)}</td>
                        <td class="py-4 px-6 text-slate-600">${formatDate(card.expiry_date)}</td>
                        <td class="py-4 px-6">
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${getStatusClass(card.status)}">
                                ${getStatusLabel(card.status)}
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="viewCard(${card.card_id})" class="text-blue-600 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-lg transition-all" title="عرض">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                                <button onclick="downloadCard(${card.card_id})" class="text-emerald-600 hover:text-emerald-700 p-2 hover:bg-emerald-50 rounded-lg transition-all" title="تحميل">
                                    <i data-lucide="download" class="w-5 h-5"></i>
                                </button>
                                <button onclick="openSendEmailModal(${card.card_id}, '${card.email}')" class="text-purple-600 hover:text-purple-700 p-2 hover:bg-purple-50 rounded-lg transition-all" title="بريد">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </button>
                                <button onclick="openSendWhatsAppModal(${card.card_id}, '${card.phone}')" class="text-green-600 hover:text-green-700 p-2 hover:bg-green-50 rounded-lg transition-all" title="واتساب">
                                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                                </button>
                                <button onclick="deleteCard(${card.card_id})" class="text-red-600 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-all" title="حذف">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Switch View
function switchView(view) {
    currentView = view;
    
    if (view === 'grid') {
        document.getElementById('cardsGridView').classList.remove('hidden');
        document.getElementById('cardsListView').classList.add('hidden');
        document.getElementById('gridViewBtn').classList.add('bg-cyan-100', 'text-cyan-700');
        document.getElementById('gridViewBtn').classList.remove('text-slate-600', 'hover:bg-slate-100');
        document.getElementById('listViewBtn').classList.remove('bg-cyan-100', 'text-cyan-700');
        document.getElementById('listViewBtn').classList.add('text-slate-600', 'hover:bg-slate-100');
    } else {
        document.getElementById('cardsGridView').classList.add('hidden');
        document.getElementById('cardsListView').classList.remove('hidden');
        document.getElementById('listViewBtn').classList.add('bg-cyan-100', 'text-cyan-700');
        document.getElementById('listViewBtn').classList.remove('text-slate-600', 'hover:bg-slate-100');
        document.getElementById('gridViewBtn').classList.remove('bg-cyan-100', 'text-cyan-700');
        document.getElementById('gridViewBtn').classList.add('text-slate-600', 'hover:bg-slate-100');
    }
    
    loadCards();
    lucide.createIcons();
}

// Filter Cards
function filterCards() {
    const cardType = document.getElementById('filterCardType').value;
    const status = document.getElementById('filterCardStatus').value;
    const searchTerm = document.getElementById('searchCards').value;
    loadCards(cardType, status, searchTerm);
}

function searchCards() {
    filterCards();
}

// Handle Card Type Change
async function handleCardTypeChange(cardType) {
    const container = document.getElementById('personSelectContainer');
    const select = document.getElementById('personSelect');
    
    if (!cardType) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    select.innerHTML = '<option value="">جاري التحميل...</option>';
    
    try {
        let apiUrl = '';
        if (cardType === 'student') {
            apiUrl = '<?php echo $managerBaseUrl; ?>/api/students.php?action=get_all';
        } else if (cardType === 'trainer') {
            apiUrl = '<?php echo $managerBaseUrl; ?>/api/trainers.php?action=get_all';
        } else if (cardType === 'staff') {
            apiUrl = '<?php echo $managerBaseUrl; ?>/api/staff.php?action=get_all';
        }
        
        const response = await fetch(apiUrl);
        const data = await response.json();
        
        select.innerHTML = '<option value="">اختر...</option>';
        if (data.success && data.data) {
            data.data.forEach(person => {
                const option = document.createElement('option');
                option.value = person.student_id || person.trainer_id || person.staff_id;
                option.textContent = person.full_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading persons:', error);
        select.innerHTML = '<option value="">فشل التحميل</option>';
    }
}

// Modal Functions
function openCreateCardModal() {
    document.getElementById('createCardModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeCreateCardModal() {
    document.getElementById('createCardModal').classList.add('hidden');
    document.getElementById('createCardForm').reset();
    document.getElementById('personSelectContainer').classList.add('hidden');
}

async function viewCard(cardId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/id_cards.php?action=get_details&card_id=${cardId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const card = data.data;
            document.getElementById('cardPreviewContent').innerHTML = `
                <div class="space-y-6">
                    <!-- Card Preview (Large) -->
                    <div class="relative bg-gradient-to-br ${getGradientColor(card.card_type)} rounded-2xl p-8 text-white shadow-2xl">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <p class="text-sm opacity-75 mb-2">مركز إبداع تعز للتدريب</p>
                                <h3 class="text-3xl font-bold">${card.full_name}</h3>
                                <p class="text-lg opacity-90 mt-2">${getCardTypeLabel(card.card_type)}</p>
                            </div>
                            <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center">
                                <i data-lucide="${getCardTypeIcon(card.card_type)}" class="w-12 h-12"></i>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <p class="text-sm opacity-90">رقم البطاقة: <span class="font-bold">${card.card_number}</span></p>
                            <p class="text-sm opacity-90">تاريخ الإصدار: <span class="font-bold">${formatDate(card.issue_date)}</span></p>
                            <p class="text-sm opacity-90">صالحة حتى: <span class="font-bold">${formatDate(card.expiry_date)}</span></p>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="text-xs opacity-75">
                                <p>${card.email}</p>
                                <p>${card.phone}</p>
                            </div>
                            <span class="px-4 py-2 rounded-full bg-white/30 font-bold">${getStatusLabel(card.status)}</span>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="downloadCard(${card.card_id})" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-5 h-5"></i>
                            تحميل البطاقة (PDF)
                        </button>
                        <button onclick="printCard(${card.card_id})" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                            طباعة
                        </button>
                        <button onclick="closeViewCardModal(); openSendEmailModal(${card.card_id}, '${card.email}');" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                            إرسال بالبريد
                        </button>
                        <button onclick="closeViewCardModal(); openSendWhatsAppModal(${card.card_id}, '${card.phone}');" class="px-6 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                            إرسال بالواتساب
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('viewCardModal').classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading card details:', error);
        alert('فشل تحميل تفاصيل البطاقة');
    }
}

function closeViewCardModal() {
    document.getElementById('viewCardModal').classList.add('hidden');
}

function openSendEmailModal(cardId, email) {
    document.getElementById('email_card_id').value = cardId;
    document.querySelector('#sendEmailForm [name="email"]').value = email || '';
    document.getElementById('sendEmailModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeSendEmailModal() {
    document.getElementById('sendEmailModal').classList.add('hidden');
    document.getElementById('sendEmailForm').reset();
}

function openSendWhatsAppModal(cardId, phone) {
    document.getElementById('whatsapp_card_id').value = cardId;
    document.querySelector('#sendWhatsAppForm [name="phone"]').value = phone || '';
    document.getElementById('sendWhatsAppModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeSendWhatsAppModal() {
    document.getElementById('sendWhatsAppModal').classList.add('hidden');
    document.getElementById('sendWhatsAppForm').reset();
}

// Form Submissions
document.getElementById('createCardForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_card');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/id_cards.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم إنشاء البطاقة بنجاح');
            closeCreateCardModal();
            loadCards();
            location.reload();
        } else {
            alert('فشل إنشاء البطاقة: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إنشاء البطاقة');
    }
});

document.getElementById('sendEmailForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'send_card_email');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/id_cards.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم إرسال البطاقة بالبريد الإلكتروني بنجاح');
            closeSendEmailModal();
        } else {
            alert('فشل الإرسال: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إرسال البريد');
    }
});

document.getElementById('sendWhatsAppForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const cardId = document.getElementById('whatsapp_card_id').value;
    const phone = this.querySelector('[name="phone"]').value;
    const message = this.querySelector('[name="message"]').value;
    
    // Generate WhatsApp link with card download
    const downloadUrl = `${window.location.origin}/Manager/api/id_cards.php?action=download&card_id=${cardId}`;
    const whatsappMessage = encodeURIComponent(`${message}\n\nرابط تحميل البطاقة:\n${downloadUrl}`);
    const whatsappUrl = `https://wa.me/${phone}?text=${whatsappMessage}`;
    
    // Open WhatsApp
    window.open(whatsappUrl, '_blank');
    
    closeSendWhatsAppModal();
});

// Download Card
function downloadCard(cardId) {
    window.open(`<?php echo $managerBaseUrl; ?>/api/id_cards.php?action=download&card_id=${cardId}`, '_blank');
}

// Print Card
function printCard(cardId) {
    window.open(`<?php echo $managerBaseUrl; ?>/api/id_cards.php?action=print&card_id=${cardId}`, '_blank');
}

// Delete Card
async function deleteCard(cardId) {
    if (!confirm('هل أنت متأكد من حذف هذه البطاقة؟')) return;
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/id_cards.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete_card&card_id=${cardId}`
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم حذف البطاقة بنجاح');
            loadCards();
        } else {
            alert('فشل الحذف: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء الحذف');
    }
}

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG');
}

function getCardTypeLabel(type) {
    const types = {
        'student': 'بطاقة طالب',
        'trainer': 'بطاقة مدرب',
        'staff': 'بطاقة موظف'
    };
    return types[type] || type;
}

function getCardTypeIcon(type) {
    const icons = {
        'student': 'graduation-cap',
        'trainer': 'users',
        'staff': 'briefcase'
    };
    return icons[type] || 'credit-card';
}

function getStatusLabel(status) {
    const statuses = {
        'active': 'نشطة',
        'expired': 'منتهية',
        'pending': 'معلقة'
    };
    return statuses[status] || status;
}

function getStatusClass(status) {
    const classes = {
        'active': 'bg-emerald-100 text-emerald-700',
        'expired': 'bg-red-100 text-red-700',
        'pending': 'bg-amber-100 text-amber-700'
    };
    return classes[status] || '';
}

function getTypeClass(type) {
    const classes = {
        'student': 'bg-blue-100 text-blue-700',
        'trainer': 'bg-purple-100 text-purple-700',
        'staff': 'bg-slate-100 text-slate-700'
    };
    return classes[type] || '';
}

function getBorderColor(status) {
    const colors = {
        'active': 'border-emerald-500',
        'expired': 'border-red-500',
        'pending': 'border-amber-500'
    };
    return colors[status] || 'border-slate-300';
}

function getGradientColor(type) {
    const gradients = {
        'student': 'from-blue-500 to-cyan-600',
        'trainer': 'from-purple-500 to-indigo-600',
        'staff': 'from-slate-600 to-slate-700'
    };
    return gradients[type] || 'from-slate-500 to-slate-600';
}
</script>
