<?php
/**
 * Idempotent seeder to insert a sample course for testing the manager request->approve flow.
 *
 * Usage (PowerShell):
 *   php c:\xampp\htdocs\Ibdaa-Taiz\database\seed_sample_course.php
 *
 * The script reads existing columns for the `courses` table and only inserts into columns that exist.
 */

require_once __DIR__ . '/../platform/db.php';

// adjust this sample course data as you like
$sample = [
    'title' => 'دورة تجريبية - ICDL (اختبار)',
    'short_desc' => 'دورة تجريبية لإعداد الاختبار والتأكد من سير عملية التسجيل عبر لوحة المدير.',
    'image_url' => 'photos/Sh.jpg',
    'category' => 'تقنية',
    'trainer_id' => null,
    'duration' => '30 ساعة'
];

$dbName = $conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']);

// verify courses table exists
$check = $conn->query("SHOW TABLES LIKE 'courses'");
if (!($check && $check->num_rows > 0)) {
    echo "Error: table `courses` does not exist. Please create the table or run the migrations first.\n";
    exit(1);
}

// get columns present in courses table
$colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $dbName . "' AND TABLE_NAME = 'courses'");
$cols = [];
while ($r = $colsRes->fetch_assoc()) { $cols[] = $r['COLUMN_NAME']; }

// build insertable fields by intersection
$toInsert = [];
foreach ($sample as $k => $v) {
    if (in_array($k, $cols)) $toInsert[$k] = $v;
}

if (empty($toInsert)) {
    echo "No known insertable columns found for the `courses` table. Columns on table: " . implode(', ', $cols) . "\n";
    exit(1);
}

// check idempotency by title
$titleEsc = $conn->real_escape_string($sample['title']);
$exists = $conn->query("SELECT course_id FROM courses WHERE title = '" . $titleEsc . "' LIMIT 1");
if ($exists && $exists->num_rows > 0) {
    echo "Sample course already exists (skipping).\n";
    exit(0);
}

$fields = [];
$placeholders = [];
$values = [];
foreach ($toInsert as $field => $value) {
    $fields[] = "`" . $field . "`";
    $placeholders[] = '?';
    $values[] = $value;
}

$sql = "INSERT INTO courses (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n";
    exit(1);
}

// bind params dynamically - all as strings (trainer_id may be null)
$types = str_repeat('s', count($values));
$bindNames = [];
foreach ($values as $i => $val) {
    // convert null to PHP null so bind_param handles it
    if ($val === null) $values[$i] = null;
}

$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {
    echo "Inserted sample course successfully. ID = " . $stmt->insert_id . "\n";
} else {
    echo "Insert failed: (" . $stmt->errno . ") " . $stmt->error . "\n";
    exit(1);
}

$stmt->close();
$conn->close();

echo "Done. You can now use the manager dashboard to see courses and test the approval flow.\n";

?>
