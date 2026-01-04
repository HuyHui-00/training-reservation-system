<?php
include 'db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // ตรวจสอบ email ซ้ำ
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = 'อีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password, role)
                 VALUES (?, ?, ?, 'User')"
            );
            $stmt->bind_param("sss", $username, $email, $hash);
            

            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register</title>

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

<div class="col-md-4 col-11">
    <div class="card shadow">
        <div class="card-body p-4">

            <h4 class="text-center fw-bold mb-4">สมัครสมาชิก</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">

                <!-- username -->
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <input type="text"
                           name="username"
                           class="form-control"
                           placeholder="Username"
                           required>
                </div>

                <!-- email -->
                <div class="mb-3">
                    <label class="form-label">อีเมล</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="example@email.com"
                           required>
                </div>

                <!-- password -->
                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder="Password"
                               required>
                        <button class="btn btn-outline-secondary"
                                type="button"
                                onclick="togglePassword()">
                            👁
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    สมัครสมาชิก
                </button>

            </form>

            <div class="text-center mt-3">
                <small>
                    มีบัญชีอยู่แล้ว?
                    <a href="user_login.php">เข้าสู่ระบบ</a>
                </small>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById("password");
    pwd.type = pwd.type === "password" ? "text" : "password";
}

<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'สมัครสมาชิกสำเร็จ',
    text: 'กำลังพาไปหน้าเข้าสู่ระบบ',
    timer: 1500,
    showConfirmButton: false
}).then(() => {
    window.location.href = 'user_login.php';
});
<?php endif; ?>
</script>

</body>
</html>
