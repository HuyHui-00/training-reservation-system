<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

$date = $_POST['date'] ?? '';

if ($date === '') {
    exit("ไม่พบวันที่");
}

// ลบข้อมูลตามวันที่
$stmt = $conn->prepare("DELETE FROM trainings WHERE date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();

// กลับหน้าเดิม
header("Location: /admin/a_training_program.php");
exit;
