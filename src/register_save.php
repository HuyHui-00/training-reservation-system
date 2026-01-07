<?php
session_start();
include 'db.php';

/* ===== ตรวจสอบการ login ===== */
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id <= 0) {
    exit("กรุณาเข้าสู่ระบบ");
}

/* ===== ตรวจสอบ method ===== */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

/* ===== รับค่า ===== */
$training_id  = (int)$_POST['training_id'];
$period       = $_POST['period'] ?? '';
$student_id   = trim($_POST['student_id'] ?? '');
$student_name = trim($_POST['student_name'] ?? '');
$faculty      = trim($_POST['faculty'] ?? '');
$major        = trim($_POST['major'] ?? '');
$class_group  = trim($_POST['class_group'] ?? '');
$email        = trim($_POST['email'] ?? '');
$role         = strtolower(trim($_POST['role'] ?? 'student'));

if (!in_array($role, ['student', 'teacher'])) {
    $role = 'student';
}

/* ===== ตรวจสอบข้อมูล ===== */
if ($role === 'student') {
    if ($student_id === '' || $student_name === '' || $email === '') {
        exit('กรุณากรอกข้อมูลนักศึกษาให้ครบถ้วน');
    }
    if (strlen($student_id) > 13) {
    $error = 'รหัสนักศึกษาต้องไม่เกิน 13 ตัวอักษร';
    // redirect กลับ form พร้อม error
    $student_name_url = urlencode($student_name);
    header("Location: f_register_form.php?error=" . urlencode($error) . "&name=$student_name_url&id=$training_id&period=$period&date=$date");
    exit;
    }
} else {
    if ($student_name === '' || $email === '') {
        exit('กรุณากรอกข้อมูลอาจารย์ให้ครบถ้วน');
    }
}

/* ===== ดึงข้อมูลหลักสูตร ===== */
$stmt = $conn->prepare("
    SELECT training_date, max_participants
    FROM trainings
    WHERE id = ?
");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) {
    exit("ไม่พบหลักสูตร");
}

$date = $training['training_date'];
$MAX  = (int)$training['max_participants'];

/* ===== ตรวจสอบจำนวนที่นั่ง ===== */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registrations
    WHERE training_id = ? AND period = ?
");
$stmt->bind_param("is", $training_id, $period);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

if ($total >= $MAX) {
    exit("ช่วงเวลานี้เต็มแล้ว");
}

/* ===== ตรวจสอบการลงทะเบียนซ้ำ (แก้แล้ว) ===== */
$stmt = $conn->prepare("
    SELECT r.id
    FROM registrations r
    JOIN trainings t ON t.id = r.training_id
    WHERE r.user_id = ?
      AND r.training_id = ?
      AND r.period = ?
      AND t.training_date = ?
    LIMIT 1
");
$stmt->bind_param("iiss", $user_id, $training_id, $period, $date);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    exit("⚠ คุณได้ลงทะเบียนอบรมนี้แล้ว");
}

/* ===== บันทึกข้อมูล ===== */
$created_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO registrations
    (user_id, training_id, student_id, student_name, faculty, major, class_group, email, period, role, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iisssssssss",
    $user_id,
    $training_id,
    $student_id,
    $student_name,
    $faculty,
    $major,
    $class_group,
    $email,
    $period,
    $role,
    $created_at
);

$stmt->execute();

/* ===== redirect กลับ ===== */
$student_name_url = urlencode($student_name);
header("Location: f_register_form.php?success=1&name=$student_name_url&id=$training_id&period=$period&date=$date");
exit;
