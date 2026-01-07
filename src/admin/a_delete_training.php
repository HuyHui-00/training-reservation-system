<?php
session_start();
require_once __DIR__ . '/../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

$date = $_POST['training_date'] ?? '';

if ($date === '') {
    exit("ไม่พบวันที่");
}

$stmt = $conn->prepare("DELETE FROM trainings WHERE training_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();

header("Location: /admin/a_training_program.php");
exit;
