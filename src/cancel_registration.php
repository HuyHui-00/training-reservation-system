<?php
require_once __DIR__ . '/components/user_guard.php';
require_once __DIR__ . '/db.php';

$user_id = $_SESSION['user_id'];
$reg_id  = (int)($_GET['reg_id'] ?? 0);

if ($reg_id <= 0) {
    exit("ข้อมูลไม่ถูกต้อง");
}

/* ตรวจสอบว่าการลงทะเบียนนี้เป็นของ user คนนี้จริง
   และอบรมยังไม่ผ่านวัน */
$sql = "
    SELECT r.id, t.training_date
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE r.id = ? AND r.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reg_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    exit("ไม่พบข้อมูลการลงทะเบียน");
}

/* ถ้าอบรมผ่านมาแล้ว ห้ามยกเลิก */
$today = date('Y-m-d');
if ($data['training_date'] < $today) {
    exit("ไม่สามารถยกเลิกการอบรมที่เสร็จสิ้นแล้วได้");
}

/* ลบการลงทะเบียน */
$delete = $conn->prepare("DELETE FROM registrations WHERE id = ? AND user_id = ?");
$delete->bind_param("ii", $reg_id, $user_id);
$delete->execute();

/* กลับไปหน้าโปรไฟล์ พร้อมแจ้งสถานะ */
header("Location: f_history.php?msg=cancelled");
exit;
