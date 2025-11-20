document.addEventListener('DOMContentLoaded', () => {
    const courseSelector = document.getElementById('courseSelector');
    const studentSelector = document.getElementById('studentSelector');
    const templateSelector = document.getElementById('templateSelector');
    const generateBtn = document.getElementById('generateBtn');
    const preview = document.getElementById('certificatePreview');

    function loadCourses() {
        // This is a placeholder. In a real app, you would fetch this from an API.
        const courses = [
            { id: 1, name: 'تطوير تطبيقات الويب' },
            { id: 2, name: 'التصميم الجرافيكي' },
        ];
        courseSelector.innerHTML = '<option value="">اختر دورة</option>';
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            courseSelector.appendChild(option);
        });
    }

    function loadStudents(courseId) {
        // This is a placeholder. In a real app, you would fetch this from an API.
        const students = [
            { id: 1, name: 'عبدالله الحاشدي' },
            { id: 2, name: 'محمد الأحمدي' },
        ];
        studentSelector.innerHTML = '<option value="">اختر طالب</option>';
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.name;
            studentSelector.appendChild(option);
        });
    }

    function loadTemplates() {
        fetch('api/manage_certificate_templates.php?action=getAll')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    templateSelector.innerHTML = '<option value="">اختر قالب</option>';
                    data.templates.forEach(template => {
                        const option = document.createElement('option');
                        option.value = template.id;
                        option.textContent = template.name;
                        templateSelector.appendChild(option);
                    });
                }
            });
    }

    courseSelector.addEventListener('change', (e) => {
        if (e.target.value) {
            loadStudents(e.target.value);
        }
    });

    generateBtn.addEventListener('click', () => {
        const studentId = studentSelector.value;
        const courseId = courseSelector.value;
        const templateId = templateSelector.value;

        if (!studentId || !courseId || !templateId) {
            showToast('يرجى اختيار الدورة، الطالب، والقالب', 'warning');
            return;
        }

        const url = `../generate_pdf_certificate.php?template_id=${templateId}&student_id=${studentId}&course_id=${courseId}`;
        window.open(url, '_blank');
    });

    loadCourses();
    loadTemplates();
});
