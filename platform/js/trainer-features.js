document.addEventListener('DOMContentLoaded', () => {
    const pageRenderers = {
        'dashboard': renderTrainerDashboard,
        'courses': renderTrainerCourses,
        'students': renderTrainerStudents,
        'attendance': renderTrainerAttendance,
        'grades': renderTrainerGrades,
        'profile': renderTrainerProfile,
    };

    const navLinks = document.querySelectorAll('.nav-link');

    function setPage(page) {
        navLinks.forEach(link => {
            if (link.getAttribute('href') === `#${page}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        const renderFunc = pageRenderers[page] || renderTrainerDashboard;
        renderFunc();
        window.location.hash = page;
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = link.getAttribute('href').substring(1);
            setPage(page);
        });
    });

    const initialPage = window.location.hash.substring(1) || 'dashboard';
    setPage(initialPage);
});

function setPageHeader(title, subtitle) {
    document.getElementById('pageTitle').textContent = title;
    document.getElementById('pageSubtitle').textContent = subtitle;
}

function clearPageBody() {
    document.getElementById('pageBody').innerHTML = '';
}

function renderTrainerDashboard() {
    setPageHeader('لوحة التحكم', 'نظرة عامة على دوراتك وطلابك');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">الدورات النشطة</h3>
                <p class="text-3xl font-bold text-emerald-600">2</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">إجمالي الطلاب</h3>
                <p class="text-3xl font-bold text-sky-600">45</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">واجبات تحتاج للتصحيح</h3>
                <p class="text-3xl font-bold text-yellow-600">8</p>
            </div>
        </div>
    `;
}

function renderTrainerCourses() {
    setPageHeader('دوراتي', 'الدورات التي تقوم بتدريسها');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض الدورات التي تدرسها هنا.</p>`;
}

function renderTrainerStudents() {
    setPageHeader('طلابي', 'عرض الطلاب المسجلين في دوراتك');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض قائمة الطلاب هنا.</p>`;
}

function renderTrainerAttendance() {
    setPageHeader('الحضور', 'تسجيل حضور وغياب الطلاب');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض نظام تسجيل الحضور هنا.</p>`;
}

function renderTrainerGrades() {
    setPageHeader('الدرجات', 'إدخال وتعديل درجات الطلاب');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض نظام إدخال الدرجات هنا.</p>`;
}

function renderTrainerProfile() {
    setPageHeader('ملفي الشخصي', 'تعديل معلوماتك الشخصية');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض ملفك الشخصي هنا.</p>`;
}
