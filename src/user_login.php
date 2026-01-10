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

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = "กรุณากรอกอีเมลและรหัสผ่าน";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                $login_success = true;

                // redirect ตาม role
                if ($user['role'] === 'Admin') {
                    $redirect_url = '/admin/a_training_program.php';
                } else {
                    $redirect_url = '/f_training_program.php';
                }

            } else {
                $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>เข้าสู่ระบบ</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #2563eb, #1e40af);
}
.card {
    border-radius: 12px;
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<?php if ($login_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'เข้าสู่ระบบสำเร็จ',
    text: 'กำลังเข้าสู่ระบบ...',
    timer: 1200,
    showConfirmButton: false
}).then(() => {
    window.location.replace("<?= $redirect_url ?>?v=mobile");
});
</script>
<?php endif; ?>

<div class="col-md-4 col-11">
    <div class="card shadow">
        <div class="card-body p-4">
            <h3 class="text-center fw-bold mb-4 text-primary">เข้าสู่ระบบ</h3>

            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email" autocomplete="email">
                </div>

                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>

            <div class="text-center mt-3">
                <small>ยังไม่มีบัญชี? <a href="/user_register.php">สมัครสมาชิก</a></small>
            </div>
        </div>
    </div>
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
const loginForm = document.getElementById("loginForm");
const email     = document.getElementById("email");
const password  = document.getElementById("password");

loginForm.addEventListener("submit", e => {
    if (!email.value.trim() || !password.value.trim()) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบ',
            text: 'กรุณากรอกอีเมลและรหัสผ่าน'
        });
    }
});
</script>

</body>
</html>
