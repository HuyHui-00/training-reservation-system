<?php
require_once __DIR__ . '/components/user_guard.php';
require_once __DIR__ . '/db.php'; // === เพิ่ม ===

$id     = (int)($_GET['id'] ?? 0);
$period = $_GET['period'] ?? '';
$date   = $_GET['date'] ?? '';

if ($id <= 0) {
    exit("ไม่พบข้อมูลการอบรม");
}

$user_id = $_SESSION['user_id'];
$alreadyRegistered = false;

$stmt = $conn->prepare("
    SELECT r.id
    FROM registrations r
    JOIN trainings t ON t.id = r.training_id
    WHERE r.user_id = ?
      AND r.training_id = ?
      AND t.date = ?
      AND r.period = ?
    LIMIT 1
");
$stmt->bind_param("iiss", $user_id, $id, $date, $period);
$stmt->execute();
$alreadyRegistered = $stmt->get_result()->num_rows > 0;

?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ลงทะเบียนอบรม</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  .form-container {
    max-width: 650px;
    margin: auto;
  }

  @media (max-width: 768px) {
      h4 { font-size: 20px; }
      .form-label { font-size: 14px; }
      input.form-control { font-size: 14px; padding: 10px; }
      .btn { font-size: 14px; padding: 10px; }
      .btn-cancel { width: auto !important; }
  }
</style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold fs-4 d-flex align-items-center">
      โครงการอบรม
    </span>
  </div>
</nav>

<div class="container">
  <div class="form-container">
    <div class="card shadow-sm">
      <div class="card-body">

        <h4 class="mb-3 text-center">ลงทะเบียนเข้าร่วมอบรม</h4>

        <?php if ($alreadyRegistered): ?>
        <!-- =======================
             === เพิ่ม : แจ้งว่าลงทะเบียนแล้ว ===
             ======================= -->
        <div class="alert alert-warning text-center">
            <h5 class="mb-2">คุณได้ลงทะเบียนอบรมนี้แล้ว</h5>
            <p class="text-muted mb-3">
                ระบบไม่อนุญาตให้ลงทะเบียนซ้ำ
            </p>
            <a href="f_program_detail.php?date=<?= urlencode($date) ?>"
               class="btn btn-secondary">
               กลับไปหน้ารายการอบรม
            </a>
        </div>

        <?php else: ?>

        <p class="text-muted text-center mb-4">
          กรุณากรอกข้อมูลนักศึกษาให้ครบถ้วนและถูกต้อง
        </p>

        <form method="POST" action="register_save.php">

          <!-- ส่งค่าไปบันทึก -->
          <input type="hidden" name="training_id" value="<?= htmlspecialchars($id) ?>">
          <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">
          <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
          <input type="hidden" name="role" value="student">

          <h5 class="mb-3">ข้อมูลการอบรม</h5>

          <div class="mb-3">
            <label class="form-label">ช่วงเวลาอบรม</label>
            <input type="text" class="form-control"
                   value="<?= $period === 'morning' ? 'ช่วงเช้า' : 'ช่วงบ่าย' ?>"
                   readonly>
          </div>

          <h5 class="mt-4 mb-3">ข้อมูลนักศึกษา</h5>

          <div class="mb-3">
            <label class="form-label">รหัสนักศึกษา</label>
            <input
              type="text"
              name="student_id"
              class="form-control"
              required
              inputmode="numeric"
              pattern="[0-9]+"
              oninput="this.value = this.value.replace(/[^0-9]/g, '')">
          </div>

          <div class="mb-3">
            <label class="form-label">ชื่อ - นามสกุล</label>
            <input type="text" name="student_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">คณะ</label>
            <input type="text" name="faculty" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">สาขาวิชา</label>
            <input type="text" name="major" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">กลุ่มเรียน</label>
            <input type="text" name="class_group" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">อีเมลนักศึกษา</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-success w-50 me-2" type="submit">
              ยืนยันลงทะเบียน
            </button>

            <a class="btn btn-secondary w-50 btn-cancel"
               href="f_program_detail.php?date=<?= urlencode($date) ?>">
               ยกเลิก
            </a>
          </div>

        </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET['success']) && isset($_GET['name'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'ลงทะเบียนสำเร็จ',
    html: `
        ระบบได้บันทึกข้อมูลของคุณเรียบร้อยแล้ว<br>
        <strong style="font-size:18px; color:#0d6efd;">
            ผู้ลง: <?= htmlspecialchars($_GET['name']) ?>
        </strong><br>
        <span style="color:red; font-weight:bold;">
            กรุณาแคปหน้าจอไว้เป็นหลักฐาน
        </span>
    `,
    confirmButtonText: 'ตกลง'
}).then(() => {
    window.location.href = "f_program_detail.php?date=<?= urlencode($date) ?>";
});
</script>
<?php endif; ?>

</body>
</html>
