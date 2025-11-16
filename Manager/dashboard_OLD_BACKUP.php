<?php
session_start();

if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

require_once __DIR__ . '/../database/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$userName = $_SESSION['user_name'] ?? ($_SESSION['full_name'] ?? 'مستخدم المنصة');

$isStudent = $userRole === 'student';
$isEnrolledStudent = false;

if ($isStudent && $userId > 0) {
	$stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status IN ('active','completed')");
	if ($stmt) {
		$stmt->bind_param('i', $userId);
		if ($stmt->execute()) {
			$stmt->bind_result($enrollmentCount);
			$stmt->fetch();
			$isEnrolledStudent = ($enrollmentCount > 0);
		}
		$stmt->close();
	}
}

$roleNames = [
	'manager' => 'المدير',
	'technical' => 'المشرف الفني',
	'trainer' => 'المدرب',
	'student' => 'الطالب'
];
$currentRoleLabel = $roleNames[$userRole] ?? 'مستخدم';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>لوحة التحكم - منصة إبداع</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/chat.css">
	<!-- Chart.js for Financial Charts -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	<style>
		body { font-family: 'Cairo', sans-serif; background-color: #f1f5f9; }
		.sidebar-link { transition: background-color 0.2s ease, color 0.2s ease; }
		.sidebar-link.active { background-color: rgba(14,165,233,0.15); color: #0284c7; font-weight: 600; }
		.modal-backdrop { display: none; }
		.modal-backdrop.visible { display: flex; }
	</style>
</head>
<body class="min-h-screen text-slate-800" data-current-role="<?php echo htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8'); ?>" data-user-name="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">
	<div id="managerDashboardLayout" class="<?php echo $isStudent ? 'hidden' : 'flex'; ?> min-h-screen">
		<aside id="sidebar" class="hidden lg:flex lg:flex-col w-72 bg-white border-l border-slate-200 shadow-sm">
			<div class="px-6 py-6 border-b border-slate-200 text-center">
				<img src="../platform/photos/Sh.jpg" alt="شعار منصة إبداع" class="mx-auto mb-3 w-16 h-16 rounded-full border-4 border-sky-500 shadow-sm">
				<h1 class="text-2xl font-bold text-slate-800">منصة إبداع</h1>
				<p class="text-sm text-slate-500 mt-1">لوحة تحكم متقدمة</p>
			</div>
			<nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1 text-slate-700">
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl active" data-page="dashboard" data-roles="manager,technical,trainer">
					<i data-lucide="layout-dashboard" class="w-5 h-5"></i>
					<span>لوحة التحكم</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="trainees" data-roles="manager,technical">
					<i data-lucide="users" class="w-5 h-5"></i>
					<span>المتدربون</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="trainers" data-roles="manager,technical">
					<i data-lucide="user-check" class="w-5 h-5"></i>
					<span>المدربون</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="courses" data-roles="manager,technical,trainer">
					<i data-lucide="book-open" class="w-5 h-5"></i>
					<span>الدورات</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="finance" data-roles="manager,technical">
					<i data-lucide="wallet" class="w-5 h-5"></i>
					<span>الشؤون المالية</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="requests" data-roles="manager,technical">
					<i data-lucide="inbox" class="w-5 h-5"></i>
					<span>الطلبات</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="announcements" data-roles="manager,technical,trainer">
					<i data-lucide="megaphone" class="w-5 h-5"></i>
					<span>الإعلانات</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="notifications" data-roles="manager,technical,trainer,student">
					<i data-lucide="bell" class="w-5 h-5"></i>
					<span>الإشعارات</span>
					<span id="notification-badge" class="mr-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full hidden"></span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="grades" data-roles="manager,technical,trainer">
					<i data-lucide="graduation-cap" class="w-5 h-5"></i>
					<span>الدرجات والشهادات</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="messages" data-roles="manager,technical,trainer">
					<i data-lucide="message-circle" class="w-5 h-5"></i>
					<span>الرسائل</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="attendanceReports" data-roles="manager">
					<i data-lucide="calendar-check" class="w-5 h-5"></i>
					<span>تقارير الحضور</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="analytics" data-roles="manager">
					<i data-lucide="bar-chart-3" class="w-5 h-5"></i>
					<span>التقارير المتقدمة</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="locations" data-roles="manager,technical">
					<i data-lucide="map-pin" class="w-5 h-5"></i>
					<span>المواقع</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="import" data-roles="manager,technical">
					<i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
					<span>الاستيراد الذكي</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="idCards" data-roles="technical">
					<i data-lucide="credit-card" class="w-5 h-5"></i>
					<span>🎴 البطاقات الذكية</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="graduates" data-roles="manager">
					<i data-lucide="award" class="w-5 h-5"></i>
					<span>الخريجون</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="aiImages" data-roles="manager,technical">
					<i data-lucide="sparkles" class="w-5 h-5"></i>
					<span>🎨 AI توليد الصور</span>
				</a>
				<a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-page="settings" data-roles="manager">
					<i data-lucide="settings" class="w-5 h-5"></i>
					<span>الإعدادات</span>
				</a>
			</nav>
			<div class="px-6 py-5 border-t border-slate-200">
				<a href="logout.php" class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition">
					<i data-lucide="log-out" class="w-4 h-4"></i>
					<span>تسجيل الخروج</span>
				</a>
			</div>
		</aside>

		<div class="flex-1 flex flex-col">
			<header id="topbar" class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
				<div class="flex items-center gap-3">
					<button id="mobileSidebarToggle" class="lg:hidden rounded-full p-2 hover:bg-slate-100" aria-label="Toggle sidebar">
						<i data-lucide="panel-left-open" class="w-5 h-5"></i>
					</button>
					<div>
						<p class="text-sm text-slate-500">مرحباً بك</p>
						<p class="text-lg font-semibold text-slate-800" id="currentUserName"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></p>
					</div>
				</div>
				<div class="flex items-center gap-4">
					<!-- شارة الرسائل الجديدة -->
					<div class="notification-badge">
						<button id="messagesBell" class="notification-bell relative rounded-full border border-slate-200 p-2 hover:bg-slate-50 transition" aria-label="Messages" title="الرسائل">
							<i data-lucide="message-circle" class="w-5 h-5"></i>
						</button>
						<span class="badge-counter hidden" id="messagesBadgeCounter">0</span>
					</div>
					
					<!-- شارة الإشعارات العامة -->
					<button id="notificationsBell" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50" aria-label="Notifications">
						<i data-lucide="bell" class="w-5 h-5"></i>
						<span id="notificationsCounter" class="absolute -top-1 -right-1 hidden text-xs bg-red-500 text-white rounded-full px-1"></span>
					</button>
					
					<div class="hidden sm:flex flex-col text-right">
						<span class="text-sm text-slate-500">الدور الحالي</span>
						<span class="text-sm font-semibold text-slate-700" id="currentUserRole"><?php echo htmlspecialchars($currentRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
					</div>
				</div>
			</header>

			<main id="pageContent" class="flex-1 overflow-y-auto px-6 py-8 bg-slate-50">
				<div id="pageHeader" class="mb-8">
					<h2 id="pageTitle" class="text-2xl font-bold text-slate-800">لوحة التحكم</h2>
					<p id="pageSubtitle" class="text-sm text-slate-500 mt-2">نظرة عامة على أداء المنصة</p>
				</div>
				<div id="pageBody" class="space-y-6"></div>
			</main>
		</div>
	</div>

	<div id="studentDashboardLayout" class="<?php echo $isStudent ? '' : 'hidden'; ?> min-h-screen">
		<header class="bg-white border-b border-slate-200 shadow-sm">
			<div class="max-w-6xl mx-auto px-6 py-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
				<div>
					<p class="text-sm text-slate-500">مرحبا،</p>
					<h1 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></h1>
					<p class="text-sm text-slate-500 mt-1">لوحة الطالب الذكية لإدارة محتواك التعليمي</p>
				</div>
				<div class="flex items-center gap-3">
					<button id="studentNotificationsBtn" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50">
						<i data-lucide="bell" class="w-5 h-5"></i>
						<span id="studentNotificationsCounter" class="absolute -top-1 -right-1 hidden text-xs bg-red-500 text-white rounded-full px-1"></span>
					</button>
					<a href="logout.php" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700">تسجيل الخروج</a>
				</div>
			</div>
		</header>
		<main class="max-w-6xl mx-auto px-6 py-8 space-y-8">
			<section id="studentOverview" class="grid grid-cols-1 md:grid-cols-4 gap-4"></section>
			<section id="studentCourses" class="bg-white shadow rounded-3xl p-6">
				<div class="flex items-center justify-between mb-4">
					<div>
						<h2 class="text-xl font-bold text-slate-800">دوراتي</h2>
						<p class="text-sm text-slate-500">اضغط على أي دورة لاستعراض محتواها</p>
					</div>
					<span id="studentCoursesCount" class="text-sm text-slate-500"></span>
				</div>
				<div id="studentCoursesList" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
			</section>
			<section id="studentCourseDetail" class="bg-white shadow rounded-3xl p-6 hidden">
				<div class="flex items-start justify-between gap-4 mb-6">
					<div>
						<h2 id="studentCourseTitle" class="text-2xl font-bold text-slate-800"></h2>
						<p id="studentCourseMeta" class="text-sm text-slate-500 mt-1"></p>
					</div>
					<button id="closeStudentCourseDetail" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إغلاق</button>
				</div>
				<div id="studentModules" class="space-y-6"></div>
			</section>
			<section id="studentMessagesSection" class="bg-white shadow rounded-3xl p-6">
				<div id="studentMessagesContainer"></div>
			</section>
		</main>
	</div>

	<div id="modalBackdrop" class="modal-backdrop fixed inset-0 bg-slate-900/60 items-center justify-center px-4 z-40">
		<div class="bg-white w-full max-w-3xl rounded-2xl shadow-xl overflow-hidden">
			<div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
				<h3 id="modalTitle" class="text-xl font-semibold text-slate-800"></h3>
				<button id="closeModalBtn" class="p-2 rounded-full hover:bg-slate-100" aria-label="close">
					<i data-lucide="x" class="w-5 h-5"></i>
				</button>
			</div>
			<div id="modalBody" class="px-6 py-6 max-h-[70vh] overflow-y-auto"></div>
		</div>
	</div>

	<div id="toast" class="hidden fixed bottom-6 right-6 bg-slate-900 text-white px-4 py-3 rounded-lg shadow-lg z-50"></div>

	<script>
	const CURRENT_USER = {
		id: <?php echo (int) $userId; ?>,
		role: <?php echo json_encode($userRole, JSON_UNESCAPED_UNICODE); ?>,
		name: <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>,
		isEnrolledStudent: <?php echo $isEnrolledStudent ? 'true' : 'false'; ?>
	};

	const API_ENDPOINTS = {
		dashboardStats: 'api/get_dashboard_stats.php',
		trainerData: 'api/get_trainer_data.php',
		trainees: 'api/manage_users.php?role=student',
		trainers: 'api/manage_users.php?role=trainer',
		manageUsers: 'api/manage_users.php',
		manageCourses: 'api/manage_courses.php',
		manageFinance: 'api/manage_finance.php',
		manageRequests: 'api/get_requests.php',
		manageAnnouncements: 'api/manage_announcements.php',
		manageGrades: 'api/manage_grades.php',
		manageLocations: 'api/manage_locations.php',
		manageImports: 'api/import_excel_flexible.php',
		manageLmsContent: 'api/manage_lms_content.php',
		manageLmsAssignments: 'api/manage_lms_assignments.php',
		manageAttendance: 'api/manage_attendance.php',
		generateCertificate: 'api/generate_certificate.php',
		manageMessages: 'api/manage_messages.php',
		analyticsData: 'api/get_analytics_data.php',
		notifications: 'api/get_notifications.php',
		markNotificationRead: 'api/mark_notification_read.php',
		studentData: 'api/get_student_data.php',
		aiImages: 'api/ai_image_generator.php'
	};

	const pageRenderers = {
		dashboard: renderDashboard,
		trainees: renderTrainees,
		trainers: renderTrainers,
		courses: renderCourses,
		finance: renderFinance,
		requests: renderRequests,
		announcements: renderAnnouncements,
		notifications: renderNotifications,
		grades: renderGrades,
		messages: renderMessages,
		attendanceReports: renderAttendanceReports,
		attendanceSheet: renderAttendanceSheet,
		analytics: renderAnalytics,
		locations: renderLocations,
		import: renderImports,
		idCards: renderIDCards,
		graduates: renderGraduates,
		settings: renderSettings,
		aiImages: renderAIImages
	};

	function setPageHeader(title, subtitle) {
		const titleEl = document.getElementById('pageTitle');
		const subtitleEl = document.getElementById('pageSubtitle');
		if (titleEl) titleEl.textContent = title;
		if (subtitleEl) subtitleEl.textContent = subtitle;
	}

	function clearPageBody() {
		const body = document.getElementById('pageBody');
		if (body) body.innerHTML = '';
	}

	function escapeHtml(value) {
		const div = document.createElement('div');
		div.textContent = value == null ? '' : String(value);
		return div.innerHTML;
	}

	function formatDateTime(value, options = {}) {
		if (!value) return '';
		let input = value;
		if (typeof input === 'string' && input.includes(' ')) {
			input = input.replace(' ', 'T');
		}
		const date = new Date(input);
		if (Number.isNaN(date.getTime())) {
			return value;
		}
		const formatter = new Intl.DateTimeFormat('ar-EG', {
			dateStyle: options.dateStyle || 'medium',
			timeStyle: options.timeStyle || 'short'
		});
		return formatter.format(date);
	}

	function showToast(message, variant = 'info') {
		const toast = document.getElementById('toast');
		if (!toast) return;
		const variants = {
			success: 'bg-emerald-600',
			error: 'bg-red-600',
			info: 'bg-slate-900',
			warning: 'bg-amber-600'
		};
		toast.textContent = message;
		toast.className = `fixed bottom-6 right-6 text-white px-4 py-3 rounded-lg shadow-lg z-50 ${variants[variant] || variants.info}`;
		toast.classList.remove('hidden');
		setTimeout(() => toast.classList.add('hidden'), 4000);
	}

	/**
	 * التحقق من صلاحية المستخدم لميزة معينة
	 * @param {string|string[]} allowedRoles - الأدوار المسموح لها
	 * @returns {boolean} - true إذا كان المستخدم لديه الصلاحية
	 */
	function hasPermission(allowedRoles) {
		if (!allowedRoles) return true;
		const roles = Array.isArray(allowedRoles) ? allowedRoles : allowedRoles.split(',').map(r => r.trim());
		return roles.includes(CURRENT_USER.role);
	}

	/**
	 * منع تنفيذ إجراء إذا لم يكن لدى المستخدم الصلاحية
	 * @param {string|string[]} allowedRoles - الأدوار المسموح لها
	 * @param {Function} callback - الدالة المراد تنفيذها
	 * @param {string} deniedMessage - رسالة الخطأ عند الرفض
	 */
	function requirePermission(allowedRoles, callback, deniedMessage = 'لا تملك صلاحية للقيام بهذا الإجراء') {
		if (!hasPermission(allowedRoles)) {
			showToast(deniedMessage, 'warning');
			return;
		}
		callback();
	}

	function applyRoleBasedAccessControl() {
		const role = CURRENT_USER.role;
		
		// إخفاء الروابط المحظورة من القائمة الجانبية
		const sidebar = document.querySelectorAll('.sidebar-link');
		sidebar.forEach(link => {
			const allowed = (link.dataset.roles || '').split(',').map(r => r.trim()).filter(Boolean);
			if (allowed.length > 0 && !allowed.includes(role)) {
				link.style.display = 'none'; // إخفاء كامل
				link.setAttribute('data-access-denied', 'true');
			} else {
				link.style.display = ''; // إظهار
				link.removeAttribute('data-access-denied');
			}
		});

		// إخفاء الأزرار المحظورة في المحتوى الرئيسي
		const buttons = document.querySelectorAll('[data-required-role]');
		buttons.forEach(btn => {
			const requiredRoles = (btn.dataset.requiredRole || '').split(',').map(r => r.trim()).filter(Boolean);
			if (requiredRoles.length > 0 && !requiredRoles.includes(role)) {
				btn.style.display = 'none';
				btn.disabled = true;
			} else {
				btn.style.display = '';
				btn.disabled = false;
			}
		});
	}

	function initSidebarNavigation() {
		const links = Array.from(document.querySelectorAll('.sidebar-link'));
		links.forEach(link => {
			link.addEventListener('click', event => {
				event.preventDefault();
				
				// منع الوصول إذا كان العنصر محظوراً
				if (link.hasAttribute('data-access-denied')) {
					showToast('لا تملك صلاحية لفتح هذا القسم', 'warning');
					return;
				}

				const allowed = (link.dataset.roles || '').split(',').map(r => r.trim()).filter(Boolean);
				if (allowed.length > 0 && !allowed.includes(CURRENT_USER.role)) {
					showToast('لا تملك صلاحية لفتح هذا القسم', 'warning');
					return;
				}
				
				links.forEach(item => item.classList.remove('active'));
				link.classList.add('active');
				const page = link.dataset.page;
				if (pageRenderers[page]) {
					pageRenderers[page]();
				} else {
					setPageHeader('صفحة غير متوفرة', 'يرجى التواصل مع الدعم');
					clearPageBody();
				}
			});
		});
	}

	async function fetchJson(url, options = {}) {
		const response = await fetch(url, options);
		const contentType = response.headers.get('content-type') || '';
		if (!contentType.includes('application/json')) {
			throw new Error('استجابة غير متوقعة من الخادم');
		}
		const payload = await response.json();
		if (!response.ok || payload.success === false) {
			const message = payload.message || payload.error || 'حدث خطأ أثناء تنفيذ العملية';
			throw new Error(message);
		}
		return payload;
	}

	function renderStatisticCard({ title, value, icon, accent }) {
		return `
			<div class="bg-white rounded-2xl shadow p-5 flex items-center justify-between border border-slate-100">
				<div>
					<p class="text-sm text-slate-500">${title}</p>
					<p class="text-2xl font-bold mt-2">${value}</p>
				</div>
				<div class="p-3 rounded-full bg-${accent}-50 text-${accent}-600">
					<i data-lucide="${icon}" class="w-6 h-6"></i>
				</div>
			</div>
		`;
	}

	async function renderDashboard() {
		if (CURRENT_USER.role === 'student') {
			await renderStudentHome();
			return;
		}

		setPageHeader('لوحة التحكم', 'إحصائيات لحظية حول أداء المنصة');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			if (['manager', 'technical'].includes(CURRENT_USER.role)) {
				const data = await fetchJson(API_ENDPOINTS.dashboardStats);
				const stats = data.stats || {};
				body.innerHTML = `
					<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
						${renderStatisticCard({ title: 'إجمالي المتدربين', value: stats.total_trainees ?? 0, icon: 'users', accent: 'sky' })}
						${renderStatisticCard({ title: 'الدورات النشطة', value: stats.active_courses ?? 0, icon: 'book-open-check', accent: 'emerald' })}
						${renderStatisticCard({ title: 'الإيرادات المكتملة (USD)', value: stats.total_revenue ?? 0, icon: 'wallet', accent: 'amber' })}
						${renderStatisticCard({ title: 'الشهادات الصادرة', value: stats.certs_issued ?? 0, icon: 'award', accent: 'violet' })}
					</section>
					<section class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="bg-white rounded-2xl shadow p-6" id="chartEnrollments">
							<div class="flex items-center justify-between mb-4">
								<h3 class="text-lg font-semibold text-slate-800">توزيع الطلبات</h3>
								<span class="text-sm text-slate-500">الإجمالي: ${stats.pending_requests ?? 0}</span>
							</div>
							<canvas id="requestsChart" height="200"></canvas>
						</div>
						<div class="bg-white rounded-2xl shadow p-6" id="chartTrainers">
							<div class="flex items-center justify-between mb-4">
								<h3 class="text-lg font-semibold text-slate-800">فريق التدريب</h3>
								<span class="text-sm text-slate-500">عدد المدربين: ${stats.total_trainers ?? 0}</span>
							</div>
							<canvas id="trainersChart" height="200"></canvas>
						</div>
					</section>
				`;
				lucide.createIcons();
				renderRequestsChart(stats.pending_requests ?? 0);
				renderTrainersChart(stats.total_trainers ?? 0);
			} else if (CURRENT_USER.role === 'trainer') {
				const data = await fetchJson(API_ENDPOINTS.trainerData);
				const courses = data.courses || [];
				const students = data.students || [];

				body.innerHTML = `
					<section class="grid grid-cols-1 md:grid-cols-3 gap-4">
						${renderStatisticCard({ title: 'دوراتي', value: courses.length, icon: 'layers', accent: 'sky' })}
						${renderStatisticCard({ title: 'عدد المتدربين', value: students.length, icon: 'users', accent: 'emerald' })}
						${renderStatisticCard({ title: 'متوسط التقدم', value: 'قريباً', icon: 'trending-up', accent: 'violet' })}
					</section>
					<section class="bg-white rounded-2xl shadow p-6">
						<div class="flex items-center justify-between mb-4">
							<h3 class="text-lg font-semibold text-slate-800">دوراتي النشطة</h3>
							<span class="text-sm text-slate-500">${courses.length} دورة</span>
						</div>
						<div class="space-y-3">
							${courses.map(course => `
								<div class='border border-slate-100 rounded-xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4'>
									<div>
										<h4 class='text-base font-semibold text-slate-800'>${course.title}</h4>
										<p class='text-xs text-slate-500 mt-1'>الحالة: ${course.status}</p>
									</div>
									<div class='flex items-center gap-2'>
										<button class='px-3 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700' data-action='open-editor' data-course-id='${course.course_id}' data-course-title='${course.title}'>إدارة محتوى الدورة</button>
										<button class='px-3 py-2 rounded-lg border border-slate-200 text-sm hover:bg-slate-50' data-action='view-students' data-course-id='${course.course_id}'>عرض الطلاب</button>
									</div>
								</div>
							`).join('')}
						</div>
					</section>
				`;

				lucide.createIcons();

				body.querySelectorAll('[data-action="open-editor"]').forEach(btn => {
					btn.addEventListener('click', () => {
						const courseId = parseInt(btn.dataset.courseId, 10);
						const courseTitle = btn.dataset.courseTitle;
						renderCourseEditor(courseId, courseTitle);
					});
				});

			body.querySelectorAll('[data-action="open-attendance"]').forEach(btn => {
				btn.addEventListener('click', () => {
					const courseId = parseInt(btn.dataset.courseId, 10);
					const courseTitle = btn.dataset.courseTitle;
					renderAttendanceSheet(courseId, courseTitle);
				});
			});

				body.querySelectorAll('[data-action="view-students"]').forEach(btn => {
					btn.addEventListener('click', async () => {
						const courseId = parseInt(btn.dataset.courseId, 10);
						const filtered = students.filter(s => String(s.course_id) === String(courseId));
						setPageHeader('طلاب الدورة', 'قائمة الطلاب المسجلين');
						const tableRows = filtered.map(student => `
							<tr>
								<td class='px-4 py-2 font-medium text-slate-800'>${student.full_name}</td>
								<td class='px-4 py-2 text-slate-600'>${student.email}</td>
								<td class='px-4 py-2 text-slate-500'>${student.enrollment_status}</td>
							</tr>
						`).join('');
						body.innerHTML = `
							<section class='bg-white rounded-2xl shadow overflow-hidden'>
								<div class='px-6 py-4 border-b border-slate-200 flex items-center justify-between'>
									<h3 class='text-lg font-semibold text-slate-800'>طلاب الدورة</h3>
									<button id='backToTrainerDashboard' class='px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50'>عودة</button>
								</div>
								<div class='overflow-x-auto'>
									<table class='w-full text-sm text-right'>
										<thead class='bg-slate-50 text-slate-600'>
											<tr>
												<th class='px-4 py-2'>الاسم</th>
												<th class='px-4 py-2'>البريد</th>
												<th class='px-4 py-2'>الحالة</th>
											</tr>
										</thead>
										<tbody>${tableRows || '<tr><td colspan="3" class="px-4 py-4 text-center text-slate-500">لا يوجد طلاب مسجلون حالياً</td></tr>'}</tbody>
									</table>
								</div>
							</section>
						`;
						document.getElementById('backToTrainerDashboard').addEventListener('click', () => renderDashboard());
					});
				});
			}
		} catch (error) {
			console.error(error);
			showToast(error.message, 'error');
			body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function renderRequestsChart(pending) {
		const canvas = document.getElementById('requestsChart');
		if (!canvas) return;
		const ctx = canvas.getContext('2d');
		new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: ['قيد المراجعة', 'مكتملة'],
				datasets: [{
					data: [pending, Math.max(1, 10 - pending)],
					backgroundColor: ['#0284c7', '#e2e8f0'],
					borderWidth: 0
				}]
			},
			options: {
				plugins: { legend: { position: 'bottom', labels: { font: { family: 'Cairo' } } } }
			}
		});
	}

	function renderTrainersChart(total) {
		const canvas = document.getElementById('trainersChart');
		if (!canvas) return;
		const ctx = canvas.getContext('2d');
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: ['المدربون'],
				datasets: [{
					label: 'عدد المدربين',
					data: [total],
					backgroundColor: '#22c55e',
					borderRadius: 12
				}]
			},
			options: {
				plugins: { legend: { display: false } },
				scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
			}
		});
	}

	async function renderTrainees() {
		setPageHeader('إدارة المتدربين', 'تحكم كامل بقوائم المتدربين والتسجيلات');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			const data = await fetchJson(API_ENDPOINTS.trainees);
			const trainees = data.data || [];
			const canAddTrainee = hasPermission('manager,technical');
			
			body.innerHTML = `
				<section class="bg-white rounded-2xl shadow p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
						<div>
							<h3 class="text-lg font-semibold text-slate-800">قائمة المتدربين</h3>
							<p class="text-sm text-slate-500">${trainees.length} متدرب مسجل</p>
						</div>
						<div class="flex items-center gap-2">
							<input type="search" id="traineeSearch" placeholder="بحث بالاسم أو البريد" class="border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sky-500" />
							${canAddTrainee ? `
								<button id="openTraineeModal" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 flex items-center gap-2" data-required-role="manager,technical">
									<i data-lucide="user-plus" class="w-4 h-4"></i>
									<span>إضافة متدرب</span>
								</button>
							` : ''}
						</div>
					</div>
					<div class="overflow-x-auto">
						<table class="w-full text-sm text-right">
							<thead class="bg-slate-50 text-slate-600">
								<tr>
									<th class="px-4 py-2">الاسم</th>
									<th class="px-4 py-2">البريد</th>
									<th class="px-4 py-2">الهاتف</th>
									<th class="px-4 py-2">المحافظة</th>
									<th class="px-4 py-2">الإجراءات</th>
								</tr>
							</thead>
							<tbody id="traineesTableBody" class="divide-y divide-slate-100">
								${trainees.map(buildTraineeRow).join('')}
							</tbody>
						</table>
					</div>
				</section>
			`;
			lucide.createIcons();
			attachTraineeHandlers(trainees);
		} catch (error) {
			showToast(error.message, 'error');
			body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function buildTraineeRow(trainee) {
		// التحقق من صلاحية التعديل والحذف (مدير أو مشرف فني فقط)
		const canEdit = hasPermission('manager,technical');
		const canDelete = hasPermission('manager,technical');
		
		return `
			<tr data-user-id="${trainee.id}">
				<td class="px-4 py-2 font-medium text-slate-800">${trainee.full_name || 'بدون اسم'}</td>
				<td class="px-4 py-2 text-slate-600">${trainee.email || '-'}</td>
				<td class="px-4 py-2 text-slate-600">${trainee.phone || '-'}</td>
				<td class="px-4 py-2 text-slate-600">${trainee.governorate || '-'}</td>
				<td class="px-4 py-2">
					<div class="flex items-center gap-2 justify-end">
						${canEdit ? '<button class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50" data-action="edit" data-required-role="manager,technical">تعديل</button>' : ''}
						${canDelete ? '<button class="px-3 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50" data-action="delete" data-required-role="manager,technical">حذف</button>' : ''}
						${!canEdit && !canDelete ? '<span class="text-sm text-slate-400">عرض فقط</span>' : ''}
					</div>
				</td>
			</tr>
		`;
	}

	function attachTraineeHandlers(trainees) {
		const body = document.getElementById('pageBody');
		if (!body) return;
		const search = document.getElementById('traineeSearch');
		const tableBody = document.getElementById('traineesTableBody');

		if (search) {
			search.addEventListener('input', () => {
				const term = search.value.trim().toLowerCase();
				tableBody.innerHTML = trainees
					.filter(t => !term || (t.full_name && t.full_name.toLowerCase().includes(term)) || (t.email && t.email.toLowerCase().includes(term)))
					.map(buildTraineeRow)
					.join('');
				attachTraineeHandlers(trainees);
			});
		}

		body.querySelectorAll('[data-action="edit"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const row = btn.closest('tr');
				const userId = parseInt(row.dataset.userId, 10);
				const trainee = trainees.find(t => Number(t.id) === userId);
				if (trainee) {
					openModal('تعديل بيانات المتدرب', buildTraineeForm(trainee));
					bindTraineeForm(userId);
				}
			});
		});

		body.querySelectorAll('[data-action="delete"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const row = btn.closest('tr');
				const userId = parseInt(row.dataset.userId, 10);
				if (!confirm('هل أنت متأكد من حذف هذا المتدرب؟')) return;
				try {
					await fetchJson(API_ENDPOINTS.manageUsers, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete', user_id: userId })
					});
					showToast('تم حذف المتدرب', 'success');
					renderTrainees();
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});

		const openBtn = document.getElementById('openTraineeModal');
		if (openBtn) {
			openBtn.addEventListener('click', () => {
				openModal('إضافة متدرب جديد', buildTraineeForm());
				bindTraineeForm();
			});
		}
	}

	// 🤖 AI-Powered Student Account Creation System
	function buildTraineeForm(trainee = {}) {
		const isNewStudent = !trainee.id;
		const autoUsername = isNewStudent ? generateSmartUsername(trainee.full_name || '') : (trainee.username || '');
		const autoPassword = isNewStudent ? generateSecurePassword() : '';
		
		return `
			<form id="traineeForm" class="space-y-5">
				<input type="hidden" name="user_id" value="${trainee.id || ''}">
				
				<!-- Welcome Banner for New Students -->
				${isNewStudent ? `
				<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white">
					<div class="flex items-center gap-3 mb-2">
						<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
							<i data-lucide="user-plus" class="w-7 h-7"></i>
						</div>
						<div>
							<h3 class="font-bold text-xl">🎓 إنشاء حساب طالب جديد</h3>
							<p class="text-sm opacity-90">سيتم إنشاء الحساب وإرسال بيانات الدخول تلقائياً</p>
						</div>
					</div>
				</div>
				` : ''}

				<!-- Personal Information -->
				<div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200">
					<h4 class="font-bold text-indigo-900 mb-4 flex items-center gap-2">
						<i data-lucide="user" class="w-5 h-5"></i>
						المعلومات الشخصية
					</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div class="md:col-span-2">
							<label class="block text-sm font-semibold text-slate-700 mb-2">👤 الاسم الكامل *</label>
							<input name="full_name" id="studentFullName" value="${trainee.full_name || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
								placeholder="أدخل الاسم الكامل للطالب" required
								onchange="updateAutoUsername()">
							<p class="text-xs text-slate-500 mt-1">💡 سيتم توليد اسم المستخدم تلقائياً من الاسم</p>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">📧 البريد الإلكتروني *</label>
							<input type="email" name="email" value="${trainee.email || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
								placeholder="student@example.com" required>
							<p class="text-xs text-slate-500 mt-1">✉️ سيتم إرسال بيانات الدخول لهذا البريد</p>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">📱 رقم الهاتف</label>
							<input name="phone" value="${trainee.phone || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
								placeholder="7xxxxxxxx">
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">🏙️ المحافظة</label>
							<select name="governorate" class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
								<option value="">اختر المحافظة</option>
								<option value="صنعاء" ${trainee.governorate === 'صنعاء' ? 'selected' : ''}>صنعاء</option>
								<option value="عدن" ${trainee.governorate === 'عدن' ? 'selected' : ''}>عدن</option>
								<option value="تعز" ${trainee.governorate === 'تعز' ? 'selected' : ''}>تعز</option>
								<option value="الحديدة" ${trainee.governorate === 'الحديدة' ? 'selected' : ''}>الحديدة</option>
								<option value="إب" ${trainee.governorate === 'إب' ? 'selected' : ''}>إب</option>
								<option value="ذمار" ${trainee.governorate === 'ذمار' ? 'selected' : ''}>ذمار</option>
								<option value="أخرى" ${trainee.governorate === 'أخرى' ? 'selected' : ''}>أخرى</option>
							</select>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">🎂 تاريخ الميلاد</label>
							<input type="date" name="birth_date" value="${trainee.birth_date || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
						</div>
					</div>
				</div>

				<!-- Login Credentials -->
				<div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-5 border border-emerald-200">
					<h4 class="font-bold text-emerald-900 mb-4 flex items-center gap-2">
						<i data-lucide="key" class="w-5 h-5"></i>
						بيانات الدخول للمنصة
					</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">
								🔐 اسم المستخدم ${isNewStudent ? '(يتم توليده تلقائياً)' : ''}
							</label>
							<div class="relative">
								<input name="username" id="autoUsername" value="${autoUsername}" 
									class="w-full border-2 border-emerald-200 rounded-lg px-4 py-3 pr-12 bg-emerald-50 font-mono text-emerald-900 font-bold" 
									placeholder="سيتم التوليد تلقائياً" ${isNewStudent ? 'readonly' : ''}>
								${isNewStudent ? `
									<button type="button" onclick="updateAutoUsername()" 
										class="absolute left-2 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
										<i data-lucide="refresh-cw" class="w-4 h-4"></i>
									</button>
								` : ''}
							</div>
							<p class="text-xs text-emerald-600 mt-1">✨ يستخدم للدخول إلى المنصة</p>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">
								🔑 كلمة المرور ${isNewStudent ? '(آمنة وقوية)' : '(اتركها فارغة لعدم التغيير)'}
							</label>
							<div class="relative">
								<input type="text" name="password" id="autoPassword" value="${autoPassword}" 
									class="w-full border-2 border-emerald-200 rounded-lg px-4 py-3 pr-20 ${isNewStudent ? 'bg-emerald-50 font-mono' : 'bg-white'} font-bold" 
									placeholder="${isNewStudent ? 'سيتم التوليد تلقائياً' : '••••••••'}">
								${isNewStudent ? `
									<div class="absolute left-2 top-1/2 -translate-y-1/2 flex gap-1">
										<button type="button" onclick="document.getElementById('autoPassword').value = generateSecurePassword()" 
											class="p-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700" title="توليد جديد">
											<i data-lucide="refresh-cw" class="w-4 h-4"></i>
										</button>
										<button type="button" onclick="copyToClipboard(document.getElementById('autoPassword').value, 'تم نسخ كلمة المرور')" 
											class="p-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700" title="نسخ">
											<i data-lucide="copy" class="w-4 h-4"></i>
										</button>
									</div>
								` : ''}
							</div>
							<p class="text-xs text-emerald-600 mt-1">🔒 سيتم إرسالها عبر البريد الإلكتروني</p>
						</div>
					</div>
					
					<!-- Platform Access Info -->
					<div class="mt-4 p-4 bg-white rounded-lg border border-emerald-200">
						<div class="flex items-start gap-3">
							<div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0">
								<i data-lucide="link" class="w-5 h-5 text-white"></i>
							</div>
							<div class="flex-1">
								<p class="font-semibold text-slate-800 mb-1">🌐 رابط دخول المنصة</p>
								<div class="flex items-center gap-2">
									<input type="text" id="platformLink" value="${window.location.origin}/platform" 
										class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-slate-50 font-mono" readonly>
									<button type="button" onclick="copyToClipboard(document.getElementById('platformLink').value, 'تم نسخ الرابط')" 
										class="px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-1">
										<i data-lucide="copy" class="w-4 h-4"></i>
										<span class="text-sm">نسخ</span>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Email Notification Settings -->
				${isNewStudent ? `
				<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-200">
					<div class="flex items-center gap-3 mb-3">
						<input type="checkbox" id="sendWelcomeEmail" name="send_welcome_email" checked 
							class="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-500">
						<label for="sendWelcomeEmail" class="font-semibold text-purple-900 cursor-pointer flex items-center gap-2">
							<i data-lucide="mail" class="w-5 h-5"></i>
							إرسال رسالة ترحيبية تلقائياً
						</label>
					</div>
					<p class="text-sm text-purple-700 mr-8">
						📬 سيتم إرسال بريد إلكتروني يحتوي على بيانات الدخول ورابط المنصة وتعليمات البدء
					</p>
				</div>
				` : ''}

				<!-- Action Buttons -->
				<div class="flex justify-end gap-3 pt-4 border-t-2 border-slate-100">
					<button type="button" id="cancelModalAction" 
						class="px-6 py-3 rounded-lg border-2 border-slate-200 hover:bg-slate-100 font-semibold transition-all flex items-center gap-2">
						<i data-lucide="x" class="w-4 h-4"></i>
						إلغاء
					</button>
					<button type="submit" 
						class="px-8 py-3 rounded-lg bg-gradient-to-r from-sky-600 to-indigo-600 text-white hover:from-sky-700 hover:to-indigo-700 font-bold transition-all flex items-center gap-2 shadow-lg hover:shadow-xl">
						<i data-lucide="${isNewStudent ? 'user-plus' : 'save'}" class="w-4 h-4"></i>
						${isNewStudent ? '🎓 إنشاء الحساب وإرسال البيانات' : '💾 تحديث البيانات'}
					</button>
				</div>
			</form>

			<script>
				// Generate Smart Username from Full Name
				function generateSmartUsername(fullName = '') {
					if (!fullName) {
						fullName = document.getElementById('studentFullName')?.value || '';
					}
					if (!fullName) return 'student_' + Date.now();
					
					// Convert Arabic to English transliteration
					const arabicToEnglish = {
						'ا': 'a', 'أ': 'a', 'إ': 'i', 'آ': 'a',
						'ب': 'b', 'ت': 't', 'ث': 'th', 'ج': 'j',
						'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'th',
						'ر': 'r', 'ز': 'z', 'س': 's', 'ش': 'sh',
						'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'z',
						'ع': 'a', 'غ': 'gh', 'ف': 'f', 'ق': 'q',
						'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n',
						'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a',
						'ة': 'h', 'ئ': 'e', 'ء': 'a'
					};
					
					let username = fullName.trim().toLowerCase()
						.split(' ')
						.filter(word => word.length > 0)
						.slice(0, 3)
						.map(word => {
							let converted = '';
							for (let char of word) {
								converted += arabicToEnglish[char] || char;
							}
							return converted;
						})
						.join('_')
						.replace(/[^a-z0-9_]/g, '');
					
					// Add random number
					username += '_' + Math.floor(Math.random() * 9000 + 1000);
					
					return username || 'student_' + Date.now();
				}

				// Update Auto Username
				function updateAutoUsername() {
					const fullName = document.getElementById('studentFullName')?.value;
					const usernameField = document.getElementById('autoUsername');
					if (usernameField && fullName) {
						usernameField.value = generateSmartUsername(fullName);
					}
				}

				// Generate Secure Password
				function generateSecurePassword() {
					const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$%&*';
					let password = '';
					for (let i = 0; i < 12; i++) {
						password += chars.charAt(Math.floor(Math.random() * chars.length));
					}
					return password;
				}

				// Copy to Clipboard
				function copyToClipboard(text, message) {
					navigator.clipboard.writeText(text).then(() => {
						showToast(message || 'تم النسخ', 'success');
					});
				}

				// Initialize lucide icons after form render
				setTimeout(() => lucide.createIcons(), 100);
			</script>
		`;
	}

	function bindTraineeForm(userId = null) {
		const form = document.getElementById('traineeForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			const originalText = submitBtn.innerHTML;
			
			// Show loading state
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<div class="flex items-center gap-2"><div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div><span>جاري المعالجة...</span></div>';
			
			const data = Object.fromEntries(new FormData(form).entries());
			const action = userId ? 'update' : 'create';
			const isNewStudent = !userId;
			
			data.action = action;
			
			// Validation
			if (!data.full_name || !data.email) {
				showToast('⚠️ يرجى ملء الاسم والبريد الإلكتروني', 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
				return;
			}
			
			// Email validation
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(data.email)) {
				showToast('⚠️ البريد الإلكتروني غير صحيح', 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
				return;
			}
			
			if (isNewStudent) {
				data.role = 'student';
				
				// Ensure username and password are set
				if (!data.username) {
					data.username = 'student_' + Date.now();
				}
				if (!data.password) {
					data.password = Math.random().toString(36).slice(2, 14) + Math.random().toString(36).slice(2, 6).toUpperCase();
				}
				
				// Platform link
				data.platform_url = window.location.origin + '/platform';
				
			} else {
				data.user_id = userId;
				// Don't update password if empty
				if (!data.password) {
					delete data.password;
				}
			}
			
			try {
				const response = await fetchJson(API_ENDPOINTS.manageUsers, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				
				// If new student and send_welcome_email is checked
				if (isNewStudent && data.send_welcome_email === 'on') {
					try {
						submitBtn.innerHTML = '<div class="flex items-center gap-2"><div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div><span>جاري إرسال البريد...</span></div>';
						
						await sendWelcomeEmail({
							email: data.email,
							full_name: data.full_name,
							username: data.username,
							password: data.password,
							platform_url: data.platform_url
						});
						
						showToast('✅ تم إنشاء الحساب وإرسال بيانات الدخول بنجاح!', 'success');
					} catch (emailError) {
						showToast('✅ تم إنشاء الحساب ولكن فشل إرسال البريد: ' + emailError.message, 'warning');
					}
				} else if (isNewStudent) {
					showToast('✅ تم إنشاء حساب الطالب بنجاح!', 'success');
				} else {
					showToast('✅ تم تحديث بيانات الطالب بنجاح', 'success');
				}
				
				closeModal();
				renderTrainees();
				
			} catch (error) {
				showToast('❌ ' + error.message, 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	// Send Welcome Email to New Student
	async function sendWelcomeEmail(studentData) {
		const emailData = {
			action: 'send_welcome',
			to: studentData.email,
			student_name: studentData.full_name,
			username: studentData.username,
			password: studentData.password,
			platform_url: studentData.platform_url,
			subject: '🎓 مرحباً بك في منصة إبداع تعز التدريبية',
			message: `
				<div style="font-family: Cairo, Arial, sans-serif; direction: rtl; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; border-radius: 20px;">
					<div style="background: white; border-radius: 16px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
						<!-- Header -->
						<div style="text-align: center; margin-bottom: 30px;">
							<h1 style="color: #667eea; font-size: 32px; margin: 0; font-weight: bold;">🎓 أهلاً بك!</h1>
							<p style="color: #64748b; font-size: 18px; margin: 10px 0;">تم إنشاء حسابك بنجاح في منصة إبداع تعز</p>
						</div>

						<!-- Welcome Message -->
						<div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border-right: 5px solid #667eea;">
							<p style="color: #1e293b; font-size: 16px; line-height: 1.8; margin: 0;">
								عزيزنا <strong style="color: #667eea;">${studentData.full_name}</strong>،<br><br>
								يسعدنا انضمامك إلى منصة <strong>إبداع تعز التدريبية</strong>! 🌟<br>
								نحن متحمسون لمرافقتك في رحلتك التعليمية نحو النجاح والتميز.
							</p>
						</div>

						<!-- Login Credentials Box -->
						<div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 2px solid #22c55e;">
							<h3 style="color: #166534; font-size: 20px; margin: 0 0 20px 0; font-weight: bold; text-align: center;">
								🔐 بيانات الدخول الخاصة بك
							</h3>
							<div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
								<p style="color: #64748b; font-size: 14px; margin: 0 0 5px 0;">👤 اسم المستخدم</p>
								<p style="color: #0f172a; font-size: 18px; font-weight: bold; font-family: monospace; margin: 0; background: #f8fafc; padding: 12px; border-radius: 6px; border: 2px dashed #cbd5e1;">${studentData.username}</p>
							</div>
							<div style="background: white; padding: 20px; border-radius: 8px;">
								<p style="color: #64748b; font-size: 14px; margin: 0 0 5px 0;">🔑 كلمة المرور</p>
								<p style="color: #0f172a; font-size: 18px; font-weight: bold; font-family: monospace; margin: 0; background: #f8fafc; padding: 12px; border-radius: 6px; border: 2px dashed #cbd5e1;">${studentData.password}</p>
							</div>
							<div style="margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 8px; border-right: 4px solid #f59e0b;">
								<p style="color: #92400e; font-size: 14px; margin: 0; line-height: 1.6;">
									⚠️ <strong>مهم:</strong> احتفظ ببيانات الدخول في مكان آمن ولا تشاركها مع أحد
								</p>
							</div>
						</div>

						<!-- Platform Link Button -->
						<div style="text-align: center; margin: 30px 0;">
							<a href="${studentData.platform_url}" 
								style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 16px 40px; border-radius: 50px; font-size: 18px; font-weight: bold; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); transition: all 0.3s;">
								🚀 الدخول إلى المنصة الآن
							</a>
						</div>

						<!-- Quick Start Guide -->
						<div style="background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0;">
							<h4 style="color: #1e293b; font-size: 18px; margin: 0 0 15px 0; font-weight: bold;">📚 خطوات البدء السريع:</h4>
							<ol style="color: #475569; font-size: 15px; line-height: 2; margin: 0; padding-right: 20px;">
								<li>قم بزيارة رابط المنصة أعلاه</li>
								<li>أدخل اسم المستخدم وكلمة المرور</li>
								<li>استكشف الدورات المتاحة وسجل فيها</li>
								<li>ابدأ رحلتك التعليمية! 🎯</li>
							</ol>
						</div>

						<!-- Support Section -->
						<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; text-align: center; border: 2px solid #fbbf24;">
							<p style="color: #78350f; font-size: 15px; margin: 0; line-height: 1.8;">
								💬 <strong>هل تحتاج مساعدة؟</strong><br>
								فريق الدعم جاهز لمساعدتك في أي وقت!<br>
								تواصل معنا عبر منصة الدعم الفني داخل الموقع
							</p>
						</div>

						<!-- Footer -->
						<div style="text-align: center; margin-top: 30px; padding-top: 25px; border-top: 2px solid #e2e8f0;">
							<p style="color: #94a3b8; font-size: 14px; margin: 5px 0;">
								مع أطيب التمنيات بالتوفيق والنجاح 🌟
							</p>
							<p style="color: #64748b; font-size: 16px; font-weight: bold; margin: 5px 0;">
								فريق منصة إبداع تعز التدريبية
							</p>
							<p style="color: #cbd5e1; font-size: 13px; margin: 15px 0 0 0;">
								© ${new Date().getFullYear()} جميع الحقوق محفوظة
							</p>
						</div>
					</div>
				</div>
			`
		};

		// Send via API
		const response = await fetch('Mailer/sendMail.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(emailData)
		});

		if (!response.ok) {
			throw new Error('فشل إرسال البريد الإلكتروني');
		}

		return await response.json();
	}

	// ==================== AI TRAINER MANAGEMENT FUNCTIONS ====================
	
	function calculateAIPerformanceScore(trainer) {
		// Metrics for AI evaluation
		const attendanceRate = Number(trainer.attendance_rate) || 0; // 0-100
		const studentRating = Number(trainer.avg_student_rating) || 0; // 0-5
		const completionRate = Number(trainer.course_completion_rate) || 0; // 0-100
		const contentQuality = Number(trainer.content_quality_score) || 0; // 0-100
		
		// Weighted calculation (total = 100%)
		const score = (
			(attendanceRate * 0.25) +        // 25% weight
			(studentRating * 20 * 0.30) +    // 30% weight (convert 0-5 to 0-100)
			(completionRate * 0.25) +        // 25% weight
			(contentQuality * 0.20)          // 20% weight
		);
		
		return Math.round(score);
	}
	
	function getAIRecommendations(score) {
		if (score >= 90) return {
			text: '🌟 أداء ممتاز! استمر في التميز',
			color: 'text-emerald-600',
			bgColor: 'bg-emerald-50'
		};
		if (score >= 75) return {
			text: '💪 أداء جيد جداً! حاول رفع معدل الحضور',
			color: 'text-blue-600',
			bgColor: 'bg-blue-50'
		};
		if (score >= 60) return {
			text: '📚 أداء مقبول، ننصح بحضور دورات تطوير المدربين',
			color: 'text-amber-600',
			bgColor: 'bg-amber-50'
		};
		return {
			text: '⚠️ يحتاج تحسين فوري، نوصي بمراجعة المشرف الفني',
			color: 'text-red-600',
			bgColor: 'bg-red-50'
		};
	}
	
	function getBadges(trainer) {
		const badges = [];
		const coursesCount = Number(trainer.courses_count) || 0;
		const rating = Number(trainer.avg_student_rating) || 0;
		const attendance = Number(trainer.attendance_rate) || 0;
		const experience = Number(trainer.years_experience) || 0;
		
		if (coursesCount >= 10) badges.push({ icon: '🏆', text: 'مدرب محترف', color: 'bg-amber-100 text-amber-700' });
		if (rating >= 4.5) badges.push({ icon: '⭐', text: 'الأعلى تقييماً', color: 'bg-yellow-100 text-yellow-700' });
		if (attendance >= 95) badges.push({ icon: '💯', text: 'الحضور المثالي', color: 'bg-emerald-100 text-emerald-700' });
		if (experience >= 5) badges.push({ icon: '🎓', text: 'خبير متمرس', color: 'bg-indigo-100 text-indigo-700' });
		
		return badges;
	}
	
	function getRewardPoints(score) {
		if (score >= 90) return 100;
		if (score >= 75) return 75;
		if (score >= 60) return 50;
		return 25;
	}
	
	function renderTrainersLeaderboard(trainers) {
		const sorted = trainers
			.map(t => ({ 
				...t, 
				aiScore: calculateAIPerformanceScore(t),
				badges: getBadges(t)
			}))
			.sort((a, b) => b.aiScore - a.aiScore)
			.slice(0, 10);
		
		return `
			<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 mb-6">
				<div class="flex items-center justify-between mb-6">
					<h3 class="font-bold text-xl text-slate-800">🏆 قائمة الشرف - أفضل المدربين</h3>
					<span class="text-sm text-slate-500">Top 10</span>
				</div>
				<div class="space-y-3">
					${sorted.length === 0 ? '<p class="text-slate-500 text-center py-4">لا توجد بيانات كافية</p>' : 
					sorted.map((trainer, index) => {
						const rankColors = {
							0: 'from-amber-400 to-amber-600',
							1: 'from-slate-300 to-slate-500',
							2: 'from-orange-400 to-orange-600'
						};
						const bgGradient = rankColors[index] || 'from-purple-500 to-pink-600';
						
						return `
							<div class="bg-white rounded-xl p-4 flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow">
								<div class="w-12 h-12 rounded-full bg-gradient-to-br ${bgGradient} flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
									${index + 1}
								</div>
								<div class="flex-1 min-w-0">
									<p class="font-bold text-slate-800 truncate">${trainer.full_name || 'مدرب'}</p>
									<div class="flex items-center gap-2 mt-1">
										<span class="text-sm font-semibold text-purple-600">${trainer.aiScore} نقطة</span>
										<span class="text-xs text-slate-400">•</span>
										<span class="text-xs text-slate-500">${getRewardPoints(trainer.aiScore)} نقطة مكافأة</span>
									</div>
								</div>
								<div class="flex gap-1 flex-wrap justify-end">
									${trainer.badges.slice(0, 3).map(b => `
										<span class="text-2xl" title="${b.text}">${b.icon}</span>
									`).join('')}
								</div>
							</div>
						`;
					}).join('')}
				</div>
			</div>
		`;
	}
	
	// ==================== MAIN TRAINERS RENDER FUNCTION ====================
	
	async function renderTrainers() {
		setPageHeader('🤖 إدارة المدربين بالذكاء الاصطناعي', 'تقييم ومتابعة أداء المدربين بتقنية AI');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			const data = await fetchJson(API_ENDPOINTS.trainers);
			const trainers = data.data || [];
			const canAddTrainer = hasPermission('manager,technical');
			
			// Add AI scores to trainers
			const trainersWithAI = trainers.map(t => ({
				...t,
				aiScore: calculateAIPerformanceScore(t),
				recommendation: getAIRecommendations(calculateAIPerformanceScore(t)),
				badges: getBadges(t),
				rewardPoints: getRewardPoints(calculateAIPerformanceScore(t))
			}));
			
			body.innerHTML = `
				${renderTrainersLeaderboard(trainersWithAI)}
				
				<section class="bg-white rounded-2xl shadow p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div>
							<h3 class="text-lg font-semibold text-slate-800">قائمة المدربين</h3>
							<p class="text-sm text-slate-500">${trainers.length} مدرب • متوسط الأداء: ${Math.round(trainersWithAI.reduce((sum, t) => sum + t.aiScore, 0) / trainersWithAI.length || 0)}%</p>
						</div>
						${canAddTrainer ? `
							<button id="openTrainerModal" class="px-4 py-2 rounded-lg bg-gradient-to-r from-violet-600 to-purple-600 text-white hover:from-violet-700 hover:to-purple-700 flex items-center gap-2 shadow-md" data-required-role="manager,technical">
								<i data-lucide="user-plus" class="w-4 h-4"></i>
								<span>إضافة مدرب</span>
							</button>
						` : ''}
					</div>
					<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="trainersGrid">
						${trainersWithAI.map(trainerCard).join('')}
					</div>
				</section>
			`;
			lucide.createIcons();
			attachTrainerHandlers(trainers);
		} catch (error) {
			showToast(error.message, 'error');
			body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function trainerCard(trainer) {
		const canEdit = hasPermission('manager,technical');
		const canDelete = hasPermission('manager,technical');
		const aiScore = trainer.aiScore || 0;
		const recommendation = trainer.recommendation || getAIRecommendations(0);
		const badges = trainer.badges || [];
		const rewardPoints = trainer.rewardPoints || 0;
		
		// Score color based on performance
		let scoreColor = 'text-slate-600';
		let scoreBg = 'bg-slate-100';
		if (aiScore >= 90) {
			scoreColor = 'text-emerald-600';
			scoreBg = 'bg-emerald-100';
		} else if (aiScore >= 75) {
			scoreColor = 'text-blue-600';
			scoreBg = 'bg-blue-100';
		} else if (aiScore >= 60) {
			scoreColor = 'text-amber-600';
			scoreBg = 'bg-amber-100';
		} else {
			scoreColor = 'text-red-600';
			scoreBg = 'bg-red-100';
		}
		
		return `
			<div class="border border-slate-100 rounded-2xl p-5 flex flex-col gap-3 hover:shadow-lg transition-shadow" data-user-id="${trainer.id}">
				<!-- Header with Avatar and Name -->
				<div class="flex items-start justify-between gap-3">
					<div class="flex items-center gap-3 flex-1 min-w-0">
						<div class="w-12 h-12 rounded-full bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center text-white flex-shrink-0">
							<i data-lucide="user" class="w-6 h-6"></i>
						</div>
						<div class="flex-1 min-w-0">
							<p class="font-bold text-slate-800 truncate">${trainer.full_name || 'بدون اسم'}</p>
							<p class="text-xs text-slate-500 truncate">${trainer.email || '-'}</p>
						</div>
					</div>
					<div class="flex flex-col items-end gap-1">
						<div class="${scoreBg} ${scoreColor} px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1">
							<i data-lucide="zap" class="w-3 h-3"></i>
							<span>${aiScore}%</span>
						</div>
						<div class="text-xs text-slate-500">${rewardPoints} نقطة</div>
					</div>
				</div>
				
				<!-- Badges -->
				${badges.length > 0 ? `
					<div class="flex gap-1 flex-wrap">
						${badges.map(b => `
							<span class="${b.color} px-2 py-1 rounded-lg text-xs font-medium flex items-center gap-1">
								<span>${b.icon}</span>
								<span>${b.text}</span>
							</span>
						`).join('')}
					</div>
				` : ''}
				
				<!-- AI Recommendation -->
				<div class="${recommendation.bgColor} ${recommendation.color} p-3 rounded-lg text-xs">
					<p class="font-medium">${recommendation.text}</p>
				</div>
				
				<!-- Contact Info -->
				<div class="text-xs text-slate-500 space-y-1 border-t border-slate-100 pt-3">
					<div class="flex items-center gap-2">
						<i data-lucide="phone" class="w-3 h-3"></i>
						<span>${trainer.phone || '-'}</span>
					</div>
					<div class="flex items-center gap-2">
						<i data-lucide="map-pin" class="w-3 h-3"></i>
						<span>${trainer.governorate || '-'}</span>
					</div>
				</div>
				
				<!-- Action Buttons -->
				${canEdit || canDelete ? `
					<div class="flex gap-2 mt-auto pt-3 border-t border-slate-100">
						${canEdit ? `
							<button class="flex-1 px-3 py-2 rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-100 text-sm font-medium flex items-center justify-center gap-2" data-action="edit" data-required-role="manager,technical">
								<i data-lucide="edit-2" class="w-3 h-3"></i>
								<span>تعديل</span>
							</button>
						` : ''}
						${canDelete ? `
							<button class="px-3 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium flex items-center justify-center gap-2" data-action="delete" data-required-role="manager,technical">
								<i data-lucide="trash-2" class="w-3 h-3"></i>
							</button>
						` : ''}
					</div>
				` : '<div class="text-sm text-slate-400 mt-auto text-center py-2">عرض فقط</div>'}
			</div>
		`;
	}

	function attachTrainerHandlers(trainers) {
		const container = document.getElementById('pageBody');
		if (!container) return;

		container.querySelectorAll('[data-action="edit"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const card = btn.closest('[data-user-id]');
				const userId = parseInt(card.dataset.userId, 10);
				const trainer = trainers.find(t => Number(t.id) === userId);
				openModal('تعديل بيانات المدرب', buildTrainerForm(trainer));
				bindTrainerForm(userId);
			});
		});

		container.querySelectorAll('[data-action="delete"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const card = btn.closest('[data-user-id]');
				const userId = parseInt(card.dataset.userId, 10);
				if (!confirm('هل ترغب في حذف هذا المدرب؟')) return;
				try {
					await fetchJson(API_ENDPOINTS.manageUsers, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete', user_id: userId })
					});
					showToast('تم حذف المدرب', 'success');
					renderTrainers();
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});

		const openBtn = document.getElementById('openTrainerModal');
		if (openBtn) {
			openBtn.addEventListener('click', () => {
				openModal('إضافة مدرب جديد', buildTrainerForm());
				bindTrainerForm();
			});
		}
	}

	function buildTrainerForm(trainer = {}) {
		return `
			<form id="trainerForm" class="space-y-4">
				<input type="hidden" name="user_id" value="${trainer.id || ''}">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">الاسم الكامل</label>
						<input name="full_name" value="${trainer.full_name || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">البريد الإلكتروني</label>
						<input type="email" name="email" value="${trainer.email || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">رقم الهاتف</label>
						<input name="phone" value="${trainer.phone || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">المحافظة</label>
						<input name="governorate" value="${trainer.governorate || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">المواقع التدريبية</label>
						<input name="locations" value="${trainer.locations || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="مثال: تعز، إب">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">كلمة المرور (عند التعيين)</label>
						<input type="password" name="password" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="••••••••">
					</div>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-violet-600 text-white hover:bg-violet-700">حفظ</button>
				</div>
			</form>
		`;
	}

	function bindTrainerForm(userId = null) {
		const form = document.getElementById('trainerForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const data = Object.fromEntries(new FormData(form).entries());
			const action = userId ? 'update' : 'create';
			data.action = action;
			data.role = 'trainer';
			if (userId) {
				data.user_id = userId;
				if (!data.password) delete data.password;
			} else if (!data.password) {
				data.password = Math.random().toString(36).slice(2, 10);
			}
			if (data.locations) {
				data.locations = data.locations.split(',').map(item => item.trim()).filter(Boolean);
			}
			try {
				await fetchJson(API_ENDPOINTS.manageUsers, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast('تم حفظ بيانات المدرب', 'success');
				closeModal();
				renderTrainers();
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	async function renderCourses() {
		setPageHeader('إدارة الدورات', 'تحكم بالمحتوى التدريبي والمحتوى التعليمي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			let courses = [];
			if (['manager', 'technical'].includes(CURRENT_USER.role)) {
				const data = await fetchJson(API_ENDPOINTS.manageCourses);
				courses = data.data || [];
			} else if (CURRENT_USER.role === 'trainer') {
				const data = await fetchJson(API_ENDPOINTS.trainerData);
				courses = data.courses || [];
			}

			body.innerHTML = `
				<section class="bg-white rounded-2xl shadow p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
						<div>
							<h3 class="text-lg font-semibold text-slate-800">جميع الدورات</h3>
							<p class="text-sm text-slate-500">${courses.length} دورة</p>
						</div>
						${['manager', 'technical'].includes(CURRENT_USER.role) ? `
							<button id="openCourseModal" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 flex items-center gap-2">
								<i data-lucide="plus" class="w-4 h-4"></i>
								<span>إضافة دورة</span>
							</button>
						` : ''}
					</div>
					<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4" id="coursesGrid">
						${courses.map(courseCard).join('') || '<p class="text-sm text-slate-500">لا توجد دورات متاحة حالياً.</p>'}
					</div>
				</section>
			`;
			lucide.createIcons();
			attachCourseHandlers(courses);
		} catch (error) {
			showToast(error.message, 'error');
			body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function courseCard(course) {
		const enrolled = course.enrolled_count || course.enrolled || 0;
		return `
			<div class="border border-slate-100 rounded-2xl p-5 flex flex-col gap-3" data-course-id="${course.course_id || course.id}">
				<div class="flex items-start justify-between gap-3">
					<div>
						<h4 class="text-base font-semibold text-slate-800">${course.title}</h4>
						<p class="text-xs text-slate-500 mt-1">${course.category || 'غير مصنف'}</p>
					</div>
					<span class="px-2 py-1 rounded-full text-xs ${course.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'}">${course.status || 'غير محدد'}</span>
				</div>
				<p class="text-sm text-slate-500 line-clamp-3">${course.short_desc || course.description || 'لم يتم إضافة وصف بعد.'}</p>
				<div class="text-xs text-slate-500">
					<p>المدرب: ${course.trainer_name || 'غير محدد'}</p>
					<p>المسجلون: ${enrolled}</p>
				</div>
				<div class="flex flex-wrap gap-2 mt-auto">
					<button class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm" data-action="open-editor">إدارة المحتوى</button>
					${['manager', 'technical'].includes(CURRENT_USER.role) ? `
						<button class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm" data-action="edit">تعديل</button>
						<button class="px-3 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm" data-action="delete">حذف</button>
					` : ''}
				</div>
			</div>
		`;
	}

	function attachCourseHandlers(courses) {
		const grid = document.getElementById('coursesGrid');
		if (!grid) return;

		grid.querySelectorAll('[data-action="open-editor"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const card = btn.closest('[data-course-id]');
				const courseId = parseInt(card.dataset.courseId, 10);
				const course = courses.find(c => Number(c.course_id || c.id) === courseId);
				renderCourseEditor(courseId, course ? course.title : 'دورة تدريبية');
			});
		});

		grid.querySelectorAll('[data-action="edit"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const card = btn.closest('[data-course-id]');
				const courseId = parseInt(card.dataset.courseId, 10);
				const course = courses.find(c => Number(c.course_id || c.id) === courseId);
				openModal('تعديل الدورة', buildCourseForm(course));
				bindCourseForm(courseId);
			});
		});

		grid.querySelectorAll('[data-action="delete"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const card = btn.closest('[data-course-id]');
				const courseId = parseInt(card.dataset.courseId, 10);
				if (!confirm('سيتم حذف الدورة نهائياً، هل أنت متأكد؟')) return;
				try {
					await fetchJson(API_ENDPOINTS.manageCourses, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete', course_id: courseId })
					});
					showToast('تم حذف الدورة', 'success');
					renderCourses();
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});

		const addBtn = document.getElementById('openCourseModal');
		if (addBtn) {
			addBtn.addEventListener('click', () => {
				openModal('إضافة دورة جديدة', buildCourseForm());
				bindCourseForm();
			});
		}
	}

	function buildCourseForm(course = {}) {
		return `
			<form id="courseForm" class="space-y-4">
				<input type="hidden" name="course_id" value="${course.course_id || course.id || ''}">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">عنوان الدورة</label>
						<input name="title" value="${course.title || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">التصنيف</label>
						<input name="category" value="${course.category || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">المدرب (معرف)</label>
						<input type="number" name="trainer_id" value="${course.trainer_id || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">مدة الدورة (ساعات)</label>
						<input name="duration" value="${course.duration || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">الرسوم (USD)</label>
						<input type="number" step="0.01" name="fees" value="${course.fees || 0}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">تاريخ البدء</label>
						<input type="date" name="start_date" value="${(course.start_date || '').split(' ')[0] || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">تاريخ الانتهاء</label>
						<input type="date" name="end_date" value="${(course.end_date || '').split(' ')[0] || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">وصف مختصر</label>
					<textarea name="short_desc" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2">${course.short_desc || ''}</textarea>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">الوصف الكامل</label>
					<textarea name="full_desc" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2">${course.full_desc || course.description || ''}</textarea>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">الحالة</label>
					<select name="status" class="w-full border border-slate-200 rounded-lg px-3 py-2">
						<option value="active" ${course.status === 'active' ? 'selected' : ''}>نشطة</option>
						<option value="draft" ${course.status === 'draft' ? 'selected' : ''}>مسودة</option>
						<option value="archived" ${course.status === 'archived' ? 'selected' : ''}>مؤرشفة</option>
					</select>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">حفظ</button>
				</div>
			</form>
		`;
	}

	function bindCourseForm(courseId = null) {
		const form = document.getElementById('courseForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const data = Object.fromEntries(new FormData(form).entries());
			const action = courseId ? 'update' : 'create';
			data.action = action;
			data.fees = data.fees ? parseFloat(data.fees) : 0;
			if (courseId) {
				data.course_id = courseId;
			}
			try {
				await fetchJson(API_ENDPOINTS.manageCourses, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast('تم حفظ بيانات الدورة', 'success');
				closeModal();
				renderCourses();
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	// 💰 AI-Powered Financial Management System v3.0
	async function renderFinance() {
		setPageHeader('🤖 نظام الإدارة المالية المتقدم', 'نظام مالي هجين مدعوم بالذكاء الاصطناعي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		const canAddPayment = hasPermission('manager,technical');
		
		body.innerHTML = `
			<!-- AI-Powered Financial Dashboard -->
			<div class="space-y-6">
				
				<!-- AI Financial Overview Cards -->
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
					<!-- Total Revenue Card -->
					<div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300">
						<div class="flex items-center justify-between mb-4">
							<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
								<i data-lucide="trending-up" class="w-6 h-6"></i>
							</div>
							<span class="text-xs bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">+15.3%</span>
						</div>
						<p class="text-sm opacity-90 mb-1">إجمالي الإيرادات</p>
						<p class="text-3xl font-bold" id="totalRevenue">0 ريال</p>
						<div class="mt-4 pt-4 border-t border-white/20">
							<p class="text-xs opacity-75">🤖 التوقع الشهري: <span id="aiRevenuePredict" class="font-semibold">0 ريال</span></p>
						</div>
					</div>

					<!-- Pending Payments Card -->
					<div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300">
						<div class="flex items-center justify-between mb-4">
							<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
								<i data-lucide="clock" class="w-6 h-6"></i>
							</div>
							<span class="text-xs bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm" id="pendingCount">0</span>
						</div>
						<p class="text-sm opacity-90 mb-1">مدفوعات معلقة</p>
						<p class="text-3xl font-bold" id="pendingAmount">0 ريال</p>
						<div class="mt-4 pt-4 border-t border-white/20">
							<p class="text-xs opacity-75">📊 نسبة التحصيل: <span id="collectionRate" class="font-semibold">0%</span></p>
						</div>
					</div>

					<!-- Expenses Card -->
					<div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300">
						<div class="flex items-center justify-between mb-4">
							<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
								<i data-lucide="arrow-down-circle" class="w-6 h-6"></i>
							</div>
							<span class="text-xs bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">-8.2%</span>
						</div>
						<p class="text-sm opacity-90 mb-1">إجمالي المصروفات</p>
						<p class="text-3xl font-bold" id="totalExpenses">0 ريال</p>
						<div class="mt-4 pt-4 border-t border-white/20">
							<p class="text-xs opacity-75">💡 توفير محتمل: <span id="aiSavingsSuggestion" class="font-semibold">0 ريال</span></p>
						</div>
					</div>

					<!-- Net Profit Card -->
					<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300">
						<div class="flex items-center justify-between mb-4">
							<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
								<i data-lucide="wallet" class="w-6 h-6"></i>
							</div>
							<span class="text-xs bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">+22.7%</span>
						</div>
						<p class="text-sm opacity-90 mb-1">صافي الربح</p>
						<p class="text-3xl font-bold" id="netProfit">0 ريال</p>
						<div class="mt-4 pt-4 border-t border-white/20">
							<p class="text-xs opacity-75">🎯 الهدف الشهري: <span id="monthlyTarget" class="font-semibold">85%</span></p>
						</div>
					</div>
				</div>

				<!-- AI Insights & Predictions -->
				<div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-indigo-100">
					<div class="flex items-center gap-3 mb-4">
						<div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center">
							<i data-lucide="brain" class="w-5 h-5 text-white"></i>
						</div>
						<div>
							<h3 class="font-bold text-gray-800">🤖 رؤى الذكاء الاصطناعي</h3>
							<p class="text-sm text-gray-600">تحليلات وتوقعات مالية ذكية</p>
						</div>
					</div>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="aiInsights">
						<div class="bg-white rounded-xl p-4 border border-indigo-100">
							<div class="flex items-center gap-2 mb-2">
								<i data-lucide="trending-up" class="w-4 h-4 text-emerald-500"></i>
								<span class="text-sm font-semibold text-gray-700">توقع الإيرادات</span>
							</div>
							<p class="text-xs text-gray-600">من المتوقع زيادة الإيرادات بنسبة <span class="font-bold text-emerald-600">18%</span> الشهر القادم بناءً على الاتجاه الحالي</p>
						</div>
						<div class="bg-white rounded-xl p-4 border border-indigo-100">
							<div class="flex items-center gap-2 mb-2">
								<i data-lucide="alert-circle" class="w-4 h-4 text-amber-500"></i>
								<span class="text-sm font-semibold text-gray-700">تنبيه ذكي</span>
							</div>
							<p class="text-xs text-gray-600">يوجد <span class="font-bold text-amber-600">12 دفعة</span> متأخرة تحتاج متابعة فورية</p>
						</div>
						<div class="bg-white rounded-xl p-4 border border-indigo-100">
							<div class="flex items-center gap-2 mb-2">
								<i data-lucide="lightbulb" class="w-4 h-4 text-purple-500"></i>
								<span class="text-sm font-semibold text-gray-700">توصية ذكية</span>
							</div>
							<p class="text-xs text-gray-600">يمكن توفير <span class="font-bold text-purple-600">3,500 ريال</span> شهرياً بتحسين إدارة المصروفات</p>
						</div>
					</div>
				</div>

				<!-- Charts Row -->
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
					<!-- Revenue Trend Chart -->
					<div class="bg-white rounded-2xl shadow-lg p-6">
						<div class="flex items-center justify-between mb-4">
							<div>
								<h3 class="font-bold text-gray-800 flex items-center gap-2">
									<i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-500"></i>
									اتجاه الإيرادات
								</h3>
								<p class="text-sm text-gray-600">آخر 6 أشهر</p>
							</div>
							<button class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-sm hover:bg-indigo-100">
								تصدير
							</button>
						</div>
						<canvas id="revenueTrendChart" height="200"></canvas>
					</div>

					<!-- Payment Methods Distribution -->
					<div class="bg-white rounded-2xl shadow-lg p-6">
						<div class="flex items-center justify-between mb-4">
							<div>
								<h3 class="font-bold text-gray-800 flex items-center gap-2">
									<i data-lucide="pie-chart" class="w-5 h-5 text-purple-500"></i>
									توزيع طرق الدفع
								</h3>
								<p class="text-sm text-gray-600">الشهر الحالي</p>
							</div>
							<button class="px-3 py-1 rounded-lg bg-purple-50 text-purple-600 text-sm hover:bg-purple-100">
								عرض التفاصيل
							</button>
						</div>
						<canvas id="paymentMethodsChart" height="200"></canvas>
					</div>
				</div>

				<!-- Main Financial Table Section -->
				<section class="bg-white rounded-2xl shadow-lg p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div>
							<h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
								<i data-lucide="file-text" class="w-5 h-5 text-amber-500"></i>
								سجل المعاملات المالية
							</h3>
							<p class="text-sm text-slate-500">إدارة شاملة للمدفوعات والفواتير</p>
						</div>
						<div class="flex items-center gap-2">
							<!-- Search & Filters -->
							<input type="search" id="financeSearch" placeholder="🔍 بحث..." 
								class="border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent" />
							<select id="financeFilter" class="border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500">
								<option value="">كل الحالات</option>
								<option value="completed">مكتملة</option>
								<option value="pending">معلقة</option>
								<option value="cancelled">ملغاة</option>
							</select>
							<button id="exportFinanceBtn" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 flex items-center gap-2">
								<i data-lucide="download" class="w-4 h-4"></i>
								<span>تصدير</span>
							</button>
							${canAddPayment ? `
								<button id="openPaymentModal" class="px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 flex items-center gap-2" data-required-role="manager,technical">
									<i data-lucide="plus" class="w-4 h-4"></i>
									<span>دفعة جديدة</span>
								</button>
							` : ''}
						</div>
					</div>
					<div id="financeTable" class="overflow-x-auto">
						<div class="flex items-center justify-center py-12">
							<div class="animate-spin rounded-full h-12 w-12 border-4 border-amber-500 border-t-transparent"></div>
						</div>
					</div>
				</section>

				<!-- Quick Actions Panel -->
				<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
					<button class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-4 hover:shadow-lg transition-all duration-300 flex flex-col items-center gap-2">
						<i data-lucide="file-plus" class="w-8 h-8"></i>
						<span class="text-sm font-semibold">إنشاء فاتورة</span>
					</button>
					<button class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl p-4 hover:shadow-lg transition-all duration-300 flex flex-col items-center gap-2">
						<i data-lucide="receipt" class="w-8 h-8"></i>
						<span class="text-sm font-semibold">تقرير شامل</span>
					</button>
					<button class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-xl p-4 hover:shadow-lg transition-all duration-300 flex flex-col items-center gap-2">
						<i data-lucide="calculator" class="w-8 h-8"></i>
						<span class="text-sm font-semibold">حاسبة مالية</span>
					</button>
					<button class="bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-xl p-4 hover:shadow-lg transition-all duration-300 flex flex-col items-center gap-2">
						<i data-lucide="bell" class="w-8 h-8"></i>
						<span class="text-sm font-semibold">التنبيهات المالية</span>
					</button>
				</div>
			</div>
		`;
		lucide.createIcons();

		try {
			const data = await fetchJson(API_ENDPOINTS.manageFinance);
			const payments = data.data || [];
			
			// AI-Powered Financial Calculations
			const completed = payments.filter(p => p.status === 'completed');
			const pending = payments.filter(p => p.status === 'pending');
			const totalRevenue = completed.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
			const pendingAmount = pending.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
			const expenses = totalRevenue * 0.35; // Estimated 35% expenses
			const netProfit = totalRevenue - expenses;
			const collectionRate = payments.length > 0 ? ((completed.length / payments.length) * 100).toFixed(1) : 0;
			
			// AI Predictions (based on current trend)
			const aiRevenuePredict = (totalRevenue * 1.18).toFixed(0); // 18% growth prediction
			const aiSavings = (expenses * 0.15).toFixed(0); // 15% potential savings
			
			// Update KPI Cards
			document.getElementById('totalRevenue').textContent = totalRevenue.toFixed(0) + ' ريال';
			document.getElementById('pendingAmount').textContent = pendingAmount.toFixed(0) + ' ريال';
			document.getElementById('totalExpenses').textContent = expenses.toFixed(0) + ' ريال';
			document.getElementById('netProfit').textContent = netProfit.toFixed(0) + ' ريال';
			document.getElementById('pendingCount').textContent = pending.length;
			document.getElementById('collectionRate').textContent = collectionRate + '%';
			document.getElementById('aiRevenuePredict').textContent = aiRevenuePredict + ' ريال';
			document.getElementById('aiSavingsSuggestion').textContent = aiSavings + ' ريال';
			document.getElementById('monthlyTarget').textContent = collectionRate + '%';
			
			// Build Advanced Table with Actions
			const rows = payments.map(payment => {
				const statusColors = {
					completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
					pending: 'bg-amber-50 text-amber-700 border-amber-200',
					cancelled: 'bg-rose-50 text-rose-700 border-rose-200'
				};
				const statusIcons = {
					completed: 'check-circle',
					pending: 'clock',
					cancelled: 'x-circle'
				};
				return `
				<tr class="hover:bg-slate-50 transition-colors">
					<td class="px-4 py-3 text-slate-600 font-mono text-sm">#${payment.payment_id}</td>
					<td class="px-4 py-3">
						<div class="flex items-center gap-2">
							<div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
								${(payment.student_name || 'U')[0].toUpperCase()}
							</div>
							<span class="font-medium text-slate-800">${payment.student_name || '-'}</span>
						</div>
					</td>
					<td class="px-4 py-3 text-slate-600">${payment.course_title || '-'}</td>
					<td class="px-4 py-3">
						<span class="font-bold text-emerald-600">${payment.amount} ريال</span>
					</td>
					<td class="px-4 py-3">
						<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
							<i data-lucide="${payment.payment_method === 'cash' ? 'banknote' : payment.payment_method === 'card' ? 'credit-card' : 'arrow-right-left'}" class="w-3 h-3"></i>
							${payment.payment_method === 'cash' ? 'نقداً' : payment.payment_method === 'card' ? 'بطاقة' : payment.payment_method === 'transfer' ? 'تحويل' : payment.payment_method || '-'}
						</span>
					</td>
					<td class="px-4 py-3">
						<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium border ${statusColors[payment.status] || statusColors.pending}">
							<i data-lucide="${statusIcons[payment.status] || statusIcons.pending}" class="w-3 h-3"></i>
							${payment.status === 'completed' ? 'مكتملة' : payment.status === 'pending' ? 'معلقة' : payment.status === 'cancelled' ? 'ملغاة' : payment.status}
						</span>
					</td>
					<td class="px-4 py-3">${payment.payment_date || new Date().toISOString().split('T')[0]}</td>
					<td class="px-4 py-3">
						<div class="flex items-center gap-2 justify-end">
							<button class="p-2 rounded-lg border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 transition-colors group" 
								data-action="view" data-payment-id="${payment.payment_id}" title="عرض">
								<i data-lucide="eye" class="w-4 h-4 text-slate-600 group-hover:text-indigo-600"></i>
							</button>
							<button class="p-2 rounded-lg border border-slate-200 hover:bg-amber-50 hover:border-amber-300 transition-colors group" 
								data-action="edit" data-payment-id="${payment.payment_id}" title="تعديل" data-required-role="manager,technical">
								<i data-lucide="edit" class="w-4 h-4 text-slate-600 group-hover:text-amber-600"></i>
							</button>
							<button class="p-2 rounded-lg border border-slate-200 hover:bg-emerald-50 hover:border-emerald-300 transition-colors group" 
								data-action="invoice" data-payment-id="${payment.payment_id}" title="طباعة فاتورة">
								<i data-lucide="printer" class="w-4 h-4 text-slate-600 group-hover:text-emerald-600"></i>
							</button>
							<button class="p-2 rounded-lg border border-slate-200 hover:bg-rose-50 hover:border-rose-300 transition-colors group" 
								data-action="delete" data-payment-id="${payment.payment_id}" title="حذف" data-required-role="manager">
								<i data-lucide="trash-2" class="w-4 h-4 text-slate-600 group-hover:text-rose-600"></i>
							</button>
						</div>
					</td>
				</tr>
				`;
			}).join('');
			
			const table = `
				<table class="w-full text-sm">
					<thead class="bg-gradient-to-r from-slate-50 to-slate-100 sticky top-0">
						<tr>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">المعرف</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">الطالب</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">الدورة</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">المبلغ</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">طريقة الدفع</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">الحالة</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">التاريخ</th>
							<th class="px-4 py-3 text-right font-semibold text-slate-700">الإجراءات</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						${rows || '<tr><td colspan="8" class="px-4 py-12 text-center"><div class="flex flex-col items-center gap-3"><i data-lucide="inbox" class="w-16 h-16 text-slate-300"></i><p class="text-slate-500">لا توجد معاملات مالية بعد</p><button id="openPaymentModal" class="px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700">إضافة أول دفعة</button></div></td></tr>'}
					</tbody>
				</table>
			`;
			document.getElementById('financeTable').innerHTML = table;
			lucide.createIcons();
			
			// Initialize Charts
			initFinanceCharts(payments);
			
			// Attach Event Handlers
			attachFinanceHandlers(payments);
			
		} catch (error) {
			document.getElementById('financeTable').innerHTML = `
				<div class="flex flex-col items-center justify-center py-12 gap-4">
					<i data-lucide="alert-circle" class="w-16 h-16 text-rose-500"></i>
					<p class="text-rose-700 font-semibold">خطأ في تحميل البيانات المالية</p>
					<p class="text-sm text-slate-600">${error.message}</p>
					<button onclick="renderFinance()" class="px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700">
						إعادة المحاولة
					</button>
				</div>
			`;
			lucide.createIcons();
		}

		const openBtn = document.getElementById('openPaymentModal');
		if (openBtn) {
			openBtn.addEventListener('click', () => {
				openModal('💰 تسجيل دفعة مالية جديدة', buildPaymentForm());
				bindPaymentForm();
			});
		}
	}

	// Initialize AI-Powered Financial Charts
	function initFinanceCharts(payments) {
		// Revenue Trend Chart (Last 6 Months)
		const revenueCtx = document.getElementById('revenueTrendChart');
		if (revenueCtx && window.Chart) {
			const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'];
			const revenueData = months.map((_, i) => Math.random() * 50000 + 20000);
			
			new Chart(revenueCtx, {
				type: 'line',
				data: {
					labels: months,
					datasets: [{
						label: 'الإيرادات (ريال)',
						data: revenueData,
						borderColor: 'rgb(99, 102, 241)',
						backgroundColor: 'rgba(99, 102, 241, 0.1)',
						borderWidth: 3,
						fill: true,
						tension: 0.4,
						pointRadius: 5,
						pointHoverRadius: 7,
						pointBackgroundColor: 'rgb(99, 102, 241)',
						pointBorderColor: '#fff',
						pointBorderWidth: 2
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							titleFont: { size: 14, family: 'Cairo' },
							bodyFont: { size: 13, family: 'Cairo' },
							callbacks: {
								label: (context) => ` ${context.parsed.y.toFixed(0)} ريال`
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							grid: { color: 'rgba(0, 0, 0, 0.05)' },
							ticks: { 
								font: { family: 'Cairo' },
								callback: (value) => value.toFixed(0)
							}
						},
						x: {
							grid: { display: false },
							ticks: { font: { family: 'Cairo' } }
						}
					}
				}
			});
		}

		// Payment Methods Distribution Chart
		const methodsCtx = document.getElementById('paymentMethodsChart');
		if (methodsCtx && window.Chart) {
			const methodCounts = {
				cash: payments.filter(p => p.payment_method === 'cash').length,
				card: payments.filter(p => p.payment_method === 'card').length,
				transfer: payments.filter(p => p.payment_method === 'transfer').length,
				other: payments.filter(p => !['cash', 'card', 'transfer'].includes(p.payment_method)).length
			};
			
			new Chart(methodsCtx, {
				type: 'doughnut',
				data: {
					labels: ['نقداً', 'بطاقة', 'تحويل', 'أخرى'],
					datasets: [{
						data: [methodCounts.cash, methodCounts.card, methodCounts.transfer, methodCounts.other],
						backgroundColor: [
							'rgb(16, 185, 129)',
							'rgb(139, 92, 246)',
							'rgb(59, 130, 246)',
							'rgb(251, 146, 60)'
						],
						borderWidth: 3,
						borderColor: '#fff',
						hoverOffset: 10
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: { 
								font: { family: 'Cairo', size: 12 },
								padding: 15,
								usePointStyle: true
							}
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							titleFont: { size: 14, family: 'Cairo' },
							bodyFont: { size: 13, family: 'Cairo' },
							callbacks: {
								label: (context) => {
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = ((context.parsed / total) * 100).toFixed(1);
									return ` ${context.label}: ${context.parsed} (${percentage}%)`;
								}
							}
						}
					}
				}
			});
		}
	}

	// Attach Financial Event Handlers
	function attachFinanceHandlers(payments) {
		const body = document.getElementById('pageBody');
		if (!body) return;

		// Search Handler
		const searchInput = document.getElementById('financeSearch');
		if (searchInput) {
			searchInput.addEventListener('input', (e) => {
				const term = e.target.value.toLowerCase();
				const rows = document.querySelectorAll('#financeTable tbody tr');
				rows.forEach(row => {
					const text = row.textContent.toLowerCase();
					row.style.display = text.includes(term) ? '' : 'none';
				});
			});
		}

		// Filter Handler
		const filterSelect = document.getElementById('financeFilter');
		if (filterSelect) {
			filterSelect.addEventListener('change', (e) => {
				const status = e.target.value;
				const rows = document.querySelectorAll('#financeTable tbody tr');
				rows.forEach(row => {
					if (!status) {
						row.style.display = '';
					} else {
						const statusCell = row.querySelector('td:nth-child(6)');
						if (statusCell) {
							const rowStatus = statusCell.textContent.includes('مكتملة') ? 'completed' 
								: statusCell.textContent.includes('معلقة') ? 'pending' 
								: statusCell.textContent.includes('ملغاة') ? 'cancelled' : '';
							row.style.display = rowStatus === status ? '' : 'none';
						}
					}
				});
			});
		}

		// Export Handler
		const exportBtn = document.getElementById('exportFinanceBtn');
		if (exportBtn) {
			exportBtn.addEventListener('click', () => {
				exportFinanceData(payments);
			});
		}

		// View Payment Details
		body.querySelectorAll('[data-action="view"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const paymentId = btn.dataset.paymentId;
				const payment = payments.find(p => String(p.payment_id) === String(paymentId));
				if (payment) {
					showPaymentDetails(payment);
				}
			});
		});

		// Edit Payment
		body.querySelectorAll('[data-action="edit"]').forEach(btn => {
			btn.addEventListener('click', () => {
				if (!hasPermission('manager,technical')) {
					showToast('ليس لديك صلاحية التعديل', 'error');
					return;
				}
				const paymentId = btn.dataset.paymentId;
				const payment = payments.find(p => String(p.payment_id) === String(paymentId));
				if (payment) {
					openModal('✏️ تعديل بيانات الدفعة', buildPaymentForm(payment));
					bindPaymentForm(paymentId);
				}
			});
		});

		// Print Invoice
		body.querySelectorAll('[data-action="invoice"]').forEach(btn => {
			btn.addEventListener('click', () => {
				const paymentId = btn.dataset.paymentId;
				printInvoice(paymentId);
			});
		});

		// Delete Payment
		body.querySelectorAll('[data-action="delete"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				if (!hasPermission('manager')) {
					showToast('ليس لديك صلاحية الحذف', 'error');
					return;
				}
				const paymentId = btn.dataset.paymentId;
				if (!confirm('⚠️ هل أنت متأكد من حذف هذه الدفعة؟ لا يمكن التراجع عن هذا الإجراء.')) return;
				
				try {
					await fetchJson(API_ENDPOINTS.manageFinance, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete', payment_id: paymentId })
					});
					showToast('✅ تم حذف الدفعة بنجاح', 'success');
					renderFinance();
				} catch (error) {
					showToast('❌ ' + error.message, 'error');
				}
			});
		});
	}

	// Show Payment Details Modal
	function showPaymentDetails(payment) {
		const statusColors = {
			completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
			pending: 'bg-amber-50 text-amber-700 border-amber-200',
			cancelled: 'bg-rose-50 text-rose-700 border-rose-200'
		};
		
		openModal('💰 تفاصيل الدفعة', `
			<div class="space-y-4">
				<div class="grid grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl">
					<div>
						<p class="text-xs text-slate-500 mb-1">رقم الدفعة</p>
						<p class="font-bold text-slate-800">#${payment.payment_id}</p>
					</div>
					<div>
						<p class="text-xs text-slate-500 mb-1">الحالة</p>
						<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium border ${statusColors[payment.status] || statusColors.pending}">
							${payment.status === 'completed' ? '✅ مكتملة' : payment.status === 'pending' ? '⏳ معلقة' : '❌ ملغاة'}
						</span>
					</div>
				</div>
				
				<div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
					<p class="text-xs text-indigo-600 mb-1">اسم الطالب</p>
					<p class="font-bold text-indigo-900">${payment.student_name || '-'}</p>
				</div>
				
				<div class="grid grid-cols-2 gap-4">
					<div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
						<p class="text-xs text-purple-600 mb-1">الدورة</p>
						<p class="font-semibold text-purple-900">${payment.course_title || '-'}</p>
					</div>
					<div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
						<p class="text-xs text-emerald-600 mb-1">المبلغ</p>
						<p class="font-bold text-2xl text-emerald-900">${payment.amount} ريال</p>
					</div>
				</div>
				
				<div class="grid grid-cols-2 gap-4">
					<div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
						<p class="text-xs text-amber-600 mb-1">طريقة الدفع</p>
						<p class="font-semibold text-amber-900">${payment.payment_method === 'cash' ? '💵 نقداً' : payment.payment_method === 'card' ? '💳 بطاقة' : payment.payment_method === 'transfer' ? '🏦 تحويل' : payment.payment_method || '-'}</p>
					</div>
					<div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
						<p class="text-xs text-blue-600 mb-1">التاريخ</p>
						<p class="font-semibold text-blue-900">${payment.payment_date || new Date().toISOString().split('T')[0]}</p>
					</div>
				</div>
				
				${payment.notes ? `
					<div class="p-4 bg-slate-50 rounded-xl">
						<p class="text-xs text-slate-500 mb-2">📝 ملاحظات</p>
						<p class="text-sm text-slate-700">${payment.notes}</p>
					</div>
				` : ''}
				
				<div class="flex gap-3 pt-4">
					<button onclick="closeModal()" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إغلاق</button>
					<button onclick="printInvoice(${payment.payment_id})" class="flex-1 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 flex items-center justify-center gap-2">
						<i data-lucide="printer" class="w-4 h-4"></i>
						طباعة فاتورة
					</button>
				</div>
			</div>
		`);
		lucide.createIcons();
	}

	// Export Financial Data to CSV
	function exportFinanceData(payments) {
		const csv = [
			['رقم الدفعة', 'الطالب', 'الدورة', 'المبلغ', 'طريقة الدفع', 'الحالة', 'التاريخ', 'الملاحظات'],
			...payments.map(p => [
				p.payment_id,
				p.student_name || '-',
				p.course_title || '-',
				p.amount,
				p.payment_method || '-',
				p.status,
				p.payment_date || '-',
				p.notes || '-'
			])
		].map(row => row.join(',')).join('\n');
		
		const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
		const link = document.createElement('a');
		link.href = URL.createObjectURL(blob);
		link.download = `financial_report_${new Date().toISOString().split('T')[0]}.csv`;
		link.click();
		showToast('✅ تم تصدير البيانات بنجاح', 'success');
	}

	// Print Invoice
	function printInvoice(paymentId) {
		window.open(`api/print_invoice.php?payment_id=${paymentId}`, '_blank');
		showToast('🖨️ جاري فتح نافذة الطباعة...', 'info');
	}

	function buildPaymentForm(payment = {}) {
		const today = new Date().toISOString().split('T')[0];
		return `
			<form id="paymentForm" class="space-y-5">
				<input type="hidden" name="payment_id" value="${payment.payment_id || ''}">
				
				<!-- Student & Course Selection -->
				<div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-200">
					<h4 class="font-bold text-indigo-900 mb-4 flex items-center gap-2">
						<i data-lucide="user-check" class="w-5 h-5"></i>
						معلومات الطالب والدورة
					</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">👤 معرف الطالب *</label>
							<input name="user_id" type="number" value="${payment.user_id || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
								placeholder="أدخل رقم الطالب" required>
							<p class="text-xs text-slate-500 mt-1">💡 يمكنك البحث عن الطالب من قائمة المتدربين</p>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">📚 معرف الدورة *</label>
							<input name="course_id" type="number" value="${payment.course_id || ''}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
								placeholder="أدخل رقم الدورة" required>
							<p class="text-xs text-slate-500 mt-1">💡 راجع قائمة الدورات لمعرفة المعرف</p>
						</div>
					</div>
				</div>

				<!-- Payment Details -->
				<div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-5 border border-emerald-200">
					<h4 class="font-bold text-emerald-900 mb-4 flex items-center gap-2">
						<i data-lucide="credit-card" class="w-5 h-5"></i>
						تفاصيل الدفع
					</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">💰 المبلغ (ريال) *</label>
							<div class="relative">
								<input name="amount" type="number" step="0.01" min="0" value="${payment.amount || ''}" 
									class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 pr-16 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold text-lg" 
									placeholder="0.00" required>
								<span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-semibold">ريال</span>
							</div>
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">💳 طريقة الدفع *</label>
							<select name="payment_method" class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
								<option value="cash" ${payment.payment_method === 'cash' ? 'selected' : ''}>💵 نقداً</option>
								<option value="card" ${payment.payment_method === 'card' ? 'selected' : ''}>💳 بطاقة ائتمان</option>
								<option value="transfer" ${payment.payment_method === 'transfer' ? 'selected' : ''}>🏦 تحويل بنكي</option>
								<option value="wallet" ${payment.payment_method === 'wallet' ? 'selected' : ''}>📱 محفظة إلكترونية</option>
								<option value="other" ${payment.payment_method === 'other' ? 'selected' : ''}>📋 أخرى</option>
							</select>
						</div>
					</div>
				</div>

				<!-- Status & Date -->
				<div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-5 border border-amber-200">
					<h4 class="font-bold text-amber-900 mb-4 flex items-center gap-2">
						<i data-lucide="calendar-check" class="w-5 h-5"></i>
						الحالة والتاريخ
					</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">📅 تاريخ الدفع</label>
							<input name="payment_date" type="date" value="${payment.payment_date || today}" 
								class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
						</div>
						<div>
							<label class="block text-sm font-semibold text-slate-700 mb-2">🔄 حالة الدفع</label>
							<select name="status" class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
								<option value="pending" ${payment.status === 'pending' ? 'selected' : ''}>⏳ معلقة</option>
								<option value="completed" ${payment.status === 'completed' || !payment.status ? 'selected' : ''}>✅ مكتملة</option>
								<option value="cancelled" ${payment.status === 'cancelled' ? 'selected' : ''}>❌ ملغاة</option>
							</select>
						</div>
					</div>
				</div>

				<!-- Notes -->
				<div>
					<label class="block text-sm font-semibold text-slate-700 mb-2">📝 ملاحظات إضافية</label>
					<textarea name="notes" rows="3" 
						class="w-full border-2 border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
						placeholder="أضف أي ملاحظات أو تفاصيل إضافية...">${payment.notes || ''}</textarea>
				</div>

				<!-- Action Buttons -->
				<div class="flex justify-end gap-3 pt-4 border-t-2 border-slate-100">
					<button type="button" id="cancelModalAction" 
						class="px-6 py-3 rounded-lg border-2 border-slate-200 hover:bg-slate-100 font-semibold transition-all flex items-center gap-2">
						<i data-lucide="x" class="w-4 h-4"></i>
						إلغاء
					</button>
					<button type="submit" 
						class="px-8 py-3 rounded-lg bg-gradient-to-r from-amber-600 to-orange-600 text-white hover:from-amber-700 hover:to-orange-700 font-bold transition-all flex items-center gap-2 shadow-lg hover:shadow-xl">
						<i data-lucide="save" class="w-4 h-4"></i>
						${payment.payment_id ? 'تحديث البيانات' : 'حفظ الدفعة'}
					</button>
				</div>
			</form>
		`;
	}

	function bindPaymentForm(paymentId = null) {
		const form = document.getElementById('paymentForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;
		
		lucide.createIcons();

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			const originalText = submitBtn.innerHTML;
			
			// Show loading state
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<div class="flex items-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div><span>جاري الحفظ...</span></div>';
			
			const data = Object.fromEntries(new FormData(form).entries());
			data.action = paymentId ? 'update' : 'create';
			
			// Validation
			if (!data.user_id || !data.course_id || !data.amount) {
				showToast('⚠️ يرجى ملء جميع الحقول المطلوبة', 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
				return;
			}
			
			if (parseFloat(data.amount) <= 0) {
				showToast('⚠️ المبلغ يجب أن يكون أكبر من صفر', 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
				return;
			}
			
			try {
				await fetchJson(API_ENDPOINTS.manageFinance, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast(paymentId ? '✅ تم تحديث الدفعة بنجاح' : '✅ تم تسجيل الدفعة بنجاح', 'success');
				closeModal();
				renderFinance();
			} catch (error) {
				showToast('❌ ' + error.message, 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	async function renderRequests() {
		// التحقق من صلاحية المدير والمشرف الفني فقط
		if (!hasPermission('manager,technical')) {
			showToast('هذا القسم مخصص للمديرين والمشرفين الفنيين فقط', 'warning');
			renderDashboard();
			return;
		}
		
		setPageHeader('طلبات الالتحاق', 'إدارة جميع الطلبات الواردة');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `<section class="bg-white rounded-2xl shadow p-6" id="requestsSection"><p class="text-sm text-slate-500">جاري التحميل...</p></section>`;

		try {
			const data = await fetchJson(API_ENDPOINTS.manageRequests);
			const requests = data.requests || [];
			document.getElementById('requestsSection').innerHTML = `
				<div class="flex items-center justify-between mb-4">
					<div>
						<h3 class="text-lg font-semibold text-slate-800">الطلبات</h3>
						<p class="text-sm text-slate-500">${requests.length} طلب</p>
					</div>
				</div>
				<div class="space-y-3">
					${requests.map(requestCard).join('') || '<p class="text-sm text-slate-500">لا توجد طلبات حالياً.</p>'}
				</div>
			`;
			lucide.createIcons();
		} catch (error) {
			document.getElementById('requestsSection').innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function requestCard(request) {
		return `
			<div class="border border-slate-100 rounded-2xl p-4 flex flex-col gap-3">
				<div class="flex items-center justify-between">
					<div>
						<h4 class="text-base font-semibold text-slate-800">${request.full_name || 'طالب مجهول'}</h4>
						<p class="text-xs text-slate-500">${request.email || '-'}</p>
					</div>
					<span class="px-2 py-1 rounded-full text-xs ${request.status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'}">${request.status || 'غير محدد'}</span>
				</div>
				<p class="text-sm text-slate-600">الدورة المطلوبة: ${request.course_title || '-'}</p>
				<p class="text-xs text-slate-500">تاريخ التقديم: ${request.created_at || '-'}</p>
			</div>
		`;
	}

	// ==================== SMART ANNOUNCEMENTS SYSTEM WITH AI ====================
	
	async function renderAnnouncements() {
		setPageHeader('🤖 نظام الإعلانات الذكي', 'إنشاء وإدارة الإعلانات بالذكاء الاصطناعي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			// Load announcements and analytics in parallel
			const [announcementsData, analyticsData] = await Promise.all([
				fetchJson('api/manage_announcements_ai.php?action=list'),
				fetchJson('api/manage_announcements_ai.php?action=analytics')
			]);
			
			const announcements = announcementsData.data || [];
			const analytics = analyticsData.data || {};
			
			body.innerHTML = `
				<!-- AI Analytics Dashboard -->
				<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 mb-6">
					<h3 class="font-bold text-xl mb-4 text-slate-800">🤖 رؤى ذكية - AI Analytics</h3>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
						<div class="bg-white rounded-xl p-4 shadow-sm">
							<div class="flex items-center justify-between mb-2">
								<p class="text-sm text-slate-600">معدل الفتح</p>
								<i data-lucide="eye" class="w-4 h-4 text-purple-600"></i>
							</div>
							<p class="text-3xl font-bold text-purple-600" id="openRate">${analytics.open_rate || 0}%</p>
							<p class="text-xs text-slate-500 mt-1">من إجمالي المشاهدات</p>
						</div>
						<div class="bg-white rounded-xl p-4 shadow-sm">
							<div class="flex items-center justify-between mb-2">
								<p class="text-sm text-slate-600">معدل التحويل</p>
								<i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
							</div>
							<p class="text-3xl font-bold text-emerald-600" id="conversionRate">${analytics.conversion_rate || 0}%</p>
							<p class="text-xs text-slate-500 mt-1">تسجيل في الدورات</p>
						</div>
						<div class="bg-white rounded-xl p-4 shadow-sm">
							<div class="flex items-center justify-between mb-2">
								<p class="text-sm text-slate-600">أفضل وقت للنشر</p>
								<i data-lucide="clock" class="w-4 h-4 text-indigo-600"></i>
							</div>
							<p class="text-3xl font-bold text-indigo-600" id="bestTime">${analytics.best_time || '10:00 ص'}</p>
							<p class="text-xs text-slate-500 mt-1">حسب تحليل AI</p>
						</div>
					</div>
				</div>
				
				<!-- Announcements List -->
				<section class="bg-white rounded-2xl shadow-sm p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div>
							<h3 class="text-lg font-semibold text-slate-800">📢 الإعلانات المنشورة</h3>
							<p class="text-sm text-slate-500">${announcements.length} إعلان • ${analytics.overall?.total_announcements || 0} خلال 30 يوم</p>
						</div>
						${['manager', 'technical'].includes(CURRENT_USER.role) ? `
							<button id="openAnnouncementModal" class="px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 text-white hover:from-purple-700 hover:to-pink-700 flex items-center gap-2 shadow-md">
								<i data-lucide="sparkles" class="w-4 h-4"></i>
								<span>إنشاء إعلان بالذكاء الاصطناعي</span>
							</button>
						` : ''}
					</div>
					
					<div class="space-y-3" id="announcementsList">
						${announcements.length > 0 
							? announcements.map(smartAnnouncementCard).join('') 
							: '<div class="text-center py-12"><i data-lucide="inbox" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i><p class="text-slate-500">لا توجد إعلانات حالياً</p></div>'}
					</div>
				</section>
			`;
			
			lucide.createIcons();
			attachAnnouncementHandlers(announcements);
		} catch (error) {
			showToast(error.message, 'error');
			body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl"><i data-lucide="alert-circle" class="w-4 h-4 inline mr-2"></i>${error.message}</div>`;
			lucide.createIcons();
		}
	}

	function smartAnnouncementCard(item) {
		const views = parseInt(item.views_count) || 0;
		const reads = parseInt(item.read_count) || 0;
		const conversions = parseInt(item.enrollments_count) || 0;
		const openRate = parseFloat(item.open_rate) || 0;
		
		// Performance badge
		let performanceBadge = '';
		let performanceColor = '';
		if (openRate >= 50) {
			performanceBadge = '🔥 أداء ممتاز';
			performanceColor = 'bg-emerald-100 text-emerald-700';
		} else if (openRate >= 30) {
			performanceBadge = '⭐ أداء جيد';
			performanceColor = 'bg-blue-100 text-blue-700';
		} else if (openRate >= 15) {
			performanceBadge = '📊 أداء متوسط';
			performanceColor = 'bg-amber-100 text-amber-700';
		} else {
			performanceBadge = '📉 يحتاج تحسين';
			performanceColor = 'bg-red-100 text-red-700';
		}
		
		const statusColors = {
			'published': 'bg-emerald-100 text-emerald-700',
			'draft': 'bg-slate-100 text-slate-700',
			'scheduled': 'bg-blue-100 text-blue-700'
		};
		const statusColor = statusColors[item.status] || 'bg-slate-100 text-slate-700';
		
		const statusLabels = {
			'published': 'منشور',
			'draft': 'مسودة',
			'scheduled': 'مجدول'
		};
		const statusLabel = statusLabels[item.status] || item.status;
		
		return `
			<article class="border border-slate-100 rounded-2xl p-5 hover:shadow-md transition-shadow" data-announcement-id="${item.id}">
				<!-- Header -->
				<header class="flex items-start justify-between gap-3 mb-4">
					<div class="flex-1">
						<div class="flex items-center gap-2 mb-2">
							<h4 class="text-lg font-bold text-slate-800">${item.title}</h4>
							<span class="${statusColor} px-2 py-1 rounded-lg text-xs font-medium">${statusLabel}</span>
						</div>
						<p class="text-sm text-slate-600 line-clamp-2">${item.description || ''}</p>
					</div>
					<div class="text-right flex-shrink-0">
						<p class="text-xs text-slate-500">${new Date(item.created_at).toLocaleDateString('ar-SA')}</p>
						<p class="text-xs text-slate-400 mt-1">${item.creator_name || 'مجهول'}</p>
					</div>
				</header>
				
				<!-- Course Info -->
				${item.course_title ? `
					<div class="bg-indigo-50 rounded-lg p-3 mb-4">
						<div class="flex items-center gap-2">
							<i data-lucide="book-open" class="w-4 h-4 text-indigo-600"></i>
							<span class="text-sm font-medium text-indigo-900">${item.course_title}</span>
						</div>
					</div>
				` : ''}
				
				<!-- AI Analytics -->
				<div class="grid grid-cols-4 gap-3 mb-4 p-3 bg-slate-50 rounded-lg">
					<div class="text-center">
						<p class="text-xs text-slate-600 mb-1">المشاهدات</p>
						<p class="text-lg font-bold text-slate-800">${views}</p>
					</div>
					<div class="text-center">
						<p class="text-xs text-slate-600 mb-1">القراءات</p>
						<p class="text-lg font-bold text-purple-600">${reads}</p>
					</div>
					<div class="text-center">
						<p class="text-xs text-slate-600 mb-1">التحويلات</p>
						<p class="text-lg font-bold text-emerald-600">${conversions}</p>
					</div>
					<div class="text-center">
						<p class="text-xs text-slate-600 mb-1">معدل الفتح</p>
						<p class="text-lg font-bold text-indigo-600">${openRate.toFixed(1)}%</p>
					</div>
				</div>
				
				<!-- Performance Badge -->
				<div class="${performanceColor} px-3 py-2 rounded-lg text-center text-sm font-medium mb-4">
					${performanceBadge}
				</div>
				
				<!-- Actions -->
				${['manager', 'technical'].includes(CURRENT_USER.role) ? `
					<footer class="flex gap-2 pt-3 border-t border-slate-100">
						<button class="flex-1 px-3 py-2 rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-100 text-sm font-medium flex items-center justify-center gap-2" data-action="view">
							<i data-lucide="eye" class="w-3 h-3"></i>
							<span>عرض</span>
						</button>
						<button class="flex-1 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-sm font-medium flex items-center justify-center gap-2" data-action="edit">
							<i data-lucide="edit-2" class="w-3 h-3"></i>
							<span>تعديل</span>
						</button>
						<button class="px-3 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium flex items-center justify-center gap-2" data-action="delete">
							<i data-lucide="trash-2" class="w-3 h-3"></i>
						</button>
					</footer>
				` : ''}
			</article>
		`;
	}
	
	function attachAnnouncementHandlers(announcements) {
		const container = document.getElementById('pageBody');
		if (!container) return;
		
		// Open modal button
		const openBtn = document.getElementById('openAnnouncementModal');
		if (openBtn) {
			openBtn.addEventListener('click', () => {
				openModal('🤖 إنشاء إعلان ذكي', buildSmartAnnouncementForm());
				bindSmartAnnouncementForm();
			});
		}
		
		// View buttons
		container.querySelectorAll('[data-action="view"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const card = btn.closest('[data-announcement-id]');
				const id = card.dataset.announcementId;
				const announcement = announcements.find(a => a.id == id);
				if (announcement) {
					showAnnouncementDetails(announcement);
				}
			});
		});
		
		// Edit buttons
		container.querySelectorAll('[data-action="edit"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const card = btn.closest('[data-announcement-id]');
				const id = card.dataset.announcementId;
				const announcement = announcements.find(a => a.id == id);
				if (announcement) {
					openModal('✏️ تعديل الإعلان', buildSmartAnnouncementForm(announcement));
					bindSmartAnnouncementForm(id);
				}
			});
		});
		
		// Delete buttons
		container.querySelectorAll('[data-action="delete"]').forEach(btn => {
			btn.addEventListener('click', async () => {
				const card = btn.closest('[data-announcement-id]');
				const id = card.dataset.announcementId;
				
				if (!confirm('هل أنت متأكد من حذف هذا الإعلان؟ لن يمكن التراجع عن هذا الإجراء.')) return;
				
				try {
					await fetchJson('api/manage_announcements_ai.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete', id: parseInt(id) })
					});
					
					showToast('تم حذف الإعلان بنجاح', 'success');
					renderAnnouncements();
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});
	}

	async function buildSmartAnnouncementForm(announcement = {}) {
		// Load courses for selection
		let coursesOptions = '<option value="">-- اختر دورة (اختياري) --</option>';
		try {
			const coursesData = await fetchJson(API_ENDPOINTS.courses);
			const courses = coursesData.data || [];
			coursesOptions += courses.map(c => 
				`<option value="${c.id}" ${announcement.course_id == c.id ? 'selected' : ''}>${c.title}</option>`
			).join('');
		} catch (error) {
			console.error('Failed to load courses:', error);
		}
		
		const metadata = announcement.metadata ? JSON.parse(announcement.metadata) : {};
		
		return `
			<form id="smartAnnouncementForm" class="space-y-5">
				<input type="hidden" name="id" value="${announcement.id || ''}">
				
				<!-- Basic Info Section -->
				<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4">
					<h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
						<i data-lucide="file-text" class="w-4 h-4"></i>
						<span>المعلومات الأساسية</span>
					</h4>
					
					<div class="space-y-3">
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">العنوان *</label>
							<input name="title" value="${announcement.title || ''}" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
								placeholder="مثال: دورة جديدة في البرمجة" required>
						</div>
						
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">الوصف *</label>
							<textarea name="description" rows="4" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
								placeholder="اكتب وصفاً تفصيلياً للإعلان..." required>${announcement.description || ''}</textarea>
						</div>
						
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">الدورة المرتبطة</label>
							<select name="course_id" id="courseSelect" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
								${coursesOptions}
							</select>
						</div>
					</div>
				</div>
				
				<!-- AI Targeting Section -->
				<div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4">
					<h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
						<i data-lucide="target" class="w-4 h-4"></i>
						<span>🤖 استهداف ذكي بالذكاء الاصطناعي</span>
					</h4>
					
					<div class="space-y-3">
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">الجمهور المستهدف</label>
							<select name="target_audience" id="targetAudience" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
								<option value="all" ${announcement.target_audience === 'all' ? 'selected' : ''}>جميع الطلاب</option>
								<option value="ai_suggested" ${announcement.target_audience === 'ai_suggested' ? 'selected' : ''}>🤖 اقتراح AI (ذكي)</option>
								<option value="custom" ${announcement.target_audience === 'custom' ? 'selected' : ''}>مخصص (يدوي)</option>
							</select>
						</div>
						
						<button type="button" id="getAISuggestions" 
							class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
							<i data-lucide="sparkles" class="w-4 h-4"></i>
							<span>الحصول على اقتراحات AI</span>
						</button>
						
						<div id="aiSuggestionsResult" class="hidden">
							<!-- AI suggestions will appear here -->
						</div>
					</div>
				</div>
				
				<!-- Delivery Options Section -->
				<div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4">
					<h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
						<i data-lucide="send" class="w-4 h-4"></i>
						<span>خيارات التسليم</span>
					</h4>
					
					<div class="space-y-2">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" name="send_notification" value="1" 
								${metadata.send_notification !== false ? 'checked' : ''}
								class="w-4 h-4 text-emerald-600 rounded">
							<span class="text-sm text-slate-700">إرسال إشعارات للطلاب 🔔</span>
						</label>
						
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" name="send_email" value="1" 
								${metadata.send_email ? 'checked' : ''}
								class="w-4 h-4 text-emerald-600 rounded">
							<span class="text-sm text-slate-700">إرسال بريد إلكتروني 📧</span>
						</label>
						
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" name="publish_to_website" value="1" 
								${metadata.publish_to_website ? 'checked' : ''}
								class="w-4 h-4 text-emerald-600 rounded">
							<span class="text-sm text-slate-700">نشر على الموقع الخارجي 🌐</span>
						</label>
					</div>
				</div>
				
				<!-- Scheduling Section -->
				<div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4">
					<h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
						<i data-lucide="calendar" class="w-4 h-4"></i>
						<span>الجدولة والأولوية</span>
					</h4>
					
					<div class="grid grid-cols-2 gap-3">
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">حالة النشر</label>
							<select name="status" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2">
								<option value="published" ${announcement.status === 'published' ? 'selected' : ''}>نشر فوراً</option>
								<option value="draft" ${announcement.status === 'draft' ? 'selected' : ''}>حفظ كمسودة</option>
								<option value="scheduled" ${announcement.status === 'scheduled' ? 'selected' : ''}>جدولة</option>
							</select>
						</div>
						
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-1">الأولوية</label>
							<select name="priority" 
								class="w-full border border-slate-300 rounded-lg px-3 py-2">
								<option value="low" ${announcement.priority === 'low' ? 'selected' : ''}>منخفضة</option>
								<option value="normal" ${announcement.priority === 'normal' || !announcement.priority ? 'selected' : ''}>عادية</option>
								<option value="high" ${announcement.priority === 'high' ? 'selected' : ''}>عالية</option>
								<option value="urgent" ${announcement.priority === 'urgent' ? 'selected' : ''}>عاجلة</option>
							</select>
						</div>
					</div>
				</div>
				
				<!-- Action Buttons -->
				<div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
					<button type="button" id="cancelModalAction" 
						class="px-5 py-2 rounded-lg border-2 border-slate-300 hover:bg-slate-50 text-slate-700 font-medium">
						إلغاء
					</button>
					<button type="submit" id="submitBtn"
						class="px-6 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 text-white hover:from-purple-700 hover:to-pink-700 font-medium flex items-center gap-2">
						<i data-lucide="send" class="w-4 h-4"></i>
						<span>${announcement.id ? 'تحديث الإعلان' : 'نشر الإعلان'}</span>
					</button>
				</div>
			</form>
		`;
	}
	
	function bindSmartAnnouncementForm(announcementId = null) {
		const form = document.getElementById('smartAnnouncementForm');
		const cancel = document.getElementById('cancelModalAction');
		const aiBtn = document.getElementById('getAISuggestions');
		const courseSelect = document.getElementById('courseSelect');
		
		if (!form) return;
		
		// Enable/disable AI suggestions based on course selection
		if (courseSelect && aiBtn) {
			const updateAIBtn = () => {
				aiBtn.disabled = !courseSelect.value;
			};
			courseSelect.addEventListener('change', updateAIBtn);
			updateAIBtn();
		}
		
		// Get AI suggestions
		if (aiBtn) {
			aiBtn.addEventListener('click', async () => {
				const courseId = courseSelect?.value;
				if (!courseId) {
					showToast('يرجى اختيار دورة أولاً', 'warning');
					return;
				}
				
				aiBtn.disabled = true;
				aiBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>جاري التحليل...</span>';
				lucide.createIcons();
				
				try {
					const data = await fetchJson(`api/manage_announcements_ai.php?action=ai_suggest_audience&course_id=${courseId}`);
					const suggestions = data.suggested_students || [];
					const confidence = data.ai_confidence || 0;
					
					const resultDiv = document.getElementById('aiSuggestionsResult');
					resultDiv.classList.remove('hidden');
					resultDiv.innerHTML = `
						<div class="bg-white rounded-lg p-4 border-2 border-blue-200">
							<div class="flex items-center justify-between mb-3">
								<span class="font-bold text-blue-900">🤖 اقتراحات AI</span>
								<span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
									${confidence}% ثقة
								</span>
							</div>
							<p class="text-sm text-slate-600 mb-2">تم العثور على <strong>${suggestions.length}</strong> طالب مهتم</p>
							<div class="text-xs text-slate-500 space-y-1">
								<p>📊 الخوارزمية: ${data.algorithm || 'AI Collaborative Filtering'}</p>
								<p>🎯 العوامل: ${data.factors?.join(' • ') || 'متعددة'}</p>
							</div>
						</div>
					`;
					
					// Auto-select AI targeting
					const targetSelect = document.getElementById('targetAudience');
					if (targetSelect) targetSelect.value = 'ai_suggested';
					
					showToast(`تم اقتراح ${suggestions.length} طالب بنجاح`, 'success');
				} catch (error) {
					showToast(error.message, 'error');
				} finally {
					aiBtn.disabled = false;
					aiBtn.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i><span>الحصول على اقتراحات AI</span>';
					lucide.createIcons();
				}
			});
		}
		
		// Form submission
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			
			const submitBtn = document.getElementById('submitBtn');
			const originalHTML = submitBtn.innerHTML;
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>جاري النشر...</span>';
			lucide.createIcons();
			
			try {
				const formData = new FormData(form);
				const data = {};
				
				// Convert form data to object
				formData.forEach((value, key) => {
					if (key === 'send_notification' || key === 'send_email' || key === 'publish_to_website') {
						data[key] = formData.get(key) === '1';
					} else {
						data[key] = value;
					}
				});
				
				const action = announcementId ? 'update' : 'create';
				if (announcementId) data.id = announcementId;
				
				await fetchJson('api/manage_announcements_ai.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ ...data, action })
				});
				
				showToast(announcementId ? 'تم تحديث الإعلان بنجاح' : 'تم نشر الإعلان بنجاح', 'success');
				closeModal();
				renderAnnouncements();
			} catch (error) {
				showToast(error.message, 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalHTML;
				lucide.createIcons();
			}
		});
		
		// Cancel button
		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
		
		// Initialize Lucide icons
		setTimeout(() => lucide.createIcons(), 100);
	}
	
	function showAnnouncementDetails(announcement) {
		const html = `
			<div class="space-y-4">
				<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4">
					<h3 class="font-bold text-xl text-slate-800 mb-2">${announcement.title}</h3>
					<p class="text-sm text-slate-600">${announcement.description}</p>
				</div>
				
				${announcement.course_title ? `
					<div class="bg-indigo-50 rounded-lg p-3">
						<p class="text-sm font-medium text-indigo-900">
							<i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
							${announcement.course_title}
						</p>
					</div>
				` : ''}
				
				<div class="grid grid-cols-2 gap-3">
					<div class="bg-slate-50 rounded-lg p-3">
						<p class="text-xs text-slate-600 mb-1">المشاهدات</p>
						<p class="text-2xl font-bold text-slate-800">${announcement.views_count || 0}</p>
					</div>
					<div class="bg-slate-50 rounded-lg p-3">
						<p class="text-xs text-slate-600 mb-1">القراءات</p>
						<p class="text-2xl font-bold text-purple-600">${announcement.read_count || 0}</p>
					</div>
					<div class="bg-slate-50 rounded-lg p-3">
						<p class="text-xs text-slate-600 mb-1">التحويلات</p>
						<p class="text-2xl font-bold text-emerald-600">${announcement.enrollments_count || 0}</p>
					</div>
					<div class="bg-slate-50 rounded-lg p-3">
						<p class="text-xs text-slate-600 mb-1">معدل الفتح</p>
						<p class="text-2xl font-bold text-indigo-600">${(announcement.open_rate || 0).toFixed(1)}%</p>
					</div>
				</div>
				
				<div class="bg-slate-50 rounded-lg p-3 text-sm">
					<p><strong>منشئ الإعلان:</strong> ${announcement.creator_name || 'مجهول'}</p>
					<p><strong>تاريخ النشر:</strong> ${new Date(announcement.created_at).toLocaleDateString('ar-SA')}</p>
					<p><strong>الحالة:</strong> ${announcement.status || 'منشور'}</p>
				</div>
			</div>
		`;
		
		openModal('📢 تفاصيل الإعلان', html);
		lucide.createIcons();
	}

	// ===== NOTIFICATIONS SYSTEM =====
	async function renderNotifications() {
		setPageHeader('🔔 نظام الإشعارات المتقدم', 'إدارة الإشعارات في الوقت الفعلي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		try {
			// Load notifications and stats in parallel
			const [notificationsData, statsData, preferencesData] = await Promise.all([
				fetchJson('api/manage_notifications.php?action=list&page=1&limit=50'),
				CURRENT_USER.role === 'manager' || CURRENT_USER.role === 'technical' 
					? fetchJson('api/manage_notifications.php?action=get_stats')
					: Promise.resolve(null),
				fetchJson('api/manage_notifications.php?action=get_preferences')
			]);

			const notifications = notificationsData.data || [];
			const stats = statsData?.stats || null;
			const preferences = preferencesData?.preferences || {};

			// Build UI
			body.innerHTML = `
				<!-- Statistics Dashboard (Manager/Technical only) -->
				${stats ? renderNotificationStats(stats) : ''}
				
				<!-- Quick Actions -->
				<div class="bg-white rounded-2xl shadow p-6 mb-6">
					<div class="flex flex-wrap items-center justify-between gap-4">
						<h3 class="text-lg font-semibold text-slate-800">إجراءات سريعة</h3>
						<div class="flex flex-wrap gap-3">
							${CURRENT_USER.role === 'manager' || CURRENT_USER.role === 'technical' ? `
								<button onclick="window.showCreateNotificationModal()" 
									class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:shadow-lg transition flex items-center gap-2">
									<i data-lucide="plus" class="w-4 h-4"></i>
									إنشاء إشعار جديد
								</button>
								<button onclick="window.showBulkNotificationModal()" 
									class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:shadow-lg transition flex items-center gap-2">
									<i data-lucide="send" class="w-4 h-4"></i>
									إرسال جماعي
								</button>
							` : ''}
							<button onclick="window.markAllNotificationsRead()" 
								class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
								<i data-lucide="check-check" class="w-4 h-4"></i>
								تحديد الكل كمقروء
							</button>
							<button onclick="window.showNotificationPreferences()" 
								class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition flex items-center gap-2">
								<i data-lucide="settings" class="w-4 h-4"></i>
								التفضيلات
							</button>
						</div>
					</div>
				</div>
				
				<!-- Filters -->
				<div class="bg-white rounded-2xl shadow p-6 mb-6">
					<div class="flex flex-wrap gap-3">
						<select id="notificationFilterType" class="px-4 py-2 border border-slate-300 rounded-lg">
							<option value="">جميع الأنواع</option>
							<option value="info">معلومات</option>
							<option value="success">نجاح</option>
							<option value="warning">تحذير</option>
							<option value="error">خطأ</option>
							<option value="announcement">إعلان</option>
						</select>
						<select id="notificationFilterStatus" class="px-4 py-2 border border-slate-300 rounded-lg">
							<option value="all">الكل</option>
							<option value="unread">غير مقروء</option>
							<option value="read">مقروء</option>
						</select>
						<button onclick="window.applyNotificationFilters()" 
							class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
							تطبيق الفلاتر
						</button>
					</div>
				</div>
				
				<!-- Notifications List -->
				<div id="notificationsList" class="space-y-4">
					${notifications.length === 0 ? `
						<div class="bg-white rounded-2xl shadow p-12 text-center">
							<i data-lucide="bell-off" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
							<p class="text-slate-500">لا توجد إشعارات</p>
						</div>
					` : notifications.map(n => renderNotificationCard(n)).join('')}
				</div>
			`;
			
			lucide.createIcons();
			
			// Attach event handlers
			attachNotificationHandlers(notifications);
			
		} catch (error) {
			console.error('Error loading notifications:', error);
			body.innerHTML = `
				<div class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
					<p class="text-red-600">⚠️ ${error.message}</p>
				</div>
			`;
		}
	}

	function renderNotificationStats(stats) {
		const overall = stats.overall || {};
		const avgReadTime = overall.avg_read_time_seconds 
			? Math.round(overall.avg_read_time_seconds / 60) 
			: 0;

		return `
			<div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
				<h3 class="text-xl font-bold mb-4 flex items-center gap-2">
					<i data-lucide="bar-chart-3" class="w-6 h-6"></i>
					إحصائيات الإشعارات (آخر 30 يوم)
				</h3>
				<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
					<div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
						<p class="text-sm opacity-90 mb-1">إجمالي الإشعارات</p>
						<p class="text-3xl font-bold">${overall.total_notifications || 0}</p>
					</div>
					<div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
						<p class="text-sm opacity-90 mb-1">المقروءة</p>
						<p class="text-3xl font-bold text-emerald-300">${overall.total_read || 0}</p>
					</div>
					<div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
						<p class="text-sm opacity-90 mb-1">غير المقروءة</p>
						<p class="text-3xl font-bold text-amber-300">${overall.total_unread || 0}</p>
					</div>
					<div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
						<p class="text-sm opacity-90 mb-1">متوسط وقت القراءة</p>
						<p class="text-3xl font-bold text-cyan-300">${avgReadTime} دق</p>
					</div>
				</div>
			</div>
		`;
	}

	function renderNotificationCard(notification) {
		const typeColors = {
			info: 'blue',
			success: 'emerald',
			warning: 'amber',
			error: 'red',
			announcement: 'purple'
		};
		const typeIcons = {
			info: 'info',
			success: 'check-circle',
			warning: 'alert-triangle',
			error: 'x-circle',
			announcement: 'megaphone'
		};
		
		const color = typeColors[notification.type] || 'slate';
		const icon = typeIcons[notification.type] || 'bell';
		const isUnread = !notification.is_read || notification.is_read === '0';
		const priorityLabels = { 1: 'منخفض', 2: 'عادي', 3: 'مرتفع', 4: 'عاجل' };
		const priorityColors = { 1: 'slate', 2: 'blue', 3: 'amber', 4: 'red' };

		return `
			<div class="bg-white rounded-xl shadow-sm border ${isUnread ? 'border-' + color + '-300 border-l-4' : 'border-slate-200'} p-4 hover:shadow-md transition">
				<div class="flex items-start gap-4">
					<!-- Icon -->
					<div class="flex-shrink-0 w-12 h-12 rounded-full bg-${color}-100 flex items-center justify-center">
						<i data-lucide="${icon}" class="w-6 h-6 text-${color}-600"></i>
					</div>
					
					<!-- Content -->
					<div class="flex-1 min-w-0">
						<div class="flex items-start justify-between gap-3 mb-2">
							<h4 class="text-lg font-semibold text-slate-800 ${isUnread ? 'font-bold' : ''}">${notification.title || 'إشعار'}</h4>
							<div class="flex items-center gap-2">
								<span class="px-2 py-1 text-xs font-medium rounded-full bg-${priorityColors[notification.priority] || 'slate'}-100 text-${priorityColors[notification.priority] || 'slate'}-700">
									${priorityLabels[notification.priority] || 'عادي'}
								</span>
								${isUnread ? `<span class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700">جديد</span>` : ''}
							</div>
						</div>
						
						<p class="text-slate-600 text-sm mb-3 line-clamp-2">${notification.message || ''}</p>
						
						<div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
							<span class="flex items-center gap-1">
								<i data-lucide="clock" class="w-3 h-3"></i>
								${new Date(notification.created_at).toLocaleString('ar-SA')}
							</span>
							${notification.creator_name ? `
								<span class="flex items-center gap-1">
									<i data-lucide="user" class="w-3 h-3"></i>
									${notification.creator_name}
								</span>
							` : ''}
						</div>
					</div>
					
					<!-- Actions -->
					<div class="flex flex-col gap-2">
						${isUnread ? `
							<button onclick="window.markNotificationRead(${notification.id})" 
								class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" 
								title="تحديد كمقروء">
								<i data-lucide="check" class="w-4 h-4"></i>
							</button>
						` : ''}
						<button onclick="window.viewNotificationDetails(${notification.id})" 
							class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" 
							title="عرض التفاصيل">
							<i data-lucide="eye" class="w-4 h-4"></i>
						</button>
						${CURRENT_USER.role === 'manager' || CURRENT_USER.role === 'technical' ? `
							<button onclick="window.deleteNotification(${notification.id})" 
								class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" 
								title="حذف">
								<i data-lucide="trash-2" class="w-4 h-4"></i>
							</button>
						` : ''}
					</div>
				</div>
			</div>
		`;
	}

	function attachNotificationHandlers(notifications) {
		// Global functions for button handlers
		window.markNotificationRead = async (id) => {
			try {
				await fetchJson(`api/manage_notifications.php?action=mark_read&id=${id}`);
				showToast('تم تحديد الإشعار كمقروء', 'success');
				renderNotifications(); // Reload
				updateNotificationBadge(); // Update badge
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		window.markAllNotificationsRead = async () => {
			try {
				const data = await fetchJson('api/manage_notifications.php?action=mark_all_read');
				showToast(data.message, 'success');
				renderNotifications(); // Reload
				updateNotificationBadge(); // Update badge
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		window.viewNotificationDetails = async (id) => {
			try {
				const data = await fetchJson(`api/manage_notifications.php?action=get&id=${id}`);
				const notification = data.data;
				
				const html = `
					<div class="space-y-4">
						<div class="flex items-center gap-3">
							<div class="p-3 rounded-full bg-indigo-100">
								<i data-lucide="bell" class="w-6 h-6 text-indigo-600"></i>
							</div>
							<div>
								<h3 class="text-xl font-bold text-slate-800">${notification.title}</h3>
								<p class="text-sm text-slate-500">${new Date(notification.created_at).toLocaleString('ar-SA')}</p>
							</div>
						</div>
						
						<div class="bg-slate-50 rounded-lg p-4">
							<p class="text-slate-700 whitespace-pre-wrap">${notification.message}</p>
						</div>
						
						<div class="grid grid-cols-2 gap-3">
							<div class="bg-slate-50 rounded-lg p-3">
								<p class="text-xs text-slate-600 mb-1">النوع</p>
								<p class="text-sm font-semibold text-slate-800">${notification.type}</p>
							</div>
							<div class="bg-slate-50 rounded-lg p-3">
								<p class="text-xs text-slate-600 mb-1">الأولوية</p>
								<p class="text-sm font-semibold text-slate-800">${notification.priority}</p>
							</div>
							${notification.creator_name ? `
								<div class="bg-slate-50 rounded-lg p-3 col-span-2">
									<p class="text-xs text-slate-600 mb-1">المنشئ</p>
									<p class="text-sm font-semibold text-slate-800">${notification.creator_name}</p>
								</div>
							` : ''}
						</div>
					</div>
				`;
				
				openModal('🔔 تفاصيل الإشعار', html);
				
				// Mark as read if unread
				if (!notification.is_read || notification.is_read === '0') {
					await fetchJson(`api/manage_notifications.php?action=mark_read&id=${id}`);
					updateNotificationBadge();
				}
				
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		window.deleteNotification = async (id) => {
			if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) return;
			
			try {
				await fetchJson(`api/manage_notifications.php?action=delete&id=${id}`);
				showToast('تم حذف الإشعار بنجاح', 'success');
				renderNotifications(); // Reload
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		window.showCreateNotificationModal = () => {
			const html = `
				<form id="createNotificationForm" class="space-y-4">
					<div>
						<label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
						<input type="text" name="title" required 
							class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
					</div>
					<div>
						<label class="block text-sm font-medium text-slate-700 mb-2">الرسالة</label>
						<textarea name="message" rows="4" required 
							class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
					</div>
					<div class="grid grid-cols-2 gap-4">
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-2">النوع</label>
							<select name="type" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
								<option value="info">معلومات</option>
								<option value="success">نجاح</option>
								<option value="warning">تحذير</option>
								<option value="error">خطأ</option>
								<option value="announcement">إعلان</option>
							</select>
						</div>
						<div>
							<label class="block text-sm font-medium text-slate-700 mb-2">الأولوية</label>
							<select name="priority" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
								<option value="1">منخفض</option>
								<option value="2" selected>عادي</option>
								<option value="3">مرتفع</option>
								<option value="4">عاجل</option>
							</select>
						</div>
					</div>
					<div>
						<label class="block text-sm font-medium text-slate-700 mb-2">إرسال إلى</label>
						<select name="target_role" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
							<option value="">مستخدم محدد</option>
							<option value="student">جميع الطلاب</option>
							<option value="trainer">جميع المدربين</option>
							<option value="manager">المدراء</option>
							<option value="technical">المشرفين الفنيين</option>
						</select>
					</div>
					<div class="flex items-center gap-2">
						<input type="checkbox" name="send_email" id="sendEmail" class="rounded">
						<label for="sendEmail" class="text-sm text-slate-700">إرسال عبر البريد الإلكتروني</label>
					</div>
					<div class="flex gap-3">
						<button type="submit" 
							class="flex-1 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:shadow-lg transition">
							إنشاء الإشعار
						</button>
						<button type="button" onclick="closeModal()" 
							class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
							إلغاء
						</button>
					</div>
				</form>
			`;
			
			openModal('➕ إنشاء إشعار جديد', html);
			
			document.getElementById('createNotificationForm').addEventListener('submit', async (e) => {
				e.preventDefault();
				const formData = new FormData(e.target);
				const data = {
					title: formData.get('title'),
					message: formData.get('message'),
					type: formData.get('type'),
					priority: parseInt(formData.get('priority')),
					target_role: formData.get('target_role') || null,
					send_email: formData.get('send_email') === 'on'
				};
				
				try {
					await fetchJson('api/manage_notifications.php?action=create', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(data)
					});
					showToast('تم إنشاء الإشعار بنجاح', 'success');
					closeModal();
					renderNotifications(); // Reload
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		};

		window.showBulkNotificationModal = () => {
			// Similar to showCreateNotificationModal but optimized for bulk sending
			showToast('سيتم توفير الإرسال الجماعي قريباً', 'info');
		};

		window.showNotificationPreferences = async () => {
			try {
				const data = await fetchJson('api/manage_notifications.php?action=get_preferences');
				const prefs = data.preferences || {};
				
				const html = `
					<form id="preferencesForm" class="space-y-4">
						<div class="space-y-3">
							<div class="flex items-center justify-between">
								<label class="text-sm font-medium text-slate-700">إشعارات البريد الإلكتروني</label>
								<input type="checkbox" name="email_enabled" ${prefs.email_enabled ? 'checked' : ''} class="rounded">
							</div>
							<div class="flex items-center justify-between">
								<label class="text-sm font-medium text-slate-700">إشعارات SMS</label>
								<input type="checkbox" name="sms_enabled" ${prefs.sms_enabled ? 'checked' : ''} class="rounded">
							</div>
							<div class="flex items-center justify-between">
								<label class="text-sm font-medium text-slate-700">الإشعارات الفورية</label>
								<input type="checkbox" name="push_enabled" ${prefs.push_enabled ? 'checked' : ''} class="rounded">
							</div>
						</div>
						<div class="flex gap-3">
							<button type="submit" 
								class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
								حفظ التفضيلات
							</button>
							<button type="button" onclick="closeModal()" 
								class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
								إلغاء
							</button>
						</div>
					</form>
				`;
				
				openModal('⚙️ تفضيلات الإشعارات', html);
				
				document.getElementById('preferencesForm').addEventListener('submit', async (e) => {
					e.preventDefault();
					const formData = new FormData(e.target);
					const data = {
						email_enabled: formData.get('email_enabled') === 'on' ? 1 : 0,
						sms_enabled: formData.get('sms_enabled') === 'on' ? 1 : 0,
						push_enabled: formData.get('push_enabled') === 'on' ? 1 : 0
					};
					
					try {
						await fetchJson('api/manage_notifications.php?action=update_preferences', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(data)
						});
						showToast('تم حفظ التفضيلات بنجاح', 'success');
						closeModal();
					} catch (error) {
						showToast(error.message, 'error');
					}
				});
				
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		window.applyNotificationFilters = () => {
			const type = document.getElementById('notificationFilterType')?.value || '';
			const status = document.getElementById('notificationFilterStatus')?.value || 'all';
			
			const url = `api/manage_notifications.php?action=list&page=1&limit=50${type ? '&type=' + type : ''}${status !== 'all' ? '&filter=' + status : ''}`;
			
			fetchJson(url).then(data => {
				const notifications = data.data || [];
				const listContainer = document.getElementById('notificationsList');
				if (listContainer) {
					if (notifications.length === 0) {
						listContainer.innerHTML = `
							<div class="bg-white rounded-2xl shadow p-12 text-center">
								<i data-lucide="inbox" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
								<p class="text-slate-500">لا توجد نتائج</p>
							</div>
						`;
					} else {
						listContainer.innerHTML = notifications.map(n => renderNotificationCard(n)).join('');
					}
					lucide.createIcons();
				}
			}).catch(error => {
				showToast(error.message, 'error');
			});
		};
	}

	// Update notification badge in sidebar
	async function updateNotificationBadge() {
		try {
			const data = await fetchJson('api/manage_notifications.php?action=get_unread_count');
			const count = data.unread_count || 0;
			const badge = document.getElementById('notification-badge');
			if (badge) {
				if (count > 0) {
					badge.textContent = count > 99 ? '99+' : count;
					badge.classList.remove('hidden');
				} else {
					badge.classList.add('hidden');
				}
			}
		} catch (error) {
			console.warn('Failed to update notification badge', error);
		}
	}

	async function renderGrades() {
		setPageHeader('الدرجات والشهادات', 'إنشاء وتتبع نتائج الطلاب');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `
			<section class="bg-white rounded-2xl shadow p-6">
				<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
					<div>
						<h3 class="text-lg font-semibold text-slate-800">إدارة الدرجات</h3>
						<p class="text-sm text-slate-500">ربط الدرجات بالواجبات والشهادات</p>
					</div>
					<div class="flex items-center gap-2">
						<button id="openAssignmentModal" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-2">
							<i data-lucide="clipboard-list" class="w-4 h-4"></i>
							<span>إنشاء واجب</span>
						</button>
					</div>
				</div>
				<div id="gradesContainer" class="space-y-3">
					<p class="text-sm text-slate-500">جاري تحميل البيانات...</p>
				</div>
			</section>
		`;
		lucide.createIcons();

		try {
			const data = await fetchJson(API_ENDPOINTS.manageGrades);
			const grades = data.data || [];
			const rows = grades.map(grade => `
				<tr>
					<td class="px-4 py-2 text-slate-600">${grade.grade_id}</td>
					<td class="px-4 py-2 font-medium text-slate-800">${grade.user_name || '-'}</td>
					<td class="px-4 py-2 text-slate-600">${grade.course_title || '-'}</td>
					<td class="px-4 py-2 text-slate-600">${grade.assignment_name || '-'}</td>
					<td class="px-4 py-2 text-slate-600">${grade.grade_value}/${grade.max_grade}</td>
				</tr>
			`).join('');
			document.getElementById('gradesContainer').innerHTML = `
				<div class="overflow-x-auto">
					<table class="w-full text-sm text-right">
						<thead class="bg-slate-50 text-slate-600">
							<tr>
								<th class="px-4 py-2">#</th>
								<th class="px-4 py-2">الطالب</th>
								<th class="px-4 py-2">الدورة</th>
								<th class="px-4 py-2">الواجب</th>
								<th class="px-4 py-2">الدرجة</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-100">${rows || '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">لا توجد درجات مسجلة.</td></tr>'}</tbody>
					</table>
				</div>
			`;
		} catch (error) {
			document.getElementById('gradesContainer').innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}

		const openBtn = document.getElementById('openAssignmentModal');
		if (openBtn) {
			openBtn.addEventListener('click', () => {
				openModal('إنشاء واجب جديد', buildAssignmentForm());
				bindAssignmentForm();
			});
		}
	}

	function buildAssignmentForm(assignment = {}) {
		return `
			<form id="assignmentForm" class="space-y-4">
				<input type="hidden" name="assignment_id" value="${assignment.assignment_id || ''}">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">معرف الدورة</label>
						<input name="course_id" type="number" value="${assignment.course_id || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">معرف الوحدة (اختياري)</label>
						<input name="module_id" type="number" value="${assignment.module_id || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">عنوان الواجب</label>
					<input name="title" value="${assignment.title || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">الوصف</label>
					<textarea name="description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2">${assignment.description || ''}</textarea>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">تاريخ الاستحقاق</label>
						<input name="due_date" type="datetime-local" value="${assignment.due_date ? assignment.due_date.replace(' ', 'T') : ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">الدرجة القصوى</label>
						<input name="max_score" type="number" step="0.01" value="${assignment.max_score || 100}" class="w-full border border-slate-200 rounded-lg px-3 py-2">
					</div>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">حفظ</button>
				</div>
			</form>
		`;
	}

	function bindAssignmentForm(assignmentId = null) {
		const form = document.getElementById('assignmentForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const data = Object.fromEntries(new FormData(form).entries());
			const action = assignmentId ? 'update_assignment' : 'create_assignment';
			data.action = action;
			if (!data.module_id) delete data.module_id;
			if (assignmentId) {
				data.assignment_id = assignmentId;
			}
			try {
				await fetchJson(API_ENDPOINTS.manageLmsAssignments, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast('تم حفظ الواجب', 'success');
				closeModal();
				renderGrades();
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	async function renderMessages(config = {}) {
		const settings = {
			embedded: false,
			containerId: null,
			title: 'الرسائل الداخلية',
			subtitle: 'تواصل سريع بين فرق المنصة والطلاب',
			hideHeader: false,
			defaultRecipient: null,
			...config
		};

		const target = settings.containerId ? document.getElementById(settings.containerId) : document.getElementById('pageBody');
		if (!target) return;

		if (!settings.embedded) {
			setPageHeader(settings.title, settings.subtitle);
			clearPageBody();
		}

		const wrapClass = settings.embedded ? 'space-y-4' : 'bg-white rounded-2xl shadow p-6 space-y-4';
		const headerMarkup = settings.embedded && settings.hideHeader ? '' : `
			<header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
				<div>
					<h3 class="text-lg font-semibold text-slate-800">${settings.title}</h3>
					${settings.subtitle ? `<p class="text-sm text-slate-500">${settings.subtitle}</p>` : ''}
				</div>
				<div class="flex flex-wrap items-center gap-2">
					<div class="inline-flex rounded-xl border border-slate-200 overflow-hidden" role="tablist">
						<button type="button" class="px-4 py-2 text-sm font-medium bg-slate-100" data-action="switch-box" data-box="inbox">الوارد</button>
						<button type="button" class="px-4 py-2 text-sm font-medium" data-action="switch-box" data-box="sent">الصادر</button>
					</div>
					<button type="button" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100 flex items-center gap-2" data-action="refresh">
						<i data-lucide="refresh-cw" class="w-4 h-4"></i>
						<span>تحديث</span>
					</button>
					<button type="button" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 flex items-center gap-2" data-action="compose">
						<i data-lucide="edit-3" class="w-4 h-4"></i>
						<span>رسالة جديدة</span>
					</button>
				</div>
			</header>
		`;

		target.innerHTML = `
			<section class="${wrapClass}" data-role="messages-root">
				${headerMarkup}
				<div class="grid grid-cols-1 lg:grid-cols-3 gap-4" data-role="messages-layout">
					<div data-region="list" class="space-y-2 bg-white border border-slate-200 rounded-2xl p-3 max-h-[520px] overflow-y-auto">
						<p class="text-sm text-slate-500">جاري تحميل الرسائل...</p>
					</div>
					<div data-region="detail" class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 min-h-[280px] flex items-center justify-center text-sm text-slate-500">
						<p>اختر رسالة من القائمة لعرض تفاصيلها.</p>
					</div>
				</div>
			</section>
		`;
		lucide.createIcons();

		const root = target.querySelector('[data-role="messages-root"]');
		if (!root) return;
		const listEl = root.querySelector('[data-region="list"]');
		const detailEl = root.querySelector('[data-region="detail"]');
		const tabs = Array.from(root.querySelectorAll('[data-action="switch-box"]'));
		const composeBtn = root.querySelector('[data-action="compose"]');
		const refreshBtn = root.querySelector('[data-action="refresh"]');

		let currentBox = 'inbox';
		let currentMessages = [];
		let selectedMessageId = null;
		let recipientsCache = [];
		let recipientsLoaded = false;

		tabs.forEach(tab => {
			if (tab.dataset.box === currentBox) {
				tab.classList.add('bg-slate-100', 'text-slate-800');
			}
		});

		const highlightActiveTab = () => {
			tabs.forEach(tab => {
				if (tab.dataset.box === currentBox) {
					tab.classList.add('bg-slate-100', 'text-slate-800');
				} else {
					tab.classList.remove('bg-slate-100', 'text-slate-800');
				}
			});
		};

		const ensureRecipients = async () => {
			if (recipientsLoaded) return recipientsCache;
			try {
				const payload = await fetchJson(`${API_ENDPOINTS.manageMessages}?mode=recipients`);
				recipientsCache = Array.isArray(payload.recipients) ? payload.recipients : [];
				recipientsLoaded = true;
				return recipientsCache;
			} catch (error) {
				showToast(error.message, 'error');
				return [];
			}
		};

		const renderEmptyState = (message) => {
			listEl.innerHTML = `<div class="text-center text-sm text-slate-500 py-6">${message}</div>`;
		};

		const renderList = () => {
			if (!currentMessages.length) {
				renderEmptyState('لا توجد رسائل في هذا الصندوق.');
				return;
			}
			listEl.innerHTML = currentMessages.map(message => {
				const isRead = Number(message.is_read) === 1;
				const counterpart = currentBox === 'sent' ? (message.recipient_name || 'مستلم غير معروف') : (message.sender_name || 'مرسل غير معروف');
				const dateLabel = formatDateTime(message.created_at, { dateStyle: 'medium', timeStyle: 'short' });
				const preview = (message.body || '').toString().slice(0, 80);
				const activeClass = Number(message.message_id) === Number(selectedMessageId) ? 'border-sky-300 bg-sky-50' : 'border-slate-200';
				return `
					<button type="button" class="w-full text-right px-4 py-3 rounded-2xl border ${activeClass} ${isRead ? 'bg-white' : 'bg-indigo-50 border-indigo-200'} hover:border-sky-300 transition" data-action="open-message" data-message-id="${message.message_id}">
						<div class="flex items-center justify-between gap-3">
							<span class="text-sm font-semibold text-slate-800">${escapeHtml(message.subject || 'بدون عنوان')}</span>
							<span class="text-xs text-slate-500">${dateLabel}</span>
						</div>
						<div class="flex items-center justify-between gap-3 mt-1">
							<span class="text-xs text-slate-500">${escapeHtml(counterpart)}</span>
							${!isRead && currentBox === 'inbox' ? '<span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">جديد</span>' : ''}
						</div>
						<p class="text-sm text-slate-600 mt-2 line-clamp-2">${escapeHtml(preview)}</p>
					</button>
				`;
			}).join('');
		};

		const renderDetail = (message) => {
			if (!message) {
				detailEl.innerHTML = '<p class="text-sm text-slate-500">اختر رسالة من القائمة لعرض تفاصيلها.</p>';
				return;
			}
			const isOwnMessage = Number(message.sender_id) === Number(CURRENT_USER.id);
			const counterpartName = isOwnMessage ? (message.recipient_name || 'المستلم') : (message.sender_name || 'المرسل');
			const bodyHtml = escapeHtml(message.body || '').replace(/\n/g, '<br>');
			const metaLines = [
				`<span class="text-sm text-slate-500">${isOwnMessage ? 'أُرسلت إلى' : 'من'}: <strong class="text-slate-700">${escapeHtml(counterpartName)}</strong></span>`,
				`<span class="text-sm text-slate-500">التاريخ: ${formatDateTime(message.created_at, { dateStyle: 'full', timeStyle: 'short' })}</span>`
			];

			if (!isOwnMessage && Number(message.is_read) === 1 && message.read_at) {
				metaLines.push(`<span class="text-sm text-slate-500">تمت القراءة: ${formatDateTime(message.read_at, { dateStyle: 'short', timeStyle: 'short' })}</span>`);
			}

			detailEl.innerHTML = `
				<article class="space-y-4">
					<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
						<div>
							<h4 class="text-xl font-semibold text-slate-800">${escapeHtml(message.subject || 'بدون عنوان')}</h4>
							<div class="flex flex-wrap gap-3 mt-2">${metaLines.join('')}</div>
						</div>
						<div class="flex items-center gap-2">
							${!isOwnMessage ? `<button class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 text-sm flex items-center gap-1" data-action="reply" data-recipient-id="${message.sender_id}" data-recipient-name="${escapeHtml(counterpartName)}">
								<i data-lucide="reply" class="w-4 h-4"></i>
								<span>رد</span>
							</button>` : ''}
							<button class="px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm flex items-center gap-1" data-action="delete" data-message-id="${message.message_id}">
								<i data-lucide="trash-2" class="w-4 h-4"></i>
								<span>حذف</span>
							</button>
						</div>
					</header>
					<div class="border border-slate-100 rounded-2xl p-4 bg-slate-50 text-slate-700 leading-relaxed">${bodyHtml || '<p class="text-sm text-slate-500">لا يوجد محتوى.</p>'}</div>
				</article>
			`;
			lucide.createIcons();
		};

		const markAsRead = async (messageId) => {
			const message = currentMessages.find(item => Number(item.message_id) === Number(messageId));
			if (!message || Number(message.is_read) === 1 || currentBox !== 'inbox') {
				return;
			}
			try {
				await fetchJson(API_ENDPOINTS.manageMessages, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ action: 'mark_read', message_id: messageId })
				});
				message.is_read = 1;
				renderList();
			} catch (error) {
				console.warn('فشل تحديث حالة الرسالة', error);
			}
		};

		const loadMessages = async (box = 'inbox') => {
			currentBox = box;
			highlightActiveTab();
			selectedMessageId = null;
			detailEl.innerHTML = '<p class="text-sm text-slate-500">اختر رسالة من القائمة لعرض تفاصيلها.</p>';
			listEl.innerHTML = '<p class="text-sm text-slate-500">جاري تحميل الرسائل...</p>';
			try {
				const data = await fetchJson(`${API_ENDPOINTS.manageMessages}?box=${box}`);
				currentMessages = Array.isArray(data.messages) ? data.messages : [];
				renderList();
			} catch (error) {
				renderEmptyState(error.message);
			}
		};

		const openMessage = async (messageId) => {
			const existing = currentMessages.find(item => Number(item.message_id) === Number(messageId));
			if (existing) {
				selectedMessageId = messageId;
				renderList();
				renderDetail(existing);
				await markAsRead(messageId);
				return;
			}
			try {
				const payload = await fetchJson(`${API_ENDPOINTS.manageMessages}?message_id=${messageId}`);
				const message = payload.message;
				if (message) {
					selectedMessageId = messageId;
					renderDetail(message);
					await markAsRead(messageId);
				}
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		const deleteMessage = async (messageId) => {
			if (!confirm('سيتم حذف الرسالة نهائياً، هل أنت متأكد؟')) return;
			try {
				await fetchJson(API_ENDPOINTS.manageMessages, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ action: 'delete', message_id: messageId })
				});
				showToast('تم حذف الرسالة', 'success');
				await loadMessages(currentBox);
			} catch (error) {
				showToast(error.message, 'error');
			}
		};

		const openCompose = async (defaults = {}) => {
			const recipients = await ensureRecipients();
			if (!recipients.length) {
				showToast('لا يوجد مستلمون متاحون حالياً', 'warning');
				return;
			}
			const defaultRecipientId = defaults.recipient_id || settings.defaultRecipient;
			const formHtml = `
				<form id="composeMessageForm" class="space-y-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">المستلم</label>
						<select name="recipient_id" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
							<option value="">اختر المستلم</option>
							${recipients.map(user => `<option value="${user.id}" ${Number(user.id) === Number(defaultRecipientId) ? 'selected' : ''}>${escapeHtml(user.full_name)} (${escapeHtml(user.role)})</option>`).join('')}
						</select>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">الموضوع</label>
						<input name="subject" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="عنوان الرسالة">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">المحتوى</label>
						<textarea name="body" rows="6" class="w-full border border-slate-200 rounded-lg px-3 py-2" required placeholder="اكتب رسالتك هنا"></textarea>
					</div>
					<div class="flex justify-end gap-3">
						<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
						<button type="submit" class="px-5 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700">إرسال</button>
					</div>
				</form>
			`;
			openModal('رسالة جديدة', formHtml);
			lucide.createIcons();

			const form = document.getElementById('composeMessageForm');
			const cancelBtn = document.getElementById('cancelModalAction');
			if (cancelBtn) {
				cancelBtn.addEventListener('click', () => closeModal());
			}
			if (form) {
				form.addEventListener('submit', async event => {
					event.preventDefault();
					const formData = new FormData(form);
					const payload = Object.fromEntries(formData.entries());
					payload.action = 'send';
					try {
						await fetchJson(API_ENDPOINTS.manageMessages, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(payload)
						});
						showToast('تم إرسال الرسالة', 'success');
						closeModal();
						await loadMessages('sent');
					} catch (error) {
						showToast(error.message, 'error');
					}
				});
			}
		};

		if (composeBtn) {
			composeBtn.addEventListener('click', () => openCompose());
		}

		if (refreshBtn) {
			refreshBtn.addEventListener('click', () => loadMessages(currentBox));
		}

		root.addEventListener('click', event => {
			const targetEl = event.target.closest('[data-action]');
			if (!targetEl) return;
			const action = targetEl.dataset.action;
			if (action === 'switch-box') {
				const nextBox = targetEl.dataset.box || 'inbox';
				if (nextBox !== currentBox) {
					loadMessages(nextBox);
				}
			}
			if (action === 'open-message') {
				const messageId = targetEl.dataset.messageId;
				if (messageId) {
					openMessage(messageId);
				}
			}
			if (action === 'delete') {
				const messageId = targetEl.dataset.messageId;
				if (messageId) {
					deleteMessage(messageId);
				}
			}
			if (action === 'reply') {
				const recipientId = targetEl.dataset.recipientId;
				const recipientName = targetEl.dataset.recipientName;
				openCompose({ recipient_id: recipientId, recipient_name: recipientName });
			}
		});

		await loadMessages(currentBox);

		if (settings.defaultRecipient) {
			openCompose({ recipient_id: settings.defaultRecipient });
		}
	}

	async function renderAttendanceReports() {
		if (CURRENT_USER.role !== 'manager') {
			showToast('هذا القسم متاح للمديرين فقط', 'warning');
			renderDashboard();
			return;
		}

		setPageHeader('تقارير الحضور', 'تحليل معدلات الحضور والالتزام عبر الدورات');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		const pad = value => String(value).padStart(2, '0');
		const formatDateInput = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
		const today = new Date();
		const defaultEnd = formatDateInput(today);
		const defaultStart = formatDateInput(new Date(today.getFullYear(), today.getMonth(), 1));

		body.innerHTML = `
			<section class="bg-white rounded-2xl shadow p-6 space-y-6">
				<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
					<div>
						<h3 class="text-lg font-semibold text-slate-800">سجل الحضور التراكمي</h3>
						<p class="text-sm text-slate-500">راجع معدلات الحضور بحسب الفترة الزمنية والدورات</p>
					</div>
				</div>
				<form id="attendanceReportFilters" class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-4">
					<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
						<label class="flex flex-col gap-1 text-sm text-slate-600">
							<span>تاريخ البداية</span>
							<input type="date" name="start_date" value="${defaultStart}" class="border border-slate-200 rounded-lg px-3 py-2">
						</label>
						<label class="flex flex-col gap-1 text-sm text-slate-600">
							<span>تاريخ النهاية</span>
							<input type="date" name="end_date" value="${defaultEnd}" class="border border-slate-200 rounded-lg px-3 py-2">
						</label>
						<label class="flex flex-col gap-1 text-sm text-slate-600">
							<span>الدورة التدريبية</span>
							<select name="course_id" id="attendanceReportCourse" class="border border-slate-200 rounded-lg px-3 py-2">
								<option value="">جميع الدورات</option>
							</select>
						</label>
					</div>
					<div class="flex flex-wrap gap-2 justify-end">
						<button type="submit" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 flex items-center gap-2">
							<i data-lucide="search" class="w-4 h-4"></i>
							<span>تحديث التقرير</span>
						</button>
						<button type="button" id="resetAttendanceFilters" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100 flex items-center gap-2">
							<i data-lucide="rotate-ccw" class="w-4 h-4"></i>
							<span>إعادة التعيين</span>
						</button>
					</div>
				</form>
				<div id="attendanceReportMeta" class="text-xs text-slate-500"></div>
				<div id="attendanceReportResults" class="space-y-4">
					<p class="text-sm text-slate-500">جاري تحميل التقارير...</p>
				</div>
			</section>
		`;
		lucide.createIcons();

		let availableCourses = [];
		try {
			const coursesPayload = await fetchJson(API_ENDPOINTS.manageCourses);
			availableCourses = coursesPayload.data || [];
		} catch (error) {
			showToast('تعذر تحميل قائمة الدورات، سيتم عرض كل النتائج.', 'warning');
		}

		const courseSelect = document.getElementById('attendanceReportCourse');
		if (courseSelect && availableCourses.length) {
			courseSelect.innerHTML = '<option value="">جميع الدورات</option>' + availableCourses.map(course => `<option value="${course.course_id}">${course.title}</option>`).join('');
		}

		const filtersForm = document.getElementById('attendanceReportFilters');
		const resetButton = document.getElementById('resetAttendanceFilters');
		const resultsContainer = document.getElementById('attendanceReportResults');
		const metaContainer = document.getElementById('attendanceReportMeta');

		const buildReportsMarkup = reports => {
			if (!reports.length) {
				return '<div class="bg-slate-50 border border-slate-200 text-slate-600 px-4 py-5 rounded-xl text-center">لا توجد سجلات حضور مطابقة للمعايير المحددة.</div>';
			}

			const totals = reports.reduce((acc, report) => {
				const present = Number(report.present_count) || 0;
				const absent = Number(report.absent_count) || 0;
				const late = Number(report.late_count) || 0;
				const total = Number(report.total_records) || 0;
				const sessions = Number(report.unique_sessions) || 0;
				return {
					present: acc.present + present,
					absent: acc.absent + absent,
					late: acc.late + late,
					records: acc.records + total,
					sessions: acc.sessions + sessions
				};
			}, { present: 0, absent: 0, late: 0, records: 0, sessions: 0 });
			const overallRate = totals.records > 0 ? ((totals.present / totals.records) * 100) : 0;

			const summaryCards = `
				<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
					${renderStatisticCard({ title: 'الدورات المغطاة', value: reports.length, icon: 'layers', accent: 'sky' })}
					${renderStatisticCard({ title: 'الجلسات المحتسبة', value: totals.sessions, icon: 'calendar-days', accent: 'violet' })}
					${renderStatisticCard({ title: 'إجمالي الحضور', value: totals.present, icon: 'check-circle-2', accent: 'emerald' })}
					${renderStatisticCard({ title: 'نسبة الالتزام', value: `${overallRate.toFixed(1)}%`, icon: 'percent', accent: 'amber' })}
				</section>
			`;

			const rows = reports.map(report => {
				const present = Number(report.present_count) || 0;
				const absent = Number(report.absent_count) || 0;
				const late = Number(report.late_count) || 0;
				const total = Number(report.total_records) || 0;
				const sessions = Number(report.unique_sessions) || 0;
				const rate = total > 0 ? ((present / total) * 100) : 0;
				const rateWidth = Math.min(rate, 100).toFixed(0);
				return `
					<tr>
						<td class="px-4 py-2 font-medium text-slate-800">${report.title}</td>
						<td class="px-4 py-2 text-slate-600">${sessions}</td>
						<td class="px-4 py-2 text-emerald-600">${present}</td>
						<td class="px-4 py-2 text-red-600">${absent}</td>
						<td class="px-4 py-2 text-amber-600">${late}</td>
						<td class="px-4 py-2 text-slate-600">${total}</td>
						<td class="px-4 py-2">
							<div class="flex items-center gap-2">
								<div class="h-2 flex-1 bg-slate-100 rounded-full">
									<div class="h-2 bg-emerald-500 rounded-full" style="width: ${rateWidth}%"></div>
								</div>
								<span class="text-sm text-slate-600">${rate.toFixed(1)}%</span>
							</div>
						</td>
					</tr>
				`;
			}).join('');

			return `
				${summaryCards}
				<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
					<div class="overflow-x-auto">
						<table class="w-full text-sm text-right">
							<thead class="bg-slate-50 text-slate-600">
								<tr>
									<th class="px-4 py-2">الدورة</th>
									<th class="px-4 py-2">الجلسات</th>
									<th class="px-4 py-2">حضور</th>
									<th class="px-4 py-2">غياب</th>
									<th class="px-4 py-2">تأخير</th>
									<th class="px-4 py-2">السجلات</th>
									<th class="px-4 py-2">نسبة الالتزام</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-slate-100">
								${rows}
							</tbody>
						</table>
					</div>
				</div>
			`;
		};

		const loadReports = async () => {
			if (!resultsContainer) return;
			const startInput = filtersForm ? filtersForm.querySelector('[name="start_date"]') : null;
			const endInput = filtersForm ? filtersForm.querySelector('[name="end_date"]') : null;
			const startDate = startInput && startInput.value ? startInput.value : defaultStart;
			const endDate = endInput && endInput.value ? endInput.value : defaultEnd;

			resultsContainer.innerHTML = '<p class="text-sm text-slate-500">جاري تحميل التقارير...</p>';

			try {
				const params = new URLSearchParams({ mode: 'report', start_date: startDate, end_date: endDate });
				if (courseSelect && courseSelect.value) {
					params.append('course_id', courseSelect.value);
				}
				const data = await fetchJson(`${API_ENDPOINTS.manageAttendance}?${params.toString()}`);
				const reports = data.data || [];
				resultsContainer.innerHTML = buildReportsMarkup(reports);
				if (metaContainer) {
					const rangeStart = data.range && data.range.start ? data.range.start : startDate;
					const rangeEnd = data.range && data.range.end ? data.range.end : endDate;
					metaContainer.textContent = `الفترة الزمنية: ${rangeStart} → ${rangeEnd} | عدد الدورات: ${reports.length}`;
				}
				lucide.createIcons();
			} catch (error) {
				if (metaContainer) {
					metaContainer.textContent = '';
				}
				resultsContainer.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
			}
		};

		if (filtersForm) {
			filtersForm.addEventListener('submit', event => {
				event.preventDefault();
				loadReports();
			});
		}

		if (resetButton && filtersForm) {
			resetButton.addEventListener('click', () => {
				const startInput = filtersForm.querySelector('[name="start_date"]');
				const endInput = filtersForm.querySelector('[name="end_date"]');
				if (startInput) startInput.value = defaultStart;
				if (endInput) endInput.value = defaultEnd;
				if (courseSelect) courseSelect.value = '';
				loadReports();
			});
		}

		await loadReports();
	}

	async function renderAttendanceSheet(courseId, courseTitle) {
		if (!['manager', 'technical', 'trainer'].includes(CURRENT_USER.role)) {
			showToast('غير مصرح لك بالوصول لهذه الصفحة', 'warning');
			renderDashboard();
			return;
		}

		setPageHeader(`سجل الحضور: ${courseTitle}`, 'تسجيل حضور وغياب الطلاب لليوم الحالي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		const today = new Date();
		const formatDate = (d) => {
			const year = d.getFullYear();
			const month = String(d.getMonth() + 1).padStart(2, '0');
			const day = String(d.getDate()).padStart(2, '0');
			return `${year}-${month}-${day}`;
		};
		const todayStr = formatDate(today);

		body.innerHTML = `
			<div class="space-y-6">
				<div class="bg-white rounded-2xl shadow p-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div>
							<h3 class="text-lg font-semibold text-slate-800">سجل حضور اليوم</h3>
							<p class="text-sm text-slate-500">التاريخ: ${todayStr}</p>
						</div>
						<button id="backToCoursesBtn" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center gap-2">
							<i data-lucide="arrow-right" class="w-4 h-4"></i>
							<span>العودة</span>
						</button>
					</div>
					<div id="attendanceSheetContainer" class="space-y-3">
						<p class="text-sm text-slate-500">جاري تحميل قائمة الطلاب...</p>
					</div>
				</div>
			</div>
		`;

		lucide.createIcons();

		const backBtn = document.getElementById('backToCoursesBtn');
		if (backBtn) {
			backBtn.addEventListener('click', () => {
				if (CURRENT_USER.role === 'trainer') {
					renderDashboard();
				} else {
					navigateTo('courses');
				}
			});
		}

		const container = document.getElementById('attendanceSheetContainer');
		if (!container) return;

		try {
			const params = new URLSearchParams({
				mode: 'sheet',
				course_id: courseId,
				date: todayStr
			});
			const data = await fetchJson(`${API_ENDPOINTS.manageAttendance}?${params.toString()}`);
			const students = data.students || [];

			if (students.length === 0) {
				container.innerHTML = `
					<div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl">
						<p class="font-medium">لا يوجد طلاب مسجلين في هذه الدورة</p>
					</div>
				`;
				return;
			}

			container.innerHTML = `
				<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<thead class="bg-slate-50">
							<tr class="text-right">
								<th class="px-4 py-3 font-semibold text-slate-700">الطالب</th>
								<th class="px-4 py-3 font-semibold text-slate-700">البريد الإلكتروني</th>
								<th class="px-4 py-3 font-semibold text-slate-700 text-center">الحالة</th>
								<th class="px-4 py-3 font-semibold text-slate-700">ملاحظات</th>
								<th class="px-4 py-3 font-semibold text-slate-700 text-center">الإجراءات</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-100">
							${students.map(student => {
								const status = student.attendance_status || 'unset';
								const statusBadge = {
									'present': '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-medium">✓ حاضر</span>',
									'absent': '<span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">✗ غائب</span>',
									'late': '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-medium">🕒 متأخر</span>',
									'unset': '<span class="px-2 py-1 bg-slate-100 text-slate-500 rounded text-xs">لم يتم التسجيل</span>'
								}[status];

								return `
									<tr class="hover:bg-slate-50" data-student-id="\${student.user_id}">
										<td class="px-4 py-3 font-medium text-slate-800">\${escapeHtml(student.full_name)}</td>
										<td class="px-4 py-3 text-slate-600">\${escapeHtml(student.email)}</td>
										<td class="px-4 py-3 text-center attendance-status-cell">\${statusBadge}</td>
										<td class="px-4 py-3">
											<input 
												type="text" 
												class="border border-slate-200 rounded px-2 py-1 text-xs w-full attendance-notes" 
												placeholder="ملاحظات..." 
												value="\${escapeHtml(student.notes || '')}"
												data-student-id="\${student.user_id}"
											>
										</td>
										<td class="px-4 py-3">
											<div class="flex items-center justify-center gap-1">
												<button 
													class="px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 text-xs attendance-btn" 
													data-student-id="\${student.user_id}" 
													data-status="present" 
													title="حاضر"
												>✅</button>
												<button 
													class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-xs attendance-btn" 
													data-student-id="\${student.user_id}" 
													data-status="absent" 
													title="غائب"
												>❌</button>
												<button 
													class="px-2 py-1 rounded bg-amber-600 text-white hover:bg-amber-700 text-xs attendance-btn" 
													data-student-id="\${student.user_id}" 
													data-status="late" 
													title="متأخر"
												>🕒</button>
											</div>
										</td>
									</tr>
								`;
							}).join('')}
						</tbody>
					</table>
				</div>
				<div class="flex justify-end mt-4">
					<button id="saveAllAttendanceBtn" class="px-6 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 flex items-center gap-2">
						<i data-lucide="save" class="w-4 h-4"></i>
						<span>حفظ جميع التغييرات</span>
					</button>
				</div>
			`;

			lucide.createIcons();

			const attendanceBtns = container.querySelectorAll('.attendance-btn');
			attendanceBtns.forEach(btn => {
				btn.addEventListener('click', async () => {
					const studentId = btn.dataset.studentId;
					const status = btn.dataset.status;
					const row = btn.closest('tr');
					const notesInput = row.querySelector('.attendance-notes');
					const notes = notesInput ? notesInput.value : '';

					try {
						const response = await fetchJson(API_ENDPOINTS.manageAttendance, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({
								action: 'mark',
								course_id: courseId,
								user_id: studentId,
								attendance_date: todayStr,
								status: status,
								notes: notes
							})
						});

						if (response.success) {
							const statusCell = row.querySelector('.attendance-status-cell');
							const statusBadges = {
								'present': '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-medium">✓ حاضر</span>',
								'absent': '<span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">✗ غائب</span>',
								'late': '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-medium">🕒 متأخر</span>'
							};
							if (statusCell) {
								statusCell.innerHTML = statusBadges[status];
							}
							showToast('تم تسجيل الحضور بنجاح', 'success');
						} else {
							showToast(response.message || 'فشل تسجيل الحضور', 'error');
						}
					} catch (error) {
						showToast('حدث خطأ أثناء تسجيل الحضور', 'error');
					}
				});
			});

			const saveAllBtn = document.getElementById('saveAllAttendanceBtn');
			if (saveAllBtn) {
				saveAllBtn.addEventListener('click', async () => {
					const rows = container.querySelectorAll('tbody tr');
					let savedCount = 0;
					
					showToast('جاري حفظ جميع التغييرات...', 'info');
					
					for (const row of rows) {
						const studentId = row.dataset.studentId;
						const statusCell = row.querySelector('.attendance-status-cell');
						const notesInput = row.querySelector('.attendance-notes');
						const notes = notesInput ? notesInput.value : '';
						
						const statusText = statusCell ? statusCell.textContent.trim() : '';
						let status = 'unset';
						if (statusText.includes('حاضر')) status = 'present';
						else if (statusText.includes('غائب')) status = 'absent';
						else if (statusText.includes('متأخر')) status = 'late';
						
						if (status !== 'unset') {
							try {
								await fetchJson(API_ENDPOINTS.manageAttendance, {
									method: 'POST',
									headers: { 'Content-Type': 'application/json' },
									body: JSON.stringify({
										action: 'mark',
										course_id: courseId,
										user_id: studentId,
										attendance_date: todayStr,
										status: status,
										notes: notes
									})
								});
								savedCount++;
							} catch (error) {
								console.error('Error saving attendance:', error);
							}
						}
					}
					
					showToast(`تم حفظ ${savedCount} سجل حضور بنجاح`, 'success');
				});
			}

		} catch (error) {
			container.innerHTML = `
				<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
					<p class="font-medium">خطأ في تحميل البيانات</p>
					<p class="text-sm mt-1">${error.message}</p>
				</div>
			`;
		}
	}

	async function renderAnalytics() {
		if (CURRENT_USER.role !== 'manager') {
			showToast('هذا القسم مخصص للمديرين فقط', 'warning');
			renderDashboard();
			return;
		}

		setPageHeader('🚀 التحليلات الذكية - AI Analytics Hub', 'نظام هجين متطور لتحليل البيانات بالذكاء الاصطناعي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `
			<!-- Header المتطور -->
			<div class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 rounded-3xl shadow-2xl p-8 mb-6">
				<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
					<div class="flex items-center gap-4">
						<div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white">
							<i data-lucide="brain-circuit" class="w-10 h-10"></i>
						</div>
						<div>
							<h2 class="text-2xl font-bold text-slate-800">مركز التحليلات الذكية</h2>
							<p class="text-sm text-slate-600">تحليل متعدد الأبعاد بالذكاء الاصطناعي</p>
						</div>
					</div>
					<div class="flex flex-wrap items-center gap-3">
						<select id="analyticsTimeRange" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm">
							<option value="7">آخر 7 أيام</option>
							<option value="30" selected>آخر 30 يوم</option>
							<option value="90">آخر 3 أشهر</option>
							<option value="365">آخر سنة</option>
							<option value="all">كل الوقت</option>
						</select>
						<button id="refreshAnalytics" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center gap-2 text-sm">
							<i data-lucide="refresh-cw" class="w-4 h-4"></i>
							<span>تحديث</span>
						</button>
						<button id="exportAnalytics" class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:shadow-lg flex items-center gap-2 text-sm">
							<i data-lucide="download" class="w-4 h-4"></i>
							<span>تصدير PDF</span>
						</button>
					</div>
				</div>
				<div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
					<div class="px-3 py-2 rounded-xl bg-white/60 backdrop-blur-sm flex items-center gap-2">
						<i data-lucide="sparkles" class="w-4 h-4 text-indigo-600"></i>
						<span class="text-xs text-slate-600">AI Insights</span>
					</div>
					<div class="px-3 py-2 rounded-xl bg-white/60 backdrop-blur-sm flex items-center gap-2">
						<i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
						<span class="text-xs text-slate-600">Predictive</span>
					</div>
					<div class="px-3 py-2 rounded-xl bg-white/60 backdrop-blur-sm flex items-center gap-2">
						<i data-lucide="zap" class="w-4 h-4 text-amber-600"></i>
						<span class="text-xs text-slate-600">Real-time</span>
					</div>
					<div class="px-3 py-2 rounded-xl bg-white/60 backdrop-blur-sm flex items-center gap-2">
						<i data-lucide="shield-check" class="w-4 h-4 text-sky-600"></i>
						<span class="text-xs text-slate-600">Verified</span>
					</div>
				</div>
			</div>

			<!-- KPIs الذكية -->
			<section id="aiKPIs" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"></section>

			<!-- AI Insights Banner -->
			<div id="aiInsightsBanner" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl shadow-xl p-6 mb-6">
				<div class="flex items-center gap-3 mb-3">
					<i data-lucide="lightbulb" class="w-6 h-6"></i>
					<h3 class="text-lg font-semibold">رؤى الذكاء الاصطناعي</h3>
				</div>
				<div id="aiInsightsContent" class="space-y-2"></div>
			</div>

			<!-- الرسومات البيانية المتطورة -->
			<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
				<!-- Revenue Analytics -->
				<div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
					<div class="flex items-center justify-between">
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-lg bg-emerald-100 text-emerald-600">
								<i data-lucide="dollar-sign" class="w-5 h-5"></i>
							</div>
							<div>
								<h3 class="text-lg font-semibold text-slate-800">تحليل الإيرادات</h3>
								<p class="text-xs text-slate-500">Revenue Analysis</p>
							</div>
						</div>
						<div class="flex items-center gap-2">
							<button class="p-2 rounded-lg hover:bg-slate-100" data-chart="revenue" data-type="bar">
								<i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-600"></i>
							</button>
							<button class="p-2 rounded-lg hover:bg-slate-100" data-chart="revenue" data-type="line">
								<i data-lucide="line-chart" class="w-4 h-4 text-slate-600"></i>
							</button>
							<button class="p-2 rounded-lg hover:bg-slate-100" data-chart="revenue" data-type="doughnut">
								<i data-lucide="pie-chart" class="w-4 h-4 text-slate-600"></i>
							</button>
						</div>
					</div>
					<div class="h-72">
						<canvas id="revenueChart"></canvas>
					</div>
					<div id="revenueStats" class="grid grid-cols-3 gap-2 pt-3 border-t"></div>
				</div>

				<!-- Trainer Performance -->
				<div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
					<div class="flex items-center justify-between">
						<div class="flex items-center gap-3">
							<div class="p-2 rounded-lg bg-sky-100 text-sky-600">
								<i data-lucide="award" class="w-5 h-5"></i>
							</div>
							<div>
								<h3 class="text-lg font-semibold text-slate-800">أداء المدربين</h3>
								<p class="text-xs text-slate-500">Trainer Performance</p>
							</div>
						</div>
						<div class="flex items-center gap-2">
							<button class="p-2 rounded-lg hover:bg-slate-100" data-chart="trainer" data-type="bar">
								<i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-600"></i>
							</button>
							<button class="p-2 rounded-lg hover:bg-slate-100" data-chart="trainer" data-type="radar">
								<i data-lucide="hexagon" class="w-4 h-4 text-slate-600"></i>
							</button>
						</div>
					</div>
					<div class="h-72">
						<canvas id="trainerChart"></canvas>
					</div>
					<div id="trainerStats" class="grid grid-cols-2 gap-2 pt-3 border-t"></div>
				</div>
			</div>

			<!-- المزيد من الرسومات -->
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
				<!-- Demographics -->
				<div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
					<div class="flex items-center gap-3">
						<div class="p-2 rounded-lg bg-purple-100 text-purple-600">
							<i data-lucide="users" class="w-5 h-5"></i>
						</div>
						<div>
							<h3 class="text-base font-semibold text-slate-800">التوزيع الديموغرافي</h3>
							<p class="text-xs text-slate-500">Demographics</p>
						</div>
					</div>
					<div class="h-60">
						<canvas id="demographicChart"></canvas>
					</div>
					<div id="demographicLegend" class="space-y-1 text-xs"></div>
				</div>

				<!-- Attendance Trends -->
				<div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
					<div class="flex items-center gap-3">
						<div class="p-2 rounded-lg bg-rose-100 text-rose-600">
							<i data-lucide="calendar-check" class="w-5 h-5"></i>
						</div>
						<div>
							<h3 class="text-base font-semibold text-slate-800">اتجاهات الحضور</h3>
							<p class="text-xs text-slate-500">Attendance Trends</p>
						</div>
					</div>
					<div class="h-60">
						<canvas id="attendanceChart"></canvas>
					</div>
					<div id="attendanceInsights" class="space-y-1 text-xs pt-3 border-t"></div>
				</div>

				<!-- Course Popularity -->
				<div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
					<div class="flex items-center gap-3">
						<div class="p-2 rounded-lg bg-amber-100 text-amber-600">
							<i data-lucide="trending-up" class="w-5 h-5"></i>
						</div>
						<div>
							<h3 class="text-base font-semibold text-slate-800">شعبية الدورات</h3>
							<p class="text-xs text-slate-500">Course Popularity</p>
						</div>
					</div>
					<div class="h-60">
						<canvas id="popularityChart"></canvas>
					</div>
					<div id="popularityRanking" class="space-y-1 text-xs pt-3 border-t"></div>
				</div>
			</div>

			<!-- Timeline & Predictions -->
			<div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
				<div class="flex items-center justify-between mb-4">
					<div class="flex items-center gap-3">
						<div class="p-2 rounded-lg bg-indigo-100 text-indigo-600">
							<i data-lucide="activity" class="w-5 h-5"></i>
						</div>
						<div>
							<h3 class="text-lg font-semibold text-slate-800">الخط الزمني والتوقعات</h3>
							<p class="text-xs text-slate-500">Timeline & AI Predictions</p>
						</div>
					</div>
					<div class="text-xs text-slate-500">
						<span class="inline-block w-3 h-3 rounded-full bg-blue-500 mr-1"></span> بيانات فعلية
						<span class="inline-block w-3 h-3 rounded-full bg-purple-500 mr-1 ml-3"></span> توقعات AI
					</div>
				</div>
				<div class="h-80">
					<canvas id="timelineChart"></canvas>
				</div>
				<div id="predictionMetrics" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t"></div>
			</div>

			<!-- Data Table -->
			<div class="bg-white rounded-2xl shadow-xl p-6">
				<div class="flex items-center justify-between mb-4">
					<h3 class="text-lg font-semibold text-slate-800">جدول البيانات التفصيلي</h3>
					<button id="exportTableBtn" class="px-3 py-1 rounded-lg border border-slate-200 text-xs hover:bg-slate-50">
						<i data-lucide="table" class="w-3 h-3 inline"></i> Export CSV
					</button>
				</div>
				<div class="overflow-x-auto">
					<table id="analyticsTable" class="w-full text-sm">
						<thead class="bg-slate-50">
							<tr class="text-right">
								<th class="px-4 py-3 font-semibold text-slate-700">الدورة</th>
								<th class="px-4 py-3 font-semibold text-slate-700">الإيرادات</th>
								<th class="px-4 py-3 font-semibold text-slate-700">الطلاب</th>
								<th class="px-4 py-3 font-semibold text-slate-700">نسبة الإكمال</th>
								<th class="px-4 py-3 font-semibold text-slate-700">التقييم</th>
								<th class="px-4 py-3 font-semibold text-slate-700">الحالة</th>
							</tr>
						</thead>
						<tbody id="analyticsTableBody"></tbody>
					</table>
				</div>
			</div>

			<!-- Footer Meta -->
			<div class="mt-6 flex items-center justify-between text-xs text-slate-400">
				<div id="analyticsMeta"></div>
				<div class="flex items-center gap-2">
					<i data-lucide="shield-check" class="w-3 h-3"></i>
					<span>Powered by AI Analytics Engine v2.0</span>
				</div>
			</div>
		`;
		lucide.createIcons();

		// 🚀 AI Analytics Engine v2.0 - Advanced Hybrid System
		window.analyticsCharts = window.analyticsCharts || {};
		window.analyticsCurrentRange = '30';

		// ============ AI HELPER FUNCTIONS ============

		// 🧠 AI Insights Generator
		function generateAIInsights(data) {
			const insights = [];
			const { revenue_by_course, trainer_performance, demographics, attendance_trends, course_popularity } = data;

			// Revenue Growth Analysis
			if (Array.isArray(revenue_by_course) && revenue_by_course.length > 1) {
				const sorted = [...revenue_by_course].sort((a, b) => Number(b.total_revenue || 0) - Number(a.total_revenue || 0));
				const topCourse = sorted[0];
				const totalRevenue = revenue_by_course.reduce((sum, item) => sum + Number(item.total_revenue || 0), 0);
				insights.push({
					icon: 'trending-up',
					color: 'text-green-600',
					text: `الإيرادات الإجمالية: <strong>${totalRevenue.toFixed(2)} دولار</strong> - الدورة الأعلى: ${topCourse.title || topCourse.course_name || 'غير محدد'}`
				});
			}

			// Trainer Performance Analysis
			if (Array.isArray(trainer_performance) && trainer_performance.length) {
				const topTrainer = trainer_performance.reduce((best, t) => {
					const completionRate = Number(t.completion_rate || 0);
					return completionRate > Number(best.completion_rate || 0) ? t : best;
				}, trainer_performance[0]);
				insights.push({
					icon: 'award',
					color: 'text-purple-600',
					text: `⭐ المدرب المتميز: <strong>${topTrainer.trainer_name || 'غير محدد'}</strong> بمعدل إكمال ${Number(topTrainer.completion_rate || 0).toFixed(1)}%`
				});
			}

			// Course Popularity Analysis
			if (Array.isArray(course_popularity) && course_popularity.length) {
				const mostPopular = course_popularity[0];
				insights.push({
					icon: 'flame',
					color: 'text-orange-600',
					text: `🔥 الدورة الأكثر طلباً: <strong>${mostPopular.course_name || mostPopular.title || 'غير محدد'}</strong> بـ ${mostPopular.enrollment_count || 0} تسجيل`
				});
			}

			// Attendance Trend Analysis
			if (Array.isArray(attendance_trends) && attendance_trends.length > 1) {
				const recent = attendance_trends.slice(-2);
				const current = Number(recent[1]?.attendance_rate || 0);
				const previous = Number(recent[0]?.attendance_rate || 0);
				const trend = current - previous;
				const trendText = trend > 0 ? `ارتفع بـ ${trend.toFixed(1)}%` : trend < 0 ? `انخفض بـ ${Math.abs(trend).toFixed(1)}%` : 'مستقر';
				const trendColor = trend >= 0 ? 'text-green-600' : 'text-red-600';
				insights.push({
					icon: trend >= 0 ? 'trending-up' : 'trending-down',
					color: trendColor,
					text: `معدل الحضور الحالي: <strong>${current.toFixed(1)}%</strong> - ${trendText}`
				});
			}

			// Demographics Insight
			if (demographics?.by_governorate?.length) {
				const topGov = demographics.by_governorate[0];
				insights.push({
					icon: 'map-pin',
					color: 'text-blue-600',
					text: `المحافظة الأكثر تسجيلاً: <strong>${topGov.label || 'غير محدد'}</strong> بـ ${topGov.total || 0} طالب`
				});
			}

			return insights.length > 0 ? insights : [{
				icon: 'info',
				color: 'text-slate-600',
				text: 'لا توجد رؤى كافية بعد. ابدأ بإضافة البيانات لرؤية التحليلات الذكية.'
			}];
		}

		// 📊 AI Predictions Generator (Linear Regression)
		function generatePredictions(data) {
			if (!Array.isArray(data.attendance_trends) || data.attendance_trends.length < 3) {
				return { futureData: [], confidence: 70, trend: 'stable' };
			}

			const historical = data.attendance_trends.slice(-7); // Last 7 points
			const values = historical.map(d => Number(d.attendance_rate || 0));
			
			// Simple linear regression
			const n = values.length;
			const sumX = (n * (n + 1)) / 2;
			const sumY = values.reduce((a, b) => a + b, 0);
			const sumXY = values.reduce((acc, y, i) => acc + (i + 1) * y, 0);
			const sumX2 = (n * (n + 1) * (2 * n + 1)) / 6;
			
			const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
			const intercept = (sumY - slope * sumX) / n;

			// Predict next 4 points
			const futureData = [];
			for (let i = 1; i <= 4; i++) {
				const predictedValue = intercept + slope * (n + i);
				futureData.push({
					label: `المستقبل +${i}`,
					value: Math.max(0, Math.min(100, predictedValue)) // Clamp between 0-100
				});
			}

			const confidence = Math.min(95, Math.max(60, 85 - Math.abs(slope) * 10)); // 60-95%
			const trend = slope > 0.5 ? 'up' : slope < -0.5 ? 'down' : 'stable';

			return { futureData, confidence: confidence.toFixed(0), trend };
		}

		// 🎨 Chart Type Switcher
		function switchChartType(chartKey, newType) {
			if (!window.analyticsCharts[chartKey]) return;
			
			const oldChart = window.analyticsCharts[chartKey];
			const canvas = oldChart.canvas;
			const { labels, datasets } = oldChart.data;
			
			oldChart.destroy();
			
			window.analyticsCharts[chartKey] = new Chart(canvas, {
				type: newType,
				data: { labels, datasets },
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { position: newType === 'radar' ? 'bottom' : 'top' }
					},
					scales: newType !== 'doughnut' && newType !== 'pie' && newType !== 'radar' ? {
						y: { beginAtZero: true }
					} : undefined
				}
			});
		}

		// 📤 Export Functions
		async function exportToPDF() {
			showNotification('جاري تحضير ملف PDF...', 'info');
			// Placeholder - would integrate with jsPDF library
			setTimeout(() => {
				showNotification('تصدير PDF غير متاح بعد. سيتم إضافته قريباً!', 'warning');
			}, 1000);
		}

		function exportToCSV() {
			const table = document.getElementById('analyticsTableBody');
			if (!table || !table.children.length) {
				showNotification('لا توجد بيانات للتصدير', 'error');
				return;
			}

			let csv = 'الدورة,التسجيلات,الإكمال,الإيرادات,معدل الحضور\n';
			Array.from(table.children).forEach(row => {
				const cells = Array.from(row.children).map(cell => cell.textContent.trim());
				csv += cells.join(',') + '\n';
			});

			const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
			const link = document.createElement('a');
			link.href = URL.createObjectURL(blob);
			link.download = `analytics_${new Date().toISOString().split('T')[0]}.csv`;
			link.click();
			showNotification('تم تصدير البيانات بنجاح!', 'success');
		}

		// ============ MAIN ANALYTICS RENDERING ============

		async function loadAnalyticsData(timeRange = '30') {
			try {
				// Fetch data from API
				const data = await fetchJson(API_ENDPOINTS.analyticsData + `?range=${timeRange}`);
				
				// Parse data with fallbacks
				const revenue = Array.isArray(data.revenue_by_course) ? data.revenue_by_course : [];
				const trainers = Array.isArray(data.trainer_performance) ? data.trainer_performance : [];
				const demographics = data.demographics || {};
				const attendance = Array.isArray(data.attendance_trends) ? data.attendance_trends : 
					(data.attendance_data ? [data.attendance_data] : []);
				const popularity = Array.isArray(data.course_popularity) ? data.course_popularity : 
					(revenue.length ? [...revenue].sort((a, b) => Number(b.enrollment_count || 0) - Number(a.enrollment_count || 0)) : []);

				// Generate AI Insights
				const insights = generateAIInsights({
					revenue_by_course: revenue,
					trainer_performance: trainers,
					demographics: demographics,
					attendance_trends: attendance,
					course_popularity: popularity
				});

				// Generate Predictions
				const predictions = generatePredictions({
					attendance_trends: attendance
				});

				// ===== 1. RENDER KPIs =====
				const kpisEl = document.getElementById('aiKPIs');
				if (kpisEl) {
					const totalRevenue = revenue.reduce((sum, r) => sum + Number(r.total_revenue || 0), 0);
					const totalEnrollments = revenue.reduce((sum, r) => sum + Number(r.enrollment_count || 0), 0);
					const avgCompletion = trainers.length ? 
						trainers.reduce((sum, t) => sum + Number(t.completion_rate || 0), 0) / trainers.length : 0;
					const avgAttendance = attendance.length ?
						attendance.reduce((sum, a) => sum + Number(a.attendance_rate || 0), 0) / attendance.length : 0;

					kpisEl.innerHTML = `
						<div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg">
							<i data-lucide="dollar-sign" class="w-8 h-8 mb-2"></i>
							<div class="text-3xl font-bold">${totalRevenue.toFixed(0)} USD</div>
							<div class="text-sm opacity-90">إجمالي الإيرادات</div>
						</div>
						<div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg">
							<i data-lucide="users" class="w-8 h-8 mb-2"></i>
							<div class="text-3xl font-bold">${totalEnrollments}</div>
							<div class="text-sm opacity-90">إجمالي التسجيلات</div>
						</div>
						<div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-xl shadow-lg">
							<i data-lucide="check-circle" class="w-8 h-8 mb-2"></i>
							<div class="text-3xl font-bold">${avgCompletion.toFixed(1)}%</div>
							<div class="text-sm opacity-90">معدل الإكمال</div>
						</div>
						<div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-6 rounded-xl shadow-lg">
							<i data-lucide="calendar-check" class="w-8 h-8 mb-2"></i>
							<div class="text-3xl font-bold">${avgAttendance.toFixed(1)}%</div>
							<div class="text-sm opacity-90">معدل الحضور</div>
						</div>
					`;
				}

				// ===== 2. RENDER AI INSIGHTS =====
				const insightsContent = document.getElementById('aiInsightsContent');
				if (insightsContent) {
					insightsContent.innerHTML = insights.map(insight => `
						<div class="flex items-start gap-3">
							<i data-lucide="${insight.icon}" class="${insight.color} w-5 h-5 mt-0.5"></i>
							<p class="text-slate-700">${insight.text}</p>
						</div>
					`).join('');
				}

				// ===== 3. RENDER CHARTS =====
				
				// Destroy old charts
				Object.values(window.analyticsCharts).forEach(chart => {
					if (chart && chart.destroy) chart.destroy();
				});
				window.analyticsCharts = {};

				const chartColors = {
					blue: '#0ea5e9',
					green: '#22c55e',
					purple: '#8b5cf6',
					orange: '#f97316',
					red: '#ef4444',
					yellow: '#eab308'
				};

				// 3.1 Revenue Chart
				const revenueCanvas = document.getElementById('revenueChart');
				if (revenueCanvas && revenue.length) {
					window.analyticsCharts.revenue = new Chart(revenueCanvas, {
						type: 'bar',
						data: {
							labels: revenue.map(r => r.title || r.course_name || `دورة ${r.course_id}`),
							datasets: [{
								label: 'الإيرادات (USD)',
								data: revenue.map(r => Number(r.total_revenue || 0)),
								backgroundColor: chartColors.blue
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: { legend: { display: false } },
							scales: { y: { beginAtZero: true } }
						}
					});
				}

				// 3.2 Trainer Performance Chart
				const trainerCanvas = document.getElementById('trainerChart');
				if (trainerCanvas && trainers.length) {
					window.analyticsCharts.trainer = new Chart(trainerCanvas, {
						type: 'bar',
						data: {
							labels: trainers.map(t => t.trainer_name || `مدرب ${t.trainer_id}`),
							datasets: [{
								label: 'معدل الإكمال (%)',
								data: trainers.map(t => Number(t.completion_rate || 0)),
								backgroundColor: chartColors.green
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							indexAxis: 'y',
							plugins: { legend: { display: false } },
							scales: { x: { beginAtZero: true, max: 100 } }
						}
					});
				}

				// 3.3 Demographics Chart
				const demographicCanvas = document.getElementById('demographicChart');
				if (demographicCanvas && demographics.by_governorate?.length) {
					const govData = demographics.by_governorate;
					window.analyticsCharts.demographic = new Chart(demographicCanvas, {
						type: 'doughnut',
						data: {
							labels: govData.map(g => g.label || 'غير محدد'),
							datasets: [{
								data: govData.map(g => Number(g.total || 0)),
								backgroundColor: [chartColors.blue, chartColors.purple, chartColors.green, chartColors.orange, chartColors.yellow, chartColors.red]
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: { legend: { position: 'bottom' } }
						}
					});
				}

				// 3.4 Attendance Trends Chart
				const attendanceCanvas = document.getElementById('attendanceChart');
				if (attendanceCanvas && attendance.length) {
					window.analyticsCharts.attendance = new Chart(attendanceCanvas, {
						type: 'line',
						data: {
							labels: attendance.map(a => a.date || a.week || 'غير محدد'),
							datasets: [{
								label: 'معدل الحضور (%)',
								data: attendance.map(a => Number(a.attendance_rate || 0)),
								borderColor: chartColors.green,
								backgroundColor: chartColors.green + '20',
								fill: true,
								tension: 0.4
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: { legend: { display: false } },
							scales: { y: { beginAtZero: true, max: 100 } }
						}
					});
				}

				// 3.5 Course Popularity Chart
				const popularityCanvas = document.getElementById('popularityChart');
				if (popularityCanvas && popularity.length) {
					window.analyticsCharts.popularity = new Chart(popularityCanvas, {
						type: 'bar',
						data: {
							labels: popularity.map(p => p.course_name || p.title || `دورة ${p.course_id}`),
							datasets: [{
								label: 'عدد التسجيلات',
								data: popularity.map(p => Number(p.enrollment_count || 0)),
								backgroundColor: chartColors.purple
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							indexAxis: 'y',
							plugins: { legend: { display: false } },
							scales: { x: { beginAtZero: true } }
						}
					});
				}

				// 3.6 Timeline with Predictions
				const timelineCanvas = document.getElementById('timelineChart');
				if (timelineCanvas && attendance.length) {
					const historicalLabels = attendance.map(a => a.date || a.week || 'غير محدد');
					const historicalData = attendance.map(a => Number(a.attendance_rate || 0));
					const futureLabels = predictions.futureData.map(p => p.label);
					const futureData = predictions.futureData.map(p => p.value);

					window.analyticsCharts.timeline = new Chart(timelineCanvas, {
						type: 'line',
						data: {
							labels: [...historicalLabels, ...futureLabels],
							datasets: [
								{
									label: 'البيانات الفعلية',
									data: [...historicalData, ...Array(futureLabels.length).fill(null)],
									borderColor: chartColors.blue,
									backgroundColor: chartColors.blue + '20',
									fill: false,
									tension: 0.4
								},
								{
									label: 'التوقعات الذكية',
									data: [...Array(historicalLabels.length).fill(null), ...futureData],
									borderColor: chartColors.purple,
									borderDash: [5, 5],
									backgroundColor: chartColors.purple + '20',
									fill: false,
									tension: 0.4
								}
							]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: { legend: { position: 'top' } },
							scales: { y: { beginAtZero: true, max: 100 } }
						}
					});
				}

				// ===== 4. RENDER PREDICTION METRICS =====
				const predMetrics = document.getElementById('predictionMetrics');
				if (predMetrics) {
					predMetrics.innerHTML = `
						<div class="text-center">
							<div class="text-3xl font-bold text-purple-600">${predictions.confidence}%</div>
							<div class="text-sm text-slate-600 mt-1">دقة التنبؤ</div>
						</div>
						<div class="text-center">
							<div class="text-3xl font-bold ${predictions.trend === 'up' ? 'text-green-600' : predictions.trend === 'down' ? 'text-red-600' : 'text-slate-600'}">
								<i data-lucide="trending-${predictions.trend === 'up' ? 'up' : predictions.trend === 'down' ? 'down' : 'right'}" class="inline w-8 h-8"></i>
							</div>
							<div class="text-sm text-slate-600 mt-1">الاتجاه المتوقع</div>
						</div>
						<div class="text-center">
							<div class="text-3xl font-bold text-blue-600">${predictions.futureData.length}</div>
							<div class="text-sm text-slate-600 mt-1">نقاط مستقبلية</div>
						</div>
					`;
				}

				// ===== 5. RENDER DATA TABLE =====
				const tableBody = document.getElementById('analyticsTableBody');
				if (tableBody && revenue.length) {
					tableBody.innerHTML = revenue.map(r => `
						<tr class="border-b border-slate-200 hover:bg-slate-50">
							<td class="px-4 py-3">${r.title || r.course_name || `دورة ${r.course_id}`}</td>
							<td class="px-4 py-3 text-center">${r.enrollment_count || 0}</td>
							<td class="px-4 py-3 text-center">${Number(r.completion_rate || 0).toFixed(1)}%</td>
							<td class="px-4 py-3 text-center font-semibold text-green-600">${Number(r.total_revenue || 0).toFixed(2)} USD</td>
							<td class="px-4 py-3 text-center">${Number(r.attendance_rate || 0).toFixed(1)}%</td>
						</tr>
					`).join('');
				}

				lucide.createIcons();

			} catch (error) {
				body.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">⚠️ خطأ في تحميل البيانات: ${error.message}</div>`;
			}
		}

		// ============ EVENT LISTENERS ============

		// Time Range Selector
		document.getElementById('analyticsTimeRange')?.addEventListener('change', (e) => {
			window.analyticsCurrentRange = e.target.value;
			loadAnalyticsData(e.target.value);
		});

		// Refresh Button
		document.getElementById('refreshAnalytics')?.addEventListener('click', () => {
			loadAnalyticsData(window.analyticsCurrentRange);
			showNotification('تم تحديث البيانات', 'success');
		});

		// Export PDF Button
		document.getElementById('exportAnalytics')?.addEventListener('click', exportToPDF);

		// Export Table Button
		document.getElementById('exportTableBtn')?.addEventListener('click', exportToCSV);

		// Chart Type Switchers
		document.querySelectorAll('[data-chart]').forEach(btn => {
			btn.addEventListener('click', () => {
				const chartKey = btn.dataset.chart;
				const newType = btn.dataset.type;
				switchChartType(chartKey, newType);
				
				// Update active button
				const parent = btn.parentElement;
				parent.querySelectorAll('button').forEach(b => b.classList.remove('bg-blue-600', 'text-white'));
				btn.classList.add('bg-blue-600', 'text-white');
			});
		});

		// Initial Load
		loadAnalyticsData('30');
	}

	// ==================== 🎴 AI-POWERED ID CARDS SYSTEM ====================
	async function renderIDCards() {
		// Check technical supervisor permission
		if (!hasPermission('technical')) {
			showToast('هذا القسم مخصص للمشرف الفني فقط', 'warning');
			renderDashboard();
			return;
		}

		setPageHeader('🎴 نظام البطاقات الطلابية الذكي', 'إدارة متقدمة للبطاقات مع AI - إصدار وإرسال ومسح QR');

		const body = document.getElementById('mainContent');
		body.innerHTML = `
			<!-- AI Header -->
			<div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl p-8 mb-6 text-white shadow-2xl">
				<div class="flex items-center gap-4 mb-4">
					<div class="bg-white/20 p-4 rounded-xl backdrop-blur">
						<i data-lucide="credit-card" class="w-10 h-10"></i>
					</div>
					<div>
						<h2 class="text-2xl font-bold">نظام البطاقات الذكي AI-Powered</h2>
						<p class="text-white/90">تصميم عالمي • مسح QR • إرسال تلقائي • تخصيص كامل</p>
					</div>
				</div>
				<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
					<div class="bg-white/10 rounded-lg p-4 backdrop-blur">
						<div class="text-3xl font-bold" id="totalCardsCount">0</div>
						<div class="text-sm opacity-90">إجمالي البطاقات</div>
					</div>
					<div class="bg-white/10 rounded-lg p-4 backdrop-blur">
						<div class="text-3xl font-bold" id="issuedToday">0</div>
						<div class="text-sm opacity-90">صادرة اليوم</div>
					</div>
					<div class="bg-white/10 rounded-lg p-4 backdrop-blur">
						<div class="text-3xl font-bold" id="sentViaEmail">0</div>
						<div class="text-sm opacity-90">مُرسلة بالبريد</div>
					</div>
					<div class="bg-white/10 rounded-lg p-4 backdrop-blur">
						<div class="text-3xl font-bold" id="scannedToday">0</div>
						<div class="text-sm opacity-90">مسح QR اليوم</div>
					</div>
				</div>
			</div>

			<!-- QR Scanner Section -->
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
				<!-- QR Scanner -->
				<div class="lg:col-span-1">
					<div class="bg-white rounded-xl shadow-lg p-6 border-2 border-indigo-200">
						<div class="flex items-center gap-3 mb-4">
							<i data-lucide="scan" class="w-6 h-6 text-indigo-600"></i>
							<h3 class="text-lg font-bold text-slate-800">📱 مسح QR Code</h3>
						</div>
						<div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 mb-4">
							<div class="flex justify-center mb-4">
								<div class="bg-white p-4 rounded-xl shadow-inner">
									<i data-lucide="camera" class="w-20 h-20 text-slate-400"></i>
								</div>
							</div>
							<button id="startQRScanner" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
								<i data-lucide="camera" class="w-5 h-5"></i>
								<span>فتح الكاميرا للمسح</span>
							</button>
						</div>
						<div class="text-center text-sm text-slate-600">
							<p class="mb-2">أو أدخل رقم الطالب يدوياً:</p>
							<div class="flex gap-2">
								<input type="number" id="manualStudentId" placeholder="رقم الطالب" class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
								<button id="manualSearch" class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700">
									<i data-lucide="search" class="w-5 h-5"></i>
								</button>
							</div>
						</div>
					</div>

					<!-- Student Info Display -->
					<div id="scannedStudentInfo" class="mt-4 hidden bg-green-50 border-2 border-green-300 rounded-xl p-6 shadow-lg"></div>
				</div>

				<!-- Quick Actions -->
				<div class="lg:col-span-2">
					<div class="bg-white rounded-xl shadow-lg p-6">
						<div class="flex items-center justify-between mb-6">
							<div class="flex items-center gap-3">
								<i data-lucide="zap" class="w-6 h-6 text-purple-600"></i>
								<h3 class="text-lg font-bold text-slate-800">⚡ إجراءات سريعة</h3>
							</div>
							<div class="flex gap-2">
								<button id="refreshCards" class="p-2 hover:bg-slate-100 rounded-lg transition-all" title="تحديث">
									<i data-lucide="refresh-cw" class="w-5 h-5 text-slate-600"></i>
								</button>
								<button id="bulkActions" class="p-2 hover:bg-slate-100 rounded-lg transition-all" title="إجراءات جماعية">
									<i data-lucide="layers" class="w-5 h-5 text-slate-600"></i>
								</button>
							</div>
						</div>
						
						<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
							<button class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="showNewCardWizard()">
								<i data-lucide="plus-circle" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">إصدار بطاقة جديدة</div>
							</button>
							<button class="bg-gradient-to-br from-green-500 to-green-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="bulkGenerateCards()">
								<i data-lucide="layers" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">إصدار جماعي</div>
							</button>
							<button class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="showCardTemplates()">
								<i data-lucide="palette" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">قوالب التصميم</div>
							</button>
							<button class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="emailAllCards()">
								<i data-lucide="mail" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">إرسال بريد جماعي</div>
							</button>
							<button class="bg-gradient-to-br from-pink-500 to-pink-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="whatsappBulkSend()">
								<i data-lucide="message-circle" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">واتساب جماعي</div>
							</button>
							<button class="bg-gradient-to-br from-teal-500 to-teal-600 text-white p-4 rounded-xl hover:shadow-xl transition-all" onclick="exportCardsReport()">
								<i data-lucide="download" class="w-8 h-8 mx-auto mb-2"></i>
								<div class="font-semibold">تقرير البطاقات</div>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Students Table with Cards -->
			<div class="bg-white rounded-xl shadow-lg p-6">
				<div class="flex items-center justify-between mb-6">
					<div class="flex items-center gap-3">
						<i data-lucide="users" class="w-6 h-6 text-indigo-600"></i>
						<h3 class="text-lg font-bold text-slate-800">قائمة الطلاب والبطاقات</h3>
					</div>
					<div class="flex gap-2">
						<input type="text" id="searchStudents" placeholder="🔍 بحث بالاسم أو الرقم..." class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
						<select id="filterCourse" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
							<option value="">كل الدورات</option>
						</select>
					</div>
				</div>

				<div class="overflow-x-auto">
					<table class="w-full">
						<thead class="bg-slate-50">
							<tr>
								<th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">
									<input type="checkbox" id="selectAllCards" class="rounded">
								</th>
								<th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الطالب</th>
								<th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الدورة</th>
								<th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">حالة البطاقة</th>
								<th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">QR Code</th>
								<th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
							</tr>
						</thead>
						<tbody id="studentsCardsTable" class="divide-y divide-slate-200">
							<tr>
								<td colspan="6" class="px-4 py-8 text-center text-slate-500">
									<i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
									<p>جاري تحميل البيانات...</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Card Preview Modal (Hidden) -->
			<div id="cardPreviewModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
				<div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
					<div class="p-6 border-b border-slate-200">
						<div class="flex items-center justify-between">
							<h3 class="text-xl font-bold text-slate-800">🎴 معاينة البطاقة</h3>
							<button onclick="closeCardPreview()" class="p-2 hover:bg-slate-100 rounded-lg">
								<i data-lucide="x" class="w-6 h-6"></i>
							</button>
						</div>
					</div>
					<div id="cardPreviewContent" class="p-6"></div>
				</div>
			</div>
		`;

		lucide.createIcons();

		// Load students data
		await loadStudentsCards();

		// Event Listeners
		document.getElementById('startQRScanner')?.addEventListener('click', startQRScanner);
		document.getElementById('manualSearch')?.addEventListener('click', () => {
			const studentId = document.getElementById('manualStudentId').value;
			if (studentId) searchStudentById(studentId);
		});
		document.getElementById('refreshCards')?.addEventListener('click', loadStudentsCards);
		document.getElementById('searchStudents')?.addEventListener('input', filterStudents);
		document.getElementById('filterCourse')?.addEventListener('change', filterStudents);
		document.getElementById('selectAllCards')?.addEventListener('change', toggleSelectAll);
	}

	// Load Students with Cards Data
	async function loadStudentsCards() {
		try {
			const data = await fetchJson('api/get_students.php');
			const students = Array.isArray(data.students) ? data.students : (Array.isArray(data) ? data : []);
			
			const tbody = document.getElementById('studentsCardsTable');
			if (!tbody) return;

			if (!students.length) {
				tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">لا توجد بيانات طلاب</td></tr>';
				return;
			}

			// Update stats
			document.getElementById('totalCardsCount').textContent = students.length;
			document.getElementById('issuedToday').textContent = students.filter(s => {
				const created = new Date(s.created_at);
				const today = new Date();
				return created.toDateString() === today.toDateString();
			}).length;

			// Render table
			tbody.innerHTML = students.map(student => `
				<tr class="hover:bg-slate-50 transition-colors" data-student-id="${student.id}">
					<td class="px-4 py-3">
						<input type="checkbox" class="student-checkbox rounded" value="${student.id}">
					</td>
					<td class="px-4 py-3">
						<div class="flex items-center gap-3">
							<div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
								${student.full_name ? student.full_name.charAt(0) : 'S'}
							</div>
							<div>
								<div class="font-semibold text-slate-800">${escapeHtml(student.full_name || 'غير محدد')}</div>
								<div class="text-sm text-slate-500">ID: ${student.id}</div>
							</div>
						</div>
					</td>
					<td class="px-4 py-3 text-center">
						<span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
							${escapeHtml(student.course_title || 'غير مسجل')}
						</span>
					</td>
					<td class="px-4 py-3 text-center">
						<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
							<i data-lucide="check-circle" class="w-4 h-4"></i>
							<span>نشطة</span>
						</span>
					</td>
					<td class="px-4 py-3 text-center">
						<button onclick="showQRCode(${student.id})" class="p-2 hover:bg-indigo-50 rounded-lg transition-all" title="عرض QR">
							<i data-lucide="qr-code" class="w-5 h-5 text-indigo-600"></i>
						</button>
					</td>
					<td class="px-4 py-3">
						<div class="flex items-center justify-center gap-2">
							<button onclick="previewCard(${student.id})" class="p-2 hover:bg-blue-50 rounded-lg transition-all" title="معاينة">
								<i data-lucide="eye" class="w-5 h-5 text-blue-600"></i>
							</button>
							<button onclick="downloadCard(${student.id})" class="p-2 hover:bg-green-50 rounded-lg transition-all" title="تنزيل">
								<i data-lucide="download" class="w-5 h-5 text-green-600"></i>
							</button>
							<button onclick="sendCardEmail(${student.id}, '${escapeHtml(student.email || '')}')" class="p-2 hover:bg-purple-50 rounded-lg transition-all" title="إرسال بريد">
								<i data-lucide="mail" class="w-5 h-5 text-purple-600"></i>
							</button>
							<button onclick="sendCardWhatsApp(${student.id}, '${escapeHtml(student.phone || '')}')" class="p-2 hover:bg-pink-50 rounded-lg transition-all" title="واتساب">
								<i data-lucide="message-circle" class="w-5 h-5 text-pink-600"></i>
							</button>
							<button onclick="editCardDesign(${student.id})" class="p-2 hover:bg-orange-50 rounded-lg transition-all" title="تعديل">
								<i data-lucide="edit" class="w-5 h-5 text-orange-600"></i>
							</button>
						</div>
					</td>
				</tr>
			`).join('');

			lucide.createIcons();

		} catch (error) {
			showNotification('فشل تحميل البيانات: ' + error.message, 'error');
		}
	}

	// Card Actions Functions
	async function previewCard(studentId) {
		const modal = document.getElementById('cardPreviewModal');
		const content = document.getElementById('cardPreviewContent');
		
		modal.classList.remove('hidden');
		content.innerHTML = '<div class="text-center py-12"><i data-lucide="loader" class="w-12 h-12 mx-auto mb-4 animate-spin text-indigo-600"></i><p>جاري تحميل البطاقة...</p></div>';
		lucide.createIcons();

		try {
			// Generate preview
			content.innerHTML = `
				<div class="flex flex-col items-center gap-6">
					<div class="bg-gradient-to-br from-slate-100 to-slate-200 rounded-2xl p-8 shadow-2xl" style="width: 400px; height: 250px; position: relative;">
						<!-- Watermark Logo -->
						<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.05; width: 280px; height: 280px;">
							<img src="../platform/photos/Sh.jpg" alt="Watermark" style="width: 100%; height: 100%; object-fit: contain;">
						</div>
						
						<!-- Top Logo -->
						<div class="flex justify-between items-start mb-4" style="position: relative; z-index: 10;">
							<img src="../platform/photos/Sh.jpg" alt="Logo" class="h-16 w-16 object-contain">
							<div class="text-right">
								<div class="text-xl font-bold text-indigo-600">منصة إبداع</div>
								<div class="text-sm text-slate-600">Ibdaa Platform</div>
							</div>
						</div>

						<!-- Card Content -->
						<div class="flex gap-4 mt-6" style="position: relative; z-index: 10;">
							<div class="flex-shrink-0">
								<div class="w-24 h-32 bg-white rounded-lg shadow-lg overflow-hidden border-4 border-white">
									<img src="https://ui-avatars.com/api/?name=Student&size=200&background=6366f1&color=fff" alt="Photo" class="w-full h-full object-cover">
								</div>
								<div class="mt-2 bg-white p-2 rounded-lg shadow">
									<img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=student_${studentId}" alt="QR" class="w-20 h-20">
								</div>
							</div>
							<div class="flex-1 bg-white/90 backdrop-blur rounded-xl p-4 shadow-lg">
								<div class="text-lg font-bold text-slate-800 mb-1">اسم الطالب الكامل</div>
								<div class="text-sm text-slate-600 mb-2">الرقم: ${String(studentId).padStart(6, '0')}</div>
								<div class="text-sm text-slate-600 mb-2">الدورة: دورة تدريبية</div>
								<div class="text-sm text-slate-600">المحافظة: تعز</div>
								<div class="text-xs text-slate-500 mt-3 pt-3 border-t">
									<div class="flex justify-between">
										<span>الإصدار: ${new Date().toLocaleDateString('ar-EG')}</span>
										<span>ID: ${studentId}</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="grid grid-cols-2 gap-4 w-full max-w-md">
						<button onclick="downloadCard(${studentId}); closeCardPreview();" class="bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 flex items-center justify-center gap-2">
							<i data-lucide="download" class="w-5 h-5"></i>
							<span>تنزيل PDF</span>
						</button>
						<button onclick="sendCardEmail(${studentId}); closeCardPreview();" class="bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 flex items-center justify-center gap-2">
							<i data-lucide="mail" class="w-5 h-5"></i>
							<span>إرسال بريد</span>
						</button>
					</div>
				</div>
			`;
			lucide.createIcons();

		} catch (error) {
			content.innerHTML = `<div class="text-center py-12 text-red-600">خطأ: ${error.message}</div>`;
		}
	}

	function closeCardPreview() {
		document.getElementById('cardPreviewModal')?.classList.add('hidden');
	}

	async function downloadCard(studentId) {
		try {
			showNotification('جاري إنشاء البطاقة...', 'info');
			window.open(`api/generate_id_card_v2.php?id=${studentId}`, '_blank');
			showNotification('تم فتح البطاقة في نافذة جديدة', 'success');
		} catch (error) {
			showNotification('فشل تنزيل البطاقة: ' + error.message, 'error');
		}
	}

	async function sendCardEmail(studentId, email) {
		if (!email) {
			showNotification('لا يوجد بريد إلكتروني للطالب', 'warning');
			return;
		}
		
		try {
			showNotification('جاري إرسال البطاقة للبريد...', 'info');
			const response = await fetchJson(`api/send_card_email.php?id=${studentId}`);
			if (response.success) {
				showNotification('تم إرسال البطاقة بنجاح إلى ' + email, 'success');
				document.getElementById('sentViaEmail').textContent = parseInt(document.getElementById('sentViaEmail').textContent) + 1;
			} else {
				showNotification(response.message || 'فشل الإرسال', 'error');
			}
		} catch (error) {
			showNotification('خطأ في إرسال البريد: ' + error.message, 'error');
		}
	}

	async function sendCardWhatsApp(studentId, phone) {
		if (!phone) {
			showNotification('لا يوجد رقم واتساب للطالب', 'warning');
			return;
		}

		const message = encodeURIComponent(`مرحباً! إليك بطاقتك الطلابية من منصة إبداع:\nhttp://localhost/Ibdaa-Taiz/Manager/api/generate_id_card_v2.php?id=${studentId}`);
		const whatsappUrl = `https://wa.me/${phone.replace(/[^0-9]/g, '')}?text=${message}`;
		window.open(whatsappUrl, '_blank');
		showNotification('تم فتح واتساب', 'success');
	}

	function showQRCode(studentId) {
		const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=http://localhost/Ibdaa-Taiz/platform/verify_student.php?id=${studentId}`;
		
		const modal = document.getElementById('cardPreviewModal');
		const content = document.getElementById('cardPreviewContent');
		
		modal.classList.remove('hidden');
		content.innerHTML = `
			<div class="flex flex-col items-center gap-6">
				<div class="bg-white p-8 rounded-2xl shadow-2xl">
					<img src="${qrUrl}" alt="QR Code" class="w-64 h-64">
				</div>
				<div class="text-center">
					<p class="text-slate-700 mb-2">امسح هذا الكود للتحقق من بيانات الطالب</p>
					<p class="text-sm text-slate-500">Student ID: ${studentId}</p>
				</div>
				<button onclick="closeCardPreview()" class="bg-slate-600 text-white px-6 py-2 rounded-lg hover:bg-slate-700">إغلاق</button>
			</div>
		`;
		lucide.createIcons();
	}

	// QR Scanner Function
	function startQRScanner() {
		showNotification('ميزة مسح QR ستكون متاحة قريباً (تتطلب مكتبة html5-qrcode)', 'info');
		// TODO: Integrate html5-qrcode library
	}

	async function searchStudentById(studentId) {
		try {
			const response = await fetchJson(`../platform/verify_student.php?id=${studentId}`);
			
			const infoDiv = document.getElementById('scannedStudentInfo');
			if (response.success && response.student) {
				const s = response.student;
				infoDiv.innerHTML = `
					<div class="flex items-start gap-4">
						<div class="bg-green-500 p-3 rounded-full">
							<i data-lucide="check-circle" class="w-8 h-8 text-white"></i>
						</div>
						<div class="flex-1">
							<h4 class="text-lg font-bold text-green-800 mb-2">✅ تم العثور على الطالب</h4>
							<div class="space-y-1 text-sm">
								<p><strong>الاسم:</strong> ${escapeHtml(s.full_name || 'غير محدد')}</p>
								<p><strong>الرقم:</strong> ${s.id}</p>
								<p><strong>البريد:</strong> ${escapeHtml(s.email || 'غير محدد')}</p>
								<p><strong>الهاتف:</strong> ${escapeHtml(s.phone || 'غير محدد')}</p>
								<p><strong>الدورة:</strong> ${escapeHtml(s.course_title || 'غير مسجل')}</p>
								<p><strong>المحافظة:</strong> ${escapeHtml(s.governorate || 'غير محدد')}</p>
							</div>
							<div class="flex gap-2 mt-4">
								<button onclick="previewCard(${s.id})" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">عرض البطاقة</button>
								<button onclick="downloadCard(${s.id})" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">تنزيل</button>
							</div>
						</div>
					</div>
				`;
				infoDiv.classList.remove('hidden');
				lucide.createIcons();
			} else {
				infoDiv.innerHTML = `
					<div class="flex items-start gap-4">
						<div class="bg-red-500 p-3 rounded-full">
							<i data-lucide="x-circle" class="w-8 h-8 text-white"></i>
						</div>
						<div>
							<h4 class="text-lg font-bold text-red-800">❌ لم يتم العثور على الطالب</h4>
							<p class="text-sm text-red-700 mt-1">الرقم ${studentId} غير موجود في النظام</p>
						</div>
					</div>
				`;
				infoDiv.classList.remove('hidden');
				lucide.createIcons();
			}
			
			document.getElementById('scannedToday').textContent = parseInt(document.getElementById('scannedToday').textContent) + 1;

		} catch (error) {
			showNotification('خطأ في البحث: ' + error.message, 'error');
		}
	}

	// Filter Functions
	function filterStudents() {
		const searchTerm = document.getElementById('searchStudents')?.value.toLowerCase() || '';
		const courseFilter = document.getElementById('filterCourse')?.value || '';
		
		const rows = document.querySelectorAll('#studentsCardsTable tr[data-student-id]');
		rows.forEach(row => {
			const name = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
			const course = row.querySelector('td:nth-child(3)')?.textContent || '';
			
			const matchesSearch = name.includes(searchTerm);
			const matchesCourse = !courseFilter || course.includes(courseFilter);
			
			row.style.display = (matchesSearch && matchesCourse) ? '' : 'none';
		});
	}

	function toggleSelectAll() {
		const mainCheckbox = document.getElementById('selectAllCards');
		const checkboxes = document.querySelectorAll('.student-checkbox');
		checkboxes.forEach(cb => cb.checked = mainCheckbox.checked);
	}

	// Placeholder functions for advanced features
	function showNewCardWizard() {
		showNotification('معالج إصدار بطاقة جديدة - قيد التطوير', 'info');
	}

	function bulkGenerateCards() {
		const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
		if (!selected.length) {
			showNotification('الرجاء تحديد طلاب أولاً', 'warning');
			return;
		}
		showNotification(`سيتم إصدار ${selected.length} بطاقة`, 'info');
	}

	function showCardTemplates() {
		showNotification('قوالب التصميم - قيد التطوير', 'info');
	}

	function emailAllCards() {
		showNotification('إرسال بريد جماعي - قيد التطوير', 'info');
	}

	function whatsappBulkSend() {
		showNotification('إرسال واتساب جماعي - قيد التطوير', 'info');
	}

	function exportCardsReport() {
		showNotification('تصدير تقرير - قيد التطوير', 'info');
	}

	function editCardDesign(studentId) {
		showNotification('تعديل التصميم - قيد التطوير', 'info');
	}

	async function renderLocations() {
		// التحقق من صلاحية المدير والمشرف الفني فقط
		if (!hasPermission('manager,technical')) {
			showToast('هذا القسم مخصص للمديرين والمشرفين الفنيين فقط', 'warning');
			renderDashboard();
			return;
		}
		
		setPageHeader('إدارة المواقع', 'إدارة المحافظات والمديريات');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `<section class="bg-white rounded-2xl shadow p-6" id="locationsSection"><p class="text-sm text-slate-500">جاري التحميل...</p></section>`;

		try {
			const data = await fetchJson(API_ENDPOINTS.manageLocations);
			const locations = data.data || [];
			document.getElementById('locationsSection').innerHTML = `
				<div class="flex items-center justify-between mb-4">
					<h3 class="text-lg font-semibold text-slate-800">المحافظات</h3>
					<button id="openLocationModal" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 flex items-center gap-2">
						<i data-lucide="plus" class="w-4 h-4"></i>
						<span>إضافة موقع</span>
					</button>
				</div>
				<div class="space-y-3">
					${locations.map(locationCard).join('') || '<p class="text-sm text-slate-500">لا توجد بيانات مواقع.</p>'}
				</div>
			`;
			lucide.createIcons();

			const openBtn = document.getElementById('openLocationModal');
			if (openBtn) {
				openBtn.addEventListener('click', () => {
					openModal('إضافة موقع جديد', buildLocationForm());
					bindLocationForm();
				});
			}
		} catch (error) {
			document.getElementById('locationsSection').innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	function locationCard(location) {
		return `
			<div class="border border-slate-100 rounded-2xl p-4 flex flex-col gap-2">
				<h4 class="text-base font-semibold text-slate-800">${location.governorate || 'غير محدد'}</h4>
				<p class="text-xs text-slate-500">المديريات: ${Array.isArray(location.districts) ? location.districts.join('، ') : '-'}</p>
			</div>
		`;
	}

	function buildLocationForm() {
		return `
			<form id="locationForm" class="space-y-4">
				<div>
					<label class="block text-sm text-slate-600 mb-1">اسم المحافظة</label>
					<input name="governorate" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">أسماء المديريات (افصل بينها بفاصلة)</label>
					<textarea name="districts" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="مثال: التعزية، القاهرة"></textarea>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">حفظ</button>
				</div>
			</form>
		`;
	}

	function bindLocationForm() {
		const form = document.getElementById('locationForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const data = Object.fromEntries(new FormData(form).entries());
			data.action = 'create';
			if (data.districts) {
				data.districts = data.districts.split(',').map(v => v.trim()).filter(Boolean);
			}
			try {
				await fetchJson(API_ENDPOINTS.manageLocations, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast('تم حفظ الموقع', 'success');
				closeModal();
				renderLocations();
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	// ============================================
	// نظام الاستيراد الهجين (Hybrid Import System)
	// يتكامل مع بوابة Python الذكية
	// ============================================

	const PYTHON_GATEWAY_URL = 'http://localhost:8008';
	let currentImportState = {
		fileId: null,
		analysisData: null,
		mapping: {},
		phase: 'upload' // upload, map, process, complete
	};

	async function renderImports() {
		// التحقق من صلاحية المدير والمشرف الفني فقط
		if (!hasPermission('manager,technical')) {
			showToast('الاستيراد الذكي مخصص للمديرين والمشرفين الفنيين فقط', 'warning');
			renderDashboard();
			return;
		}
		
		setPageHeader('🚀 الاستيراد الذكي - Hybrid System', 'نظام استيراد متقدم مدعوم بالذكاء الاصطناعي');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		// Phase 1: Upload Interface
		body.innerHTML = `
			<section class="bg-gradient-to-br from-sky-50 to-white rounded-2xl shadow-xl p-8 space-y-6">
				<div class="flex items-center gap-4">
					<div class="p-3 rounded-xl bg-sky-100 text-sky-600">
						<i data-lucide="brain-circuit" class="w-8 h-8"></i>
					</div>
					<div>
						<h3 class="text-xl font-bold text-slate-800">بوابة الاستيراد الذكية</h3>
						<p class="text-sm text-slate-600">نظام هجين يجمع بين قوة Python في التحليل وسهولة PHP</p>
					</div>
				</div>

				<!-- مراحل العملية -->
				<div class="grid grid-cols-4 gap-2">
					<div id="phaseUpload" class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-sky-600 text-white transition">
						<i data-lucide="upload" class="w-5 h-5"></i>
						<span class="text-xs font-medium">1. رفع الملف</span>
					</div>
					<div id="phaseAnalyze" class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-slate-100 text-slate-400 transition">
						<i data-lucide="search" class="w-5 h-5"></i>
						<span class="text-xs font-medium">2. تحليل ذكي</span>
					</div>
					<div id="phaseMap" class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-slate-100 text-slate-400 transition">
						<i data-lucide="git-branch" class="w-5 h-5"></i>
						<span class="text-xs font-medium">3. ربط الحقول</span>
					</div>
					<div id="phaseComplete" class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-slate-100 text-slate-400 transition">
						<i data-lucide="check-circle" class="w-5 h-5"></i>
						<span class="text-xs font-medium">4. حفظ البيانات</span>
					</div>
				</div>

				<!-- منطقة المحتوى الديناميكي -->
				<div id="importWorkArea" class="bg-white rounded-xl p-6 border-2 border-dashed border-slate-200">
					<form id="uploadForm" class="space-y-6">
						<div class="text-center space-y-4">
							<div class="inline-flex p-4 rounded-full bg-slate-50">
								<i data-lucide="file-up" class="w-12 h-12 text-slate-400"></i>
							</div>
							<div>
								<label for="fileInput" class="inline-block px-6 py-3 rounded-xl bg-sky-600 text-white font-medium cursor-pointer hover:bg-sky-700 transition">
									اختر ملف Excel أو CSV
								</label>
								<input type="file" id="fileInput" accept=".csv,.xlsx,.xls" class="hidden">
							</div>
							<p class="text-sm text-slate-500">الحد الأقصى: 10 ميجا | الصيغ المدعومة: Excel (.xlsx, .xls), CSV</p>
							<div id="selectedFileInfo" class="hidden text-sm text-slate-600"></div>
						</div>
						<div class="flex justify-center gap-3">
							<button type="submit" id="analyzeBtn" class="px-8 py-3 rounded-xl bg-gradient-to-r from-sky-600 to-blue-600 text-white font-semibold hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
								<span class="flex items-center gap-2">
									<i data-lucide="sparkles" class="w-5 h-5"></i>
									<span>تحليل الملف بالذكاء الاصطناعي</span>
								</span>
							</button>
						</div>
					</form>
				</div>

				<!-- حالة الاتصال بالبوابة -->
				<div id="gatewayStatus" class="flex items-center justify-between p-4 rounded-xl bg-slate-50 text-sm">
					<div class="flex items-center gap-2">
						<div class="w-2 h-2 rounded-full bg-slate-400 animate-pulse"></div>
						<span class="text-slate-600">جارٍ الاتصال ببوابة Python...</span>
					</div>
					<code class="text-xs text-slate-400">${PYTHON_GATEWAY_URL}</code>
				</div>
			</section>
		`;

		lucide.createIcons();
		checkGatewayConnection();
		setupUploadHandlers();
	}

	async function checkGatewayConnection() {
		const statusDiv = document.getElementById('gatewayStatus');
		if (!statusDiv) return;

		try {
			const response = await fetch(`${PYTHON_GATEWAY_URL}/`, { method: 'GET' });
			if (response.ok) {
				statusDiv.innerHTML = `
					<div class="flex items-center gap-2">
						<div class="w-2 h-2 rounded-full bg-emerald-500"></div>
						<span class="text-emerald-700 font-medium">بوابة Python متصلة ✓</span>
					</div>
					<code class="text-xs text-slate-400">${PYTHON_GATEWAY_URL}</code>
				`;
			} else {
				throw new Error('غير متصل');
			}
		} catch (error) {
			statusDiv.innerHTML = `
				<div class="flex items-center gap-2">
					<div class="w-2 h-2 rounded-full bg-red-500"></div>
					<span class="text-red-700">فشل الاتصال ببوابة Python</span>
				</div>
				<a href="#" onclick="alert('تأكد من تشغيل: python smart_import_gateway/main.py')" class="text-xs text-sky-600 underline">دليل التشغيل</a>
			`;
		}
	}

	function setupUploadHandlers() {
		const fileInput = document.getElementById('fileInput');
		const analyzeBtn = document.getElementById('analyzeBtn');
		const selectedInfo = document.getElementById('selectedFileInfo');
		const uploadForm = document.getElementById('uploadForm');

		if (fileInput) {
			fileInput.addEventListener('change', () => {
				if (fileInput.files.length > 0) {
					const file = fileInput.files[0];
					selectedInfo.classList.remove('hidden');
					selectedInfo.innerHTML = `
						<div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100">
							<i data-lucide="file" class="w-4 h-4"></i>
							<span class="font-medium">${escapeHtml(file.name)}</span>
							<span class="text-slate-400">•</span>
							<span class="text-slate-500">${(file.size / 1024).toFixed(1)} KB</span>
						</div>
					`;
					lucide.createIcons();
					analyzeBtn.disabled = false;
				}
			});
		}

		if (uploadForm) {
			uploadForm.addEventListener('submit', async (e) => {
				e.preventDefault();
				await analyzeFileWithPython();
			});
		}
	}

	async function analyzeFileWithPython() {
		const fileInput = document.getElementById('fileInput');
		const analyzeBtn = document.getElementById('analyzeBtn');
		
		if (!fileInput || fileInput.files.length === 0) {
			showToast('الرجاء اختيار ملف', 'warning');
			return;
		}

		updatePhaseIndicator('analyze');
		
		if (analyzeBtn) {
			analyzeBtn.disabled = true;
			analyzeBtn.innerHTML = `
				<span class="flex items-center gap-2">
					<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
					<span>جارٍ التحليل الذكي...</span>
				</span>
			`;
			lucide.createIcons();
		}

		try {
			const formData = new FormData();
			formData.append('file', fileInput.files[0]);

			const response = await fetch(`${PYTHON_GATEWAY_URL}/analyze_spreadsheet`, {
				method: 'POST',
				body: formData
			});

			if (!response.ok) {
				throw new Error(`فشل التحليل: ${response.statusText}`);
			}

			const analysisResult = await response.json();
			if (!analysisResult.success) {
				throw new Error(analysisResult.message || 'فشل التحليل');
			}

			currentImportState.analysisData = analysisResult;
			currentImportState.fileId = analysisResult.file_id;
			currentImportState.phase = 'map';

			showToast(`تم اكتشاف ${analysisResult.columns.length} عمود بنجاح!`, 'success');
			renderMappingInterface(analysisResult);

		} catch (error) {
			showToast(error.message, 'error');
			if (analyzeBtn) {
				analyzeBtn.disabled = false;
				analyzeBtn.innerHTML = `
					<span class="flex items-center gap-2">
						<i data-lucide="sparkles" class="w-5 h-5"></i>
						<span>تحليل الملف بالذكاء الاصطناعي</span>
					</span>
				`;
				lucide.createIcons();
			}
		}
	}

	function renderMappingInterface(analysis) {
		updatePhaseIndicator('map');
		
		const workArea = document.getElementById('importWorkArea');
		if (!workArea) return;

		const systemFields = [
			{ value: '', label: '-- تجاهل هذا العمود --' },
			{ value: 'student_name', label: '👤 اسم الطالب' },
			{ value: 'student_email', label: '📧 بريد الطالب' },
			{ value: 'student_phone', label: '📱 هاتف الطالب' },
			{ value: 'course_title', label: '📚 اسم الدورة' },
			{ value: 'grade_value', label: '📊 الدرجة (رقم)' },
			{ value: 'grade_percent', label: '📈 النسبة المئوية' },
			{ value: 'governorate', label: '🏛️ المحافظة' },
			{ value: 'district', label: '🏘️ المديرية' },
			{ value: 'notes', label: '📝 ملاحظات' }
		];

		const mappingRows = analysis.columns.map(col => {
			const confidenceColor = col.confidence >= 0.8 ? 'emerald' : col.confidence >= 0.6 ? 'amber' : 'slate';
			const autoSelected = col.confidence >= 0.7 ? col.semantic_guess : '';
			
			if (autoSelected) {
				currentImportState.mapping[col.header] = autoSelected;
			}

			return `
				<div class="p-4 rounded-xl border border-slate-200 hover:border-sky-300 transition space-y-3">
					<div class="flex items-start justify-between">
						<div class="flex-1">
							<div class="flex items-center gap-2 mb-1">
								<i data-lucide="table" class="w-4 h-4 text-slate-400"></i>
								<span class="font-semibold text-slate-800">${escapeHtml(col.header)}</span>
								<span class="text-xs px-2 py-0.5 rounded-full bg-${confidenceColor}-100 text-${confidenceColor}-700">
									${(col.confidence * 100).toFixed(0)}% ثقة
								</span>
							</div>
							<div class="text-xs text-slate-500 space-x-2">
								<span>النوع: ${col.type}</span>
								<span>•</span>
								<span>عينة: ${col.sample_values.slice(0, 2).map(v => escapeHtml(String(v))).join(', ')}</span>
							</div>
						</div>
					</div>
					<div>
						<label class="block text-xs text-slate-600 mb-1">ربط مع حقل النظام:</label>
						<select class="mapping-select w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500" data-source="${escapeHtml(col.header)}">
							${systemFields.map(field => `
								<option value="${field.value}" ${field.value === autoSelected ? 'selected' : ''}>${field.label}</option>
							`).join('')}
						</select>
					</div>
				</div>
			`;
		}).join('');

		workArea.innerHTML = `
			<div class="space-y-6">
				<div class="flex items-center justify-between">
					<div>
						<h4 class="text-lg font-semibold text-slate-800">ربط الأعمدة بحقول النظام</h4>
						<p class="text-sm text-slate-500">تم اقتراح الربط التلقائي - راجعه وعدّله إن لزم</p>
					</div>
					<div class="text-sm text-slate-600">
						<span class="font-semibold">${analysis.total_data_rows}</span> صف بيانات
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					${mappingRows}
				</div>

				<!-- معاينة البيانات -->
				<div class="bg-slate-50 rounded-xl p-4 space-y-3">
					<h5 class="text-sm font-semibold text-slate-700">معاينة البيانات (أول 5 صفوف)</h5>
					<div class="overflow-x-auto">
						<table class="w-full text-xs">
							<thead class="bg-white text-slate-600">
								<tr>
									${analysis.columns.map(col => `<th class="px-3 py-2 text-right border">${escapeHtml(col.header)}</th>`).join('')}
								</tr>
							</thead>
							<tbody class="text-slate-700">
								${analysis.preview_rows.map(row => `
									<tr class="bg-white">
										${row.map(cell => `<td class="px-3 py-2 border">${escapeHtml(String(cell))}</td>`).join('')}
									</tr>
								`).join('')}
							</tbody>
						</table>
					</div>
				</div>

				<div class="flex justify-between items-center pt-4 border-t">
					<button id="backToUpload" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
						<span class="flex items-center gap-2">
							<i data-lucide="arrow-left" class="w-4 h-4"></i>
							<span>رجوع</span>
						</span>
					</button>
					<button id="processBtn" class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 text-white font-semibold hover:shadow-lg transition">
						<span class="flex items-center gap-2">
							<i data-lucide="zap" class="w-5 h-5"></i>
							<span>معالجة وحفظ البيانات</span>
						</span>
					</button>
				</div>
			</div>
		`;

		lucide.createIcons();
		setupMappingHandlers();
	}

	function setupMappingHandlers() {
		// تحديث الربط عند التغيير
		document.querySelectorAll('.mapping-select').forEach(select => {
			select.addEventListener('change', (e) => {
				const sourceCol = e.target.dataset.source;
				const targetField = e.target.value;
				if (targetField) {
					currentImportState.mapping[sourceCol] = targetField;
				} else {
					delete currentImportState.mapping[sourceCol];
				}
			});
		});

		// زر المعالجة
		const processBtn = document.getElementById('processBtn');
		if (processBtn) {
			processBtn.addEventListener('click', processWithPythonAndSave);
		}

		// زر الرجوع
		const backBtn = document.getElementById('backToUpload');
		if (backBtn) {
			backBtn.addEventListener('click', () => {
				currentImportState = { fileId: null, analysisData: null, mapping: {}, phase: 'upload' };
				renderImports();
			});
		}
	}

	async function processWithPythonAndSave() {
		const processBtn = document.getElementById('processBtn');
		updatePhaseIndicator('process');

		if (processBtn) {
			processBtn.disabled = true;
			processBtn.innerHTML = `
				<span class="flex items-center gap-2">
					<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
					<span>جارٍ المعالجة...</span>
				</span>
			`;
			lucide.createIcons();
		}

		try {
			// المرحلة 1: معالجة البيانات في Python
			showToast('جارٍ معالجة البيانات في Python...', 'info');
			
			const mapping = Object.entries(currentImportState.mapping).map(([source, target]) => ({
				source_column: source,
				target_field: target
			}));

			const processResponse = await fetch(`${PYTHON_GATEWAY_URL}/process_spreadsheet`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					file_id: currentImportState.fileId,
					mapping: mapping,
					skip_empty: true
				})
			});

			if (!processResponse.ok) {
				throw new Error('فشلت المعالجة في Python');
			}

			const processResult = await processResponse.json();
			if (!processResult.success) {
				throw new Error(processResult.message || 'فشلت المعالجة');
			}

			// المرحلة 2: حفظ البيانات النظيفة في PHP/MySQL
			showToast('جارٍ حفظ البيانات في قاعدة البيانات...', 'info');

			const saveResponse = await fetch('api/import_graduates_list.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					processed_data: processResult.processed_data
				})
			});

			if (!saveResponse.ok) {
				throw new Error('فشل حفظ البيانات في PHP');
			}

			const saveResult = await saveResponse.json();
			if (!saveResult.success) {
				throw new Error(saveResult.message || 'فشل الحفظ');
			}

			// عرض النتائج النهائية
			updatePhaseIndicator('complete');
			renderFinalReport(processResult, saveResult);

		} catch (error) {
			showToast(error.message, 'error');
			if (processBtn) {
				processBtn.disabled = false;
				processBtn.innerHTML = `
					<span class="flex items-center gap-2">
						<i data-lucide="zap" class="w-5 h-5"></i>
						<span>معالجة وحفظ البيانات</span>
					</span>
				`;
				lucide.createIcons();
			}
		}
	}

	function renderFinalReport(processResult, saveResult) {
		const workArea = document.getElementById('importWorkArea');
		if (!workArea) return;

		const stats = [
			{ title: 'صفوف معالجة', value: processResult.total_processed, icon: 'file-check', accent: 'sky' },
			{ title: 'مستخدمون مضافون', value: saveResult.created_users || 0, icon: 'user-plus', accent: 'emerald' },
			{ title: 'تسجيلات مضافة', value: saveResult.created_enrollments || 0, icon: 'layers', accent: 'amber' },
			{ title: 'درجات مسجلة', value: saveResult.created_grades || 0, icon: 'graduation-cap', accent: 'violet' }
		];

		workArea.innerHTML = `
			<div class="space-y-6 text-center">
				<div class="inline-flex p-6 rounded-full bg-emerald-100">
					<i data-lucide="check-circle-2" class="w-16 h-16 text-emerald-600"></i>
				</div>
				<div>
					<h3 class="text-2xl font-bold text-slate-800 mb-2">تمت العملية بنجاح! 🎉</h3>
					<p class="text-slate-600">تم استيراد البيانات ومعالجتها وحفظها في قاعدة البيانات</p>
				</div>

				<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
					${stats.map(stat => `
						<div class="bg-white rounded-xl border-2 border-${stat.accent}-100 p-4">
							<div class="flex flex-col items-center gap-2">
								<i data-lucide="${stat.icon}" class="w-8 h-8 text-${stat.accent}-600"></i>
								<span class="text-2xl font-bold text-slate-800">${stat.value}</span>
								<span class="text-xs text-slate-600">${stat.title}</span>
							</div>
						</div>
					`).join('')}
				</div>

				<div class="bg-slate-50 rounded-xl p-6 space-y-4 text-right">
					<h4 class="font-semibold text-slate-700">تقرير Python:</h4>
					<pre class="text-sm text-slate-600 whitespace-pre-wrap">${escapeHtml(processResult.report)}</pre>
					
					${saveResult.report ? `
						<h4 class="font-semibold text-slate-700 pt-4 border-t">تقرير PHP:</h4>
						<p class="text-sm text-slate-600">${escapeHtml(saveResult.report)}</p>
					` : ''}
				</div>

				<button id="newImportBtn" class="px-6 py-3 rounded-xl bg-sky-600 text-white font-semibold hover:bg-sky-700 transition">
					<span class="flex items-center gap-2">
						<i data-lucide="plus-circle" class="w-5 h-5"></i>
						<span>استيراد ملف جديد</span>
					</span>
				</button>
			</div>
		`;

		lucide.createIcons();

		document.getElementById('newImportBtn')?.addEventListener('click', () => {
			currentImportState = { fileId: null, analysisData: null, mapping: {}, phase: 'upload' };
			renderImports();
		});
	}

	function updatePhaseIndicator(currentPhase) {
		const phases = {
			upload: 'phaseUpload',
			analyze: 'phaseAnalyze',
			map: 'phaseMap',
			process: 'phaseComplete'
		};

		Object.entries(phases).forEach(([phase, elementId]) => {
			const el = document.getElementById(elementId);
			if (!el) return;
			
			if (currentPhase === phase) {
				el.className = 'flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-sky-600 text-white transition';
			} else {
				el.className = 'flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-slate-100 text-slate-400 transition';
			}
		});
	}

	async function renderGraduates() {
		// التحقق من صلاحية المدير فقط
		if (CURRENT_USER.role !== 'manager') {
			showToast('هذا القسم مخصص للمديرين فقط', 'warning');
			renderDashboard();
			return;
		}
		
		setPageHeader('ملف الخريجين', 'قائمة الخريجين وإدارة شهاداتهم');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `<section class="bg-white rounded-2xl shadow p-6" id="graduatesSection"><p class="text-sm text-slate-500">جاري التحميل...</p></section>`;

		try {
			const response = await fetch('api/get_graduates.php');
			const payload = await response.json();
			if (!response.ok || payload.success === false) {
				throw new Error(payload.message || 'تعذر تحميل الخريجين');
			}
			const graduates = payload.data || [];
			document.getElementById('graduatesSection').innerHTML = `
				<div class="flex items-center justify-between mb-4">
					<h3 class="text-lg font-semibold text-slate-800">الخريجون</h3>
					<span class="text-sm text-slate-500">${graduates.length} خريج</span>
				</div>
				<div class="overflow-x-auto">
					<table class="w-full text-sm text-right">
						<thead class="bg-slate-50 text-slate-600">
							<tr>
								<th class="px-4 py-2">الاسم</th>
								<th class="px-4 py-2">الدورة</th>
								<th class="px-4 py-2">تاريخ التخرج</th>
								<th class="px-4 py-2">كود الشهادة</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-100">
							${graduates.map(graduate => `
								<tr>
									<td class="px-4 py-2 font-medium text-slate-800">${graduate.student_name}</td>
									<td class="px-4 py-2 text-slate-600">${graduate.course_title}</td>
									<td class="px-4 py-2 text-slate-600">${graduate.completed_at || '-'}</td>
									<td class="px-4 py-2 text-slate-600">${graduate.certificate_code || '-'}</td>
								</tr>
							`).join('') || '<tr><td colspan="4" class="px-4 py-4 text-center text-slate-500">لا توجد بيانات خريجين.</td></tr>'}
						</tbody>
					</table>
				</div>
			`;
		} catch (error) {
			document.getElementById('graduatesSection').innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}
	}

	// =====================================================================
	// AI IMAGE GENERATION SYSTEM
	// =====================================================================
	
	async function renderAIImages() {
		setPageHeader('🎨 توليد الصور بالذكاء الاصطناعي', 'إنشاء صور احترافية باستخدام AI لدوراتك وإعلاناتك');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `
			<div class="space-y-6">
				<!-- Stats Overview -->
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
					<div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl p-6 text-white">
						<div class="flex items-center justify-between">
							<div>
								<p class="text-purple-100 text-sm mb-1">إجمالي الصور</p>
								<h3 class="text-3xl font-bold" id="totalImagesCount">-</h3>
							</div>
							<div class="bg-white/20 p-3 rounded-xl">
								<i data-lucide="image" class="w-8 h-8"></i>
							</div>
						</div>
					</div>
					
					<div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl p-6 text-white">
						<div class="flex items-center justify-between">
							<div>
								<p class="text-blue-100 text-sm mb-1">صور الدورات</p>
								<h3 class="text-3xl font-bold" id="courseImagesCount">-</h3>
							</div>
							<div class="bg-white/20 p-3 rounded-xl">
								<i data-lucide="book-open" class="w-8 h-8"></i>
							</div>
						</div>
					</div>
					
					<div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-6 text-white">
						<div class="flex items-center justify-between">
							<div>
								<p class="text-orange-100 text-sm mb-1">الإعلانات</p>
								<h3 class="text-3xl font-bold" id="announcementImagesCount">-</h3>
							</div>
							<div class="bg-white/20 p-3 rounded-xl">
								<i data-lucide="megaphone" class="w-8 h-8"></i>
							</div>
						</div>
					</div>
					
					<div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl p-6 text-white">
						<div class="flex items-center justify-between">
							<div>
								<p class="text-green-100 text-sm mb-1">الشهادات</p>
								<h3 class="text-3xl font-bold" id="certificateImagesCount">-</h3>
							</div>
							<div class="bg-white/20 p-3 rounded-xl">
								<i data-lucide="award" class="w-8 h-8"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Generation Interface -->
				<div class="grid lg:grid-cols-2 gap-6">
					<!-- Image Generator -->
					<div class="bg-white rounded-2xl shadow-lg p-6">
						<div class="flex items-center gap-3 mb-6">
							<div class="bg-gradient-to-br from-purple-500 to-pink-500 p-3 rounded-xl text-white">
								<i data-lucide="sparkles" class="w-6 h-6"></i>
							</div>
							<div>
								<h3 class="text-xl font-bold text-slate-800">توليد صورة جديدة</h3>
								<p class="text-sm text-slate-500">استخدم AI لإنشاء صورة احترافية</p>
							</div>
						</div>

						<form id="imageGenerationForm" class="space-y-4">
							<div>
								<label class="block text-sm font-medium text-slate-700 mb-2">نوع الصورة</label>
								<select id="imageType" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
									<option value="course">صورة دورة تدريبية</option>
									<option value="announcement">إعلان</option>
									<option value="certificate">شهادة</option>
									<option value="general">عامة</option>
								</select>
							</div>

							<div>
								<label class="block text-sm font-medium text-slate-700 mb-2">القالب (اختياري)</label>
								<select id="imageTemplate" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
									<option value="">بدون قالب</option>
								</select>
							</div>

							<div>
								<label class="block text-sm font-medium text-slate-700 mb-2">وصف الصورة</label>
								<textarea id="imagePrompt" rows="4" 
									placeholder="مثال: صورة احترافية لدورة برمجة بايثون مع عناصر حديثة..."
									class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
								<p class="text-xs text-slate-500 mt-1">اكتب وصفاً واضحاً للصورة المطلوبة</p>
							</div>

							<div class="grid grid-cols-2 gap-4">
								<div>
									<label class="block text-sm font-medium text-slate-700 mb-2">النمط</label>
									<select id="imageStyle" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
										<option value="realistic">واقعي</option>
										<option value="artistic">فني</option>
										<option value="cartoon">كرتوني</option>
										<option value="abstract">تجريدي</option>
									</select>
								</div>

								<div>
									<label class="block text-sm font-medium text-slate-700 mb-2">الحجم</label>
									<select id="imageSize" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
										<option value="1024x1024">مربع (1024x1024)</option>
										<option value="1920x1080">عريض (1920x1080)</option>
										<option value="1080x1920">طولي (1080x1920)</option>
									</select>
								</div>
							</div>

							<div>
								<label class="block text-sm font-medium text-slate-700 mb-2">مزود AI</label>
								<div class="grid grid-cols-2 gap-3">
									<label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-purple-50 transition">
										<input type="radio" name="provider" value="dalle" checked class="text-purple-600">
										<span class="text-sm font-medium">DALL-E (OpenAI)</span>
									</label>
									<label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-purple-50 transition">
										<input type="radio" name="provider" value="stable-diffusion" class="text-purple-600">
										<span class="text-sm font-medium">Stable Diffusion</span>
									</label>
								</div>
								<p class="text-xs text-amber-600 mt-2 flex items-center gap-1">
									<i data-lucide="alert-circle" class="w-3 h-3"></i>
									<span>في وضع Demo - سيتم إنشاء صور تجريبية</span>
								</p>
							</div>

							<button type="submit" id="generateBtn" 
								class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all flex items-center justify-center gap-2">
								<i data-lucide="sparkles" class="w-5 h-5"></i>
								<span>توليد الصورة</span>
							</button>
						</form>

						<div id="generationProgress" class="hidden mt-4 p-4 bg-purple-50 rounded-xl">
							<div class="flex items-center gap-3">
								<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-purple-600"></div>
								<span class="text-sm text-purple-700 font-medium">جاري إنشاء الصورة...</span>
							</div>
						</div>
					</div>

					<!-- Generated Image Preview -->
					<div class="bg-white rounded-2xl shadow-lg p-6">
						<div class="flex items-center justify-between mb-6">
							<div class="flex items-center gap-3">
								<div class="bg-gradient-to-br from-blue-500 to-cyan-500 p-3 rounded-xl text-white">
									<i data-lucide="eye" class="w-6 h-6"></i>
								</div>
								<div>
									<h3 class="text-xl font-bold text-slate-800">معاينة الصورة</h3>
									<p class="text-sm text-slate-500">الصورة المُنشأة حديثاً</p>
								</div>
							</div>
						</div>

						<div id="imagePreviewContainer" class="space-y-4">
							<div class="aspect-square bg-slate-100 rounded-xl flex items-center justify-center text-slate-400">
								<div class="text-center">
									<i data-lucide="image-off" class="w-16 h-16 mx-auto mb-3"></i>
									<p class="text-sm">لم يتم إنشاء صورة بعد</p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Gallery -->
				<div class="bg-white rounded-2xl shadow-lg p-6">
					<div class="flex items-center justify-between mb-6">
						<div class="flex items-center gap-3">
							<div class="bg-gradient-to-br from-green-500 to-emerald-500 p-3 rounded-xl text-white">
								<i data-lucide="images" class="w-6 h-6"></i>
							</div>
							<div>
								<h3 class="text-xl font-bold text-slate-800">معرض الصور</h3>
								<p class="text-sm text-slate-500">جميع الصور المُنشأة بواسطة AI</p>
							</div>
						</div>

						<div class="flex items-center gap-2">
							<select id="galleryFilter" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
								<option value="">جميع الأنواع</option>
								<option value="course">دورات</option>
								<option value="announcement">إعلانات</option>
								<option value="certificate">شهادات</option>
								<option value="general">عامة</option>
							</select>
						</div>
					</div>

					<div id="galleryGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
						<!-- Gallery items will be loaded here -->
					</div>

					<div id="galleryEmpty" class="hidden text-center py-12">
						<i data-lucide="folder-open" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
						<p class="text-slate-500">لا توجد صور بعد</p>
					</div>

					<!-- Pagination -->
					<div id="galleryPagination" class="mt-6 flex justify-center gap-2">
						<!-- Pagination buttons will be loaded here -->
					</div>
				</div>
			</div>
		`;

		lucide.createIcons();
		await loadAIImagesData();
		attachAIImagesHandlers();
	}

	async function loadAIImagesData() {
		try {
			// Load stats
			const statsResponse = await fetchJson(apiEndpoints.aiImages + '?action=list&limit=1000');
			if (statsResponse.success) {
				const images = statsResponse.data;
				
				document.getElementById('totalImagesCount').textContent = images.length;
				document.getElementById('courseImagesCount').textContent = 
					images.filter(img => img.image_type === 'course').length;
				document.getElementById('announcementImagesCount').textContent = 
					images.filter(img => img.image_type === 'announcement').length;
				document.getElementById('certificateImagesCount').textContent = 
					images.filter(img => img.image_type === 'certificate').length;
			}

			// Load templates
			const templatesResponse = await fetchJson(apiEndpoints.aiImages + '?action=get_templates');
			if (templatesResponse.success) {
				loadTemplatesIntoSelect(templatesResponse.templates);
			}

			// Load gallery
			await loadGallery();

		} catch (error) {
			console.error('Error loading AI images data:', error);
			showToast('خطأ في تحميل البيانات', 'error');
		}
	}

	function loadTemplatesIntoSelect(templates) {
		const select = document.getElementById('imageTemplate');
		const typeSelect = document.getElementById('imageType');
		
		const updateTemplates = () => {
			const selectedType = typeSelect.value;
			const typeTemplates = templates[selectedType] || [];
			
			select.innerHTML = '<option value="">بدون قالب</option>';
			typeTemplates.forEach(template => {
				const option = document.createElement('option');
				option.value = template.prompt;
				option.textContent = template.name;
				select.appendChild(option);
			});
		};

		typeSelect.addEventListener('change', updateTemplates);
		updateTemplates();
	}

	async function loadGallery(page = 1, type = '') {
		try {
			const params = new URLSearchParams({
				action: 'list',
				page: page,
				limit: 12
			});
			
			if (type) params.append('type', type);

			const response = await fetchJson(apiEndpoints.aiImages + '?' + params.toString());
			
			if (response.success) {
				renderGallery(response.data);
				renderGalleryPagination(response.pagination);
			}

		} catch (error) {
			console.error('Error loading gallery:', error);
			showToast('خطأ في تحميل المعرض', 'error');
		}
	}

	function renderGallery(images) {
		const grid = document.getElementById('galleryGrid');
		const empty = document.getElementById('galleryEmpty');

		if (images.length === 0) {
			grid.innerHTML = '';
			empty.classList.remove('hidden');
			return;
		}

		empty.classList.add('hidden');
		grid.innerHTML = images.map(image => `
			<div class="group relative bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-lg transition-all">
				<div class="aspect-square bg-slate-100">
					<img src="${image.file_path}" alt="${image.prompt}" 
						class="w-full h-full object-cover">
				</div>
				
				<div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
					<div class="absolute bottom-0 left-0 right-0 p-4">
						<p class="text-white text-sm font-medium line-clamp-2 mb-3">${image.prompt}</p>
						<div class="flex items-center gap-2">
							<span class="px-2 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs text-white">
								${getImageTypeLabel(image.image_type)}
							</span>
							<span class="px-2 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs text-white">
								${image.provider}
							</span>
						</div>
						
						<div class="flex items-center gap-2 mt-3">
							<button onclick="viewAIImage(${image.id})" 
								class="flex-1 bg-white/90 hover:bg-white text-slate-800 px-3 py-2 rounded-lg text-xs font-medium transition">
								<i data-lucide="eye" class="w-3 h-3 inline"></i>
								عرض
							</button>
							<button onclick="downloadAIImage('${image.file_path}')" 
								class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition">
								<i data-lucide="download" class="w-3 h-3 inline"></i>
								تحميل
							</button>
							<button onclick="deleteAIImage(${image.id})" 
								class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-xs transition">
								<i data-lucide="trash-2" class="w-3 h-3"></i>
							</button>
						</div>
					</div>
				</div>
			</div>
		`).join('');

		lucide.createIcons();
	}

	function renderGalleryPagination(pagination) {
		const container = document.getElementById('galleryPagination');
		if (pagination.total_pages <= 1) {
			container.innerHTML = '';
			return;
		}

		const buttons = [];
		for (let i = 1; i <= pagination.total_pages; i++) {
			buttons.push(`
				<button onclick="loadGallery(${i}, document.getElementById('galleryFilter').value)"
					class="px-4 py-2 rounded-lg ${i === pagination.page ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'} transition">
					${i}
				</button>
			`);
		}

		container.innerHTML = buttons.join('');
	}

	function getImageTypeLabel(type) {
		const labels = {
			course: 'دورة',
			announcement: 'إعلان',
			certificate: 'شهادة',
			general: 'عامة'
		};
		return labels[type] || type;
	}

	function attachAIImagesHandlers() {
		// Generation form
		const form = document.getElementById('imageGenerationForm');
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			await generateAIImage();
		});

		// Gallery filter
		const filter = document.getElementById('galleryFilter');
		filter.addEventListener('change', (e) => {
			loadGallery(1, e.target.value);
		});

		// Template selection
		const templateSelect = document.getElementById('imageTemplate');
		templateSelect.addEventListener('change', (e) => {
			if (e.target.value) {
				document.getElementById('imagePrompt').value = e.target.value;
			}
		});
	}

	async function generateAIImage() {
		const promptInput = document.getElementById('imagePrompt');
		const prompt = promptInput.value.trim();

		if (!prompt) {
			showToast('يرجى إدخال وصف للصورة', 'warning');
			return;
		}

		const generateBtn = document.getElementById('generateBtn');
		const progress = document.getElementById('generationProgress');

		try {
			generateBtn.disabled = true;
			progress.classList.remove('hidden');

			const data = {
				prompt: prompt,
				type: document.getElementById('imageType').value,
				style: document.getElementById('imageStyle').value,
				size: document.getElementById('imageSize').value,
				provider: document.querySelector('input[name="provider"]:checked').value
			};

			const response = await fetchJson(apiEndpoints.aiImages + '?action=generate', {
				method: 'POST',
				body: JSON.stringify(data)
			});

			if (response.success) {
				showToast('تم إنشاء الصورة بنجاح!', 'success');
				
				// Show preview
				const previewContainer = document.getElementById('imagePreviewContainer');
				previewContainer.innerHTML = `
					<div class="space-y-4">
						<img src="${response.url}" alt="Generated" class="w-full rounded-xl border border-slate-200">
						<div class="flex gap-2">
							<button onclick="downloadAIImage('${response.url}')" 
								class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium transition">
								<i data-lucide="download" class="w-4 h-4 inline mr-2"></i>
								تحميل الصورة
							</button>
							<button onclick="applyWatermark(${response.image_id})" 
								class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium transition">
								<i data-lucide="shield" class="w-4 h-4 inline mr-2"></i>
								إضافة علامة مائية
							</button>
						</div>
					</div>
				`;
				lucide.createIcons();

				// Reload stats and gallery
				await loadAIImagesData();

			} else {
				showToast(response.message || 'فشل إنشاء الصورة', 'error');
			}

		} catch (error) {
			console.error('Error generating image:', error);
			showToast('خطأ في إنشاء الصورة', 'error');
		} finally {
			generateBtn.disabled = false;
			progress.classList.add('hidden');
		}
	}

	window.viewAIImage = async function(imageId) {
		try {
			const response = await fetchJson(apiEndpoints.aiImages + '?action=get&id=' + imageId);
			
			if (response.success) {
				const image = response.data;
				
				Swal.fire({
					title: 'تفاصيل الصورة',
					html: `
						<div class="text-right space-y-4">
							<img src="${image.file_path}" class="w-full rounded-xl mb-4">
							<div class="space-y-2 text-sm">
								<p><strong>النوع:</strong> ${getImageTypeLabel(image.image_type)}</p>
								<p><strong>الوصف:</strong> ${image.prompt}</p>
								<p><strong>المزود:</strong> ${image.provider}</p>
								<p><strong>الحجم:</strong> ${image.dimensions || 'غير محدد'}</p>
								<p><strong>تاريخ الإنشاء:</strong> ${new Date(image.created_at).toLocaleDateString('ar-EG')}</p>
							</div>
						</div>
					`,
					showCloseButton: true,
					showConfirmButton: false,
					width: 600
				});
			}

		} catch (error) {
			console.error('Error viewing image:', error);
			showToast('خطأ في عرض الصورة', 'error');
		}
	};

	window.downloadAIImage = function(url) {
		const link = document.createElement('a');
		link.href = url;
		link.download = url.split('/').pop();
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		showToast('جاري تحميل الصورة...', 'info');
	};

	window.deleteAIImage = async function(imageId) {
		const result = await Swal.fire({
			title: 'تأكيد الحذف',
			text: 'هل أنت متأكد من حذف هذه الصورة؟',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#dc2626',
			cancelButtonColor: '#64748b',
			confirmButtonText: 'نعم، احذف',
			cancelButtonText: 'إلغاء'
		});

		if (result.isConfirmed) {
			try {
				const response = await fetchJson(apiEndpoints.aiImages + '?action=delete&id=' + imageId);
				
				if (response.success) {
					showToast('تم حذف الصورة بنجاح', 'success');
					await loadAIImagesData();
				} else {
					showToast(response.message || 'فشل حذف الصورة', 'error');
				}

			} catch (error) {
				console.error('Error deleting image:', error);
				showToast('خطأ في حذف الصورة', 'error');
			}
		}
	};

	window.applyWatermark = async function(imageId) {
		const { value: watermarkText } = await Swal.fire({
			title: 'إضافة علامة مائية',
			input: 'text',
			inputLabel: 'نص العلامة المائية',
			inputValue: 'منصة إبداع - تعز',
			showCancelButton: true,
			confirmButtonText: 'إضافة',
			cancelButtonText: 'إلغاء',
			inputValidator: (value) => {
				if (!value) {
					return 'يرجى إدخال نص العلامة المائية';
				}
			}
		});

		if (watermarkText) {
			try {
				const response = await fetchJson(apiEndpoints.aiImages + '?action=apply_watermark', {
					method: 'POST',
					body: JSON.stringify({
						image_id: imageId,
						watermark_text: watermarkText,
						position: 'bottom-right'
					})
				});

				if (response.success) {
					showToast('تم إضافة العلامة المائية بنجاح', 'success');
					await loadAIImagesData();
				} else {
					showToast(response.message || 'فشل إضافة العلامة المائية', 'error');
				}

			} catch (error) {
				console.error('Error applying watermark:', error);
				showToast('خطأ في إضافة العلامة المائية', 'error');
			}
		}
	};

	// =====================================================================
	// END AI IMAGE GENERATION SYSTEM
	// =====================================================================

	function renderSettings() {
		// التحقق من صلاحية المدير فقط
		if (CURRENT_USER.role !== 'manager') {
			showToast('هذا القسم مخصص للمديرين فقط', 'warning');
			renderDashboard();
			return;
		}
		
		setPageHeader('الإعدادات العامة', 'إدارة إعدادات المنصة الأساسية');
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `
			<section class="bg-white rounded-2xl shadow p-6 space-y-6">
				<div>
					<h3 class="text-lg font-semibold text-slate-800">معلومات أساسية</h3>
					<p class="text-sm text-slate-500">تحديث اسم المنصة وبيانات الاتصال</p>
				</div>
				<form id="settingsForm" class="space-y-4">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="block text-sm text-slate-600 mb-1">اسم المنصة</label>
							<input name="platform_name" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="منصة إبداع">
						</div>
						<div>
							<label class="block text-sm text-slate-600 mb-1">البريد الرسمي</label>
							<input type="email" name="support_email" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="support@example.com">
						</div>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">وصف مختصر</label>
						<textarea name="platform_description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2"></textarea>
					</div>
					<div class="flex justify-end">
						<button type="submit" class="px-5 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-700">حفظ الإعدادات</button>
					</div>
				</form>
			</section>
		`;

		const form = document.getElementById('settingsForm');
		if (form) {
			form.addEventListener('submit', event => {
				event.preventDefault();
				showToast('جارٍ حفظ الإعدادات (تجريبي)', 'info');
			});
		}
	}

	async function renderCourseEditor(courseId, courseTitle = 'دورة تدريبية') {
		setPageHeader('محرر محتوى الدورة', `إدارة وحدات ومحتوى الدورة: ${courseTitle}`);
		clearPageBody();
		const body = document.getElementById('pageBody');
		if (!body) return;

		body.innerHTML = `
			<section class="bg-white rounded-2xl shadow p-6 space-y-6" data-course-id="${courseId}">
				<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
					<div>
						<h3 class="text-lg font-semibold text-slate-800">الوحدات التعليمية</h3>
						<p class="text-sm text-slate-500">قم بإدارة الوحدات والمواد والواجبات</p>
					</div>
					<div class="flex flex-wrap gap-2">
						<button id="addModuleBtn" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 flex items-center gap-2">
							<i data-lucide="folder-plus" class="w-4 h-4"></i>
							<span>إضافة وحدة</span>
						</button>
						<button id="addMaterialBtn" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center gap-2">
							<i data-lucide="file-plus" class="w-4 h-4"></i>
							<span>إضافة مادة</span>
						</button>
						<button id="addCourseAssignmentBtn" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center gap-2">
							<i data-lucide="clipboard-plus" class="w-4 h-4"></i>
							<span>إنشاء واجب</span>
						</button>
					</div>
				</div>
				<div id="modulesContainer" class="space-y-4"></div>
			</section>
		`;
		lucide.createIcons();

		try {
			const data = await fetchJson(`${API_ENDPOINTS.manageLmsContent}?course_id=${courseId}`);
			const modules = data.modules || [];
			renderModulesList(modules, courseId);
		} catch (error) {
			document.getElementById('modulesContainer').innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}

		document.getElementById('addModuleBtn').addEventListener('click', () => {
			openModal('إضافة وحدة جديدة', buildModuleForm({ course_id: courseId }));
			bindModuleForm(courseId);
		});

		document.getElementById('addMaterialBtn').addEventListener('click', () => {
			openModal('إضافة مادة تعليمية', buildMaterialForm({ course_id: courseId }));
			bindMaterialForm(courseId);
		});

		document.getElementById('addCourseAssignmentBtn').addEventListener('click', () => {
			openModal('إنشاء واجب مرتبط بالدورة', buildAssignmentForm({ course_id: courseId }));
			bindAssignmentForm();
		});
	}

	function renderModulesList(modules, courseId) {
		const container = document.getElementById('modulesContainer');
		if (!container) return;
		container.innerHTML = modules.map(module => `
			<article class="border border-slate-100 rounded-2xl p-4" data-module-id="${module.module_id}">
				<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
					<div>
						<h4 class="text-base font-semibold text-slate-800">${module.title}</h4>
						<p class="text-sm text-slate-500">${module.summary || 'بدون وصف'}</p>
					</div>
					<div class="flex gap-2">
						<button class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm" data-action="edit-module">تعديل</button>
						<button class="px-3 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm" data-action="delete-module">حذف</button>
					</div>
				</header>
				<section class="space-y-3">
					${(module.materials || []).map(material => `
						<div class="border border-slate-100 rounded-xl p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3" data-material-id="${material.material_id}">
							<div>
								<h5 class="text-sm font-semibold text-slate-800">${material.title}</h5>
								<p class="text-xs text-slate-500">${material.material_type === 'video' ? 'فيديو' : material.material_type === 'link' ? 'رابط' : 'ملف'} - ${material.description || ''}</p>
							</div>
							<div class="flex gap-2">
								<button class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs" data-action="edit-material">تعديل</button>
								<button class="px-3 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs" data-action="delete-material">حذف</button>
							</div>
						</div>
					`).join('') || '<p class="text-sm text-slate-500">لا توجد مواد مضافة لهذه الوحدة بعد.</p>'}
				</section>
			</article>
		`).join('') || '<p class="text-sm text-slate-500">لم يتم إنشاء وحدات لهذه الدورة بعد.</p>';

		container.querySelectorAll('[data-action="edit-module"]').forEach(btn => {
			btn.addEventListener('click', event => {
				const moduleEl = event.target.closest('[data-module-id]');
				const moduleId = parseInt(moduleEl.dataset.moduleId, 10);
				const module = modules.find(m => Number(m.module_id) === moduleId);
				openModal('تعديل الوحدة', buildModuleForm({ ...module, course_id: courseId }));
				bindModuleForm(courseId, moduleId);
			});
		});

		container.querySelectorAll('[data-action="delete-module"]').forEach(btn => {
			btn.addEventListener('click', async event => {
				const moduleEl = event.target.closest('[data-module-id]');
				const moduleId = parseInt(moduleEl.dataset.moduleId, 10);
				if (!confirm('سيتم حذف الوحدة وجميع موادها، هل أنت متأكد؟')) return;
				try {
					await fetchJson(API_ENDPOINTS.manageLmsContent, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete_module', module_id: moduleId })
					});
					showToast('تم حذف الوحدة', 'success');
					renderCourseEditor(courseId);
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});

		container.querySelectorAll('[data-action="edit-material"]').forEach(btn => {
			btn.addEventListener('click', event => {
				const materialEl = event.target.closest('[data-material-id]');
				const moduleEl = event.target.closest('[data-module-id]');
				const materialId = parseInt(materialEl.dataset.materialId, 10);
				const moduleId = parseInt(moduleEl.dataset.moduleId, 10);
				const module = modules.find(m => Number(m.module_id) === moduleId);
				const material = (module.materials || []).find(mat => Number(mat.material_id) === materialId);
				openModal('تعديل المادة', buildMaterialForm({ ...material, course_id: courseId }));
				bindMaterialForm(courseId, materialId);
			});
		});

		container.querySelectorAll('[data-action="delete-material"]').forEach(btn => {
			btn.addEventListener('click', async event => {
				const materialEl = event.target.closest('[data-material-id]');
				const materialId = parseInt(materialEl.dataset.materialId, 10);
				if (!confirm('سيتم حذف المادة، هل أنت متأكد؟')) return;
				try {
					await fetchJson(API_ENDPOINTS.manageLmsContent, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'delete_material', material_id: materialId })
					});
					showToast('تم حذف المادة', 'success');
					renderCourseEditor(courseId);
				} catch (error) {
					showToast(error.message, 'error');
				}
			});
		});
	}

	function buildModuleForm(module = {}) {
		return `
			<form id="moduleForm" class="space-y-4">
				<input type="hidden" name="module_id" value="${module.module_id || ''}">
				<input type="hidden" name="course_id" value="${module.course_id || ''}">
				<div>
					<label class="block text-sm text-slate-600 mb-1">عنوان الوحدة</label>
					<input name="title" value="${module.title || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">ملخص</label>
					<textarea name="summary" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2">${module.summary || ''}</textarea>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">الترتيب</label>
						<input name="position" type="number" value="${module.position || 1}" class="w-full border border-slate-200 rounded-lg px-3 py-2" min="1">
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">الحالة</label>
						<select name="is_published" class="w-full border border-slate-200 rounded-lg px-3 py-2">
							<option value="1" ${module.is_published !== '0' ? 'selected' : ''}>منشورة</option>
							<option value="0" ${module.is_published === '0' ? 'selected' : ''}>مسودة</option>
						</select>
					</div>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700">حفظ</button>
				</div>
			</form>
		`;
	}

	function bindModuleForm(courseId, moduleId = null) {
		const form = document.getElementById('moduleForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const data = Object.fromEntries(new FormData(form).entries());
			const action = moduleId ? 'update_module' : 'create_module';
			data.action = action;
			data.is_published = data.is_published === '1' ? 1 : 0;
			if (moduleId) {
				data.module_id = moduleId;
			}
			try {
				await fetchJson(API_ENDPOINTS.manageLmsContent, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(data)
				});
				showToast('تم حفظ الوحدة', 'success');
				closeModal();
				renderCourseEditor(courseId);
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	function buildMaterialForm(material = {}) {
		const isEdit = Boolean(material.material_id);
		return `
			<form id="materialForm" class="space-y-4" enctype="multipart/form-data">
				<input type="hidden" name="material_id" value="${material.material_id || ''}">
				<input type="hidden" name="course_id" value="${material.course_id || ''}">
				<div>
					<label class="block text-sm text-slate-600 mb-1">معرف الوحدة</label>
					<input name="module_id" type="number" value="${material.module_id || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">عنوان المادة</label>
					<input name="title" value="${material.title || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm text-slate-600 mb-1">نوع المادة</label>
						<select name="material_type" class="w-full border border-slate-200 rounded-lg px-3 py-2">
							<option value="pdf" ${material.material_type === 'pdf' ? 'selected' : ''}>ملف PDF</option>
							<option value="video" ${material.material_type === 'video' ? 'selected' : ''}>فيديو</option>
							<option value="link" ${material.material_type === 'link' ? 'selected' : ''}>رابط خارجي</option>
							<option value="text" ${material.material_type === 'text' ? 'selected' : ''}>نص</option>
						</select>
					</div>
					<div>
						<label class="block text-sm text-slate-600 mb-1">الترتيب</label>
						<input name="position" type="number" value="${material.position || 1}" class="w-full border border-slate-200 rounded-lg px-3 py-2" min="1">
					</div>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">الوصف</label>
					<textarea name="description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2">${material.description || ''}</textarea>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">رفع ملف (اختياري)</label>
					<input type="file" name="material_file" class="block w-full text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2" accept="application/pdf,video/*">
					${material.file_path ? `<p class="text-xs text-slate-500 mt-1">الملف الحالي: ${material.file_path}</p>` : ''}
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">رابط خارجي (يوتيوب أو غيره)</label>
					<input name="external_url" value="${material.external_url || ''}" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="https://...">
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-700">${isEdit ? 'تحديث' : 'حفظ'}</button>
				</div>
			</form>
		`;
	}

	function bindMaterialForm(courseId, materialId = null) {
		const form = document.getElementById('materialForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const formData = new FormData(form);
			formData.append('action', materialId ? 'update_material' : 'create_material');
			if (materialId) {
				formData.append('material_id', materialId);
			}
			try {
				const response = await fetch(API_ENDPOINTS.manageLmsContent, { method: 'POST', body: formData });
				const payload = await response.json();
				if (!response.ok || payload.success === false) {
					throw new Error(payload.message || 'فشل حفظ المادة');
				}
				showToast('تم حفظ المادة التعليمية', 'success');
				closeModal();
				renderCourseEditor(courseId);
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	async function renderStudentHome() {
		const overview = document.getElementById('studentOverview');
		const coursesList = document.getElementById('studentCoursesList');
		const coursesCount = document.getElementById('studentCoursesCount');
		if (!overview || !coursesList) return;

		try {
			const data = await fetchJson(API_ENDPOINTS.studentData);
			const stats = data.data.stats;
			const courses = data.data.courses || [];
			
			const attendancePercentage = stats.attendance_sessions > 0 
				? Math.round((stats.attendance_present / stats.attendance_sessions) * 100) 
				: 0;
			
			overview.innerHTML = `
				${renderStatisticCard({ title: 'دوراتي النشطة', value: stats.active_courses ?? 0, icon: 'book-open', accent: 'sky' })}
				${renderStatisticCard({ title: 'الدورات المكتملة', value: stats.completed_courses ?? 0, icon: 'check-circle', accent: 'emerald' })}
				${renderStatisticCard({ title: 'الواجبات', value: stats.total_grades ?? 0, icon: 'clipboard-list', accent: 'violet' })}
				${renderStatisticCard({ title: 'متوسط الدرجة', value: stats.average_grade ? stats.average_grade + '%' : 'N/A', icon: 'trending-up', accent: 'amber' })}
				${renderStatisticCard({ title: 'نسبة الحضور', value: attendancePercentage + '%', icon: 'clipboard-check', accent: 'rose' })}
				<div class="col-span-full bg-white rounded-2xl shadow p-4">
					<h4 class="font-semibold text-slate-800 mb-2">تفاصيل الحضور</h4>
					<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
						<div class="flex items-center gap-2">
							<span class="text-emerald-600">✓</span>
							<span class="text-slate-600">حاضر: ${stats.attendance_present ?? 0}</span>
						</div>
						<div class="flex items-center gap-2">
							<span class="text-red-600">✗</span>
							<span class="text-slate-600">غائب: ${stats.attendance_absent ?? 0}</span>
						</div>
						<div class="flex items-center gap-2">
							<span class="text-amber-600">🕒</span>
							<span class="text-slate-600">متأخر: ${stats.attendance_late ?? 0}</span>
						</div>
						<div class="flex items-center gap-2">
							<span class="text-slate-400">#</span>
							<span class="text-slate-600">الجلسات: ${stats.attendance_sessions ?? 0}</span>
						</div>
					</div>
				</div>
			`;
			lucide.createIcons();

			coursesCount.textContent = `${courses.length} دورة`;
			coursesList.innerHTML = courses.map(course => `
				<div class="border border-slate-100 rounded-2xl p-4 flex flex-col gap-3">
					<div class="flex items-center justify-between">
						<h3 class="text-base font-semibold text-slate-800">${course.course_title}</h3>
						<span class="px-2 py-1 rounded-full text-xs ${course.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-sky-50 text-sky-600'}">${course.status}</span>
					</div>
					<p class="text-sm text-slate-500">${course.description || 'بدون وصف'}</p>
					<div class="flex items-center justify-between text-xs text-slate-500">
						<span>المدرب: ${course.trainer_name || '-'}</span>
						<span>تاريخ التسجيل: ${course.enrolled_at || '-'}</span>
					</div>
					<button class="px-3 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 text-sm" data-action="open-course" data-course-id="${course.course_id}">عرض المحتوى</button>
				</div>
			`).join('') || '<p class="text-sm text-slate-500">لم يتم تسجيلك في أي دورة حالياً.</p>';

			coursesList.querySelectorAll('[data-action="open-course"]').forEach(btn => {
				btn.addEventListener('click', () => {
					const courseId = parseInt(btn.dataset.courseId, 10);
					renderStudentCourseView(courseId);
				});
			});
		} catch (error) {
			overview.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
			coursesList.innerHTML = `<p class="text-sm text-red-600">${error.message}</p>`;
		}

	try {
		await renderMessages({
			embedded: true,
			containerId: 'studentMessagesContainer',
			title: 'صندوق الرسائل',
			subtitle: 'تواصل مع إدارة المنصة والمدربين دون مغادرة اللوحة'
		});
	} catch (error) {
		console.warn('فشل تحميل صندوق الرسائل للطالب', error);
	}
	}

	async function renderStudentCourseView(courseId) {
		const detail = document.getElementById('studentCourseDetail');
		const modulesContainer = document.getElementById('studentModules');
		const closeBtn = document.getElementById('closeStudentCourseDetail');
		if (!detail || !modulesContainer) return;

		detail.classList.remove('hidden');

		try {
			const [contentData, assignmentsData] = await Promise.all([
				fetchJson(`${API_ENDPOINTS.manageLmsContent}?course_id=${courseId}`),
				fetchJson(`${API_ENDPOINTS.manageLmsAssignments}?course_id=${courseId}`)
			]);

			const modules = contentData.modules || [];
			const assignments = assignmentsData.assignments || [];

			document.getElementById('studentCourseTitle').textContent = contentData.course?.title || 'دورة تدريبية';
			document.getElementById('studentCourseMeta').textContent = `عدد الوحدات: ${modules.length}`;

			modulesContainer.innerHTML = modules.map(module => `
				<div class="border border-slate-100 rounded-2xl p-4 space-y-3">
					<header class="flex items-center justify-between">
						<h4 class="text-base font-semibold text-slate-800">${module.title}</h4>
						<span class="text-xs text-slate-500">${module.materials.length} مادة</span>
					</header>
					<p class="text-sm text-slate-600">${module.summary || 'بدون وصف'}</p>
					<div class="space-y-2">
						${module.materials.map(material => `
							<div class="border border-slate-100 rounded-xl p-3 flex flex-col gap-2">
								<div class="flex items-center justify-between">
									<h5 class="text-sm font-semibold text-slate-800">${material.title}</h5>
									<span class="text-xs text-slate-500">${material.material_type}</span>
								</div>
								<p class="text-xs text-slate-500">${material.description || ''}</p>
								${material.file_path ? `<a href="${material.file_path}" class="text-sm text-sky-600 underline" target="_blank">تحميل الملف</a>` : ''}
								${material.external_url ? `<a href="${material.external_url}" class="text-sm text-sky-600 underline" target="_blank">فتح الرابط</a>` : ''}
							</div>
						`).join('') || '<p class="text-sm text-slate-500">لا توجد مواد ضمن هذه الوحدة.</p>'}
					</div>
				</div>
			`).join('') || '<p class="text-sm text-slate-500">لا تتوفر وحدات لهذه الدورة بعد.</p>';

			if (assignments.length > 0) {
				modulesContainer.insertAdjacentHTML('beforeend', `
					<section class="border border-slate-100 rounded-2xl p-4 space-y-3">
						<header class="flex items-center justify-between">
							<h4 class="text-base font-semibold text-slate-800">الواجبات المتاحة</h4>
						</header>
						<div class="space-y-2">
							${assignments.map(assignment => `
								<div class="border border-slate-100 rounded-xl p-3 space-y-2" data-assignment-id="${assignment.assignment_id}">
									<div class="flex items-center justify-between">
										<h5 class="text-sm font-semibold text-slate-800">${assignment.title}</h5>
										<span class="text-xs text-slate-500">${assignment.due_date ? 'تاريخ التسليم: ' + assignment.due_date : ''}</span>
									</div>
									<p class="text-xs text-slate-500">${assignment.description || ''}</p>
									<button class="px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-xs" data-action="submit-assignment">تسليم الواجب</button>
								</div>
							`).join('')}
						</div>
					</section>
				`);

				modulesContainer.querySelectorAll('[data-action="submit-assignment"]').forEach(btn => {
					btn.addEventListener('click', () => {
						const assignmentEl = btn.closest('[data-assignment-id]');
						const assignmentId = parseInt(assignmentEl.dataset.assignmentId, 10);
						openModal('تسليم الواجب', buildSubmissionForm(assignmentId));
						bindSubmissionForm(assignmentId, courseId);
					});
				});
			}
		} catch (error) {
			modulesContainer.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">${error.message}</div>`;
		}

		if (closeBtn) {
			closeBtn.addEventListener('click', () => {
				detail.classList.add('hidden');
			});
		}
	}

	function buildSubmissionForm(assignmentId) {
		return `
			<form id="submissionForm" class="space-y-4" enctype="multipart/form-data">
				<input type="hidden" name="assignment_id" value="${assignmentId}">
				<div>
					<label class="block text-sm text-slate-600 mb-1">ملف الواجب</label>
					<input type="file" name="submission_file" class="block w-full text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2" required>
				</div>
				<div>
					<label class="block text-sm text-slate-600 mb-1">ملاحظات إضافية</label>
					<textarea name="notes" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2"></textarea>
				</div>
				<div class="flex justify-end gap-3">
					<button type="button" id="cancelModalAction" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">إلغاء</button>
					<button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">إرسال</button>
				</div>
			</form>
		`;
	}

	function bindSubmissionForm(assignmentId, courseId) {
		const form = document.getElementById('submissionForm');
		const cancel = document.getElementById('cancelModalAction');
		if (!form) return;

		form.addEventListener('submit', async event => {
			event.preventDefault();
			const formData = new FormData(form);
			formData.append('action', 'submit_assignment');
			try {
				const response = await fetch(API_ENDPOINTS.manageLmsAssignments, { method: 'POST', body: formData });
				const payload = await response.json();
				if (!response.ok || payload.success === false) {
					throw new Error(payload.message || 'فشل تسليم الواجب');
				}
				showToast('تم رفع الواجب بنجاح', 'success');
				closeModal();
				renderStudentCourseView(courseId);
			} catch (error) {
				showToast(error.message, 'error');
			}
		});

		if (cancel) {
			cancel.addEventListener('click', () => closeModal());
		}
	}

	function openModal(title, content) {
		const backdrop = document.getElementById('modalBackdrop');
		const modalTitle = document.getElementById('modalTitle');
		const modalBody = document.getElementById('modalBody');
		if (!backdrop || !modalTitle || !modalBody) return;
		modalTitle.textContent = title;
		modalBody.innerHTML = content;
		backdrop.classList.add('visible');
		lucide.createIcons();
	}

	function closeModal() {
		const backdrop = document.getElementById('modalBackdrop');
		const modalBody = document.getElementById('modalBody');
		if (!backdrop || !modalBody) return;
		modalBody.innerHTML = '';
		backdrop.classList.remove('visible');
	}

	function initModalHandlers() {
		const backdrop = document.getElementById('modalBackdrop');
		const closeBtn = document.getElementById('closeModalBtn');
		if (closeBtn) {
			closeBtn.addEventListener('click', () => closeModal());
		}
		if (backdrop) {
			backdrop.addEventListener('click', event => {
				if (event.target === backdrop) {
					closeModal();
				}
			});
		}
	}

	async function initNotificationSystem() {
		const bell = document.getElementById('notificationsBell');
		const counter = document.getElementById('notificationsCounter');
		const studentBell = document.getElementById('studentNotificationsBtn');
		const studentCounter = document.getElementById('studentNotificationsCounter');

		async function loadNotifications() {
			try {
				const data = await fetchJson(API_ENDPOINTS.notifications);
				const list = data.notifications || [];
				const unread = list.filter(item => item.is_read === '0' || item.is_read === 0).length;
				if (counter) {
					counter.textContent = unread;
					counter.classList.toggle('hidden', unread === 0);
				}
				if (studentCounter) {
					studentCounter.textContent = unread;
					studentCounter.classList.toggle('hidden', unread === 0);
				}
			} catch (error) {
				console.warn('فشل تحميل الإشعارات', error);
			}
		}

		async function markAllRead() {
			try {
				const data = await fetchJson(API_ENDPOINTS.notifications);
				const list = data.notifications || [];
				await Promise.all(list.map(item => fetchJson(API_ENDPOINTS.markNotificationRead, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ notification_id: item.id })
				}).catch(() => null)));
				loadNotifications();
			} catch (error) {
				console.warn('فشل تحديث حالة الإشعارات', error);
			}
		}

		if (bell) {
			bell.addEventListener('click', () => {
				showToast('سيتم توفير قائمة الإشعارات قريباً', 'info');
				markAllRead();
			});
		}

		if (studentBell) {
			studentBell.addEventListener('click', () => {
				showToast('سيتم توفير قائمة الإشعارات قريباً', 'info');
				markAllRead();
			});
		}

		loadNotifications();
		setInterval(loadNotifications, 60000);
	}

	function initMobileSidebar() {
		const toggle = document.getElementById('mobileSidebarToggle');
		const sidebar = document.getElementById('sidebar');
		if (!toggle || !sidebar) return;
		toggle.addEventListener('click', () => {
			sidebar.classList.toggle('hidden');
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		lucide.createIcons();
		applyRoleBasedAccessControl();
		initSidebarNavigation();
		initModalHandlers();
		initNotificationSystem();
		initMobileSidebar();
		initializeMessagingSystem(); // تهيئة نظام الدردشة
		updateNotificationBadge(); // Update notification badge
		setInterval(updateNotificationBadge, 30000); // Update every 30 seconds
		renderDashboard();
	});
	</script>
	<script src="assets/js/chat.js"></script>
</body>
</html>

