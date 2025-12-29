<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

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

if ($role === 'student') {
    if ($student_id === '' || $student_name === '' || $email === '') {
        exit('กรุณากรอกข้อมูลนักศึกษาให้ครบถ้วน');
    }
} else {
    if ($student_name === '' || $email === '') {
        exit('กรุณากรอกข้อมูลอาจารย์ให้ครบถ้วน');
    }
}

/* ===== ดึงข้อมูลหลักสูตร ===== */
$stmt = $conn->prepare(
    "SELECT date, max_participants 
     FROM trainings 
     WHERE id = ?"
);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) {
    exit("ไม่พบหลักสูตร");
}

$date = $training['date'];
$MAX  = (int)$training['max_participants'];

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM registrations 
     WHERE training_id = ? AND period = ?"
);
$stmt->bind_param("is", $training_id, $period);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

if ($total >= $MAX) {
    exit("ช่วงเวลานี้เต็มแล้ว");
}

/* ===== ตรวจสอบการลงทะเบียนซ้ำ ===== */
if ($role === 'student') {
    $stmt = $conn->prepare(
        "SELECT id 
         FROM registrations 
         WHERE training_id = ? AND student_id = ? AND period = ?"
    );
    $stmt->bind_param("iss", $training_id, $student_id, $period);
} else {
    $stmt = $conn->prepare(
        "SELECT id 
         FROM registrations 
         WHERE training_id = ? AND email = ? AND period = ?"
    );
    $stmt->bind_param("iss", $training_id, $email, $period);
}

$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    exit("⚠ คุณลงทะเบียนช่วงนี้แล้ว");
}

/* ===== บันทึกข้อมูล ===== */
$created_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare(
    "INSERT INTO registrations
    (training_id, student_id, student_name, faculty, major, class_group, email, period, role, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "isssssssss",
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

$student_name_url = urlencode($student_name);
header("Location: f_register_form.php?success=1&name=$student_name_url&id=$training_id&period=$period&date=$date");
exit;
