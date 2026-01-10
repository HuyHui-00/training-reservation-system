<?php
require_once __DIR__ . '/components/user_guard.php';
include 'db.php';

$user_id = $_SESSION['user_id'];

/* ===== user info ===== */
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit("ไม่พบข้อมูลผู้ใช้");
}

/* ===== helper ===== */
function thaiDate($date) {
    $months = [
        1=>"ม.ค.",2=>"ก.พ.",3=>"มี.ค.",4=>"เม.ย.",
        5=>"พ.ค.",6=>"มิ.ย.",7=>"ก.ค.",8=>"ส.ค.",
        9=>"ก.ย.",10=>"ต.ค.",11=>"พ.ย.",12=>"ธ.ค."
    ];
    $time = strtotime($date);
    return date("d", $time)." ".$months[(int)date("m",$time)]." ".date("Y",$time);
}

/* ===== search ===== */
$keyword = trim($_GET['keyword'] ?? '');

/* ===== history ===== */
$today = date('Y-m-d');

$sql = "
    SELECT r.id AS reg_id, t.title, t.training_date, t.period, t.speaker
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE r.user_id = ?
";

$types = "i";
$params = [$user_id];

if ($keyword !== '') {
    $sql .= " AND t.title LIKE ? ";
    $types .= "s";
    $params[] = "%{$keyword}%";
}

$sql .= " ORDER BY t.training_date DESC, t.period ASC";

$stmt_hist = $conn->prepare($sql);
$stmt_hist->bind_param($types, ...$params);
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
.mobile-history-card {
    border-left: 5px solid #0d6efd;
    transition: transform .2s;
}
.mobile-history-card:active {
    transform: scale(.98);
}
</style>
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

<!-- ================== ประวัติการอบรม ================== -->
<div class="card shadow-sm">
<div class="card-header text-white"
     style="background:linear-gradient(135deg,#0d6efd,#0b5ed7);">
    <h5 class="mb-0">
        <i class="bi bi-clock-history"></i> ประวัติการลงทะเบียนอบรม
    </h5>
</div>

<div class="card-body">

<!-- ===== search box ===== -->
<form method="get" class="mb-3">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text"
               name="keyword"
               class="form-control"
               placeholder="ค้นหาชื่ออบรม"
               value="<?= htmlspecialchars($keyword) ?>">
        <button class="btn btn-primary">ค้นหา</button>
        <?php if($keyword !== ''): ?>
            <a href="f_profile.php" class="btn btn-outline-secondary">ล้าง</a>
        <?php endif; ?>
    </div>
</form>

<!-- ===== desktop ===== -->
<div class="table-responsive d-none d-md-block">
<table class="table table-bordered table-striped align-middle text-center">
<thead style="background:#1f2937;color:white;">
<tr>
    <th>ลำดับ</th>
    <th>วันที่</th>
    <th>ช่วงเวลา</th>
    <th>หัวข้อ</th>
    <th>วิทยากร</th>
    <th>สถานะ</th>
    <th>จัดการ</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
if ($history->num_rows > 0):
while($row = $history->fetch_assoc()):
$is_past = ($row['training_date'] < $today);
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= thaiDate($row['training_date']) ?></td>
    <td>
        <?= $row['period']=='morning'
            ? '<span class="badge bg-warning">เช้า</span>'
            : '<span class="badge bg-info">บ่าย</span>' ?>
    </td>
    <td class="text-start"><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['speaker']) ?></td>
    <td>
        <?= $is_past
            ? '<span class="badge bg-secondary">อบรมเสร็จสิ้น</span>'
            : '<span class="badge bg-success">ลงทะเบียนแล้ว</span>' ?>
    </td>
    <td>
        <?php if(!$is_past): ?>
        <button onclick="confirmCancel(<?= $row['reg_id'] ?>)"
                class="btn btn-outline-danger btn-sm">
            ยกเลิก
        </button>
        <?php else: ?> -
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; else: ?>
<tr>
<td colspan="7" class="text-muted text-center py-4">
    ยังไม่มีประวัติการลงทะเบียน
</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

<!-- ===== mobile ===== -->
<div class="d-md-none">
<?php
$history->data_seek(0);
while($row = $history->fetch_assoc()):
$is_past = ($row['training_date'] < $today);
?>
<div class="card shadow-sm mb-3 mobile-history-card">
<div class="card-body">
    <h6 class="fw-bold text-primary"><?= htmlspecialchars($row['title']) ?></h6>
    <small class="text-muted">
        <?= thaiDate($row['training_date']) ?> |
        <?= $row['period']=='morning'?'เช้า':'บ่าย' ?>
    </small>

    <div class="mt-2 d-flex justify-content-between">
        <?= $is_past
            ? '<span class="badge bg-secondary">อบรมเสร็จสิ้น</span>'
            : '<span class="badge bg-success">ลงทะเบียนแล้ว</span>' ?>
        <?php if(!$is_past): ?>
        <button onclick="confirmCancel(<?= $row['reg_id'] ?>)"
                class="btn btn-outline-danger btn-sm">
            ยกเลิก
        </button>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endwhile; ?>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function confirmCancel(id){
    Swal.fire({
        title:'ยืนยันการยกเลิก?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#dc2626',
        cancelButtonText:'ไม่',
        confirmButtonText:'ใช่'
    }).then(r=>{
        if(r.isConfirmed){
            location.href='cancel_registration.php?reg_id='+id;
        }
    })
}
</script>
<?php if(isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'ยกเลิกสำเร็จ',
    text: 'ระบบได้ยกเลิกการลงทะเบียนอบรมเรียบร้อยแล้ว',
    confirmButtonText: 'ตกลง'
}).then(() => {
    // ลบ ?msg=cancelled ออกจาก URL หลังแสดง popup
    history.replaceState(null, '', 'f_profile.php');
});
</script>
<?php endif; ?>


</body>
</html>
