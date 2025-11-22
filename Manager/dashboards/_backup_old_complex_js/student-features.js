/**
 * Student Dashboard Features
 * ميزات لوحة الطالب
 */

const StudentFeatures = {
    // Courses Management
    courses: {
        async getMyCourses() {
            try {
                const response = await fetch(`../api/student_courses.php?student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch courses:', error);
                return { success: false, message: 'فشل تحميل الدورات' };
            }
        },

        async getCourseDetails(courseId) {
            try {
                const response = await fetch(`../api/student_courses.php?action=details&course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch course details:', error);
                return { success: false, message: 'فشل تحميل التفاصيل' };
            }
        }
    },

    // Grades Management
    grades: {
        async getMyGrades(courseId = null) {
            try {
                const url = courseId 
                    ? `../api/student_grades.php?student_id=${window.CURRENT_USER.id}&course_id=${courseId}`
                    : `../api/student_grades.php?student_id=${window.CURRENT_USER.id}`;
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch grades:', error);
                return { success: false, message: 'فشل تحميل الدرجات' };
            }
        },

        async getGPA() {
            try {
                const response = await fetch(`../api/student_grades.php?action=gpa&student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch GPA:', error);
                return { success: false, message: 'فشل تحميل المعدل' };
            }
        }
    },

    // Attendance Management
    attendance: {
        async getMyAttendance(courseId = null) {
            try {
                const url = courseId
                    ? `../api/student_attendance.php?student_id=${window.CURRENT_USER.id}&course_id=${courseId}`
                    : `../api/student_attendance.php?student_id=${window.CURRENT_USER.id}`;
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch attendance:', error);
                return { success: false, message: 'فشل تحميل الحضور' };
            }
        }
    },

    // Assignments Management
    assignments: {
        async getMyAssignments(courseId = null) {
            try {
                const url = courseId
                    ? `../api/student_assignments.php?student_id=${window.CURRENT_USER.id}&course_id=${courseId}`
                    : `../api/student_assignments.php?student_id=${window.CURRENT_USER.id}`;
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch assignments:', error);
                return { success: false, message: 'فشل تحميل الواجبات' };
            }
        },

        async submitAssignment(assignmentId, formData) {
            try {
                formData.append('student_id', window.CURRENT_USER.id);
                formData.append('assignment_id', assignmentId);
                
                const response = await fetch('../api/student_assignments.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to submit assignment:', error);
                return { success: false, message: 'فشل تسليم الواجب' };
            }
        }
    },

    // Materials Management
    materials: {
        async getCourseMaterials(courseId) {
            try {
                const response = await fetch(`../api/student_materials.php?student_id=${window.CURRENT_USER.id}&course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch materials:', error);
                return { success: false, message: 'فشل تحميل المواد' };
            }
        },

        async downloadMaterial(materialId) {
            try {
                const response = await fetch(`../api/student_materials.php?action=download&material_id=${materialId}&student_id=${window.CURRENT_USER.id}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `material_${materialId}`;
                    a.click();
                    return { success: true };
                }
                return { success: false, message: 'فشل تحميل المادة' };
            } catch (error) {
                console.error('Failed to download material:', error);
                return { success: false, message: 'فشل تحميل المادة' };
            }
        }
    },

    // Schedule Management
    schedule: {
        async getMySchedule() {
            try {
                const response = await fetch(`../api/student_schedule.php?student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch schedule:', error);
                return { success: false, message: 'فشل تحميل الجدول' };
            }
        }
    },

    // ID Card Management
    idCard: {
        async getMyIDCard() {
            try {
                const response = await fetch(`../api/student_id_card.php?student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch ID card:', error);
                return { success: false, message: 'فشل تحميل البطاقة' };
            }
        },

        async downloadIDCard(format = 'pdf') {
            try {
                const response = await fetch(`../api/student_id_card.php?action=download&student_id=${window.CURRENT_USER.id}&format=${format}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `student_id_card.${format}`;
                    a.click();
                    return { success: true };
                }
                return { success: false, message: 'فشل تحميل البطاقة' };
            } catch (error) {
                console.error('Failed to download ID card:', error);
                return { success: false, message: 'فشل تحميل البطاقة' };
            }
        }
    },

    // Payments Management
    payments: {
        async getMyPayments() {
            try {
                const response = await fetch(`../api/student_payments.php?student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch payments:', error);
                return { success: false, message: 'فشل تحميل الحالة المالية' };
            }
        },

        async getBalance() {
            try {
                const response = await fetch(`../api/student_payments.php?action=balance&student_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch balance:', error);
                return { success: false, message: 'فشل تحميل الرصيد' };
            }
        },

        async requestPaymentPlan() {
            try {
                const response = await fetch('../api/student_payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'request_plan',
                        student_id: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to request payment plan:', error);
                return { success: false, message: 'فشل طلب خطة السداد' };
            }
        }
    },

    // UI Helpers
    ui: {
        showAssignmentSubmitModal(assignmentId, assignmentTitle) {
            const modalHTML = `
                <form id="submitAssignmentForm" class="space-y-4" enctype="multipart/form-data">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">الواجب</label>
                        <input type="text" value="${assignmentTitle}" readonly 
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">ملاحظات (اختياري)</label>
                        <textarea id="submissionNotes" rows="3"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                            placeholder="أضف أي ملاحظات أو تعليقات"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">المرفقات</label>
                        <input type="file" id="submissionFile" required multiple
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                        <p class="text-sm text-slate-500 mt-2">الحد الأقصى: 25MB لكل ملف</p>
                    </div>
                </form>
            `;
            
            DashboardIntegration.ui.showModal('تسليم الواجب', modalHTML, [
                {
                    text: 'تسليم',
                    class: 'bg-amber-600 text-white hover:bg-amber-700',
                    onclick: `StudentFeatures.ui.submitAssignment(${assignmentId})`
                },
                {
                    text: 'إلغاء',
                    class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
                    onclick: 'this.closest(".fixed").remove()'
                }
            ]);
        },

        async submitAssignment(assignmentId) {
            const notes = document.getElementById('submissionNotes').value;
            const files = document.getElementById('submissionFile').files;
            
            if (files.length === 0) {
                DashboardIntegration.ui.showToast('الرجاء اختيار الملفات', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('notes', notes);
            for (let file of files) {
                formData.append('files[]', file);
            }
            
            const response = await StudentFeatures.assignments.submitAssignment(assignmentId, formData);
            
            if (response.success) {
                DashboardIntegration.ui.showToast('تم تسليم الواجب بنجاح', 'success');
                document.querySelector('.fixed').remove();
                // Reload assignments
                if (typeof loadAssignments === 'function') {
                    loadAssignments();
                }
            } else {
                DashboardIntegration.ui.showToast(response.message || 'فشل تسليم الواجب', 'error');
            }
        },

        showPaymentDetailsModal(payment) {
            const modalHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">رقم الفاتورة</label>
                            <p class="text-slate-800">${payment.invoice_number}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">التاريخ</label>
                            <p class="text-slate-800">${payment.date}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">المبلغ</label>
                            <p class="text-lg font-bold text-amber-600">${payment.amount} ريال</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">الحالة</label>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                                payment.status === 'paid' ? 'bg-emerald-100 text-emerald-700' :
                                payment.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                'bg-red-100 text-red-700'
                            }">
                                ${payment.status === 'paid' ? 'مدفوع' : payment.status === 'pending' ? 'معلق' : 'متأخر'}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">الوصف</label>
                        <p class="text-slate-600">${payment.description || 'لا يوجد وصف'}</p>
                    </div>
                </div>
            `;
            
            DashboardIntegration.ui.showModal('تفاصيل الدفعة', modalHTML, [
                {
                    text: 'إغلاق',
                    class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
                    onclick: 'this.closest(".fixed").remove()'
                }
            ]);
        }
    }
};

// Make it globally available
window.StudentFeatures = StudentFeatures;

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Student Features Loaded');
});
