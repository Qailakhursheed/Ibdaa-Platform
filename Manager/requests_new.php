<?php
require_once __DIR__ . '/../database/db.php';
$result = $conn->query("SELECT * FROM course_requests ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إدارة الطلبات | منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; }
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-approved { background-color: #d1fae5; color: #065f46; }
    .status-rejected { background-color: #fee2e2; color: #991b1b; }
    .status-paid { background-color: #dbeafe; color: #1e40af; }
  </style>
</head>
<body class="bg-gray-50 p-6 md:p-10">
  <div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-sky-700">📋 إدارة طلبات التسجيل</h1>
        <a href="index.html" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
          🏠 العودة للبوابة
        </a>
      </div>

      <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-12">
          <div class="text-6xl mb-4">📭</div>
          <p class="text-xl text-gray-600">لا توجد طلبات حتى الآن</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead class="bg-sky-600 text-white">
              <tr>
                <th class="p-3 text-center">#</th>
                <th class="p-3 text-right">الاسم</th>
                <th class="p-3 text-right">البريد</th>
                <th class="p-3 text-right">الهاتف</th>
                <th class="p-3 text-right">الدورة</th>
                <th class="p-3 text-right">المحافظة</th>
                <th class="p-3 text-right">المديرية</th>
                <th class="p-3 text-center">الحالة</th>
                <th class="p-3 text-center">التاريخ</th>
                <th class="p-3 text-center">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php while($req = $result->fetch_assoc()): 
                $statusClass = '';
                switch($req['status']) {
                  case 'قيد المراجعة': $statusClass = 'status-pending'; break;
                  case 'مقبول': $statusClass = 'status-approved'; break;
                  case 'مرفوض': $statusClass = 'status-rejected'; break;
                  case 'تم الدفع': $statusClass = 'status-paid'; break;
                }
              ?>
              <tr class="border-b hover:bg-sky-50 transition">
                <td class="p-3 text-center font-bold text-gray-700"><?= $req['id'] ?></td>
                <td class="p-3"><?= htmlspecialchars($req['full_name']) ?></td>
                <td class="p-3 text-sm"><?= htmlspecialchars($req['email']) ?></td>
                <td class="p-3 text-sm"><?= htmlspecialchars($req['phone']) ?></td>
                <td class="p-3 font-semibold text-sky-700"><?= htmlspecialchars($req['course']) ?></td>
                <td class="p-3 text-sm"><?= htmlspecialchars($req['governorate']) ?></td>
                <td class="p-3 text-sm"><?= htmlspecialchars($req['district'] ?? '-') ?></td>
                <td class="p-3 text-center">
                  <span class="px-3 py-1 rounded-full text-sm font-bold <?= $statusClass ?>">
                    <?= htmlspecialchars($req['status']) ?>
                  </span>
                </td>
                <td class="p-3 text-center text-xs text-gray-600">
                  <?= date('Y-m-d', strtotime($req['created_at'])) ?>
                </td>
                <td class="p-3">
                  <form action="updateRequest.php" method="POST" class="flex gap-2 justify-center flex-wrap">
                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                    <?php if ($req['status'] !== 'مقبول'): ?>
                      <button name="action" value="approve" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition">
                        ✓ قبول
                      </button>
                    <?php endif; ?>
                    <?php if ($req['status'] !== 'مرفوض'): ?>
                      <button name="action" value="reject" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">
                        ✗ رفض
                      </button>
                    <?php endif; ?>
                    <?php if ($req['status'] === 'مقبول' || $req['status'] === 'تم الدفع'): ?>
                      <button name="action" value="paid" class="bg-sky-500 hover:bg-sky-600 text-white px-3 py-1 rounded text-sm transition">
                        💰 دفع
                      </button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-6 text-center text-gray-600">
          <p>إجمالي الطلبات: <strong class="text-sky-700"><?= $result->num_rows ?></strong></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
