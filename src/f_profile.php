<?php
require_once __DIR__ . '/components/user_guard.php';
include 'db.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT prefix, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit("ไม่พบข้อมูลผู้ใช้");
}

// ฟังก์ชันแปลงวันที่
function thaiDate($date) {
    $months = [
        1=>"ม.ค.",2=>"ก.พ.",3=>"มี.ค.",4=>"เม.ย.",
        5=>"พ.ค.",6=>"มิ.ย.",7=>"ก.ค.",8=>"ส.ค.",
        9=>"ก.ย.",10=>"ต.ค.",11=>"พ.ย.",12=>"ธ.ค."
    ];
    $time = strtotime($date);
    return date("d", $time)." ".$months[(int)date("m",$time)]." ".date("Y",$time);
}

// ดึงประวัติการลงทะเบียน
$today = date('Y-m-d');
$sql_history = "
    SELECT r.id as reg_id, t.title, t.training_date, t.period, t.speaker 
    FROM registrations r 
    JOIN trainings t ON r.training_id = t.id 
    WHERE r.user_id = ? 
    ORDER BY t.training_date DESC, t.period ASC
";
$stmt_hist = $conn->prepare($sql_history);
$stmt_hist->bind_param("i", $user_id);
$stmt_hist->execute();
$history = $stmt_hist->get_result();
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
<style>
    /* ปรับแต่งการ์ดในมือถือ */
    .mobile-history-card {
        border-left: 5px solid #0d6efd;
        transition: transform 0.2s;
    }
    .mobile-history-card:active {
        transform: scale(0.98);
    }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">

    <!-- Brand -->
    <span class="navbar-brand fw-bold fs-4">
      โครงการอบรม
    </span>

    <!-- Toggle (mobile) -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navMenu">

      <!-- ดันไปขวาสุด -->
      <div class="ms-auto d-flex align-items-center">
        <a href="f_profile.php" class="text-white text-decoration-none me-3 fw-bold">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
        </a>
        <a href="/logout.php"
           id="btnLogout"
           class="btn btn-outline-light btn-sm">
           ออกจากระบบ
        </a>
      </div>

    </div>

  </div>
</nav>

<div class="container mt-5">
<div class="container mt-4">
    <div class="row justify-content-center">
    <div class="d-flex justify-content-end mb-3">
        <a href="f_training_program.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
        </a>
    </div>

    <!-- คอลัมน์หลัก -->
    <div class="col-md-12">

        <!-- ข้อมูลส่วนตัว -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="bi bi-person-vcard"></i> ข้อมูลส่วนตัว
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold text-muted">ชื่อผู้ใช้</label>
                    <div class="fs-5"><?= htmlspecialchars($user['prefix']) . ' ' . htmlspecialchars($user['username'])?></div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted">อีเมล<div class="fs-5"><?= htmlspecialchars($user['email']) ?></div></label>
                    
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted">สถานะ:
                        <?php
                        $roles = [
                            'Admin'   => 'ผู้ดูแลระบบ',
                            'Student' => 'นักศึกษา',
                            'Staff'   => 'เจ้าหน้าที่'
                        ];
                
                        $roleText = $roles[$user['role']] ?? $user['role'];
                        ?>
                        <span class="badge bg-info"><?= htmlspecialchars($roleText) ?></span>
                    </label>
                </div>

            </div>
        </div>

        <!-- ประวัติการอบรม -->
        <div class="card shadow-sm">
            <!-- (โค้ดตารางของคุณ ใช้เหมือนเดิมได้เลย) -->


    <!-- ส่วนประวัติการอบรม -->
    <div class="card shadow-sm">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> ประวัติการลงทะเบียนอบรม</h5>
        </div>
        <div class="card-body">

            <!-- 1. แสดงผลแบบตารางสำหรับ Desktop -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-striped align-middle text-center">
                            <thead style="background:#1f2937;color:white;">
                                <tr>
                            <th style="width: 60px;">ลำดับ</th>
                            <th style="width: 120px;">วันที่อบรม</th>
                            <th style="width: 100px;">ช่วงเวลา</th>
                            <th>หัวข้อ</th>
                            <th>วิทยากร</th>
                            <th style="width: 120px;">สถานะ</th>
                            <th style="width: 100px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php 
                    $index = 1;
                    if ($history->num_rows > 0): 
                        while($row = $history->fetch_assoc()): 
                                    $is_past = ($row['training_date'] < $today);
                    ?>
                                <tr>
                            <td><?= $index++ ?></td>
                            <td><?= thaiDate($row['training_date']) ?></td>
                                    <td>
                                        <?php if($row['period'] == 'morning'): ?>
                                            <span class="badge text-bg-warning"><i class="bi bi-sun"></i> เช้า</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-info"><i class="bi bi-moon"></i> บ่าย</span>
                                        <?php endif; ?>
                                    </td>
                            <td class="text-start"><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['speaker']) ?></td>
                                    <td>
                                        <?php if($is_past): ?>
                                            <span class="badge bg-secondary">อบรมเสร็จสิ้น</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">ลงทะเบียนแล้ว</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!$is_past): ?>
                                            <button onclick="confirmCancel(<?= $row['reg_id'] ?>)" 
                                                    class="btn btn-outline-danger btn-sm">
                                                ยกเลิก
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                                <tr>
                            <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีประวัติการลงทะเบียน</td>
                                </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 2. แสดงผลแบบการ์ดสำหรับ Mobile -->
            <div class="d-md-none">
                <?php 
                if ($history->num_rows > 0):
                    $history->data_seek(0); // Reset pointer
                    while($row = $history->fetch_assoc()): 
                        $is_past = ($row['training_date'] < $today);
                ?>
                <div class="card shadow-sm mb-3 mobile-history-card">
                    <div class="card-body">
                        <h6 class="card-title fw-bold text-primary mb-2"><?= htmlspecialchars($row['title']) ?></h6>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar-event"></i> <?= thaiDate($row['training_date']) ?> 
                            <span class="mx-1">|</span> 
                            <?= $row['period'] == 'morning' ? 'ช่วงเช้า' : 'ช่วงบ่าย' ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <?php if($is_past): ?>
                                <span class="badge bg-secondary">อบรมเสร็จสิ้น</span>
                            <?php else: ?>
                                <span class="badge bg-success">ลงทะเบียนแล้ว</span>
                                <button onclick="confirmCancel(<?= $row['reg_id'] ?>)" class="btn btn-outline-danger btn-sm">ยกเลิก</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-secondary text-center">ยังไม่มีประวัติการลงทะเบียน</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmCancel(regId) {
    Swal.fire({
        title: 'ยืนยันการยกเลิก?',
        text: "คุณต้องการยกเลิกการลงทะเบียนนี้ใช่หรือไม่",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ยกเลิกเลย',
        cancelButtonText: 'ไม่'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cancel_registration.php?reg_id=' + regId;
        }
    })
}

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
    Swal.fire({
        icon: 'success',
        title: 'ยกเลิกเรียบร้อย',
        text: 'ระบบได้ยกเลิกการลงทะเบียนของคุณแล้ว',
        timer: 2000,
        showConfirmButton: false
    });
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>
</body>
</html>