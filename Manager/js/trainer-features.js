/**
 * Trainer Dashboard Features
 * ميزات لوحة المدرب
 */

const TrainerFeatures = {
    // Courses Management
    courses: {
        async getMyCourses() {
            try {
                const response = await fetch(`../api/trainer_courses.php?trainer_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch courses:', error);
                return { success: false, message: 'فشل تحميل الدورات' };
            }
        },

        async getCourseDetails(courseId) {
            try {
                const response = await fetch(`../api/trainer_courses.php?action=details&course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch course details:', error);
                return { success: false, message: 'فشل تحميل التفاصيل' };
            }
        }
    },

    // Students Management
    students: {
        async getMyStudents(courseId = null) {
            try {
                const url = courseId 
                    ? `../api/trainer_students.php?trainer_id=${window.CURRENT_USER.id}&course_id=${courseId}`
                    : `../api/trainer_students.php?trainer_id=${window.CURRENT_USER.id}`;
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch students:', error);
                return { success: false, message: 'فشل تحميل الطلاب' };
            }
        },

        async getStudentProfile(studentId) {
            try {
                const response = await fetch(`../api/trainer_students.php?action=profile&student_id=${studentId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch student profile:', error);
                return { success: false, message: 'فشل تحميل الملف الشخصي' };
            }
        }
    },

    // Attendance Management
    attendance: {
        async getAttendanceRecords(courseId, date = null) {
            try {
                const url = date
                    ? `../api/attendance.php?course_id=${courseId}&date=${date}`
                    : `../api/attendance.php?course_id=${courseId}`;
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch attendance:', error);
                return { success: false, message: 'فشل تحميل الحضور' };
            }
        },

        async recordAttendance(courseId, date, attendanceData) {
            try {
                const response = await fetch('../api/attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'record',
                        course_id: courseId,
                        date: date,
                        trainer_id: window.CURRENT_USER.id,
                        attendance: attendanceData
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to record attendance:', error);
                return { success: false, message: 'فشل تسجيل الحضور' };
            }
        },

        async updateAttendance(attendanceId, status, notes = '') {
            try {
                const response = await fetch('../api/attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        attendance_id: attendanceId,
                        status: status,
                        notes: notes
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to update attendance:', error);
                return { success: false, message: 'فشل تحديث الحضور' };
            }
        }
    },

    // Grades Management
    grades: {
        async getGrades(courseId) {
            try {
                const response = await fetch(`../api/grades.php?course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch grades:', error);
                return { success: false, message: 'فشل تحميل الدرجات' };
            }
        },

        async submitGrade(studentId, courseId, gradeData) {
            try {
                const response = await fetch('../api/grades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit',
                        student_id: studentId,
                        course_id: courseId,
                        trainer_id: window.CURRENT_USER.id,
                        ...gradeData
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to submit grade:', error);
                return { success: false, message: 'فشل إرسال الدرجة' };
            }
        },

        async updateGrade(gradeId, gradeData) {
            try {
                const response = await fetch('../api/grades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        grade_id: gradeId,
                        ...gradeData
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to update grade:', error);
                return { success: false, message: 'فشل تحديث الدرجة' };
            }
        }
    },

    // Materials Management
    materials: {
        async getMaterials(courseId) {
            try {
                const response = await fetch(`../api/materials.php?course_id=${courseId}&trainer_id=${window.CURRENT_USER.id}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch materials:', error);
                return { success: false, message: 'فشل تحميل المواد' };
            }
        },

        async uploadMaterial(courseId, formData) {
            try {
                formData.append('trainer_id', window.CURRENT_USER.id);
                formData.append('course_id', courseId);
                
                const response = await fetch('../api/materials.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to upload material:', error);
                return { success: false, message: 'فشل رفع المادة' };
            }
        },

        async deleteMaterial(materialId) {
            try {
                const response = await fetch('../api/materials.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        material_id: materialId,
                        trainer_id: window.CURRENT_USER.id
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to delete material:', error);
                return { success: false, message: 'فشل حذف المادة' };
            }
        }
    },

    // Assignments Management
    assignments: {
        async getAssignments(courseId) {
            try {
                const response = await fetch(`../api/assignments.php?course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch assignments:', error);
                return { success: false, message: 'فشل تحميل الواجبات' };
            }
        },

        async createAssignment(courseId, assignmentData) {
            try {
                const response = await fetch('../api/assignments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        course_id: courseId,
                        trainer_id: window.CURRENT_USER.id,
                        ...assignmentData
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to create assignment:', error);
                return { success: false, message: 'فشل إنشاء الواجب' };
            }
        },

        async gradeSubmission(submissionId, grade, feedback) {
            try {
                const response = await fetch('../api/assignments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'grade_submission',
                        submission_id: submissionId,
                        grade: grade,
                        feedback: feedback
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to grade submission:', error);
                return { success: false, message: 'فشل تقييم الواجب' };
            }
        }
    },

    // Announcements
    announcements: {
        async getAnnouncements(courseId) {
            try {
                const response = await fetch(`../api/announcements.php?course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch announcements:', error);
                return { success: false, message: 'فشل تحميل الإعلانات' };
            }
        },

        async createAnnouncement(courseId, announcementData) {
            try {
                const response = await fetch('../api/announcements.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        course_id: courseId,
                        trainer_id: window.CURRENT_USER.id,
                        ...announcementData
                    })
                });
                return await response.json();
            } catch (error) {
                console.error('Failed to create announcement:', error);
                return { success: false, message: 'فشل إنشاء الإعلان' };
            }
        }
    },

    // Reports
    reports: {
        async getCourseReport(courseId) {
            try {
                const response = await fetch(`../api/reports.php?type=course&course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch report:', error);
                return { success: false, message: 'فشل تحميل التقرير' };
            }
        },

        async getStudentReport(studentId, courseId) {
            try {
                const response = await fetch(`../api/reports.php?type=student&student_id=${studentId}&course_id=${courseId}`);
                return await response.json();
            } catch (error) {
                console.error('Failed to fetch student report:', error);
                return { success: false, message: 'فشل تحميل التقرير' };
            }
        },

        async exportReport(reportType, courseId, format = 'pdf') {
            try {
                const response = await fetch('../api/reports.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'export',
                        type: reportType,
                        course_id: courseId,
                        format: format
                    })
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `report_${reportType}_${Date.now()}.${format}`;
                    a.click();
                    return { success: true };
                }
                return { success: false, message: 'فشل التصدير' };
            } catch (error) {
                console.error('Failed to export report:', error);
                return { success: false, message: 'فشل التصدير' };
            }
        }
    },

    // UI Helpers
    ui: {
        showAttendanceModal(courseId, date) {
            const modalHTML = `
                <div class="space-y-4" id="attendanceForm">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">التاريخ</label>
                        <input type="date" id="attendanceDate" value="${date}" 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <div id="studentsAttendance" class="space-y-2 max-h-96 overflow-y-auto">
                        <div class="text-center py-4">
                            <i data-lucide="loader" class="w-6 h-6 mx-auto animate-spin text-slate-400"></i>
                        </div>
                    </div>
                </div>
            `;

            DashboardIntegration.ui.showModal('تسجيل الحضور', modalHTML, [
                {
                    text: 'حفظ',
                    class: 'bg-emerald-600 text-white hover:bg-emerald-700',
                    onclick: 'TrainerFeatures.ui.saveAttendance(' + courseId + ')'
                },
                {
                    text: 'إلغاء',
                    class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
                    onclick: 'this.closest(".fixed").remove()'
                }
            ]);

            // Load students
            this.loadAttendanceStudents(courseId);
        },

        async loadAttendanceStudents(courseId) {
            const container = document.getElementById('studentsAttendance');
            const response = await TrainerFeatures.students.getMyStudents(courseId);
            
            if (response.success && response.data) {
                container.innerHTML = response.data.map(student => `
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <img src="${student.photo || '../platform/photos/default-avatar.png'}" 
                                 class="w-10 h-10 rounded-full object-cover">
                            <span class="font-semibold text-slate-800">${student.full_name}</span>
                        </div>
                        <div class="flex gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="attendance_${student.id}" value="present" class="text-emerald-600">
                                <span class="text-emerald-600">حاضر</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="attendance_${student.id}" value="absent" class="text-red-600">
                                <span class="text-red-600">غائب</span>
                            </label>
                        </div>
                    </div>
                `).join('');
            }
        },

        async saveAttendance(courseId) {
            const date = document.getElementById('attendanceDate').value;
            const attendanceData = [];
            
            document.querySelectorAll('[id^="studentsAttendance"] input[type="radio"]:checked').forEach(input => {
                const studentId = input.name.replace('attendance_', '');
                attendanceData.push({
                    student_id: studentId,
                    status: input.value
                });
            });
            
            const response = await TrainerFeatures.attendance.recordAttendance(courseId, date, attendanceData);
            if (response.success) {
                DashboardIntegration.ui.showToast('تم تسجيل الحضور بنجاح', 'success');
                document.querySelector('.fixed').remove();
            } else {
                DashboardIntegration.ui.showToast('فشل تسجيل الحضور', 'error');
            }
        }
    }
};

// Make it globally available
window.TrainerFeatures = TrainerFeatures;

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Trainer Features Loaded');
});
