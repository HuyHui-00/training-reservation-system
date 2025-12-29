<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== รับค่า ===== */
$q      = trim($_GET['q'] ?? '');
$start  = $_GET['start_date'] ?? '';
$end    = $_GET['end_date'] ?? '';
$period = $_GET['period'] ?? 'all';

/* ถือว่าค้นหา ถ้ามีการกดปุ่มค้นหา */
$searched = isset($_GET['period']) || $q !== '' || $start !== '' || $end !== '';
$grouped  = [];

if ($searched) {

    $types  = '';
    $params = [];

    /* ===== SQL หลัก ===== */
    $sql = "
        SELECT 
            t.id,
            t.title,
            t.date,
            r.period,
            r.name AS teacher_name
        FROM trainings t
        JOIN registrations r ON r.training_id = t.id
        WHERE r.role = 'teacher'
          AND r.name IS NOT NULL
          AND r.name <> ''
    ";

    if ($q !== '') {
        $sql     .= " AND r.name LIKE ?";
        $types   .= 's';
        $params[] = "%$q%";
    }

    if ($start !== '' && $end !== '') {
        $sql     .= " AND t.date BETWEEN ? AND ?";
        $types   .= 'ss';
        $params[] = $start;
        $params[] = $end;
    } elseif ($start !== '') {
        $sql     .= " AND t.date >= ?";
        $types   .= 's';
        $params[] = $start;
    } elseif ($end !== '') {
        $sql     .= " AND t.date <= ?";
        $types   .= 's';
        $params[] = $end;
    }

    if ($period === 'morning' || $period === 'afternoon') {
        $sql     .= " AND r.period = ?";
        $types   .= 's';
        $params[] = $period;
    }

    $sql .= " ORDER BY r.name ASC, t.date ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    if (!empty($params)) {
        $bind = [];
        $bind[] = &$types;
        foreach ($params as $k => $v) {
            $bind[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $teacher = $r['teacher_name'];
        $grouped[$teacher][] = $r;
    }
}

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
<title>ค้นหารายชื่ออาจารย์</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.speaker-card {
  border-left: 4px solid #0d6efd;
}
</style>
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

    <span class="navbar-brand fw-bold fs-4">ค้นหารายชื่ออาจารย์</span>

    <span class="text-white small d-none d-md-block">Admin Panel</span>
  </div>
</nav>

<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ค้นหารายชื่ออาจารย์</h4>
    <a href="a_training_program.php" class="btn btn-outline-secondary btn-sm">กลับ</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control"
                 placeholder="ค้นหาชื่ออาจารย์ (ไม่ใส่ก็ได้)"
                 value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-2">
          <input type="date" name="start_date" class="form-control"
                 value="<?= htmlspecialchars($start) ?>">
        </div>
        <div class="col-md-2">
          <input type="date" name="end_date" class="form-control"
                 value="<?= htmlspecialchars($end) ?>">
        </div>
        <div class="col-md-2">
          <select name="period" class="form-select">
            <option value="all" <?= $period==='all'?'selected':'' ?>>ทั้งหมด</option>
            <option value="morning" <?= $period==='morning'?'selected':'' ?>>ช่วงเช้า</option>
            <option value="afternoon" <?= $period==='afternoon'?'selected':'' ?>>ช่วงบ่าย</option>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-primary">ค้นหา</button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($searched && empty($grouped)): ?>
    <div class="alert alert-info">ไม่พบรายชื่ออาจารย์</div>

  <?php elseif ($searched): ?>
    <?php foreach ($grouped as $teacher => $items): ?>
      <div class="card mb-3 speaker-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><?= htmlspecialchars($teacher) ?></h6>
            <a href="a_teacher_detail.php?name=<?= urlencode($teacher) ?>"
               class="btn btn-sm btn-outline-primary">
              ดูรายละเอียด
            </a>
          </div>

          <small class="text-muted">
            จำนวนรายการอบรม: <?= count($items) ?>
          </small>

          <hr>

          <?php foreach ($items as $it): ?>
            <div class="mb-2">
              <strong><?= htmlspecialchars($it['title']) ?></strong><br>
              <small class="text-muted">
                <?= thaiDate($it['date']) ?> •
                <?= $it['period']==='morning'?'ช่วงเช้า':'ช่วงบ่าย' ?>
              </small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/components/sidebar_admin.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
