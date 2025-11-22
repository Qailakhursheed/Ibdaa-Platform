<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التوجيه حسب الصلاحية
switch ($_SESSION['user_role']) {
    case 'manager':
        header("Location: dashboards/manager-dashboard.php");
        break;
    case 'technical':
        header("Location: dashboards/technical-dashboard.php");
        break;
    case 'trainer':
        header("Location: dashboards/trainer-dashboard.php");
        break;
    case 'student':
        header("Location: dashboards/student-dashboard.php");
        break;
    default:
        header("Location: login.php?error=role_unknown");
}
exit();
?>
