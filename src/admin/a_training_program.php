<?php
require_once __DIR__ . '/../components/admin_guard.php';
require_once __DIR__ . '/../db.php';

function limitText($text, $limit = 40) {
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit, 'UTF-8') . '...';
}

// --- Search / filter inputs ---
$keyword = trim($_GET['keyword'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build dynamic WHERE clause and params for prepared statement
$where = [];
$types = '';
$params = [];

if ($keyword !== '') {
  $where[] = "(title LIKE ? OR speaker LIKE ?)";
  $kw = "%" . $keyword . "%";
  $types .= 'ss';
  $params[] = $kw;
  $params[] = $kw;
}

if ($start_date !== '' && $end_date !== '') {
  $where[] = "date BETWEEN ? AND ?";
  $types .= 'ss';
  $params[] = $start_date;
  $params[] = $end_date;
} elseif ($start_date !== '') {
  $where[] = "date >= ?";
  $types .= 's';
  $params[] = $start_date;
} elseif ($end_date !== '') {
  $where[] = "date <= ?";
  $types .= 's';
  $params[] = $end_date;
}

$sql = "
  SELECT 
    training_date,
    MAX(CASE WHEN period = 'morning' THEN title END) AS morning_title,
    MAX(CASE WHEN period = 'afternoon' THEN title END) AS afternoon_title
  FROM trainings
";

if (!empty($where)) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= "\n    GROUP BY training_date\n    ORDER BY training_date ASC\n";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
  die('Prepare failed: ' . $conn->error);
}

if (!empty($params)) {
  $bind_names = [];
  $bind_names[] = &$types;
  for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = &$params[$i];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$result = $stmt->get_result();

function thaiDate($date) {
    $months = [
        1=>"ม.ค.",2=>"ก.พ.",3=>"มี.ค.",4=>"เม.ย.",
        5=>"พ.ค.",6=>"มิ.ย.",7=>"ก.ค.",8=>"ส.ค.",
        9=>"ก.ย.",10=>"ต.ค.",11=>"พ.ย.",12=>"ธ.ค."
    ];
    $time = strtotime($date);
    return date("d", $time)." ".$months[(int)date("m",$time)]." ".date("Y",$time);
}
// helper: ดึง training โดย date+period
function getTraining($conn, $date, $period) {
  $stmt = $conn->prepare("SELECT * FROM trainings WHERE training_date=? AND period=? LIMIT 1");
  $stmt->bind_param("ss", $date, $period);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

function countRegister($conn, $training_id, $period) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE training_id=? AND period=?");
  $stmt->bind_param("is", $training_id, $period);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc()['total'];
}

$now = time();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ระบบลงทะเบียนอบรม</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@media (max-width: 768px) {
  .desktop-table { display: none; }
  .mobile-cards { display: block; }
}
@media (min-width: 769px) {
  .mobile-cards { display: none; }
}
</style>
</head>

<body class="bg-light">
<?php include __DIR__ . '/../components/sidebar_admin.php'; ?>

<?php if (isset($_GET['saved'])): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'เพิ่มข้อมูลสำเร็จ',
  text: 'บันทึกหลักสูตรอบรมเรียบร้อยแล้ว',
  showConfirmButton: false,
  timer: 1800
});
</script>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">

    <!-- ปุ่มเปิด Sidebar -->
    <button class="btn btn-outline-light me-2" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#adminSidebar" 
            aria-controls="adminSidebar">
      ☰ เมนู
    </button>

    <span class="navbar-brand fw-bold fs-4">ตารางหลักสูตรอบรม</span>
    <div class="d-flex align-items-center gap-2">
      <span class="text-white small d-none d-md-block">Admin Panel</span>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">
  <div class="container">

    <div class="d-flex justify-content-end mb-3">
      <a href="/admin/a_add_training.php" class="btn btn-success rounded-pill px-4 shadow-sm">
        เพิ่มหลักสูตรอบรม
      </a>
    </div>

    <div class="card shadow-sm">
      <div class="card-header fw-bold fs-5 text-white"
           style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
        ตารางกำหนดการอบรม
      </div>

      <div class="card-body">

        <!-- Search form -->
        <form class="row g-2 mb-3" method="GET" action="/admin/a_training_program.php">
          <div class="col-md-5">
            <input type="text" name="keyword" class="form-control" placeholder="ค้นหาจากชื่อวิทยากร หรือ ชื่อหลักสูตร"
                   value="<?= htmlspecialchars($keyword) ?>">
          </div>
          <div class="col-md-3">
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
          </div>
          <div class="col-md-3">
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
          </div>
          <div class="col-md-1 d-grid">
            <button class="btn btn-primary" type="submit">ค้นหา</button>
          </div>
          <div class="col-12">
            <small class="text-muted">ใส่คำค้นหรือเลือกช่วงวันที่ แล้วกด 'ค้นหา' เพื่อกรองรายการ</small>
            <a href="/admin/a_training_program.php" class="btn btn-link btn-sm">รีเซ็ต</a>
          </div>
        </form>

        <!-- ตาราง Desktop -->
        <div class="table-responsive desktop-table">
          <table class="table table-bordered table-striped align-middle text-center">
            <thead style="background:#1f2937;color:white;">
              <tr>
                <th style="width:60px;">ลำดับ</th>
                <th style="width:180px;">วันที่</th>
                <th>ช่วงเช้า</th>
                <th>ช่วงบ่าย</th>
                <th style="width:140px;">จัดการ</th>
              </tr>
            </thead>
            <tbody>
