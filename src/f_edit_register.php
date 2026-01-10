<?php
require_once __DIR__ . '/components/user_guard.php';
require_once __DIR__ . '/db.php';

$reg_id = (int)($_GET['reg_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($reg_id <= 0) {
    exit("ข้อมูลไม่ถูกต้อง");
}

// ดึงข้อมูลการลงทะเบียน
$sql = "
    SELECT r.*, t.title, t.training_date, t.period as training_period
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE r.id = ? AND r.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reg_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    exit("ไม่พบข้อมูลการลงทะเบียน");
}

// ตรวจสอบว่าอบรมผ่านไปหรือยัง
$today = date('Y-m-d');
if ($data['training_date'] < $today) {
    exit("ไม่สามารถแก้ไขข้อมูลการอบรมที่ผ่านมาแล้วได้");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขข้อมูลลงทะเบียน</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  .form-container { max-width: 650px; margin: auto; }
  @media (max-width: 768px) {
      h4 { font-size: 20px; }
      .form-label { font-size: 14px; }
      input.form-control { font-size: 14px; padding: 10px; }
      .btn { font-size: 14px; padding: 10px; }
  }
</style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold fs-4">โครงการอบรม</span>
    <div class="ms-auto d-flex align-items-center">
        <a href="f_profile.php" class="text-white text-decoration-none me-3 fw-bold">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
        </a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="form-container">
    <div class="card shadow-sm">
      <div class="card-body">

        <h4 class="mb-3 text-center">แก้ไขข้อมูลการลงทะเบียน</h4>

        <form method="POST" action="edit_register_save.php">
          <input type="hidden" name="reg_id" value="<?= $reg_id ?>">

          <h5 class="mb-3 text-primary">ข้อมูลการอบรม</h5>
          <div class="mb-3">
            <label class="form-label">หัวข้ออบรม</label>
            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['title']) ?>" readonly>
          </div>
          <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">วันที่</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['training_date']) ?>" readonly>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">ช่วงเวลา</label>
                <input type="text" class="form-control bg-light" 
                       value="<?= $data['training_period'] === 'morning' ? 'ช่วงเช้า' : 'ช่วงบ่าย' ?>" readonly>
              </div>
          </div>

          <h5 class="mt-4 mb-3 text-primary">ข้อมูลนักศึกษา</h5>

          <div class="mb-3">
            <label class="form-label">รหัสนักศึกษา</label>
            <input type="text" name="student_id" class="form-control" 
                   value="<?= htmlspecialchars($data['student_id']) ?>"
                   required inputmode="numeric" pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
          </div>

          <div class="mb-3">
              <label class="form-label">ชื่อ - นามสกุล</label>
              <input type="text" class="form-control bg-light" 
                     value="<?= htmlspecialchars($data['student_name']) ?>" readonly>
              <div class="form-text text-muted">หากต้องการเปลี่ยนชื่อ กรุณาติดต่อเจ้าหน้าที่</div>
          </div>

          <div class="mb-3">
            <label class="form-label">คณะ</label>
            <input type="text" name="faculty" class="form-control" value="<?= htmlspecialchars($data['faculty']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">สาขาวิชา</label>
            <input type="text" name="major" class="form-control" value="<?= htmlspecialchars($data['major']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">กลุ่มเรียน</label>
            <input type="text" name="class_group" class="form-control" value="<?= htmlspecialchars($data['class_group']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">อีเมล</label>
            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($data['email']) ?>" readonly>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-warning w-50 me-2" type="submit">
              <i class="bi bi-save"></i> บันทึกการแก้ไข
            </button>

            <a class="btn btn-secondary w-50" href="f_history.php">
               ยกเลิก
            </a>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
