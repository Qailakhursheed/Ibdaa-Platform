<?php
// Load payment data using StudentHelper
global $studentHelper;
$balance = $studentHelper->getAccountBalance();
$paymentHistory = $studentHelper->getPaymentHistory();

$currentBalance = $balance['balance'] ?? 0;
$totalPaid = array_sum(array_column($paymentHistory, 'amount'));
$remainingAmount = $currentBalance;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">الحالة المالية</h2>
            <p class="text-slate-600 mt-1">متابعة المدفوعات والرسوم الدراسية - <?php echo count($paymentHistory); ?> عملية</p>
        </div>
    </div>

    <!-- Balance Summary - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-amber-600 to-orange-600 rounded-xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="wallet" class="w-10 h-10"></i>
                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-semibold">الرصيد الحالي</span>
            </div>
            <div class="text-4xl font-bold mb-2"><?php echo number_format($currentBalance, 0); ?> ريال</div>
            <p class="text-amber-100 text-sm">إجمالي المبلغ المستحق</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="check-circle" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo number_format($totalPaid, 0); ?> ريال</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">المبلغ المدفوع</p>
            <p class="text-xs text-slate-500 mt-1"><?php echo count($paymentHistory); ?> دفعة</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg <?php echo $remainingAmount > 0 ? 'border-red-300' : 'border-emerald-300'; ?>">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="<?php echo $remainingAmount > 0 ? 'clock' : 'check-circle-2'; ?>" class="w-10 h-10 <?php echo $remainingAmount > 0 ? 'text-red-600' : 'text-emerald-600'; ?>"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo number_format($remainingAmount, 0); ?> ريال</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">المبلغ المتبقي</p>
            <p class="text-xs <?php echo $remainingAmount > 0 ? 'text-red-500' : 'text-emerald-500'; ?> mt-1">
                <?php echo $remainingAmount > 0 ? '⚠ يجب السداد' : '✓ مسدد بالكامل'; ?>
            </p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <button onclick="requestPaymentPlan()" 
            class="bg-white border-2 border-amber-200 rounded-xl p-6 hover:border-amber-400 transition-colors text-right">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">طلب خطة دفع</h3>
                    <p class="text-sm text-slate-600">تقسيط الرسوم على دفعات</p>
                </div>
            </div>
        </button>

        <button onclick="printReceipt()" 
            class="bg-white border-2 border-slate-200 rounded-xl p-6 hover:border-slate-400 transition-colors text-right">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="printer" class="w-6 h-6 text-slate-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">طباعة إيصال</h3>
                    <p class="text-sm text-slate-600">طباعة إيصالات الدفع</p>
                </div>
            </div>
        </button>
    </div>

    <!-- Payment History -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800">سجل المدفوعات</h3>
                <select id="paymentFilter" onchange="filterPayments()" 
                    class="px-4 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="all">جميع المدفوعات</option>
                    <option value="paid">مدفوعة</option>
                    <option value="pending">معلقة</option>
                    <option value="overdue">متأخرة</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">رقم الفاتورة</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">الوصف</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">التاريخ</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">المبلغ</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">الحالة</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="paymentsTable" class="bg-white divide-y divide-slate-200">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-2"></i>
                            <p class="text-slate-500">جاري التحميل...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Outstanding Invoices -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">الفواتير المستحقة</h3>
        <div id="outstandingInvoices" class="space-y-3">
            <div class="text-center py-8">
                <i data-lucide="file-text" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
let allPayments = [];

async function loadPayments() {
    const response = await StudentFeatures.payments.getMyPayments();
    
    if (response.success && response.data) {
        allPayments = response.data;
        updateBalanceSummary();
        renderPayments(allPayments);
        loadOutstandingInvoices();
    } else {
        // Show sample data
        allPayments = generateSamplePayments();
        updateBalanceSummary();
        renderPayments(allPayments);
        loadOutstandingInvoices();
    }
    
    lucide.createIcons();
}

function updateBalanceSummary() {
    const totalPaid = allPayments
        .filter(p => p.status === 'paid')
        .reduce((sum, p) => sum + parseFloat(p.amount), 0);
    
    const totalPending = allPayments
        .filter(p => p.status !== 'paid')
        .reduce((sum, p) => sum + parseFloat(p.amount), 0);
    
    const totalAmount = totalPaid + totalPending;
    
    document.getElementById('currentBalance').textContent = `${totalAmount.toLocaleString()} ريال`;
    document.getElementById('paidAmount').textContent = `${totalPaid.toLocaleString()} ريال`;
    document.getElementById('remainingAmount').textContent = `${totalPending.toLocaleString()} ريال`;
}

