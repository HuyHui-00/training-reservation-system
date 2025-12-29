<?php
include 'db.php';

$date = $_GET['date'] ?? '';
$period = $_GET['period'] ?? '';

if (!$date) exit("ไม่พบข้อมูล");

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=training_" . $date . "_" . $period . ".xls");

echo "<meta charset='UTF-8'>";
echo "<table border='1'>
<tr>
  <th>ลำดับ</th>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ - นามสกุล</th>
  <th>คณะ</th>
  <th>สาขา</th>
  <th>กลุ่มเรียน</th>
  <th>Email</th>
</tr>";

if ($period) {
    $sql = "
    SELECT r.*
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE t.date = ? AND t.period = ?
    ORDER BY r.student_id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $date, $period);
} else {
    $sql = "
    SELECT r.*
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE t.date = ?
    ORDER BY r.student_id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
}

$stmt->execute();
$result = $stmt->get_result();

$i = 1;
while ($row = $result->fetch_assoc()) {
    echo "
    <tr>
      <td>" . $i++ . "</td>
      <td>{$row['student_id']}</td>
      <td>{$row['student_name']}</td>
      <td>{$row['faculty']}</td>
      <td>{$row['major']}</td>
      <td>{$row['class_group']}</td>
      <td>{$row['email']}</td>
    </tr>";
}

echo "</table>";
