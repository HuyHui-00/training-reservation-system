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

$morningFull   = !empty($trainings['morning']) ? $morningCount >= $trainings['morning']['max_participants'] : false;
$afternoonFull = !empty($trainings['afternoon']) ? $afternoonCount >= $trainings['afternoon']['max_participants'] : false;

// เช็ควันที่: หากวันที่การอบรมผ่านไปแล้ว (น้อยกว่า today) ให้ปิดการลงทะเบียน
$today = date('Y-m-d');
$morningAllowed = !empty($trainings['morning']) ? (strtotime($trainings['morning']['date']) >= strtotime($today)) : false;
$afternoonAllowed = !empty($trainings['afternoon']) ? (strtotime($trainings['afternoon']['date']) >= strtotime($today)) : false;
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>รายละเอียดหลักสูตร</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@media (max-width: 576px) {
    .card {
        margin-bottom: 20px;
    }
    h5 {
        font-size: 16px;
    }
    .btn {
        font-size: 14px;
        padding: 10px;
    }
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    .mb-2 {
        font-size: 14px;
    }
    .border.rounded.p-2 {
        font-size: 14px;
    }
}

.btn-back {
    width: auto !important;
    display: inline-block;
}
</style>

</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    
    <span class="navbar-brand fw-bold fs-4 d-flex align-items-center">
      โครงงการอบรม
    </span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

  </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

<div class="mb-3">
  <a href="f_training_program.php" class="btn btn-secondary btn-back">ย้อนกลับ</a>
</div>

<div class="row">

  <!-- ===================== ช่วงเช้า ===================== -->
  <div class="col-md-6">
    <div class="card shadow-sm p-3 mb-4">
      <h5 class="mb-3 text-primary text-center">ข้อมูลช่วงเช้า</h5>

      <?php if (!empty($trainings['morning'])): $t = $trainings['morning']; ?>
      <div class="mb-2"><strong>วันที่:</strong> <?= htmlspecialchars($t['date']) ?></div>
      <div class="mb-2"><strong>หัวข้ออบรม:</strong> <?= htmlspecialchars($t['title']) ?></div>
      <div class="mb-2"><strong>รายละเอียด:</strong></div>
      <div class="border rounded p-2 mb-2 bg-light">
        <?= nl2br(htmlspecialchars($t['detail'])) ?>
      </div>
      <div class="mb-2"><strong>วิทยากร:</strong> <?= htmlspecialchars($t['speaker']) ?></div>

      <div class="mb-2 fw-bold text-primary">
        ผู้ลงทะเบียน: <?= $morningCount ?> / <?= $t['max_participants'] ?> คน
      </div>

        <div class="text-center mt-3">
        <?php if (!$morningAllowed): ?>
          <span class="badge bg-success p-2">อบรมสำเร็จ</span>
        <?php elseif ($morningFull): ?>
          <span class="badge bg-danger p-2">เต็มแล้ว</span>
        <?php else: ?>
          <a class="btn btn-primary w-100"
             href="f_register_form.php?id=<?= $t['id'] ?>&period=morning&date=<?= urlencode($t['date']) ?>">
             ลงทะเบียนช่วงเช้า
          </a>
        <?php endif; ?>
        </div>

      <?php else: ?>
      <div class="alert alert-secondary text-center">
          ไม่มีการอบรมช่วงเช้า
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ===================== ช่วงบ่าย ===================== -->
  <div class="col-md-6">
    <div class="card shadow-sm p-3 mb-4">
      <h5 class="mb-3 text-primary text-center">ข้อมูลช่วงบ่าย</h5>

      <?php if (!empty($trainings['afternoon'])): $t = $trainings['afternoon']; ?>
      <div class="mb-2"><strong>วันที่:</strong> <?= htmlspecialchars($t['date']) ?></div>
      <div class="mb-2"><strong>หัวข้ออบรม:</strong> <?= htmlspecialchars($t['title']) ?></div>
      <div class="mb-2"><strong>รายละเอียด:</strong></div>
      <div class="border rounded p-2 mb-2 bg-light">
        <?= nl2br(htmlspecialchars($t['detail'])) ?>
      </div>
      <div class="mb-2"><strong>วิทยากร:</strong> <?= htmlspecialchars($t['speaker']) ?></div>

      <div class="mb-2 fw-bold text-warning">
        ผู้ลงทะเบียน: <?= $afternoonCount ?> / <?= $t['max_participants'] ?> คน
      </div>

        <div class="text-center mt-3">
        <?php if (!$afternoonAllowed): ?>
          <span class="badge bg-success p-2">อบรมสำเร็จ</span>
        <?php elseif ($afternoonFull): ?>
          <span class="badge bg-danger p-2">เต็มแล้ว</span>
        <?php else: ?>
          <a class="btn btn-primary w-100"
             href="f_register_form.php?id=<?= $t['id'] ?>&period=afternoon&date=<?= urlencode($t['date']) ?>">
             ลงทะเบียนช่วงบ่าย
          </a>
        <?php endif; ?>
        </div>

      <?php else: ?>
      <div class="alert alert-secondary text-center">
          ไม่มีการอบรมช่วงบ่าย
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>