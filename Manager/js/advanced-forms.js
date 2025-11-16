/**
 * نظام إدارة النماذج المتقدم - Advanced Forms Management
 * Ibdaa Training Platform
 * 
 * الميزات:
 * - نماذج Bootstrap حديثة مع تحقق متقدم
 * - عرض البيانات السابقة عند التعديل
 * - تحديث تلقائي للبيانات
 * - دعم رفع الملفات والصور
 * - تكامل مع جميع أنظمة API
 */

// ==============================================
// Constants & Configuration
// ==============================================
const API_BASE = window.location.origin + '/Manager/api/';

const API_ENDPOINTS = {
    users: 'manage_users.php',
    finance: 'manage_finance.php',
    courses: 'manage_courses.php',
    chat: 'chat_system.php',
    notifications: 'notifications_system.php',
    requests: 'registration_requests.php',
    import: 'smart_import.php',
    idCards: 'id_cards_system.php'
};

// ==============================================
// Utility Functions
// ==============================================

/**
 * إرسال طلب API بشكل آمن
 */
async function apiRequest(endpoint, options = {}) {
    try {
        const url = API_BASE + endpoint;
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success === false) {
            throw new Error(data.message || 'حدث خطأ غير معروف');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        throw error;
    }
}

/**
 * عرض رسالة منبثقة
 */
