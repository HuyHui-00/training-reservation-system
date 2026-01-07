<?php
require_once __DIR__ . '/../components/admin_guard.php';
require_once __DIR__ . '/../db.php';

$date = $_GET['training_date'] ?? '';
if (!$date) exit("ไม่พบข้อมูลหลักสูตร");

/* ======================== ดึงข้อมูล ======================== */
$stmt = $conn->prepare("SELECT * FROM trainings WHERE training_date=? ORDER BY period ASC");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$trainings = [];
while ($row = $result->fetch_assoc()) {
    $trainings[$row['period']] = $row;
}

/* ======================== บันทึกการแก้ไข ======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($trainings['morning'])) {
        $id = $trainings['morning']['id'];
        $stmt = $conn->prepare("
            UPDATE trainings 
            SET training_date=?, period=?, title=?, speaker=?, location=?, detail=?, max_participants=? 
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssssii",
            $_POST['morning_date'],
            $_POST['morning_period'],
            $_POST['morning_title'],
            $_POST['morning_speaker'],
            $_POST['morning_location'],
            $_POST['morning_detail'],
            $_POST['morning_max'],
            $id
        );
        $stmt->execute();
    }

    if (!empty($trainings['afternoon'])) {
        $id = $trainings['afternoon']['id'];
        $stmt = $conn->prepare("
            UPDATE trainings 
            SET training_date=?, period=?, title=?, speaker=?, location=?, detail=?, max_participants=? 
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssssii",
            $_POST['afternoon_date'],
            $_POST['afternoon_period'],
            $_POST['afternoon_title'],
            $_POST['afternoon_speaker'],
            $_POST['afternoon_location'],
            $_POST['afternoon_detail'],
            $_POST['afternoon_max'],
            $id
        );
        $stmt->execute();
    }

    header("Location: a_training_program.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขหลักสูตรอบรม</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ================= Desktop (เหมือนเดิม) ================= */
.container-custom {
    max-width: 1000px;
}
label {
    font-weight: 600;
}
textarea {
    min-height: 120px;
}

/* ================= Mobile Only ================= */
@media (max-width: 576px) {

    /* ให้ card ชิดจอน้อยลง */
    .card-body {
        padding: 14px;
    }

    /* ตัวอักษรอ่านง่ายขึ้น */
    label {
        font-size: 0.95rem;
    }

    input, textarea, select {
        font-size: 0.95rem;
    }

    /* ปุ่มบันทึกเต็มความกว้าง */
    #saveBtn {
        width: 100%;
        padding: 12px;
    }

    /* ระยะห่าง card */
    .col-md-6 {
        margin-bottom: 16px;
    }
}
</style>
</head>

<body class="bg-light">
<?php include __DIR__ . '/../components/sidebar_admin.php'; ?>
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

    <span class="navbar-brand fw-bold fs-4">
      แก้ไขหลักสูตรอบรมอบรม
    </span>

    <div class="d-flex align-items-center gap-2">
      <span class="text-white small d-none d-md-block">
        Admin Panel
      </span>
    </div>
  </div>
</nav>

<!-- ======================== Content ======================== -->
<div class="container container-custom mt-4">

    <a href="/admin/a_training_program.php" class="btn btn-secondary mb-3">ย้อนกลับ</a>

    <form method="POST" id="editForm">
        <div class="row g-4">

            <!-- ================== ช่วงเช้า ================== -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning fw-bold">ช่วงเช้า</div>
                    <div class="card-body">

                        <?php if (!empty($trainings['morning'])): $t = $trainings['morning']; ?>

                        <div class="mb-2">
                            <label>วันที่</label>
                            <input type="date" name="morning_date" class="form-control" value="<?= $t['training_date'] ?>" required>
                        </div>

                        <div class="mb-2">
                            <label>ช่วงเวลา</label>
                            <select name="morning_period" class="form-select">
                                <option value="morning" selected>ช่วงเช้า</option>
                                <option value="afternoon">ช่วงบ่าย</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label>หัวข้ออบรม</label>
                            <input type="text" name="morning_title" class="form-control" value="<?= htmlspecialchars($t['title']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>วิทยากร</label>
                            <input type="text" name="morning_speaker" class="form-control" value="<?= htmlspecialchars($t['speaker']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>สถานที่</label>
                            <input type="text" name="morning_location" class="form-control" value="<?= htmlspecialchars($t['location']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>จำนวนที่รับ</label>
                            <input type="number" name="morning_max" class="form-control" value="<?= $t['max_participants'] ?>">
                        </div>

                        <div class="mb-2">
                            <label>รายละเอียด</label>
                            <textarea name="morning_detail" class="form-control"><?= htmlspecialchars($t['detail']) ?></textarea>
                        </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- ================== ช่วงบ่าย ================== -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info fw-bold text-white">ช่วงบ่าย</div>
                    <div class="card-body">

                        <?php if (!empty($trainings['afternoon'])): $t = $trainings['afternoon']; ?>

                        <div class="mb-2">
                            <label>วันที่</label>
                            <input type="date" name="afternoon_date" class="form-control" value="<?= $t['training_date'] ?>">
                        </div>

                        <div class="mb-2">
                            <label>ช่วงเวลา</label>
                            <select name="afternoon_period" class="form-select">
                                <option value="morning">ช่วงเช้า</option>
                                <option value="afternoon" selected>ช่วงบ่าย</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label>หัวข้ออบรม</label>
                            <input type="text" name="afternoon_title" class="form-control" value="<?= htmlspecialchars($t['title']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>วิทยากร</label>
                            <input type="text" name="afternoon_speaker" class="form-control" value="<?= htmlspecialchars($t['speaker']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>สถานที่</label>
                            <input type="text" name="afternoon_location" class="form-control" value="<?= htmlspecialchars($t['location']) ?>">
                        </div>

                        <div class="mb-2">
                            <label>จำนวนที่รับ</label>
                            <input type="number" name="afternoon_max" class="form-control" value="<?= $t['max_participants'] ?>">
                        </div>

                        <div class="mb-2">
                            <label>รายละเอียด</label>
                            <textarea name="afternoon_detail" class="form-control"><?= htmlspecialchars($t['detail']) ?></textarea>
                        </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success btn-lg px-5" id="saveBtn">
                บันทึกการแก้ไข
            </button>
        </div>

    </form>
</div>

<script>
const form = document.getElementById("editForm");
const saveBtn = document.getElementById("saveBtn");

form.addEventListener("submit", function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'ยืนยันการบันทึก?',
        text: 'คุณต้องการบันทึกการแก้ไขนี้ใช่หรือไม่',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
        allowOutsideClick: false,
    }).then((result) => {
        if (result.isConfirmed) {
            saveBtn.innerHTML = "กำลังบันทึก...";
            saveBtn.disabled = true;
            form.submit();
        }
    });
});
</script>
</body>
</html>
