<?php
$dataFile = __DIR__ . '/../database/requests.json';
$requests = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
if (!is_array($requests)) $requests = [];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إدارة الطلبات | منصة إبداع</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
<style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50 p-10">

<div class="max-w-7xl mx-auto">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-bold text-sky-700">📋 إدارة طلبات التسجيل</h1>
    <div class="flex gap-4">
      <a href="../platform/index.html" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">الصفحة الرئيسية</a>
      <a href="Portal.html" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">بوابة المدير</a>
    </div>
  </div>

  <?php if (empty($requests)): ?>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-lg text-center">
      <p class="text-xl">📭 لا توجد طلبات تسجيل حتى الآن</p>
    </div>
  <?php else: ?>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="w-full">
        <thead class="bg-sky-600 text-white">
          <tr>
            <th class="p-3">الاسم</th>
            <th class="p-3">البريد</th>
            <th class="p-3">الهاتف</th>
            <th class="p-3">الدورة</th>
            <th class="p-3">المحافظة</th>
            <th class="p-3">المديرية</th>
            <th class="p-3">الحالة</th>
            <th class="p-3">التاريخ</th>
            <th class="p-3">إجراء</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($requests as $req): ?>
        <tr class="border-b hover:bg-sky-50 text-center">
          <td class="p-3"><?= htmlspecialchars($req['full_name']) ?></td>
          <td class="p-3"><?= htmlspecialchars($req['email']) ?></td>
          <td class="p-3"><?= htmlspecialchars($req['phone']) ?></td>
          <td class="p-3"><?= htmlspecialchars($req['course']) ?></td>
          <td class="p-3"><?= htmlspecialchars($req['governorate']) ?></td>
          <td class="p-3"><?= htmlspecialchars($req['district']) ?></td>
          <td class="p-3">
            <span class="px-3 py-1 rounded-full text-sm font-bold
              <?php 
                if ($req['status'] === 'مقبول') echo 'bg-green-100 text-green-700';
                elseif ($req['status'] === 'مرفوض') echo 'bg-red-100 text-red-700';
                elseif ($req['status'] === 'تم الدفع') echo 'bg-blue-100 text-blue-700';
                else echo 'bg-yellow-100 text-yellow-700';
              ?>
            ">
              <?= htmlspecialchars($req['status']) ?>
            </span>
          </td>
          <td class="p-3 text-sm"><?= htmlspecialchars($req['date']) ?></td>
          <td class="p-3">
            <form action="updateRequest.php" method="POST" class="flex gap-2 justify-center">
              <input type="hidden" name="id" value="<?= htmlspecialchars($req['id']) ?>">
              <button name="action" value="approve" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">قبول</button>
              <button name="action" value="reject" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">رفض</button>
              <button name="action" value="paid" class="bg-sky-500 hover:bg-sky-600 text-white px-3 py-1 rounded text-sm">تم الدفع</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-6 bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-bold mb-2">📊 إحصائيات:</h3>
      <div class="grid grid-cols-4 gap-4 text-center">
        <?php
        $total = count($requests);
        $pending = count(array_filter($requests, fn($r) => $r['status'] === 'قيد المراجعة'));
        $approved = count(array_filter($requests, fn($r) => $r['status'] === 'مقبول'));
        $paid = count(array_filter($requests, fn($r) => $r['status'] === 'تم الدفع'));
        ?>
        <div class="bg-gray-100 p-4 rounded">
          <p class="text-2xl font-bold"><?= $total ?></p>
          <p class="text-sm text-gray-600">إجمالي الطلبات</p>
        </div>
        <div class="bg-yellow-100 p-4 rounded">
          <p class="text-2xl font-bold"><?= $pending ?></p>
          <p class="text-sm text-gray-600">قيد المراجعة</p>
        </div>
        <div class="bg-green-100 p-4 rounded">
          <p class="text-2xl font-bold"><?= $approved ?></p>
          <p class="text-sm text-gray-600">مقبول</p>
        </div>
        <div class="bg-blue-100 p-4 rounded">
          <p class="text-2xl font-bold"><?= $paid ?></p>
          <p class="text-sm text-gray-600">تم الدفع</p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
