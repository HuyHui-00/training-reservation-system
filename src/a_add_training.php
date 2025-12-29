<?php
  include 'db.php';

  $error = '';

  /* เก็บค่าเดิม */
  $title    = $_POST['title'] ?? '';
  $speaker  = $_POST['speaker'] ?? '';
  $location = $_POST['location'] ?? '';
  $period   = $_POST['period'] ?? '';
  $detail   = $_POST['detail'] ?? '';
  $date     = $_POST['date'] ?? '';
  $max      = $_POST['max_participants'] ?? 30;

  /* วันที่ที่เต็มทั้งวัน */
  $fullDates = [];
  $sql = "
    SELECT date
    FROM trainings
    GROUP BY date
    HAVING COUNT(DISTINCT period) >= 2
  ";
  $result = $conn->query($sql);
  while ($row = $result->fetch_assoc()) {
    $fullDates[] = $row['date'];
  }

  /* บันทึกข้อมูล */
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']);
    $speaker  = trim($_POST['speaker']);
    $location = trim($_POST['location']);
    $period   = $_POST['period'];
    $detail   = trim($_POST['detail']);
    $date     = $_POST['date'];
    $max      = (int) $_POST['max_participants'];

    $checkStmt = $conn->prepare(
      'SELECT id FROM trainings WHERE date = ? AND period = ?'
    );
    $checkStmt->bind_param('ss', $date, $period);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows > 0) {
      $error = 'วันที่นี้มีการอบรมช่วงเวลานี้แล้ว';
    } else {
      $stmt = $conn->prepare(
        "INSERT INTO trainings
        (title, speaker, location, period, detail, date, max_participants)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
      );
      $stmt->bind_param(
        'ssssssi',
        $title,
        $speaker,
        $location,
        $period,
        $detail,
        $date,
        $max
      );
      $stmt->execute();

      header('Location: a_training_program.php?saved=1');
      exit;
    }
  }
?>

<!DOCTYPE html>
<html lang="th">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เพิ่มข้อมูลหลักสูตรอบรม</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
      /* ===============================
         ปรับเฉพาะมือถือเท่านั้น
         =============================== */
      @media (max-width: 576px) {
        .card {
          padding: 18px 14px !important;
        }

        .btn-group-mobile {
          display: flex;
          flex-direction: column;
          gap: 10px;
        }

        .btn-group-mobile .btn {
          width: 100%;
        }
      }
    </style>

    <script>
      const fullDates = <?= json_encode($fullDates) ?>;

      document.addEventListener('DOMContentLoaded', () => {
        const dateInput = document.querySelector("input[name='date']");
        dateInput.addEventListener('input', function () {
          if (fullDates.includes(this.value)) {
            Swal.fire({
              icon: 'warning',
              title: 'เลือกวันที่ไม่ได้',
              text: 'วันที่นี้มีการอบรมครบทั้งวันแล้ว',
            });
            this.value = '';
          }
        });
      });
    </script>
  </head>

  <body class="bg-light">
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

        <span class="navbar-brand fw-bold fs-4">เพิ่มหลักสูตรอบรมอบรม</span>

        <div class="d-flex align-items-center gap-2">
          <span class="text-white small d-none d-md-block">Admin Panel</span>
        </div>
      </div>
    </nav>

    <div class="container mt-4" style="max-width: 750px;">
      <div class="mb-3">
        <a href="a_training_program.php" class="btn btn-secondary">ย้อนกลับ</a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="card shadow-sm p-4">
        <h5 class="mb-4 text-primary fw-bold text-center">ข้อมูลหลักสูตรอบรม</h5>

        <div class="mb-3">
          <label class="form-label">ชื่อหลักสูตรอบรม</label>
          <input type="text" name="title" class="form-control"
            value="<?= htmlspecialchars($title) ?>" required>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">วันที่อบรม</label>
            <input type="date" name="date" class="form-control"
              value="<?= htmlspecialchars($date) ?>" required>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">ช่วงเวลา</label>
            <select name="period" class="form-select" required>
              <option value="">เลือก</option>
              <option value="morning" <?= ($period === 'morning') ? 'selected' : '' ?>>ช่วงเช้า</option>
              <option value="afternoon" <?= ($period === 'afternoon') ? 'selected' : '' ?>>ช่วงบ่าย</option>
            </select>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">จำนวนรับสมัคร</label>
            <input type="number" name="max_participants" class="form-control"
              min="1" max="500" value="<?= htmlspecialchars($max) ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">ชื่อวิทยากร</label>
          <input type="text" name="speaker" class="form-control"
            value="<?= htmlspecialchars($speaker) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">สถานที่จัดอบรม</label>
          <input type="text" name="location" class="form-control"
            value="<?= htmlspecialchars($location) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">รายละเอียดหลักสูตร</label>
          <textarea name="detail" class="form-control" rows="5" required><?= htmlspecialchars($detail) ?></textarea>
        </div>

        <div class="d-flex justify-content-between mt-4 btn-group-mobile">
          <button type="submit" class="btn btn-success btn-lg px-5">บันทึกข้อมูล</button>
          <a href="a_training_program.php" class="btn btn-outline-secondary btn-lg px-5">ยกเลิก</a>
        </div>
      </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      const form = document.querySelector('form');
      const saveBtn = document.querySelector("button[type='submit']");

      form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
          title: 'ยืนยันการบันทึก?',
          text: 'คุณต้องการเพิ่มหลักสูตรอบรมนี้ใช่หรือไม่',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'บันทึกข้อมูล',
          cancelButtonText: 'ยกเลิก',
          reverseButtons: true,
          allowOutsideClick: false,
        }).then((result) => {
          if (result.isConfirmed) {
            saveBtn.innerHTML = 'กำลังบันทึก...';
            saveBtn.disabled = true;

            Swal.fire({
              title: 'กำลังบันทึกข้อมูล...',
              allowOutsideClick: false,
              didOpen: () => Swal.showLoading(),
            });

            setTimeout(() => form.submit(), 700);
          }
        });
      });
    </script>

    <?php include __DIR__ . '/components/sidebar_admin.php'; ?>
  </body>
</html>