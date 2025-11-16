<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../Mailer/sendMail.php';

$result = $conn->query("SELECT * FROM course_requests ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة المتابع الفني | منصة إبداع</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
<style>
body { 
  font-family: 'Cairo', sans-serif; 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.modal { 
  display:none; 
  position:fixed; 
  top:0; 
  left:0; 
  width:100%; 
  height:100%; 
  background:rgba(0,0,0,0.6); 
  align-items:center; 
  justify-content:center; 
  z-index:1000;
  backdrop-filter: blur(5px);
}
.modal.active { display:flex; }
.modal-content { 
  background:white; 
  padding:2rem; 
  border-radius:1rem; 
  width:90%; 
  max-width:600px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: slideIn 0.3s ease;
}
@keyframes slideIn {
  from { transform: translateY(-50px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.status-pending { background-color: #fef3c7; color: #92400e; }
.status-approved { background-color: #d1fae5; color: #065f46; }
.status-rejected { background-color: #fee2e2; color: #991b1b; }
.status-paid { background-color: #dbeafe; color: #1e40af; }
</style>
</head>
<body class="p-4 md:p-8 min-h-screen">

<div class="max-w-7xl mx-auto">
  <!-- Header -->
  <header class="bg-white rounded-2xl shadow-xl p-6 mb-6 flex justify-between items-center">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
        🔧
      </div>
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">لوحة المتابع الفني</h1>
        <p class="text-sm text-gray-600">إدارة الطلبات والرسوم</p>
      </div>
    </div>
    <div class="flex gap-2">
      <a href="../Manager/index.html" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm transition">
        👔 لوحة المدير
      </a>
      <a href="../platform/index.html" class="bg-gray-200 px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-300 text-sm transition">
        تسجيل الخروج
      </a>
    </div>
  </header>

  <!-- Statistics Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
      'قيد المراجعة' => ['count' => 0, 'color' => 'yellow', 'icon' => '⏳'],
      'مقبول' => ['count' => 0, 'color' => 'green', 'icon' => '✅'],
      'مرفوض' => ['count' => 0, 'color' => 'red', 'icon' => '❌'],
      'تم الدفع' => ['count' => 0, 'color' => 'blue', 'icon' => '💰']
    ];
    
    $statsQuery = $conn->query("SELECT status, COUNT(*) as count FROM course_requests GROUP BY status");
    while($row = $statsQuery->fetch_assoc()) {
      if(isset($stats[$row['status']])) {
        $stats[$row['status']]['count'] = $row['count'];
      }
    }
    
    foreach($stats as $status => $data):
      $colorClass = "from-{$data['color']}-400 to-{$data['color']}-600";
    ?>
    <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-2xl transition">
      <div class="text-3xl mb-2"><?= $data['icon'] ?></div>
      <div class="text-2xl font-bold text-gray-800"><?= $data['count'] ?></div>
      <div class="text-sm text-gray-600"><?= $status ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Requests Table -->
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="p-6 bg-gradient-to-r from-teal-500 to-cyan-600 text-white">
      <h2 class="text-2xl font-bold">📋 جميع طلبات التسجيل</h2>
      <p class="text-sm opacity-90 mt-1">مراجعة وإدارة طلبات المتدربين</p>
    </div>

    <?php if ($result->num_rows === 0): ?>
      <div class="p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <p class="text-xl text-gray-600">لا توجد طلبات حالياً</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b-2 border-gray-200">
            <tr>
              <th class="p-3 text-center">#</th>
              <th class="p-3 text-right">الاسم</th>
              <th class="p-3 text-right">البريد</th>
              <th class="p-3 text-right">الهاتف</th>
              <th class="p-3 text-right">الدورة</th>
              <th class="p-3 text-right">المحافظة</th>
              <th class="p-3 text-right">المديرية</th>
              <th class="p-3 text-center">الرسوم</th>
              <th class="p-3 text-center">الحالة</th>
              <th class="p-3 text-center">الإجراءات</th>
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
            <tr class="border-b hover:bg-teal-50 transition">
              <td class="p-3 text-center font-bold text-gray-700"><?= $req['id'] ?></td>
              <td class="p-3 font-semibold"><?= htmlspecialchars($req['full_name']) ?></td>
              <td class="p-3 text-xs"><?= htmlspecialchars($req['email']) ?></td>
              <td class="p-3 text-xs"><?= htmlspecialchars($req['phone']) ?></td>
              <td class="p-3 font-semibold text-teal-700"><?= htmlspecialchars($req['course']) ?></td>
              <td class="p-3 text-xs"><?= htmlspecialchars($req['governorate']) ?></td>
              <td class="p-3 text-xs"><?= htmlspecialchars($req['district'] ?? '-') ?></td>
              <td class="p-3 text-center font-bold text-blue-600">
                <?= $req['fees'] > 0 ? number_format($req['fees'], 0) . ' ر.ي' : '-' ?>
              </td>
              <td class="p-3 text-center">
                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                  <?= htmlspecialchars($req['status']) ?>
                </span>
              </td>
              <td class="p-3 text-center">
                <div class="flex gap-1 justify-center flex-wrap">
                  <?php if($req['status'] == 'قيد المراجعة'): ?>
                    <button onclick="openModal(<?= $req['id'] ?>, '<?= htmlspecialchars($req['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($req['course'], ENT_QUOTES) ?>')" 
                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs transition">
                      ✓ قبول
                    </button>
                    <form action="updateRequest.php" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب؟')">
                      <input type="hidden" name="id" value="<?= $req['id'] ?>">
                      <button name="action" value="reject" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition">
                        ✗ رفض
                      </button>
                    </form>
                  <?php elseif($req['status'] == 'مقبول'): ?>
                    <form action="updateRequest.php" method="POST" class="inline" onsubmit="return confirm('هل تم استلام المبلغ المطلوب؟')">
                      <input type="hidden" name="id" value="<?= $req['id'] ?>">
                      <button name="action" value="paid" class="bg-sky-500 hover:bg-sky-600 text-white px-3 py-1 rounded text-xs transition">
                        💰 تأكيد الدفع
                      </button>
                    </form>
                  <?php elseif($req['status'] == 'تم الدفع'): ?>
                    <span class="text-green-600 font-bold text-xs">✓ مكتمل</span>
                  <?php else: ?>
                    <span class="text-gray-400 text-xs">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="p-4 bg-gray-50 border-t text-center text-sm text-gray-600">
        إجمالي الطلبات: <strong class="text-teal-700"><?= $result->num_rows ?></strong>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- نافذة الموافقة -->
<div id="approvalModal" class="modal">
  <div class="modal-content">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-2xl font-bold text-teal-700">✅ الموافقة على الطلب</h3>
      <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    </div>
    
    <form action="updateRequest.php" method="POST">
      <input type="hidden" name="id" id="reqId">
      
      <div class="bg-teal-50 p-4 rounded-lg mb-4">
        <p class="text-sm text-gray-700"><strong>الطالب:</strong> <span id="studentName"></span></p>
        <p class="text-sm text-gray-700 mt-1"><strong>الدورة:</strong> <span id="courseName"></span></p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2 text-gray-700">اسم الدورة (تأكيد):</label>
        <input type="text" name="course_title" id="courseInput" required 
               class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-teal-500 focus:outline-none transition">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2 text-gray-700">الرسوم المطلوبة (ريال يمني):</label>
        <input type="number" name="fees" step="0.01" required placeholder="مثال: 50000" 
               class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-teal-500 focus:outline-none transition">
      </div>

      <div class="mb-6">
        <label class="block text-sm font-semibold mb-2 text-gray-700">ملاحظات إضافية:</label>
        <textarea name="note" rows="3" placeholder="اختياري - أي ملاحظات للطالب..." 
                  class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-teal-500 focus:outline-none transition"></textarea>
      </div>

      <div class="flex gap-3">
        <button type="button" onclick="closeModal()" 
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
          إلغاء
        </button>
        <button name="action" value="approve" 
                class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-lg font-bold transition shadow-lg">
          ✓ تأكيد الموافقة
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id, studentName, courseName){
  document.getElementById('approvalModal').classList.add('active');
  document.getElementById('reqId').value = id;
  document.getElementById('studentName').textContent = studentName;
  document.getElementById('courseName').textContent = courseName;
  document.getElementById('courseInput').value = courseName;
}

function closeModal(){
  document.getElementById('approvalModal').classList.remove('active');
}

// إغلاق النافذة عند الضغط خارجها
document.getElementById('approvalModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeModal();
  }
});
</script>

</body>
</html>