function showToast(message, type = 'info') {
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 350px;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * تنظيف وتحقق من البيانات
 */
function sanitizeInput(value) {
    if (typeof value !== 'string') return value;
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * التحقق من رقم الهاتف
 */
function validatePhone(phone) {
    const re = /^[0-9]{9,15}$/;
    return re.test(phone.replace(/[\s\-\+]/g, ''));
}

// ==============================================
// Modal Management - إدارة النماذج المنبثقة
// ==============================================

/**
 * نموذج إضافة/تعديل طالب متقدم
 */
window.openAdvancedStudentModal = async function(studentData = null) {
    const isEdit = !!studentData;
    const modalTitle = isEdit ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد';

    const modalHTML = `
        <div class="modal fade" id="advancedStudentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">${modalTitle}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="studentForm" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <!-- معلومات أساسية -->
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2">المعلومات الأساسية</h6>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_full_name" 
                                           value="${studentData?.full_name || ''}" required>
                                    <div class="invalid-feedback">الرجاء إدخال الاسم الكامل</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">الاسم بالإنجليزية</label>
                                    <input type="text" class="form-control" id="student_full_name_en" 
                                           value="${studentData?.full_name_en || ''}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="student_email" 
                                           value="${studentData?.email || ''}" required>
                                    <div class="invalid-feedback">الرجاء إدخال بريد صحيح</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="student_phone" 
                                           value="${studentData?.phone || ''}" required pattern="[0-9]{9,15}">
                                    <div class="invalid-feedback">رقم هاتف غير صحيح</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">تاريخ الميلاد</label>
                                    <input type="date" class="form-control" id="student_dob" 
                                           value="${studentData?.dob || ''}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">الجنس</label>
                                    <select class="form-select" id="student_gender">
                                        <option value="male" ${!studentData || studentData?.gender === 'male' ? 'selected' : ''}>ذكر</option>
                                        <option value="female" ${studentData?.gender === 'female' ? 'selected' : ''}>أنثى</option>
                                    </select>
                                </div>

                                <!-- معلومات الموقع -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary border-bottom pb-2">معلومات الموقع</h6>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">المحافظة</label>
                                    <input type="text" class="form-control" id="student_governorate" 
                                           value="${studentData?.governorate || ''}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">المديرية</label>
                                    <input type="text" class="form-control" id="student_district" 
                                           value="${studentData?.district || ''}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">العزلة</label>
                                    <input type="text" class="form-control" id="student_sub_district" 
                                           value="${studentData?.sub_district || ''}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">العنوان التفصيلي</label>
                                    <textarea class="form-control" id="student_address" rows="2">${studentData?.address || ''}</textarea>
                                </div>

                                <!-- معلومات الدورة -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary border-bottom pb-2">معلومات الدورة</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">الدورة</label>
                                    <select class="form-select" id="student_course">
                                        <option value="">-- اختر الدورة --</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">منطقة الدورة</label>
                                    <input type="text" class="form-control" id="student_course_region" 
                                           value="${studentData?.course_region || ''}">
                                </div>

                                <!-- كلمة المرور -->
                                ${!isEdit ? `
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary border-bottom pb-2">الأمان</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="student_password" required>
                                    <div class="invalid-feedback">كلمة المرور مطلوبة</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="student_password_confirm" required>
                                    <div class="invalid-feedback">كلمات المرور غير متطابقة</div>
                                </div>
                                ` : ''}
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" onclick="saveAdvancedStudent(${isEdit})">
                            <i class="fas fa-save me-1"></i>
                            ${isEdit ? 'حفظ التعديلات' : 'إضافة الطالب'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // إزالة النموذج القديم إن وجد
    const oldModal = document.getElementById('advancedStudentModal');
    if (oldModal) oldModal.remove();

    // إضافة النموذج الجديد
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // تحميل قائمة الدورات
    await loadCoursesDropdown('student_course');

    // فتح النموذج
    const modal = new bootstrap.Modal(document.getElementById('advancedStudentModal'));
    modal.show();

    // حفظ بيانات التعديل
    if (isEdit && studentData) {
        document.getElementById('advancedStudentModal').dataset.userId = studentData.id;
    }
};

/**
 * حفظ بيانات الطالب
 */
window.saveAdvancedStudent = async function(isEdit = false) {
    const form = document.getElementById('studentForm');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = {
        full_name: document.getElementById('student_full_name').value.trim(),
        full_name_en: document.getElementById('student_full_name_en').value.trim(),
        email: document.getElementById('student_email').value.trim(),
        phone: document.getElementById('student_phone').value.trim(),
        dob: document.getElementById('student_dob').value || null,
        gender: document.getElementById('student_gender').value,
        governorate: document.getElementById('student_governorate').value.trim(),
        district: document.getElementById('student_district').value.trim(),
        sub_district: document.getElementById('student_sub_district').value.trim(),
        address: document.getElementById('student_address').value.trim(),
        course_region: document.getElementById('student_course_region').value.trim(),
        role: 'student'
    };

    if (isEdit) {
        const userId = document.getElementById('advancedStudentModal').dataset.userId;
        formData.action = 'update';
        formData.user_id = parseInt(userId);
    } else {
        const password = document.getElementById('student_password').value;
        const passwordConfirm = document.getElementById('student_password_confirm').value;

        if (password !== passwordConfirm) {
            showToast('كلمات المرور غير متطابقة', 'error');
            return;
        }

        formData.action = 'create';
        formData.password = password;
    }

    try {
        const result = await apiRequest(API_ENDPOINTS.users, {
            method: 'POST',
            body: JSON.stringify(formData)
        });

        showToast(result.message || 'تم الحفظ بنجاح', 'success');
        
        // إغلاق النموذج
        bootstrap.Modal.getInstance(document.getElementById('advancedStudentModal')).hide();
        
        // تحديث القائمة
        if (typeof renderTrainees === 'function') {
            await renderTrainees();
        }
        
    } catch (error) {
        console.error('Save error:', error);
    }
};

/**
 * تحميل قائمة الدورات في القائمة المنسدلة
 */
async function loadCoursesDropdown(selectId) {
    try {
        const data = await apiRequest(API_ENDPOINTS.courses + '?action=list');
        const select = document.getElementById(selectId);
        
        if (data.courses && Array.isArray(data.courses)) {
            data.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.title} - ${course.region || ''}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

// ==============================================
// نموذج إدارة الدفعات المالية المتقدم
// ==============================================

window.openAdvancedPaymentModal = async function(paymentData = null, studentId = null) {
    const isEdit = !!paymentData;
    const modalTitle = isEdit ? 'تعديل دفعة مالية' : 'إضافة دفعة جديدة';

    const modalHTML = `
        <div class="modal fade" id="advancedPaymentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">${modalTitle}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="paymentForm" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">الطالب <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_user_id" required ${studentId ? 'disabled' : ''}>
                                        <option value="">-- اختر الطالب --</option>
                                    </select>
                                    <div class="invalid-feedback">الرجاء اختيار الطالب</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="payment_amount" 
                                           value="${paymentData?.amount || ''}" min="0" step="0.01" required>
                                    <div class="invalid-feedback">الرجاء إدخال المبلغ</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">طريقة الدفع</label>
                                    <select class="form-select" id="payment_method">
                                        <option value="cash" ${!paymentData || paymentData?.payment_method === 'cash' ? 'selected' : ''}>نقدي</option>
                                        <option value="card" ${paymentData?.payment_method === 'card' ? 'selected' : ''}>بطاقة</option>
                                        <option value="transfer" ${paymentData?.payment_method === 'transfer' ? 'selected' : ''}>تحويل</option>
                                        <option value="other" ${paymentData?.payment_method === 'other' ? 'selected' : ''}>أخرى</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">تاريخ الدفع</label>
                                    <input type="date" class="form-control" id="payment_date" 
                                           value="${paymentData?.payment_date || new Date().toISOString().split('T')[0]}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">الحالة</label>
                                    <select class="form-select" id="payment_status">
                                        <option value="completed" ${!paymentData || paymentData?.status === 'completed' ? 'selected' : ''}>مكتمل</option>
                                        <option value="pending" ${paymentData?.status === 'pending' ? 'selected' : ''}>معلق</option>
                                        <option value="cancelled" ${paymentData?.status === 'cancelled' ? 'selected' : ''}>ملغي</option>
                                        <option value="refunded" ${paymentData?.status === 'refunded' ? 'selected' : ''}>مسترد</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">ملاحظات</label>
                                    <textarea class="form-control" id="payment_notes" rows="3">${paymentData?.notes || ''}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-success" onclick="saveAdvancedPayment(${isEdit})">
                            <i class="fas fa-save me-1"></i>
                            ${isEdit ? 'حفظ التعديلات' : 'إضافة الدفعة'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const oldModal = document.getElementById('advancedPaymentModal');
    if (oldModal) oldModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // تحميل قائمة الطلاب
    await loadStudentsDropdown('payment_user_id', studentId);

    const modal = new bootstrap.Modal(document.getElementById('advancedPaymentModal'));
    modal.show();

    if (isEdit && paymentData) {
        document.getElementById('advancedPaymentModal').dataset.paymentId = paymentData.payment_id;
    }
};

/**
 * حفظ الدفعة المالية
 */
window.saveAdvancedPayment = async function(isEdit = false) {
    const form = document.getElementById('paymentForm');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = {
        user_id: parseInt(document.getElementById('payment_user_id').value),
        amount: parseFloat(document.getElementById('payment_amount').value),
        payment_method: document.getElementById('payment_method').value,
        payment_date: document.getElementById('payment_date').value,
        status: document.getElementById('payment_status').value,
        notes: document.getElementById('payment_notes').value.trim()
    };

    if (isEdit) {
        const paymentId = document.getElementById('advancedPaymentModal').dataset.paymentId;
        formData.action = 'update';
        formData.payment_id = parseInt(paymentId);
    } else {
        formData.action = 'create';
    }

    try {
        const result = await apiRequest(API_ENDPOINTS.finance, {
            method: 'POST',
            body: JSON.stringify(formData)
        });

        showToast(result.message || 'تم حفظ الدفعة بنجاح', 'success');
        
        bootstrap.Modal.getInstance(document.getElementById('advancedPaymentModal')).hide();
        
        // تحديث قائمة المالية
        if (typeof renderFinance === 'function') {
            await renderFinance();
        }
        
    } catch (error) {
        console.error('Save payment error:', error);
    }
};

/**
 * تحميل قائمة الطلاب
 */
async function loadStudentsDropdown(selectId, selectedId = null) {
    try {
        const data = await apiRequest(API_ENDPOINTS.users + '?role=student');
        const select = document.getElementById(selectId);
        
        if (data.data && Array.isArray(data.data)) {
            data.data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.full_name} - ${student.email}`;
                if (selectedId && student.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading students:', error);
    }
}

// ==============================================
// نظام رفع الملفات Drag & Drop
// ==============================================

window.initDragDropUpload = function(dropZoneId, fileInputId, onFileSelect) {
    const dropZone = document.getElementById(dropZoneId);
    const fileInput = document.getElementById(fileInputId);

    if (!dropZone || !fileInput) return;

    // منع السلوك الافتراضي
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // تمييز المنطقة عند السحب
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('drag-over');
        }, false);
    });

    // معالجة الإفلات
    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            if (typeof onFileSelect === 'function') {
                onFileSelect(files[0]);
            }
        }
    }, false);

    // معالجة النقر
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0 && typeof onFileSelect === 'function') {
            onFileSelect(e.target.files[0]);
        }
    });
};

// ==============================================
// CSS Animations
// ==============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .drag-over {
        border: 2px dashed #3b82f6 !important;
        background: rgba(59, 130, 246, 0.1) !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
    }

    .was-validated .form-control:invalid,
    .was-validated .form-select:invalid {
        border-color: #dc3545;
    }

    .was-validated .form-control:valid,
    .was-validated .form-select:valid {
        border-color: #198754;
    }
`;
document.head.appendChild(style);

console.log('✅ Advanced Forms System Loaded Successfully!');
