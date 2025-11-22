<?php
// Load student data for ID card
global $studentHelper, $userId, $userName, $userEmail;
$gpaData = $studentHelper->getGPA();
$courses = $studentHelper->getMyCourses();
$studentId = str_pad($userId, 6, '0', STR_PAD_LEFT);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">البطاقة الجامعية</h2>
            <p class="text-slate-600 mt-1">بطاقة التعريف الرقمية - رقم <?php echo $studentId; ?></p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" 
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold shadow-md">
                <i data-lucide="file-text" class="w-4 h-4 inline"></i>
                تحميل PDF
            </button>
            <button onclick="window.print()" 
                class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-semibold shadow-md">
                <i data-lucide="image" class="w-4 h-4 inline"></i>
                تحميل صورة
            </button>
        </div>
    </div>

    <!-- ID Card Preview - PHP Rendered -->
    <div class="max-w-4xl mx-auto">
        <div id="idCardPreview" class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl overflow-hidden shadow-2xl p-8">
            <div class="bg-white rounded-xl p-8">
                <div class="flex items-center gap-8 mb-6">
                    <div class="w-32 h-32 bg-slate-200 rounded-lg flex items-center justify-center">
                        <i data-lucide="user" class="w-20 h-20 text-slate-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-3xl font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($userName); ?></h3>
                        <p class="text-slate-600 mb-1">رقم الطالب: <span class="font-bold text-amber-600"><?php echo $studentId; ?></span></p>
                        <p class="text-slate-600">المعدل: <span class="font-bold text-emerald-600"><?php echo number_format($gpaData['gpa'], 2); ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <i data-lucide="info" class="w-6 h-6 text-blue-600 flex-shrink-0 mt-1"></i>
            <div>
                <h3 class="font-bold text-blue-900 mb-2">تعليمات استخدام البطاقة</h3>
                <ul class="text-sm text-blue-800 space-y-2">
                    <li>• يجب حمل البطاقة أثناء التواجد في الحرم الجامعي</li>
                    <li>• يمكن استخدام رمز QR للتحقق من صحة البطاقة</li>
                    <li>• في حالة فقدان البطاقة، يجب إبلاغ الإدارة فوراً</li>
                    <li>• يمكنك طباعة البطاقة أو حفظها على هاتفك</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Card Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="font-bold text-slate-800 mb-4">معلومات الطالب</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-slate-600">رقم الطالب:</span>
                    <span class="font-semibold text-slate-800" id="studentNumber">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">الاسم الكامل:</span>
                    <span class="font-semibold text-slate-800" id="fullName">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">التخصص:</span>
                    <span class="font-semibold text-slate-800" id="major">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">المستوى:</span>
                    <span class="font-semibold text-slate-800" id="level">-</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="font-bold text-slate-800 mb-4">معلومات الإصدار</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-slate-600">تاريخ الإصدار:</span>
                    <span class="font-semibold text-slate-800" id="issueDate">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">تاريخ الانتهاء:</span>
                    <span class="font-semibold text-slate-800" id="expiryDate">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">الحالة:</span>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-semibold" id="cardStatus">نشطة</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadIDCard() {
    const response = await StudentFeatures.idCard.getMyIDCard();
    
    if (response.success && response.data) {
        renderIDCard(response.data);
        updateCardDetails(response.data);
    } else {
        // Show sample card
        const sampleData = {
            student_number: 'STD2024001',
            full_name: '<?php echo $_SESSION['user_name']; ?>',
            major: 'علوم الحاسوب',
            level: 'المستوى الثالث',
            photo: 'assets/images/default-avatar.png',
            issue_date: '2024-01-01',
            expiry_date: '2025-12-31',
            qr_code: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        };
        renderIDCard(sampleData);
        updateCardDetails(sampleData);
    }
    
    lucide.createIcons();
}

function renderIDCard(data) {
    const preview = document.getElementById('idCardPreview');
    
    preview.innerHTML = `
        <!-- Card Front -->
        <div class="relative">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-600 to-orange-600 text-white p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">منصة إبداع تعز</h2>
                        <p class="text-amber-100">Ibdaa Taiz Platform</p>
                    </div>
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-10 h-10 text-amber-600"></i>
                    </div>
                </div>
            </div>

            <!-- Card Content -->
            <div class="p-8">
                <div class="flex gap-8">
                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <div class="w-40 h-48 bg-gradient-to-br from-amber-100 to-orange-100 rounded-lg overflow-hidden border-4 border-white shadow-lg">
                            <img src="${data.photo || 'assets/images/default-avatar.png'}" 
                                alt="Student Photo" 
                                class="w-full h-full object-cover"
                                onerror="this.src='assets/images/default-avatar.png'">
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 space-y-4">
                        <div>
                            <label class="text-xs text-slate-500 uppercase tracking-wide">رقم الطالب</label>
                            <p class="text-2xl font-bold text-slate-800">${data.student_number}</p>
                        </div>

                        <div>
                            <label class="text-xs text-slate-500 uppercase tracking-wide">الاسم الكامل</label>
                            <p class="text-xl font-bold text-slate-800">${data.full_name}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-slate-500 uppercase tracking-wide">التخصص</label>
                                <p class="font-semibold text-slate-700">${data.major}</p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 uppercase tracking-wide">المستوى</label>
                                <p class="font-semibold text-slate-700">${data.level}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-slate-500 uppercase tracking-wide">تاريخ الإصدار</label>
                                <p class="text-sm font-semibold text-slate-700">${data.issue_date}</p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 uppercase tracking-wide">تاريخ الانتهاء</label>
                                <p class="text-sm font-semibold text-slate-700">${data.expiry_date}</p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="flex-shrink-0 text-center">
                        <div class="w-32 h-32 bg-white border-2 border-slate-200 rounded-lg p-2 mb-2">
                            <img src="${data.qr_code || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjwvc3ZnPg=='}" 
                                alt="QR Code" 
                                class="w-full h-full">
                        </div>
                        <p class="text-xs text-slate-500">رمز التحقق</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 px-8 py-4 border-t border-slate-200">
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>📞 +967 777 123 456</span>
                    <span>📧 info@ibdaa-taiz.edu.ye</span>
                    <span>🌐 www.ibdaa-taiz.edu.ye</span>
                </div>
            </div>
        </div>
    `;
    
    lucide.createIcons();
}

function updateCardDetails(data) {
    document.getElementById('studentNumber').textContent = data.student_number;
    document.getElementById('fullName').textContent = data.full_name;
    document.getElementById('major').textContent = data.major;
    document.getElementById('level').textContent = data.level;
    document.getElementById('issueDate').textContent = data.issue_date;
    document.getElementById('expiryDate').textContent = data.expiry_date;
}

async function downloadCard(format) {
    const response = await StudentFeatures.idCard.downloadIDCard(format);
    if (response.success) {
        DashboardIntegration.ui.showToast(`تم تحميل البطاقة بصيغة ${format.toUpperCase()}`, 'success');
    } else {
        DashboardIntegration.ui.showToast('فشل تحميل البطاقة', 'error');
    }
}

// Initialize with conditional loading
if (typeof StudentFeatures !== 'undefined') {
    loadIDCard();
} else {
    console.log('Waiting for StudentFeatures to load...');
    setTimeout(() => {
        if (typeof StudentFeatures !== 'undefined') {
            loadIDCard();
        } else {
            console.error('StudentFeatures failed to load');
        }
    }, 1000);
}
</script>
