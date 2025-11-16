<?php
/**
 * Technical Dashboard - Financial Management System
 * نظام الإدارة المالية للمشرف الفني
 */

// Get financial statistics
$financialStats = [
    'total_revenue' => 0,
    'monthly_revenue' => 0,
    'pending_payments' => 0,
    'total_expenses' => 0
];

try {
    // Total Revenue
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $financialStats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Monthly Revenue
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'completed' 
        AND MONTH(payment_date) = MONTH(CURRENT_DATE())
        AND YEAR(payment_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $financialStats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Payments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM payments 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $financialStats['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Expenses
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE YEAR(expense_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $financialStats['total_expenses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    error_log("Financial Stats Error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="wallet" class="w-10 h-10"></i>
                الإدارة المالية
            </h1>
            <p class="text-emerald-100 text-lg">إدارة شاملة للمعاملات المالية والفواتير</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openAddPaymentModal()" class="bg-white text-emerald-600 px-6 py-3 rounded-xl font-bold hover:bg-emerald-50 transition-all flex items-center gap-2 shadow-lg">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                تسجيل دفعة جديدة
            </button>
            <button onclick="openAddExpenseModal()" class="bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center gap-2 shadow-lg border-2 border-white">
                <i data-lucide="minus-circle" class="w-5 h-5"></i>
                تسجيل مصروف
            </button>
        </div>
    </div>
</div>

<!-- Financial Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">الإيرادات</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1">
            <?php echo number_format($financialStats['total_revenue'], 0); ?>
        </h3>
        <p class="text-slate-500 text-sm">إجمالي الإيرادات</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-emerald-600 font-semibold">ريال يمني</p>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">الشهر الحالي</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1">
            <?php echo number_format($financialStats['monthly_revenue'], 0); ?>
        </h3>
        <p class="text-slate-500 text-sm">إيرادات هذا الشهر</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-blue-600 font-semibold">ريال يمني</p>
        </div>
    </div>

    <!-- Pending Payments -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">معلقة</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1">
            <?php echo $financialStats['pending_payments']; ?>
        </h3>
        <p class="text-slate-500 text-sm">دفعات معلقة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <button onclick="showPendingPayments()" class="text-sm text-amber-600 hover:text-amber-700 font-semibold">عرض الكل</button>
        </div>
    </div>

    <!-- Total Expenses -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                <i data-lucide="trending-down" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-sm font-semibold text-red-600 bg-red-50 px-3 py-1 rounded-full">المصروفات</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1">
            <?php echo number_format($financialStats['total_expenses'], 0); ?>
        </h3>
        <p class="text-slate-500 text-sm">إجمالي المصروفات</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-red-600 font-semibold">ريال يمني</p>
        </div>
    </div>
</div>

<!-- Financial Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Revenue vs Expenses Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="w-5 h-5 text-emerald-600"></i>
                الإيرادات مقابل المصروفات
            </h3>
            <select id="revenueExpensePeriod" class="border border-slate-300 rounded-lg px-3 py-2 text-sm" onchange="updateRevenueExpenseChart()">
                <option value="6months">آخر 6 أشهر</option>
                <option value="year">السنة الحالية</option>
                <option value="all">كل الفترات</option>
            </select>
        </div>
        <canvas id="revenueExpenseChart" height="300"></canvas>
    </div>

    <!-- Payment Methods Distribution -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-5 h-5 text-blue-600"></i>
            توزيع طرق الدفع
        </h3>
        <canvas id="paymentMethodsChart" height="300"></canvas>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="bg-white rounded-xl shadow-lg mb-8">
    <div class="border-b border-slate-200">
        <div class="flex gap-2 p-4">
            <button onclick="switchTab('payments')" id="paymentsTab" class="px-6 py-3 rounded-lg font-bold transition-all bg-emerald-100 text-emerald-700">
                <i data-lucide="credit-card" class="w-5 h-5 inline-block ml-2"></i>
                الدفعات
            </button>
            <button onclick="switchTab('expenses')" id="expensesTab" class="px-6 py-3 rounded-lg font-bold transition-all text-slate-600 hover:bg-slate-100">
                <i data-lucide="receipt" class="w-5 h-5 inline-block ml-2"></i>
                المصروفات
            </button>
            <button onclick="switchTab('invoices')" id="invoicesTab" class="px-6 py-3 rounded-lg font-bold transition-all text-slate-600 hover:bg-slate-100">
                <i data-lucide="file-text" class="w-5 h-5 inline-block ml-2"></i>
                الفواتير
            </button>
            <button onclick="switchTab('reports')" id="reportsTab" class="px-6 py-3 rounded-lg font-bold transition-all text-slate-600 hover:bg-slate-100">
                <i data-lucide="bar-chart" class="w-5 h-5 inline-block ml-2"></i>
                التقارير
            </button>
        </div>
    </div>
</div>

<!-- Tab Contents -->
<div id="tabContents">
    <!-- Payments Tab -->
    <div id="paymentsContent" class="tab-content">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">جميع الدفعات</h3>
                <div class="flex gap-3">
                    <input type="text" id="searchPayments" placeholder="بحث..." class="border border-slate-300 rounded-lg px-4 py-2">
                    <select id="filterPaymentStatus" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterPayments()">
                        <option value="all">كل الحالات</option>
                        <option value="completed">مكتملة</option>
                        <option value="pending">معلقة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
            </div>
            
            <div id="paymentsTableContainer" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin text-emerald-600"></i>
                    <p class="text-slate-500">جاري تحميل الدفعات...</p>
                </div>
            </div>
            
            <!-- Pagination -->
            <div id="paymentsPagination" class="flex justify-center gap-2 mt-6"></div>
        </div>
    </div>

    <!-- Expenses Tab -->
    <div id="expensesContent" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">جميع المصروفات</h3>
                <div class="flex gap-3">
                    <input type="text" id="searchExpenses" placeholder="بحث..." class="border border-slate-300 rounded-lg px-4 py-2">
                    <select id="filterExpenseCategory" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterExpenses()">
                        <option value="all">كل الفئات</option>
                        <option value="salaries">رواتب</option>
                        <option value="utilities">خدمات</option>
                        <option value="equipment">معدات</option>
                        <option value="marketing">تسويق</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
            </div>
            
            <div id="expensesTableContainer" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin text-red-600"></i>
                    <p class="text-slate-500">جاري تحميل المصروفات...</p>
                </div>
            </div>
            
            <!-- Pagination -->
            <div id="expensesPagination" class="flex justify-center gap-2 mt-6"></div>
        </div>
    </div>

    <!-- Invoices Tab -->
    <div id="invoicesContent" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">الفواتير</h3>
                <button onclick="openGenerateInvoiceModal()" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center gap-2">
                    <i data-lucide="file-plus" class="w-5 h-5"></i>
                    إنشاء فاتورة جديدة
                </button>
            </div>
            
            <div id="invoicesTableContainer" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin text-blue-600"></i>
                    <p class="text-slate-500">جاري تحميل الفواتير...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Tab -->
    <div id="reportsContent" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Financial Summary Report -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i data-lucide="file-bar-chart" class="w-5 h-5 text-emerald-600"></i>
                    الملخص المالي
                </h3>
                <div id="financialSummaryReport" class="space-y-4">
                    <div class="text-center py-8">
                        <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin text-emerald-600"></i>
                        <p class="text-slate-500">جاري التحميل...</p>
                    </div>
                </div>
                <button onclick="downloadFinancialReport()" class="w-full mt-6 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-5 h-5"></i>
                    تحميل التقرير (PDF)
                </button>
            </div>

            <!-- Monthly Comparison -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-5 h-5 text-blue-600"></i>
                    المقارنة الشهرية
                </h3>
                <canvas id="monthlyComparisonChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div id="addPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-7 h-7"></i>
                    تسجيل دفعة جديدة
                </h3>
                <button onclick="closeAddPaymentModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="addPaymentForm" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">اسم الطالب *</label>
                    <select name="student_id" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">اختر الطالب</option>
                        <!-- Options loaded dynamically -->
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الدورة *</label>
                    <select name="course_id" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">اختر الدورة</option>
                        <!-- Options loaded dynamically -->
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">المبلغ (ريال) *</label>
                    <input type="number" name="amount" required min="0" step="0.01" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">طريقة الدفع *</label>
                    <select name="payment_method" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="cash">نقدي</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="credit_card">بطاقة ائتمان</option>
                        <option value="mobile_wallet">محفظة إلكترونية</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تاريخ الدفع *</label>
                    <input type="date" name="payment_date" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الحالة *</label>
                    <select name="status" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="completed">مكتملة</option>
                        <option value="pending">معلقة</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">رقم المرجع</label>
                <input type="text" name="reference_number" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="اختياري">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="أي ملاحظات إضافية..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all">
                    حفظ الدفعة
                </button>
                <button type="button" onclick="closeAddPaymentModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-7 h-7"></i>
                    تسجيل مصروف جديد
                </h3>
                <button onclick="closeAddExpenseModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="addExpenseForm" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الفئة *</label>
                    <select name="category" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">اختر الفئة</option>
                        <option value="salaries">رواتب</option>
                        <option value="utilities">خدمات (كهرباء، ماء، إنترنت)</option>
                        <option value="equipment">معدات وأدوات</option>
                        <option value="marketing">تسويق وإعلان</option>
                        <option value="maintenance">صيانة</option>
                        <option value="supplies">مستلزمات</option>
                        <option value="rent">إيجار</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">المبلغ (ريال) *</label>
                    <input type="number" name="amount" required min="0" step="0.01" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تاريخ المصروف *</label>
                    <input type="date" name="expense_date" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">طريقة الدفع *</label>
                    <select name="payment_method" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="cash">نقدي</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="credit_card">بطاقة ائتمان</option>
                        <option value="check">شيك</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الوصف *</label>
                <input type="text" name="description" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="وصف المصروف">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="أي ملاحظات إضافية..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition-all">
                    حفظ المصروف
                </button>
                <button type="button" onclick="closeAddExpenseModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Initialize charts
    initializeFinancialCharts();
    
    // Load payments
    loadPayments();
    
    // Load students and courses for payment form
    loadFormData();
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('[name="payment_date"]').value = today;
    document.querySelector('[name="expense_date"]').value = today;
});

// Tab Switching
function switchTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Reset all tab buttons
    document.querySelectorAll('[id$="Tab"]').forEach(btn => {
        btn.classList.remove('bg-emerald-100', 'text-emerald-700');
        btn.classList.add('text-slate-600', 'hover:bg-slate-100');
    });
    
    // Show selected content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Highlight selected tab
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('bg-emerald-100', 'text-emerald-700');
    activeTab.classList.remove('text-slate-600', 'hover:bg-slate-100');
    
    // Load content based on tab
    if (tabName === 'payments') {
        loadPayments();
    } else if (tabName === 'expenses') {
        loadExpenses();
    } else if (tabName === 'invoices') {
        loadInvoices();
    } else if (tabName === 'reports') {
        loadReports();
    }
    
    lucide.createIcons();
}

// Initialize Charts
function initializeFinancialCharts() {
    // Revenue vs Expense Chart
    const revenueExpenseCtx = document.getElementById('revenueExpenseChart');
    if (revenueExpenseCtx) {
        window.revenueExpenseChart = new Chart(revenueExpenseCtx, {
            type: 'line',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                datasets: [
                    {
                        label: 'الإيرادات',
                        data: [120000, 150000, 180000, 160000, 200000, 220000],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'المصروفات',
                        data: [80000, 90000, 95000, 100000, 110000, 105000],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Cairo', size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                }
            }
        });
    }
    
    // Payment Methods Chart
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart');
    if (paymentMethodsCtx) {
        new Chart(paymentMethodsCtx, {
            type: 'doughnut',
            data: {
                labels: ['نقدي', 'تحويل بنكي', 'بطاقة ائتمان', 'محفظة إلكترونية'],
                datasets: [{
                    data: [45, 30, 15, 10],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Cairo', size: 12 } }
                    }
                }
            }
        });
    }
}

// Load Payments
async function loadPayments() {
    try {
        const response = await fetch('../../api/financial.php?action=get_payments');
        const data = await response.json();
        
        const container = document.getElementById('paymentsTableContainer');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = `
                <table class="w-full">
                    <thead class="bg-slate-50 border-b-2 border-slate-200">
                        <tr>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الطالب</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الدورة</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">المبلغ</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">طريقة الدفع</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">التاريخ</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الحالة</th>
                            <th class="text-center py-4 px-6 font-bold text-slate-700">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.data.map(payment => `
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-4 px-6">${payment.student_name}</td>
                                <td class="py-4 px-6">${payment.course_title}</td>
                                <td class="py-4 px-6 font-bold text-emerald-600">${number_format(payment.amount)} ريال</td>
                                <td class="py-4 px-6">${getPaymentMethodLabel(payment.payment_method)}</td>
                                <td class="py-4 px-6">${formatDate(payment.payment_date)}</td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold ${getStatusClass(payment.status)}">
                                        ${getStatusLabel(payment.status)}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <button onclick="viewPaymentDetails(${payment.payment_id})" class="text-blue-600 hover:text-blue-700 mx-1">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                    <button onclick="printReceipt(${payment.payment_id})" class="text-emerald-600 hover:text-emerald-700 mx-1">
                                        <i data-lucide="printer" class="w-5 h-5"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا توجد دفعات</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading payments:', error);
        document.getElementById('paymentsTableContainer').innerHTML = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
    }
}

// Load Expenses
async function loadExpenses() {
    try {
        const response = await fetch('../../api/financial.php?action=get_expenses');
        const data = await response.json();
        
        const container = document.getElementById('expensesTableContainer');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = `
                <table class="w-full">
                    <thead class="bg-slate-50 border-b-2 border-slate-200">
                        <tr>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الوصف</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الفئة</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">المبلغ</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">التاريخ</th>
                            <th class="text-center py-4 px-6 font-bold text-slate-700">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.data.map(expense => `
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-4 px-6">${expense.description}</td>
                                <td class="py-4 px-6">${getCategoryLabel(expense.category)}</td>
                                <td class="py-4 px-6 font-bold text-red-600">${number_format(expense.amount)} ريال</td>
                                <td class="py-4 px-6">${formatDate(expense.expense_date)}</td>
                                <td class="py-4 px-6 text-center">
                                    <button onclick="viewExpenseDetails(${expense.expense_id})" class="text-blue-600 hover:text-blue-700 mx-1">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                    <button onclick="deleteExpense(${expense.expense_id})" class="text-red-600 hover:text-red-700 mx-1">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا توجد مصروفات</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading expenses:', error);
        document.getElementById('expensesTableContainer').innerHTML = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
    }
}

// Modal Functions
function openAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.add('hidden');
    document.getElementById('addPaymentForm').reset();
}

function openAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.add('hidden');
    document.getElementById('addExpenseForm').reset();
}

// Form Submissions
document.getElementById('addPaymentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_payment');
    
    try {
        const response = await fetch('../../api/financial.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم تسجيل الدفعة بنجاح');
            closeAddPaymentModal();
            loadPayments();
            location.reload(); // Reload to update statistics
        } else {
            alert('فشل تسجيل الدفعة: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تسجيل الدفعة');
    }
});