function renderPayments(payments) {
    const tbody = document.getElementById('paymentsTable');
    
    if (payments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <i data-lucide="file-x" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                    <p class="text-slate-500">لا توجد مدفوعات</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = payments.map(payment => {
        const statusColors = {
            'paid': 'bg-emerald-100 text-emerald-700',
            'pending': 'bg-amber-100 text-amber-700',
            'overdue': 'bg-red-100 text-red-700'
        };
        const statusText = {
            'paid': 'مدفوعة',
            'pending': 'معلقة',
            'overdue': 'متأخرة'
        };
        
        return `
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-sm font-semibold text-slate-800">#${payment.invoice_number}</td>
                <td class="px-6 py-4 text-sm text-slate-700">${payment.description}</td>
                <td class="px-6 py-4 text-sm text-slate-600">${payment.date}</td>
                <td class="px-6 py-4 text-sm font-bold text-slate-800">${parseFloat(payment.amount).toLocaleString()} ريال</td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${statusColors[payment.status]}">
                        ${statusText[payment.status]}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        ${payment.status === 'paid' ? `
                            <button onclick="downloadReceipt('${payment.invoice_number}')" 
                                class="p-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                        ` : `
                            <button onclick="payInvoice('${payment.invoice_number}')" 
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-xs font-semibold">
                                دفع الآن
                            </button>
                        `}
                        <button onclick="viewPaymentDetails('${payment.invoice_number}')" 
                            class="p-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

function loadOutstandingInvoices() {
    const outstanding = allPayments.filter(p => p.status !== 'paid');
    const container = document.getElementById('outstandingInvoices');
    
    if (outstanding.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="check-circle" class="w-8 h-8 mx-auto text-emerald-400 mb-2"></i>
                <p class="text-emerald-600 font-semibold">لا توجد فواتير مستحقة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = outstanding.map(invoice => {
        const isOverdue = invoice.status === 'overdue';
        return `
            <div class="flex items-center justify-between p-4 ${isOverdue ? 'bg-red-50 border border-red-200' : 'bg-amber-50 border border-amber-200'} rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 ${isOverdue ? 'bg-red-600' : 'bg-amber-600'} text-white rounded-lg flex items-center justify-center">
                        <i data-lucide="${isOverdue ? 'alert-circle' : 'clock'}" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800">${invoice.description}</h4>
                        <p class="text-sm ${isOverdue ? 'text-red-600' : 'text-amber-600'} font-semibold">
                            ${isOverdue ? 'متأخرة' : 'معلقة'} - ${invoice.date}
                        </p>
                    </div>
                </div>
                <div class="text-left">
                    <div class="text-2xl font-bold text-slate-800">${parseFloat(invoice.amount).toLocaleString()} ريال</div>
                    <button onclick="payInvoice('${invoice.invoice_number}')" 
                        class="mt-2 px-4 py-1 ${isOverdue ? 'bg-red-600' : 'bg-amber-600'} text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-semibold">
                        دفع الآن
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

function generateSamplePayments() {
    return [
        { invoice_number: 'INV-2024-001', description: 'رسوم الفصل الدراسي الأول', date: '2024-01-15', amount: '50000', status: 'paid' },
        { invoice_number: 'INV-2024-002', description: 'رسوم المعامل', date: '2024-02-01', amount: '5000', status: 'paid' },
        { invoice_number: 'INV-2024-003', description: 'رسوم الفصل الدراسي الثاني', date: '2024-06-15', amount: '50000', status: 'pending' },
        { invoice_number: 'INV-2024-004', description: 'رسوم الكتب', date: '2024-05-20', amount: '3000', status: 'overdue' }
    ];
}

async function requestPaymentPlan() {
    const response = await StudentFeatures.payments.requestPaymentPlan();
    if (response.success) {
        DashboardIntegration.ui.showToast('تم تقديم طلب خطة الدفع بنجاح', 'success');
    } else {
        DashboardIntegration.ui.showToast('فشل تقديم الطلب', 'error');
    }
}

function printReceipt() {
    window.print();
}

function downloadReceipt(invoiceNumber) {
    DashboardIntegration.ui.showToast(`جاري تحميل إيصال ${invoiceNumber}`, 'info');
}

function payInvoice(invoiceNumber) {
    DashboardIntegration.ui.showToast('سيتم فتح صفحة الدفع قريباً', 'info');
}

function viewPaymentDetails(invoiceNumber) {
    StudentFeatures.ui.showPaymentDetailsModal(invoiceNumber);
}

function filterPayments() {
    const filter = document.getElementById('paymentFilter').value;
    const filtered = filter === 'all' ? allPayments : allPayments.filter(p => p.status === filter);
    renderPayments(filtered);
}

// Initialize with conditional loading
if (typeof StudentFeatures !== 'undefined') {
    loadPayments();
} else {
    console.log('Waiting for StudentFeatures to load...');
    setTimeout(() => {
        if (typeof StudentFeatures !== 'undefined') {
            loadPayments();
        } else {
            console.error('StudentFeatures failed to load');
        }
    }, 1000);
}
</script>
