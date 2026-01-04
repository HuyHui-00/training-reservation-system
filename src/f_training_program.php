<?php
include 'db.php';
require_once __DIR__ . '/components/user_guard.php';

/* ===== pagination ===== */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

/* ===== Search inputs ===== */
$keyword = trim($_GET['keyword'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = [];
$types = '';
$params = [];

if ($keyword !== '') {
  $where[] = "(title LIKE ? OR speaker LIKE ?)";
  $kw = "%{$keyword}%";
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

/* ===== นับจำนวนวันอบรมทั้งหมด ===== */
$sqlCount = "SELECT COUNT(DISTINCT date) AS total FROM trainings";
if ($where) {
  $sqlCount .= " WHERE " . implode(' AND ', $where);
}

$stmt = $conn->prepare($sqlCount);
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

/* ===== ดึงข้อมูลอบรม (จำกัด 10) ===== */
$sql = "
  SELECT 
    date,
    MAX(CASE WHEN period='morning' THEN title END) AS morning_title,
    MAX(CASE WHEN period='afternoon' THEN title END) AS afternoon_title
  FROM trainings
";

if ($where) {
  $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= "
  GROUP BY date
  ORDER BY date ASC
  LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
if ($params) {
  $stmt->bind_param($types, ...$params);
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
function shortText($text, $limit = 30) {
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit, 'UTF-8') . '...';
}

// ฟังก์ชันช่วย: คืนข้อมูล training ตามวันที่และช่วง
function getTraining($conn, $date, $period) {
  $stmt = $conn->prepare("SELECT * FROM trainings WHERE date=? AND period=? LIMIT 1");
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

$today = date('Y-m-d');
$now = time();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1"> 
<title>ระบบลงทะเบียนอบรม</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@media (max-width: 576px) {
    table {
        display: none;
    }
}

@media (max-width: 576px) {
    .mobile-card {
        display: block;
        background: white;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .mobile-card-title {
        font-weight: bold;
        font-size: 16px;
        color: #0d6efd;
    }
    .mobile-card small {
        color: #555;
    }
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
      <div class="ms-auto">
        <a href="/logout.php"
           id="btnLogout"
           class="btn btn-outline-light btn-sm">
           ออกจากระบบ
        </a>
      </div>

    </div>

  </div>
</nav>


<div class="container mt-4">

  <div class="card shadow-sm">
    
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color:white;">

      <div class="fw-bold fs-5">
        ตารางกำหนดการอบรม
      </div>

    </div>

    <div class="card-body">

      <!-- Search form -->
      <form class="row g-2 mb-3" method="GET" action="f_training_program.php">
        <div class="col-md-6">
          <input type="text" name="keyword" class="form-control" placeholder="ค้นหาจากชื่อวิทยากร หรือ ชื่อหลักสูตร"
                 value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <div class="col-md-3">
          <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3 d-grid">
          <div class="d-flex gap-2">
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            <button class="btn btn-primary" type="submit">ค้นหา</button>
          </div>
        </div>
        <div class="col-12">
          <a href="f_training_program.php" class="btn btn-link btn-sm">รีเซ็ต</a>
        </div>
      </form>
      <nav class="mt-3">
        <ul class="pagination justify-content-center">

          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
              <a class="page-link"
                 href="?page=<?= $p ?>
                 &keyword=<?= urlencode($keyword) ?>
                 &start_date=<?= urlencode($start_date) ?>
                 &end_date=<?= urlencode($end_date) ?>">
                <?= $p ?>
              </a>
            </li>
          <?php endfor; ?>
          
        </ul>
      </nav>
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">

          <thead style="background:#1f2937;color:white;">
            <tr>
              <th style="min-width:60px;">ลำดับ</th>
              <th style="min-width:120px;">วันที่</th>
              <th style="min-width:150px;">ช่วงเช้า</th>
              <th style="min-width:150px;">ช่วงบ่าย</th>
              <th style="min-width:105px;">Booking</th>
            </tr>
          </thead>

          <tbody>

<?php
$index = 1;
while ($row = $result->fetch_assoc()):
    $date      = $row['date'];
    $morning   = $row['morning_title'];
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
            echo "<span class='badge bg-success'>ว่าง</span>";
          }
        } else {
          $mCount = countRegister($conn, $mRow['id'], 'morning');
              $end_ts = strtotime($mRow['date'] . ' 12:00:00');
              if ($now > $end_ts) {
                $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
              } elseif ($mCount >= $mRow['max_participants']) {
                $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
              } else {
                $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
              }
          echo htmlspecialchars(shortText($mRow['title'], 35)) . ' ' . $status;
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
            echo "<span class='badge bg-success'>ว่าง</span>";
          }
        } else {
          $aCount = countRegister($conn, $aRow['id'], 'afternoon');
              $end_ts = strtotime($aRow['date'] . ' 17:00:00');
              if ($now > $end_ts) {
                $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
              } elseif ($aCount >= $aRow['max_participants']) {
                $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
              } else {
                $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
              }
          echo htmlspecialchars(shortText($aRow['title'], 35)) . ' ' . $status;
      }
    ?>
  </td>

  <td>
    <a href="f_program_detail.php?date=<?= $date ?>" 
       class="btn btn-primary btn-sm shadow-sm"
       title="ดูรายละเอียด">
       รายละเอียด
    </a>
  </td>
</tr>
<?php endwhile; ?>

          </tbody>
        </table>
      </div>
      <div class="d-sm-none mt-3">

<?php
$index = 1;
$result->data_seek(0); 
while ($row = $result->fetch_assoc()):
?>
    <div class="mobile-card">
        <div class="mobile-card-title">
            <?= $index++ ?>) <?= thaiDate($row['date']) ?>
        </div>

        <div class="mt-1">
          <small>ช่วงเช้า:</small><br>
          <?php
$mRow = getTraining($conn, $row['date'], 'morning');

if (empty($mRow)) {
    $other = getTraining($conn, $row['date'], 'afternoon');
    if (!empty($other)) {
        echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
    } else {
        echo "<span class='badge bg-success'>ว่าง</span>";
    }
} else {
    $mCount = countRegister($conn, $mRow['id'], 'morning');
    $end_ts = strtotime($mRow['date'] . ' 12:00:00');

    if ($now > $end_ts) {
        $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
    } elseif ($mCount >= $mRow['max_participants']) {
        $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
    } else {
        $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
    }

    echo htmlspecialchars(shortText($mRow['title'], 30)) . ' ' . $status;
}
?>

        </div>

        <div class="mt-1">
          <small>ช่วงบ่าย:</small><br>
          <?php
$aRow = getTraining($conn, $row['date'], 'afternoon');

if (empty($aRow)) {
    $other = getTraining($conn, $row['date'], 'morning');
    if (!empty($other)) {
        echo "<span class='badge bg-secondary'>ไม่มีการอบรม</span>";
    } else {
        echo "<span class='badge bg-success'>ว่าง</span>";
    }
} else {
    $aCount = countRegister($conn, $aRow['id'], 'afternoon');
    $end_ts = strtotime($aRow['date'] . ' 17:00:00');

    if ($now > $end_ts) {
        $status = "<span class='badge bg-info text-white ms-2'>อบรมสำเร็จ</span>";
    } elseif ($aCount >= $aRow['max_participants']) {
        $status = "<span class='badge bg-danger ms-2'>เต็ม</span>";
    } else {
        $status = "<span class='badge bg-success ms-2'>ว่าง</span>";
    }

    echo htmlspecialchars(shortText($aRow['title'], 30)) . ' ' . $status;
}
?>

        </div>

        <div class="mt-2">
            <a href="f_program_detail.php?date=<?= $row['date'] ?>" 
               class="btn btn-primary btn-sm w-100">
                ดูรายละเอียด
            </a>
        </div>
    </div>
<?php endwhile; ?>

      </div>

    </div>
  </div>
</div>
<?php if ($totalPages > 1): ?>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  const logoutBtn = document.getElementById("btnLogout");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault(); // ❌ ไม่ให้ redirect ทันที

      Swal.fire({
        title: 'ยืนยันการออกจากระบบ',
        text: 'คุณต้องการออกจากระบบใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ออกจากระบบ',
        cancelButtonText: 'ยกเลิก'
      }).then((result) => {
        if (result.isConfirmed) {
          // เก็บสถานะไว้โชว์ alert หลัง logout
          localStorage.setItem("logout_success", "1");
          window.location.href = "/logout.php";
        }
      });
    });
  }

});
</script>


</body>
</html> 