<?php
include 'db.php';

$date   = $_GET['date'] ?? '';
$period = $_GET['period'] ?? '';

if (!$date || !$period) exit("ข้อมูลไม่ถูกต้อง");

$stmt = $conn->prepare("
    SELECT * FROM trainings 
    WHERE date=? AND period=? 
");
$stmt->bind_param("ss", $date, $period);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) exit("ไม่พบข้อมูลอบรม");

$stmt = $conn->prepare("
    SELECT * FROM registrations
    WHERE training_id=? AND period=?
    ORDER BY student_id ASC
");
$stmt->bind_param("is", $training["id"], $period);
$stmt->execute();
$registrations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายชื่อ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@media print {

    .no-print { display: none; }

    @page {
        size: A4 portrait;
        margin: 5mm; /* ขอบเล็กลงเพื่อให้ตารางขยายเต็ม */
    }

    body {
        margin: 0;
        padding: 0;
        zoom: 1; /* ไม่ซูม ลดปัญหาตารางเล็ก */
    }

    /* บังคับ container ให้กินความกว้างเต็ม A4 */
    .container {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* ตารางกว้างเต็มหน้ากระดาษ */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: fixed !important; /* กระจายคอลัมน์เต็มหน้า */
    }

    th, td {
        font-size: 15px;
        padding: 6px 8px;
        word-break: break-word;
        text-align: left;
    }

    thead th {
        font-size: 16px;
    }
    
    .page-break {
    page-break-before: always;
}
}
</style>


</head>

<body>

<div class="container">

  <h3 class="text-center mt-3">
    รายชื่อผู้ลงทะเบียนอบรม<br>
    <small><?= htmlspecialchars($training["title"]) ?></small>
  </h3>

  <h5 class="text-center mb-3">
    วันที่: <?= htmlspecialchars($training["date"]) ?> |
    <?= $period == "morning" ? "ช่วงเช้า" : "ช่วงบ่าย" ?>
  </h5>

  <table class="table table-bordered mt-3">
    <thead class="table-dark">
      <tr>
        <th style="width:60px;">ลำดับ</th>
        <th style="width:130px;">รหัสนักศึกษา</th>
        <th>ชื่อ - นามสกุล</th>
        <th>คณะ</th>
        <th>สาขา</th>
      </tr>
    </thead>
<tbody>
<?php 
$i = 1;
$perPage = 20; // <<< แบ่งหน้า 20 คน
$count = 0;

while ($row = $registrations->fetch_assoc()):
?>
    <tr>
        <td><?= $i ?></td>
        <td><?= htmlspecialchars($row['student_id']) ?></td>
        <td><?= htmlspecialchars($row['student_name']) ?></td>
        <td><?= htmlspecialchars($row['faculty']) ?></td>
        <td><?= htmlspecialchars($row['major']) ?></td>
    </tr>

<?php
    $i++;
    $count++;

    // ✔️ เมื่อครบ 20 คน → สร้างหน้าใหม่
    if ($count % $perPage === 0) {

        echo "</tbody></table>";
        echo "<div class='page-break'></div>";

        // ✔️ แสดงหัวตารางใหม่ในหน้าใหม่
        echo "
        <table class='table table-bordered mt-3'>
            <thead class='table-dark'>
                <tr>
                    <th style='width:70px;'>ลำดับ</th>
                    <th style='width:120px;'>รหัสนักศึกษา</th>
                    <th>ชื่อ - นามสกุล</th>
                    <th>คณะ</th>
                    <th>สาขา</th>
                </tr>
            </thead>
            <tbody>
        ";
    }
endwhile;
?>
</tbody>


  </table>

  <div class="text-center no-print mt-3">
    <button onclick="window.print()" class="btn btn-primary">พิมพ์</button>
    <a href="am_detail.php?date=<?= urlencode($date) ?>" class="btn btn-secondary">กลับ</a>
  </div>

</div>

<script>
  window.onload = () => {
    setTimeout(() => { window.print(); }, 500);
  };
</script>

</body>
</html>