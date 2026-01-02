<?php
session_start();
require_once __DIR__ . '/../connect.php';

/* ถ้า login แล้ว ไม่ให้เข้า login ซ้ำ */
if (isset($_SESSION['admin'])) {
    header("Location: /admin/a_training_program.php");
    exit;
}

$login_success = false;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare(
        "SELECT id, username FROM users
         WHERE username=? AND password=?
         LIMIT 1"
    );
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

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

<link rel="stylesheet" href="/css/login.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.login-box .actions { display:flex; gap:8px; margin-top:22px; }
.login-box .actions > input { flex:1; border:none; font-size:16px; }
.login-box .actions .btn-login { background:#2563eb; color:#fff; border-radius:999px; padding:12px; }
.login-box .actions .btn-cancel { background:#dc2626; color:#fff; border-radius:999px; padding:12px; }
</style>
</head>

<body>

<?php if ($login_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'เข้าสู่ระบบสำเร็จ',
    text: 'กำลังเข้าสู่ระบบผู้ดูแล...',
    timer: 1200,
    showConfirmButton: false,
    allowOutsideClick: false
}).then(() => {
    window.location.href = "/admin/a_training_program.php";
});
</script>
<?php else: ?>

<div class="login-box">
    <h2>เข้าสู่ระบบผู้ดูแล</h2>

    <form method="POST" id="loginForm">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" id="username">

        <label>รหัสผ่าน</label>
        <input type="password" name="password" id="password">

        <div class="actions">
            <input type="submit" value="เข้าสู่ระบบ" class="btn-login">
            <input type="button" value="ยกเลิก" class="btn-cancel"
                   onclick="window.location.href='/f_training_program.php'">
        </div>
    </form>
</div>

<?php if ($error): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'เข้าสู่ระบบไม่สำเร็จ',
    text: '<?= $error ?>'
});
</script>
<?php endif; ?>

<script>
document.getElementById("loginForm").addEventListener("submit", e => {
    if (!username.value.trim() || !password.value.trim()) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบ',
            text: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'
        });
    }
});
</script>

<?php endif; ?>
</body>
</html>
