<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== รับค่า ===== */
$q      = trim($_GET['q'] ?? '');
$period = $_GET['period'] ?? 'all';

$results = [];

/* ===== SQL หลัก ===== */
$sql = "
    SELECT DISTINCT r.name
    FROM registrations r
    WHERE r.role = 'teacher'
      AND r.name IS NOT NULL
      AND r.name <> ''
";

$types  = '';
$params = [];

/* ===== ค้นหาชื่อ ===== */
if ($q !== '') {
    $sql     .= " AND r.name LIKE ?";
    $types   .= 's';
    $params[] = "%$q%";
}

/* ===== กรองช่วงเวลา (จาก registrations) ===== */
if ($period === 'morning' || $period === 'afternoon') {
    $sql     .= " AND r.period = ?";
    $types   .= 's';
    $params[] = $period;
}

/* ===== เรียง + จำกัด ===== */
$sql .= " ORDER BY r.name ASC LIMIT 50";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $bind   = [];
    $bind[] = &$types;
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

$stmt->execute();
$res = $stmt->get_result();

/* ===== ส่งออก JSON ===== */
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'id'   => $row['name'],
        'text' => $row['name']
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;
