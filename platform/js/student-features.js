document.addEventListener('DOMContentLoaded', () => {
    const pageRenderers = {
        'dashboard': renderStudentDashboard,
        'courses': renderStudentCourses,
        'grades': renderStudentGrades,
        'certificates': renderStudentCertificates,
        'profile': renderStudentProfile,
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

        const renderFunc = pageRenderers[page] || renderStudentDashboard;
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

function renderStudentDashboard() {
    setPageHeader('لوحة التحكم', 'نظرة عامة على حسابك');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">الدورات المسجلة</h3>
                <p class="text-3xl font-bold text-indigo-600">3</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">الشهادات المكتسبة</h3>
                <p class="text-3xl font-bold text-green-600">1</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold">متوسط الدرجات</h3>
                <p class="text-3xl font-bold text-yellow-600">85%</p>
            </div>
        </div>
    `;
}

function renderStudentCourses() {
    setPageHeader('دوراتي', 'الدورات التي قمت بالتسجيل فيها');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض الدورات المسجلة هنا.</p>`;
}

function renderStudentGrades() {
    setPageHeader('درجاتي', 'عرض الدرجات الخاصة بك في الدورات');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض الدرجات هنا.</p>`;
}

function renderStudentCertificates() {
    setPageHeader('شهاداتي', 'عرض وتحميل الشهادات التي حصلت عليها');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض الشهادات هنا.</p>`;
}

function renderStudentProfile() {
    setPageHeader('ملفي الشخصي', 'تعديل معلوماتك الشخصية');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `<p>سيتم عرض ملفك الشخصي هنا.</p>`;
}
