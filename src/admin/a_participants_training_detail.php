<?php
require_once __DIR__ . '/../components/admin_guard.php';
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== รับค่า ===== */
$date   = $_GET['training_date']   ?? '';
$period = $_GET['period'] ?? '';
$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

if ($date === '' || ($period !== 'morning' && $period !== 'afternoon')) {
    exit('ข้อมูลไม่ถูกต้อง');
}

$limit  = 10;
$offset = ($page - 1) * $limit;

/* ===== ดึงชื่อโครงการอบรม ===== */
$stmt = $conn->prepare("
    SELECT title 
    FROM trainings 
    WHERE training_date = ? AND period = ?
    LIMIT 1
");
$stmt->bind_param("ss", $date, $period);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) {
    exit('ไม่พบข้อมูลหลักสูตร');
}

$title = $training['title'];

/* ===== เงื่อนไขค้นหา ===== */
$where  = "r.period = ? AND t.training_date = ?";
$types  = 'ss';
$params = [$period, $date];

if ($q !== '') {
    $where   .= " AND r.student_name LIKE ?";
    $types   .= 's';
    $params[] = "%$q%";
}

/* ===== นับจำนวน ===== */
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM registrations r
    JOIN trainings t ON t.id = r.training_id
    WHERE $where
";
$stmt = $conn->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

$totalPages = ceil($total / $limit);

/* ===== ดึงรายชื่อ ===== */
$sql = "
    SELECT
        r.student_id,
        r.class_group,
        r.student_name,
        r.faculty,
        r.major,
        r.email
    FROM registrations r
    JOIN trainings t ON t.id = r.training_id
    WHERE $where
    ORDER BY r.student_name ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>รายชื่อผู้เข้าร่วมอบรม</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<?php include __DIR__ . '/../components/sidebar_admin.php'; ?>
<!-- ===== Navbar ===== -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#adminSidebar">
      ☰ เมนู
    </button>
    <span class="navbar-brand fw-bold fs-5">รายชื่อผู้เข้าร่วมอบรม</span>
    <span class="text-white small d-none d-md-block">Admin Panel</span>
  </div>
</nav>

<div class="container py-4">

  <!-- หัวข้อ -->
  <div class="mb-3">
    <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
    <small class="text-muted">
      <?= $period === 'morning' ? 'ช่วงเช้า' : 'ช่วงบ่าย' ?> |
      วันที่ <?= htmlspecialchars($date) ?>
    </small>
  </div>

  <div class="d-flex justify-content-between al ign-items-center mb-3">
    <span class="text-muted">พบทั้งหมด <?= $total ?> คน</span>
    <a href="/admin/a_program_detail.php?training_date=<?= urlencode($date) ?>" class="btn btn-outline-secondary btn-sm">กลับ</a>
  </div>

  <!-- ค้นหา -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
        <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">

        <div class="col-md-10">
          <input type="text"
                 name="q"
                 class="form-control"
                 placeholder="ค้นหาชื่อผู้เข้าร่วม"
                 value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-primary">ค้นหา</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ตาราง -->
  <div class="card">
    <div class="card-body">

      <!-- หัวตาราง + pagination -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">
          หน้า <?= $page ?> จาก <?= $totalPages ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?training_date=<?= urlencode($date) ?>&period=<?= urlencode($period) ?>&q=<?= urlencode($q) ?>&page=<?= $p ?>">
                  <?= $p ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
        <?php endif; ?>
      </div>

      <!-- ตารางข้อมูล -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px">ลำดับ</th>
              <th style="width:120px">รหัส</th>
              <th style="width:120px">กลุ่มเรียน</th>
              <th>ชื่อ</th>
              <th>คณะ</th>
              <th>สาขา</th>
              <th>อีเมล</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $i = $offset + 1;
          while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['student_id']) ?></td>
              <td><?= htmlspecialchars($row['class_group']) ?></td>
              <td><?= htmlspecialchars($row['student_name']) ?></td>
              <td><?= htmlspecialchars($row['faculty']) ?></td>
              <td><?= htmlspecialchars($row['major']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
            </tr>
          <?php endwhile; ?>

          <?php if ($total == 0): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">ไม่พบข้อมูล</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