<?php
$index = 1;
while ($row = $result->fetch_assoc()):
$date = $row['training_date'];
$morning = $row['morning_title'];
$afternoon = $row['afternoon_title'];
?>
<tr>
  <td><?= $index++ ?></td>
  <td><?= thaiDate($date) ?></td>
    <td>
    <?php
      $mRow = getTraining($conn, $date, 'morning');
      if (empty($mRow)) {
        $other = getTraining($conn, $date, 'afternoon');
        if (!empty($other)) {
          echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
        } else {
          echo "<span class='badge bg-secondary'>ว่าง</span>";
        }
      } else {
        $mCount = countRegister($conn, $mRow['id'], 'morning');
        $end_ts = strtotime($mRow['training_date'] . ' 12:00:00');
        if ($now > $end_ts) {
          $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
        } elseif ($mCount >= $mRow['max_participants']) {
          $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
        } else {
          $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
        }
        echo htmlspecialchars(limitText($mRow['title'], 40)) . $status;
      }
    ?>
    </td>
    <td>
    <?php
      $aRow = getTraining($conn, $date, 'afternoon');
      if (empty($aRow)) {
        $other = getTraining($conn, $date, 'morning');
        if (!empty($other)) {
          echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
        } else {
          echo "<span class='badge bg-secondary'>ว่าง</span>";
        }
      } else {
        $aCount = countRegister($conn, $aRow['id'], 'afternoon');
        $end_ts = strtotime($aRow['training_date'] . ' 17:00:00');
        if ($now > $end_ts) {
          $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
        } elseif ($aCount >= $aRow['max_participants']) {
          $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
        } else {
          $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
        }
        echo htmlspecialchars(limitText($aRow['title'], 40)) . $status;
      }
    ?>
    </td>
  <td>
    <div class="d-flex justify-content-center gap-2">
      <a href="/admin/a_program_detail.php?training_date=<?= $date ?>" class="btn btn-primary btn-sm">📌</a>
      <form method="POST" action="am_delete_training.php">
        <input type="hidden" name="date" value="<?= $date ?>">
        <button type="submit" class="btn btn-danger btn-sm delete-btn">🗑️</button>
      </form>
    </div>
  </td>
</tr>
<?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <!-- การ์ด Mobile -->
        <div class="mobile-cards">
<?php
$result->data_seek(0);
while ($row = $result->fetch_assoc()):
$date = $row['training_date'];
$morning = $row['morning_title'];
$afternoon = $row['afternoon_title'];
?>
          <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold text-primary">
              <?= thaiDate($date) ?>
            </div>
            <div class="card-body">
                <div class="mb-2">
                <strong>ช่วงเช้า</strong><br>
                <?php
                  $mRow = getTraining($conn, $date, 'morning');
                  if (empty($mRow)) {
                    $other = getTraining($conn, $date, 'afternoon');
                    if (!empty($other)) {
                      echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
                    } else {
                      echo "<span class='badge bg-secondary'>ว่าง</span>";
                    }
                  } else {
                    $mCount = countRegister($conn, $mRow['id'], 'morning');
                    $end_ts = strtotime($mRow['training_date'] . ' 12:00:00');
                    if ($now > $end_ts) {
                      $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
                    } elseif ($mCount >= $mRow['max_participants']) {
                      $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
                    } else {
                      $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
                    }
                    echo htmlspecialchars($mRow['title']) . ' ' . $status;
                  }
                ?>
                </div>
                <div class="mb-3">
                <strong>ช่วงบ่าย</strong><br>
                <?php
                  $aRow = getTraining($conn, $date, 'afternoon');
                  if (empty($aRow)) {
                    $other = getTraining($conn, $date, 'morning');
                    if (!empty($other)) {
                      echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
                    } else {
                      echo "<span class='badge bg-secondary'>ว่าง</span>";
                    }
                  } else {
                    $aCount = countRegister($conn, $aRow['id'], 'afternoon');
                    $end_ts = strtotime($aRow['training_date'] . ' 17:00:00');
                    if ($now > $end_ts) {
                      $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
                    } elseif ($aCount >= $aRow['max_participants']) {
                      $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
                    } else {
                      $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
                    }
                    echo htmlspecialchars($aRow['title']) . ' ' . $status;
                  }
                ?>
                </div>
              <div class="d-flex gap-2">
                <a href="/admin/a_program_detail.php?training_date=<?= $date ?>" class="btn btn-primary btn-sm flex-fill">
                  ดูรายละเอียด
                </a>
                <form method="POST" action="a_delete_training.php" class="flex-fill">
                  <input type="hidden" name="date" value="<?= $date ?>">
                  <button type="submit" class="btn btn-danger btn-sm w-100 delete-btn">
                    ลบ
                  </button>
                </form>
              </div>
            </div>
          </div>
<?php endwhile; ?>
        </div>

      </div>
    </div>

  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const form = this.closest("form");
      Swal.fire({
        title: 'ยืนยันการลบข้อมูล?',
        text: 'ระบบจะลบหลักสูตรอบรมของวันนี้ทั้งหมด และไม่สามารถกู้คืนได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
        allowOutsideClick: false
      }).then(result => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'กำลังลบข้อมูล...',
            text: 'โปรดรอสักครู่',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });
          setTimeout(() => form.submit(), 800);
        }
      });
    });
  });
});
</script>

<?php
if (!empty($_SESSION['saved'])) {
    echo "
    <script>
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มข้อมูลสำเร็จ',
        text: 'บันทึกหลักสูตรอบรมเรียบร้อยแล้ว',
        showConfirmButton: false,
        timer: 1500
      });
    </script>
    ";
    unset($_SESSION['saved']);
}
?>


</body>
</html>