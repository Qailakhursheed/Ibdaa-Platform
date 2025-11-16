/**
 * Technical Dashboard Features
 * ميزات لوحة المشرف الفني
 */

const TechnicalFeatures = {
    // Course Management
    courses: {
        async getAll(filters = {}) {
            try {
                const params = new URLSearchParams(filters);
                const response = await fetch(`../api/courses.php?${params}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch courses:', error);
                return { success: false, message: 'فشل تحميل الدورات' };
            }
        },

        async approve(courseId) {
            try {
                const response = await fetch('../api/courses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'approve',
                        course_id: courseId,
                        approved_by: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to approve course:', error);
                return { success: false, message: 'فشلت الموافقة' };
            }
        },

        async reject(courseId, reason) {
            try {
                const response = await fetch('../api/courses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reject',
                        course_id: courseId,
                        reason: reason,
                        rejected_by: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to reject course:', error);
                return { success: false, message: 'فشل الرفض' };
            }
        },

        async updateStatus(courseId, status) {
            try {
                const response = await fetch('../api/courses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_status',
                        course_id: courseId,
                        status: status
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to update course status:', error);
                return { success: false, message: 'فشل تحديث الحالة' };
            }
        }
    },

    // Trainer Management
    trainers: {
        async getAll() {
            try {
                const response = await fetch('../api/trainers.php?action=get_all');
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch trainers:', error);
                return { success: false, message: 'فشل تحميل المدربين' };
            }
        },

        async getPerformance(trainerId) {
            try {
                const response = await fetch(`../api/trainers.php?action=get_performance&trainer_id=${trainerId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch trainer performance:', error);
                return { success: false, message: 'فشل تحميل الأداء' };
            }
        },

        async evaluate(trainerId, evaluation) {
            try {
                const response = await fetch('../api/trainers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'evaluate',
                        trainer_id: trainerId,
                        evaluator_id: window.CURRENT_USER.id,
                        ...evaluation
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to evaluate trainer:', error);
                return { success: false, message: 'فشل التقييم' };
            }
        }
    },

    // Materials Management
    materials: {
        async getAll(courseId = null) {
            try {
                const url = courseId 
                    ? `../api/materials.php?course_id=${courseId}`
                    : '../api/materials.php';
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch materials:', error);
                return { success: false, message: 'فشل تحميل المواد' };
            }
        },

        async approve(materialId) {
            try {
                const response = await fetch('../api/materials.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'approve',
                        material_id: materialId,
                        approved_by: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to approve material:', error);
                return { success: false, message: 'فشلت الموافقة' };
            }
        },

        async reject(materialId, reason) {
            try {
                const response = await fetch('../api/materials.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reject',
                        material_id: materialId,
                        reason: reason
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to reject material:', error);
                return { success: false, message: 'فشل الرفض' };
            }
        }
    },

    // Support Tickets
    support: {
        async getAll(status = null) {
            try {
                const url = status 
                    ? `../api/support.php?status=${status}`
                    : '../api/support.php';
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch support tickets:', error);
                return { success: false, message: 'فشل تحميل التذاكر' };
            }
        },

        async respond(ticketId, message) {
            try {
                const response = await fetch('../api/support.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'respond',
                        ticket_id: ticketId,
                        message: message,
                        responder_id: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to respond to ticket:', error);
                return { success: false, message: 'فشل الرد' };
            }
        },

        async updateStatus(ticketId, status) {
            try {
                const response = await fetch('../api/support.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_status',
                        ticket_id: ticketId,
                        status: status
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to update ticket status:', error);
                return { success: false, message: 'فشل التحديث' };
            }
        },

        async close(ticketId, note = '') {
            try {
                const response = await fetch('../api/support.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'close',
                        ticket_id: ticketId,
                        note: note,
                        closed_by: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to close ticket:', error);
                return { success: false, message: 'فشل الإغلاق' };
            }
        }
    },

    // Quality Assurance
    quality: {
        async getCourseQuality(courseId) {
            try {
                const response = await fetch(`../api/quality.php?course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch quality data:', error);
                return { success: false, message: 'فشل تحميل البيانات' };
            }
        },

        async submitReport(courseId, report) {
            try {
                const response = await fetch('../api/quality.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit_report',
                        course_id: courseId,
                        reporter_id: window.CURRENT_USER.id,
                        ...report
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to submit quality report:', error);
                return { success: false, message: 'فشل إرسال التقرير' };
            }
        }
    },

    // Reports & Analytics
    reports: {
        async getCoursesReport(startDate, endDate) {
            try {
                const response = await fetch(`../api/reports.php?type=courses&start=${startDate}&end=${endDate}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch courses report:', error);
                return { success: false, message: 'فشل تحميل التقرير' };
            }
        },

        async getTrainersReport(startDate, endDate) {
            try {
                const response = await fetch(`../api/reports.php?type=trainers&start=${startDate}&end=${endDate}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch trainers report:', error);
                return { success: false, message: 'فشل تحميل التقرير' };
            }
        },

        async generatePDF(reportType, data) {
            try {
                const response = await fetch('../api/reports.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generate_pdf',
                        type: reportType,
                        data: data
                    })
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `report_${reportType}_${Date.now()}.pdf`;
                    a.click();
                    return { success: true };
                }
                return { success: false, message: 'فشل إنشاء PDF' };
            } catch (error) {
                console.error('Failed to generate PDF:', error);
                return { success: false, message: 'فشل إنشاء PDF' };
            }
        }
    },

    // UI Helpers
    ui: {
        showCourseDetails(courseId) {
            // Implementation using DashboardIntegration.ui.showModal
            DashboardIntegration.ui.showToast('جاري تحميل التفاصيل...', 'info');
        },

        showTrainerProfile(trainerId) {
            // Implementation
            DashboardIntegration.ui.showToast('جاري تحميل الملف الشخصي...', 'info');
        },

        showEvaluationForm(trainerId) {
            const formHTML = `
                <form id="evaluationForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">التقييم العام</label>
                        <select name="rating" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                            <option value="">اختر التقييم</option>
                            <option value="5">ممتاز (5)</option>
                            <option value="4">جيد جداً (4)</option>
                            <option value="3">جيد (3)</option>
                            <option value="2">مقبول (2)</option>
                            <option value="1">ضعيف (1)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">الملاحظات</label>
                        <textarea name="notes" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg" placeholder="أضف ملاحظاتك هنا..."></textarea>
                    </div>
                </form>
            `;

            DashboardIntegration.ui.showModal('تقييم المدرب', formHTML, [
                {
                    text: 'إرسال التقييم',
                    class: 'bg-sky-600 text-white hover:bg-sky-700',
                    onclick: `TechnicalFeatures.trainers.submitEvaluation(${trainerId})`
                },
                {
                    text: 'إلغاء',
                    class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
                    onclick: 'this.closest(".fixed").remove()'
                }
            ]);
        }
    }
};

// Make it globally available
window.TechnicalFeatures = TechnicalFeatures;

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Technical Features Loaded');
});
