<?php
include 'db.php';

$date = $_GET['date'] ?? '';
if (!$date) exit("ไม่พบข้อมูลหลักสูตร");

$stmt = $conn->prepare("SELECT * FROM trainings WHERE date=? ORDER BY period ASC");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$trainings = [];
while ($row = $result->fetch_assoc()) {
    $trainings[$row['period']] = $row;
}

if (empty($trainings)) {
    exit("ไม่พบหลักสูตรในวันนั้น");
}

function countRegister($conn, $training_id, $period) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM registrations 
        WHERE training_id=? AND period=?
    ");
    $stmt->bind_param("is", $training_id, $period);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

$morningCount   = !empty($trainings['morning']) ? countRegister($conn, $trainings['morning']['id'], 'morning') : 0;
$afternoonCount = !empty($trainings['afternoon']) ? countRegister($conn, $trainings['afternoon']['id'], 'afternoon') : 0;

$today = date('Y-m-d');
$morningAllowed   = !empty($trainings['morning'])   ? (strtotime($trainings['morning']['date'])   >= strtotime($today)) : false;
$afternoonAllowed = !empty($trainings['afternoon']) ? (strtotime($trainings['afternoon']['date']) >= strtotime($today)) : false;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>รายละเอียดหลักสูตร</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#adminSidebar">
      เมนู
    </button>

    <span class="navbar-brand fw-bold fs-4">
      รายละเอียดหลักสูตรอบรม
    </span>
  </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

<div class="mb-3 d-flex justify-content-between">
  <a href="a_training_program.php" class="btn btn-secondary">ย้อนกลับ</a>
  <a href="a_edit_training_program.php?date=<?= urlencode($date) ?>" class="btn btn-warning">แก้ไข</a>
</div>

<div class="row">

<!-- ===== ช่วงเช้า ===== -->
<div class="col-md-6">
  <div class="card shadow-sm p-3 mb-4">
    <h5 class="mb-3 text-primary text-center">ข้อมูลช่วงเช้า</h5>

    <?php if (!empty($trainings['morning'])): $t = $trainings['morning']; ?>

      <div class="mb-2"><strong>วันที่:</strong> <?= htmlspecialchars($t['date']) ?></div>
      <div class="mb-2"><strong>หัวข้ออบรม:</strong> <?= htmlspecialchars($t['title']) ?></div>

      <div class="mb-1"><strong>รายละเอียด:</strong></div>
      <div class="border rounded p-2 mb-2 bg-light">
        <?= htmlspecialchars($t['detail']) ?>
      </div>

      <div class="mb-2"><strong>วิทยากร:</strong> <?= htmlspecialchars($t['speaker']) ?></div>

      <div class="fw-bold mb-2">
        ผู้ลงทะเบียน:
        <?= $morningCount ?> / <?= $t['max_participants'] ?> คน
      </div>

      <a href="a_participants_training_detail.php?date=<?= urlencode($date) ?>&period=morning"
         class="btn btn-outline-primary btn-sm w-100">
        ดูรายละเอียดผู้เข้าร่วม
      </a>

    <?php else: ?>
      <div class="alert alert-secondary text-center">
        ไม่มีการอบรมช่วงเช้า
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== ช่วงบ่าย ===== -->
<div class="col-md-6">
  <div class="card shadow-sm p-3">
    <h5 class="mb-3 text-primary text-center">ข้อมูลช่วงบ่าย</h5>

    <?php if (!empty($trainings['afternoon'])): $t = $trainings['afternoon']; ?>

      <div class="mb-2"><strong>วันที่:</strong> <?= htmlspecialchars($t['date']) ?></div>
      <div class="mb-2"><strong>หัวข้ออบรม:</strong> <?= htmlspecialchars($t['title']) ?></div>

      <div class="mb-1"><strong>รายละเอียด:</strong></div>
      <div class="border rounded p-2 mb-2 bg-light">
        <?= htmlspecialchars($t['detail']) ?>
      </div>

      <div class="mb-2"><strong>วิทยากร:</strong> <?= htmlspecialchars($t['speaker']) ?></div>

      <div class="fw-bold mb-2">
        ผู้ลงทะเบียน:
        <?= $afternoonCount ?> / <?= $t['max_participants'] ?> คน
      </div>

      <a href="a_participants_training_detail.php?date=<?= urlencode($date) ?>&period=afternoon"
         class="btn btn-outline-primary btn-sm w-100">
        ดูรายละเอียดผู้เข้าร่วม
      </a>

    <?php else: ?>
      <div class="alert alert-secondary text-center">
        ไม่มีการอบรมช่วงบ่าย
      </div>
    <?php endif; ?>
  </div>
</div>

</div>
</div>

<?php include __DIR__ . '/components/sidebar_admin.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
