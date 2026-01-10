<?php
require_once __DIR__ . '/components/user_guard.php';
include 'db.php';

$user_id = $_SESSION['user_id'];

/* ===== user info ===== */
$stmt = $conn->prepare("SELECT username, email, role, student_id, faculty, major, class_group FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit("ไม่พบข้อมูลผู้ใช้");
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>โปรไฟล์ของฉัน</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg,#2563eb,#1e40af);">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold fs-4">โครงการอบรม</span>

    <div class="ms-auto d-flex align-items-center">
        <a href="f_profile.php" class="text-white text-decoration-none me-3 fw-bold">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
        </a>
        <a href="/logout.php" id="btnLogout" class="btn btn-outline-light btn-sm">
            ออกจากระบบ
        </a>
    </div>
  </div>
</nav>

<div class="container mt-4">

<a href="f_training_program.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
</a>

<!-- ================== ข้อมูลส่วนตัว ================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="text-primary mb-0">
            <i class="bi bi-person-vcard"></i> ข้อมูลส่วนตัว
        </h5>
    </div>
    <div class="card-body">
        <div class="mb-2"><b>ชื่อผู้ใช้:</b> <?= htmlspecialchars($user['username']) ?></div>
        
        <?php if($user['role'] === 'Student'): ?>
        <div class="mb-2"><b>รหัสนักศึกษา:</b> <?= htmlspecialchars($user['student_id']) ?></div>
        <div class="mb-2"><b>คณะ:</b> <?= htmlspecialchars($user['faculty']) ?></div>
        <div class="mb-2"><b>สาขาวิชา:</b> <?= htmlspecialchars($user['major']) ?></div>
        <div class="mb-2"><b>กลุ่มเรียน:</b> <?= htmlspecialchars($user['class_group']) ?></div>
        <?php endif; ?>

        <div class="mb-2"><b>อีเมล:</b> <?= htmlspecialchars($user['email']) ?></div>
        <div>
            <b>สถานะ:</b>
            <?php
            $roles = [
                'Admin'=>'ผู้ดูแลระบบ',
                'Student'=>'นักศึกษา',
                'Staff'=>'เจ้าหน้าที่'
            ];
            ?>
            <span class="badge bg-info">
                <?= htmlspecialchars($roles[$user['role']] ?? $user['role']) ?>
            </span>
        </div>
    </div>
</div>

<!-- ================== ปุ่มเมนูจัดการ ================== -->
<div class="row g-3">
    <div class="col-md-6">
        <a href="edit_profile.php" class="btn btn-warning w-100 py-1 shadow-sm">
            <i class="bi bi-pencil-square fs-4"></i><br>
            แก้ไขข้อมูลผู้ใช้
        </a>
    </div>
    <div class="col-md-6">
        <a href="f_history.php" class="btn btn-primary w-100 py-1 shadow-sm">
            <i class="bi bi-clock-history fs-4"></i><br>
            ประวัติการลงทะเบียน
        </a>
    </div>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
