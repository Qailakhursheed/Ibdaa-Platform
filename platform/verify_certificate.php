<?php
require_once __DIR__ . '/db.php';

$code = trim($_GET['code'] ?? ($_POST['code'] ?? ''));
$result = null;
$error = '';

if ($code !== '') {
    $stmt = $conn->prepare(
        'SELECT cert.certificate_code, cert.verification_code, cert.issued_at, cert.status, cert.file_path,
                u.full_name AS student_name, u.email AS student_email,
                c.title AS course_title, c.start_date, c.end_date
         FROM certificates cert
         JOIN users u ON u.id = cert.user_id
         JOIN courses c ON c.course_id = cert.course_id
         WHERE cert.verification_code = ? OR cert.certificate_code = ?
         LIMIT 1'
    );
    $stmt->bind_param('ss', $code, $code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        $error = 'لم يتم العثور على شهادة مطابقة لرمز التحقق المدخل.';
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الشهادات | منصة إبداع</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f1f5f9; margin:0; color:#0f172a; }
        .container { max-width: 640px; margin: 60px auto; background:#fff; border-radius:24px; padding:40px; box-shadow:0 20px 40px rgba(15,23,42,0.08); border:1px solid #e2e8f0; }
        h1 { font-size: 28px; margin-bottom: 18px; text-align:center; color:#0ea5e9; }
        form { display:flex; flex-direction:column; gap:16px; margin-bottom:24px; }
        input[type="text"] { padding:14px 16px; border-radius:12px; border:1px solid #cbd5f5; font-size:16px; direction:ltr; text-transform:uppercase; }
        button { padding:14px 16px; border:none; border-radius:12px; background:#0284c7; color:#fff; font-size:16px; font-weight:600; cursor:pointer; transition:background 0.2s ease; }
        button:hover { background:#0369a1; }
        .card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:20px; padding:24px; display:flex; flex-direction:column; gap:10px; }
        .label { font-size:14px; color:#64748b; }
        .value { font-size:16px; font-weight:600; color:#0f172a; }
        .error { background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:12px; margin-bottom:24px; }
        .success { background:#ecfdf5; border:1px solid #bbf7d0; color:#047857; padding:12px 16px; border-radius:12px; margin-bottom:24px; }
        a.download { display:inline-block; margin-top:8px; padding:10px 14px; background:#10b981; color:#fff; border-radius:10px; text-decoration:none; font-size:14px; }
        a.download:hover { background:#059669; }
    </style>
</head>
<body>
    <div class="container">
        <h1>التحقق من الشهادات</h1>
        <form method="get" action="">
            <input type="text" name="code" placeholder="أدخل رمز التحقق أو الشهادة" value="<?= h($code); ?>" required>
            <button type="submit">تحقق الآن</button>
        </form>

        <?php if ($error !== ''): ?>
            <div class="error"><?= h($error); ?></div>
        <?php elseif ($result): ?>
            <div class="success">تم العثور على شهادة مطابقة، التفاصيل أدناه.</div>
            <div class="card">
                <div>
                    <span class="label">اسم الطالب</span>
                    <div class="value"><?= h($result['student_name']); ?></div>
                </div>
                <div>
                    <span class="label">البريد الإلكتروني</span>
                    <div class="value"><?= h($result['student_email']); ?></div>
                </div>
                <div>
                    <span class="label">الدورة التدريبية</span>
                    <div class="value"><?= h($result['course_title']); ?></div>
                </div>
                <div>
                    <span class="label">رمز الشهادة</span>
                    <div class="value"><?= h($result['certificate_code']); ?></div>
                </div>
                <div>
                    <span class="label">رمز التحقق</span>
                    <div class="value"><?= h($result['verification_code']); ?></div>
                </div>
                <div>
                    <span class="label">تاريخ الإصدار</span>
                    <div class="value"><?= h($result['issued_at']); ?></div>
                </div>
                <div>
                    <span class="label">حالة الشهادة</span>
                    <div class="value"><?= h($result['status']); ?></div>
                </div>
                <?php if (!empty($result['file_path'])): ?>
                    <a class="download" href="../<?= h($result['file_path']); ?>" target="_blank" rel="noopener">تحميل الشهادة</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
