<?php
session_start();
require_once __DIR__ . '/connect.php';

/* ถ้า login แล้ว ให้เด้งตาม role */
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: /a_training_program.php");
        exit;
    } elseif ($_SESSION['role'] === 'User') {
        header("Location: /f_training_program.php");
        exit;
    }
}

$error = null;
$login_success = false;
$redirect_url = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                $login_success = true;

                // กำหนดหน้า redirect ตาม role
                if ($user['role'] === 'Admin') {
                    $redirect_url = '/admin/a_training_program.php';
                } else {
                    $redirect_url = '/f_training_program.php';
                }

            } else {
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เข้าสู่ระบบ</title>

<link rel="stylesheet" href="/css/login.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<?php if ($login_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'เข้าสู่ระบบสำเร็จ',
    text: 'กำลังเข้าสู่ระบบ...',
    timer: 1200,
    showConfirmButton: false
}).then(() => {
    window.location.href = "<?= $redirect_url ?>";
});
</script>
<?php endif; ?>

<div class="login-box">
    <h2>เข้าสู่ระบบ</h2>

    <form method="POST" id="loginForm">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" id="username">

        <label>รหัสผ่าน</label>
        <input type="password" name="password" id="password">

        <input type="submit" value="เข้าสู่ระบบ">
    </form>

    <p class="text-center">
        ยังไม่มีบัญชี?
        <a href="/user_register.php">สมัครสมาชิก</a>
    </p>
</div>

<?php if ($error): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'เข้าสู่ระบบไม่สำเร็จ',
    text: '<?= htmlspecialchars($error) ?>'
});
</script>
<?php endif; ?>

<script>
loginForm.addEventListener("submit", e => {
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

</body>
</html>