document.getElementById('addExpenseForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_expense');
    
    try {
        const response = await fetch('../../api/financial.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم تسجيل المصروف بنجاح');
            closeAddExpenseModal();
            switchTab('expenses');
            location.reload(); // Reload to update statistics
        } else {
            alert('فشل تسجيل المصروف: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تسجيل المصروف');
    }
});

// Helper Functions
function number_format(num) {
    return new Intl.NumberFormat('ar-EG').format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG');
}

function getPaymentMethodLabel(method) {
    const methods = {
        'cash': 'نقدي',
        'bank_transfer': 'تحويل بنكي',
        'credit_card': 'بطاقة ائتمان',
        'mobile_wallet': 'محفظة إلكترونية'
    };
    return methods[method] || method;
}

function getCategoryLabel(category) {
    const categories = {
        'salaries': 'رواتب',
        'utilities': 'خدمات',
        'equipment': 'معدات',
        'marketing': 'تسويق',
        'maintenance': 'صيانة',
        'supplies': 'مستلزمات',
        'rent': 'إيجار',
        'other': 'أخرى'
    };
    return categories[category] || category;
}

function getStatusLabel(status) {
    const statuses = {
        'completed': 'مكتملة',
        'pending': 'معلقة',
        'cancelled': 'ملغاة'
    };
    return statuses[status] || status;
}

function getStatusClass(status) {
    const classes = {
        'completed': 'bg-emerald-100 text-emerald-700',
        'pending': 'bg-amber-100 text-amber-700',
        'cancelled': 'bg-red-100 text-red-700'
    };
    return classes[status] || '';
}

// Load form data (students and courses)
async function loadFormData() {
    try {
        const [studentsResponse, coursesResponse] = await Promise.all([
            fetch('../../api/students.php?action=get_all'),
            fetch('../../api/courses.php?action=get_all')
        ]);
        
        const studentsData = await studentsResponse.json();
        const coursesData = await coursesResponse.json();
        
        // Populate students dropdown
        const studentSelect = document.querySelector('[name="student_id"]');
        if (studentsData.success && studentsData.data) {
            studentsData.data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.student_id;
                option.textContent = student.full_name;
                studentSelect.appendChild(option);
            });
        }
        
        // Populate courses dropdown
        const courseSelect = document.querySelector('[name="course_id"]');
        if (coursesData.success && coursesData.data) {
            coursesData.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.title;
                courseSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading form data:', error);
    }
}
</script>
