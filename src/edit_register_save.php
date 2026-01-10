<?php
session_start();
include 'db.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

$user_id = $_SESSION['user_id'];
$reg_id  = (int)$_POST['reg_id'];

// รับค่าที่แก้ไขได้
$student_id  = trim($_POST['student_id'] ?? '');
$faculty     = trim($_POST['faculty'] ?? '');
$major       = trim($_POST['major'] ?? '');
$class_group = trim($_POST['class_group'] ?? '');

if ($reg_id <= 0 || $student_id === '' || $faculty === '' || $major === '' || $class_group === '') {
    exit("กรุณากรอกข้อมูลให้ครบถ้วน");
}

if (strlen($student_id) > 13) {
    exit("รหัสนักศึกษาต้องไม่เกิน 13 ตัวอักษร");
}

// ตรวจสอบความเป็นเจ้าของและวันที่ (ป้องกันการแก้ไขย้อนหลัง)
$check = $conn->prepare("
    SELECT r.id, t.training_date 
    FROM registrations r
    JOIN trainings t ON r.training_id = t.id
    WHERE r.id = ? AND r.user_id = ?
");
$check->bind_param("ii", $reg_id, $user_id);
$check->execute();
$res = $check->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    exit("ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์แก้ไข");
}

$today = date('Y-m-d');
if ($row['training_date'] < $today) {
    exit("ไม่สามารถแก้ไขข้อมูลการอบรมที่เสร็จสิ้นแล้วได้");
}

// อัปเดตข้อมูล
$stmt = $conn->prepare("
    UPDATE registrations 
    SET student_id = ?, 
        faculty = ?, 
        major = ?, 
        class_group = ?
    WHERE id = ? AND user_id = ?
");

$stmt->bind_param("ssssii", $student_id, $faculty, $major, $class_group, $reg_id, $user_id);

if ($stmt->execute()) {
    header("Location: f_profile.php?msg=updated");
    exit;
} else {
    echo "เกิดข้อผิดพลาด: " . $conn->error;
}
