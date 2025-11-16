<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ibdaa_taiz"; // تم التوحيد - قاعدة بيانات واحدة للمنصة

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

/**
 * Compatibility helper for mysqli_stmt->get_result()
 * Returns an object with fetch_assoc() and fetch_all() when mysqlnd is not available.
 */
function stmt_get_result_compat(mysqli_stmt $stmt) {
    if (method_exists($stmt, 'get_result')) {
        return $stmt->get_result();
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return false;

    $fields = [];
    $row = [];
    $params = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = $field->name;
        $row[$field->name] = null;
        $params[] = & $row[$field->name];
    }

    // bind result to variables
    call_user_func_array([$stmt, 'bind_result'], $params);

    $results = [];
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($fields as $f) {
            $copy[$f] = $row[$f];
        }
        $results[] = $copy;
    }

    // Simple result object
    return new class($results) {
        private $rows;
        private $pos = 0;
        public function __construct(array $rows) { $this->rows = $rows; }
        public function fetch_assoc() { if ($this->pos < count($this->rows)) { return $this->rows[$this->pos++]; } return null; }
        public function fetch_all($mode = MYSQLI_ASSOC) { return $this->rows; }
        public function num_rows() { return count($this->rows); }
        public function fetch_row() { if ($this->pos < count($this->rows)) { $r = array_values($this->rows[$this->pos++]); return $r; } return null; }
    };
}
?>
