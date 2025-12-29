<?php
session_start();
include 'connect.php';

/* ถ้า login แล้ว ไม่ให้เข้า login ซ้ำ */
if (isset($_SESSION['admin'])) {
    header("Location: a_training_program.php");
    exit;
}

$login_success = false;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        /* ✅ ตั้ง session admin (แก้จุด loop) */
        $_SESSION['admin'] = [
            'id' => $user['id'],
            'username' => $user['username']
        ];

        $login_success = true;
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>

<link rel="stylesheet" href="css/login.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<style>
/* Inline override to ensure actions buttons share row and equal width */
.login-box .actions { display:flex; gap:8px; margin-top:22px; }
.login-box .actions > input { display:block; flex:1 1 0% !important; width:auto !important; min-width:0; margin-top:0 !important; border:none !important; box-shadow:none !important; outline:none !important; -webkit-appearance:none !important; appearance:none !important; text-align:center; font-size:16px; }
.login-box .actions .btn-login { background:#2563eb; color:#fff; border-radius:999px; padding:12px; font-size:16px; }
.login-box .actions .btn-cancel { background:#dc2626; color:#fff; border-radius:999px; padding:12px; font-size:16px; }
</style>
</head>

<body>

<?php if ($login_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'เข้าสู่ระบบสำเร็จ',
    text: 'กำลังเข้าสู่ระบบผู้ดูแล...',
    timer: 1300,
    showConfirmButton: false,
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
}).then(() => {
    window.location.href = "a_training_program.php";
});
</script>
<?php else: ?>

<div class="login-box">
    <h2>เข้าสู่ระบบผู้ดูแล</h2>

    <form method="POST" id="loginForm">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" id="username" placeholder="กรอกชื่อผู้ใช้">

        <label>รหัสผ่าน</label>
        <input type="password" name="password" id="password" placeholder="กรอกรหัสผ่าน">

        <div class="actions">
            <input type="submit" value="เข้าสู่ระบบ" class="btn-login" style="flex:1">
            <input type="button" value="ยกเลิก" class="btn-cancel" onclick="window.location.href='f_training_program.php'" style="flex:1">
        </div>
    </form>
</div>

<?php if ($error): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'เข้าสู่ระบบไม่สำเร็จ',
    text: '<?= $error ?>',
    confirmButtonText: 'ลองใหม่อีกครั้ง'
});
</script>
<?php endif; ?>

<script>
const form = document.getElementById("loginForm");
const username = document.getElementById("username");
const password = document.getElementById("password");

form.addEventListener("submit", function(e) {
    e.preventDefault();

    if (username.value.trim() === "" || password.value.trim() === "") {
        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบ',
            text: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'
        });
        return;
    }

    Swal.fire({
        title: 'กำลังตรวจสอบข้อมูล',
        text: 'กรุณารอสักครู่...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    setTimeout(() => form.submit(), 600);
});
</script>

<?php endif; ?>

</body>
</html>
