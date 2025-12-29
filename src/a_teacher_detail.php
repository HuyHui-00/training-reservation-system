<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = trim($_GET['name'] ?? '');
if ($name === '') {
    die('ไม่พบชื่ออาจารย์');
}

/* ===== ดึงข้อมูลการอบรม ===== */
$sql = "
    SELECT 
        t.title,
        t.date,
        r.period
    FROM registrations r
    JOIN trainings t ON t.id = r.training_id
    WHERE r.role = 'teacher'
      AND r.name = ?
    ORDER BY t.date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $name);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

/* ===== ฟังก์ชันวันที่ไทย ===== */
function thaiDate($date) {
    $months = [
        1=>"ม.ค.",2=>"ก.พ.",3=>"มี.ค.",4=>"เม.ย.",
        5=>"พ.ค.",6=>"มิ.ย.",7=>"ก.ค.",8=>"ส.ค.",
        9=>"ก.ย.",10=>"ต.ค.",11=>"พ.ย.",12=>"ธ.ค."
    ];
    $t = strtotime($date);
    return date('d', $t) . ' ' . $months[(int)date('m', $t)] . ' ' . date('Y', $t);
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>รายละเอียดอาจารย์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<!-- ===== Navbar ===== -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">

    <button class="btn btn-outline-light me-2" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#adminSidebar"
            aria-controls="adminSidebar">
      ☰ เมนู
    </button>

    <span class="navbar-brand fw-bold fs-4">รายละเอียดอาจารย์</span>

    <span class="text-white small d-none d-md-block">Admin Panel</span>
  </div>
</nav>

<div class="container py-4">

  <!-- หัวข้อ -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ประวัติการอบรม</h4>
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
      กลับ
    </a>
  </div>

  <!-- ชื่ออาจารย์ -->
  <div class="card mb-3">
    <div class="card-body">
      <h5><?= htmlspecialchars($name) ?></h5>
      <small class="text-muted">
        เข้าร่วมการอบรมทั้งหมด <?= count($items) ?> ครั้ง
      </small>
    </div>
  </div>

  <!-- รายการอบรม -->
  <?php if (empty($items)): ?>
    <div class="alert alert-info">ยังไม่พบประวัติการอบรม</div>
  <?php else: ?>
    <?php foreach ($items as $it): ?>
      <div class="card mb-2">
        <div class="card-body">
          <strong><?= htmlspecialchars($it['title']) ?></strong><br>
          <small class="text-muted">
            <?= thaiDate($it['date']) ?> •
            <?= $it['period'] === 'morning' ? 'ช่วงเช้า' : 'ช่วงบ่าย' ?>
          </small>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/components/sidebar_admin.php'; ?>

<!-- 🔥 สำคัญมาก: ถ้าไม่มี sidebar จะไม่เปิด -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
