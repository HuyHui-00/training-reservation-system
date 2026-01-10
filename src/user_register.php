<?php
include 'db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prefix   = $_POST['prefix'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $student_id  = trim($_POST['student_id'] ?? '');
    $faculty     = trim($_POST['faculty'] ?? '');
    $major       = trim($_POST['major'] ?? '');
    $class_group = trim($_POST['class_group'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($prefix === '') {
        $error = 'กรุณาเลือกคำนำหน้า';
    } elseif ($username === '' || $email === '' || $student_id === '' || 
              $faculty === '' || $major === '' || $class_group === '' || 
              $password === '' || $confirm === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password !== $confirm) {
        $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    } else {

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $length    = strlen($password) >= 8;

        if (!$uppercase || !$lowercase || !$number || !$length) {
            $error = 'รหัสผ่านไม่ตรงตามเงื่อนไขความปลอดภัย';
        } else {

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $check = $stmt->get_result();

            if ($check->num_rows > 0) {
                $error = 'อีเมลนี้ถูกใช้งานแล้ว';
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO users (prefix, username, email, password, role, student_id, faculty, major, class_group)
                     VALUES (?, ?, ?, ?, 'Student', ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssssssss", $prefix, $username, $email, $hash, $student_id, $faculty, $major, $class_group);

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
.card { border-radius: 12px; }
.password-requirements li {
    list-style: none;
    font-size: 0.9rem;
}
.valid { color: green; }
.invalid { color: red; }
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

<div class="mb-2">
<label class="form-label">คำนำหน้า</label>
<select name="prefix" id="prefix" class="form-select w-auto" required>
<option value="">เลือกคำนำหน้า</option>
<option value="นาย">นาย</option>
<option value="นางสาว">นางสาว</option>
</select>
</div>

<div class="mb-3">
<label class="form-label">ชื่อ-นามสกุล</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">รหัสนักศึกษา</label>
<input type="text" name="student_id" class="form-control" required inputmode="numeric" pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
</div>

<div class="mb-3">
<label class="form-label">คณะ</label>
<input type="text" name="faculty" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">สาขาวิชา</label>
<input type="text" name="major" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">กลุ่มเรียน</label>
<input type="text" name="class_group" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">อีเมล</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">รหัสผ่าน</label>
<input type="password" name="password" id="password" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">ยืนยันรหัสผ่าน</label>
<input type="password" name="confirm_password" id="confirm" class="form-control" required>
<small id="matchMsg"></small>
</div>

<ul class="password-requirements mb-3">
<li id="reqLength" class="invalid">อย่างน้อย 8 ตัวอักษร</li>
<li id="reqUpper" class="invalid">ตัวพิมพ์ใหญ่ 1 ตัว</li>
<li id="reqLower" class="invalid">ตัวพิมพ์เล็ก 1 ตัว</li>
<li id="reqNumber" class="invalid">ตัวเลข 1 ตัว</li>
</ul>

<button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>

</form>

<div class="text-center mt-3">
<small>มีบัญชีแล้ว? <a href="user_login.php">เข้าสู่ระบบ</a></small>
</div>

</div>
</div>
</div>

<script>
const password = document.getElementById('password');
const confirm  = document.getElementById('confirm');
const matchMsg = document.getElementById('matchMsg');

const reqLength = document.getElementById('reqLength');
const reqUpper  = document.getElementById('reqUpper');
const reqLower  = document.getElementById('reqLower');
const reqNumber = document.getElementById('reqNumber');

function checkPassword() {
    const pwd = password.value;

    reqLength.className = pwd.length >= 8 ? 'valid' : 'invalid';
    reqUpper.className  = /[A-Z]/.test(pwd) ? 'valid' : 'invalid';
    reqLower.className  = /[a-z]/.test(pwd) ? 'valid' : 'invalid';
    reqNumber.className = /[0-9]/.test(pwd) ? 'valid' : 'invalid';

    if (confirm.value !== '') {
        if (pwd === confirm.value) {
            matchMsg.textContent = 'รหัสผ่านตรงกัน';
            matchMsg.style.color = 'green';
        } else {
            matchMsg.textContent = 'รหัสผ่านไม่ตรงกัน';
            matchMsg.style.color = 'red';
        }
    }
}

password.addEventListener('input', checkPassword);
confirm.addEventListener('input', checkPassword);
</script>

<?php if ($success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'สมัครสมาชิกสำเร็จ',
    timer: 1500,
    showConfirmButton: false
}).then(() => location.href = 'user_login.php');
</script>
<?php endif; ?>

</body>
</html>
