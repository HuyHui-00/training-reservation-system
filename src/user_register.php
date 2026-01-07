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
        // ตรวจสอบความปลอดภัยรหัสผ่าน
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $length    = strlen($password) >= 8;

        if (!$uppercase || !$lowercase || !$number || !$length) {
            $error = 'รหัสผ่านไม่ตรงตามเงื่อนไขความปลอดภัย';
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
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #2563eb, #1e40af);
}
.card {
    border-radius: 12px;
}
.password-requirements li {
    list-style: none;
    font-size: 0.9rem;
    margin: 3px 0;
}
.password-requirements li.valid {
    color: green;
}
.password-requirements li.invalid {
    color: red;
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

            <form method="post" id="registerForm">

                <!-- username -->
                <div class="mb-3">
                    <label class="form-label">ชื่อ-นามสกุล</label>
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
                            <i id="toggleIcon" class="bi bi-eye"></i>
                        </button>
                    </div>
                    <ul class="password-requirements mt-1">
                        <li id="reqLength" class="invalid">อย่างน้อย 8 ตัวอักษร</li>
                        <li id="reqUpper" class="invalid">ตัวพิมพ์ใหญ่ อย่างน้อย 1 ตัว</li>
                        <li id="reqLower" class="invalid">ตัวพิมพ์เล็ก อย่างน้อย 1 ตัว</li>
                        <li id="reqNumber" class="invalid">ตัวเลข อย่างน้อย 1 ตัว</li>
                    </ul>
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
// ===== ฟังก์ชัน toggle password =====
function togglePassword() {
    const pwd = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");
    
    if (pwd.type === "password") {
        pwd.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        pwd.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}

// ===== ตรวจสอบรหัสผ่านฝั่ง client แบบ realtime =====
const passwordInput = document.getElementById('password');
const reqLength = document.getElementById('reqLength');
const reqUpper  = document.getElementById('reqUpper');
const reqLower  = document.getElementById('reqLower');
const reqNumber = document.getElementById('reqNumber');

passwordInput.addEventListener('input', () => {
    const pwd = passwordInput.value;

    reqLength.className = pwd.length >= 8 ? 'valid' : 'invalid';
    reqUpper.className  = /[A-Z]/.test(pwd) ? 'valid' : 'invalid';
    reqLower.className  = /[a-z]/.test(pwd) ? 'valid' : 'invalid';
    reqNumber.className = /[0-9]/.test(pwd) ? 'valid' : 'invalid';
});

// ===== ตรวจสอบรหัสผ่านก่อน submit =====
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pwd = passwordInput.value;
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
    if (!re.test(pwd)) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'รหัสผ่านไม่ปลอดภัย',
            html: 'โปรดตรวจสอบเงื่อนไขรหัสผ่านด้านล่างให้ครบถ้วน'
        });
    }
});

// ===== SweetAlert2 เมื่อสมัครสมาชิกสำเร็จ =====
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
