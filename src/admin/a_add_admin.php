<?php
require_once __DIR__ . '/../components/admin_guard.php';
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$success = '';
$editMode = false;
$editData = null;

// ======== แก้ไขข้อมูล ========
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare(
    "SELECT id, username, role 
     FROM users 
     WHERE id = ? AND role = 'Admin'"
);
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editData = $result->fetch_assoc();
        $editMode = true;
    }
}

// ======== ลบข้อมูล ========
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId != $_SESSION['user_id']) {
        $stmt = $conn->prepare(
            "DELETE FROM users WHERE id = ? AND role = 'Admin'"
        );
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $success = 'ลบบัญชีเรียบร้อยแล้ว';
    } else {
        $error = 'ไม่สามารถลบบัญชีของตัวเองได้';
    }
}

// ======== บันทึกข้อมูล ========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');
    $role = 'Admin';
    $editId   = (int)($_POST['edit_id'] ?? 0);

    if ($username === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้';
    } elseif ($editId > 0) {
        // โหมดแก้ไข
        if ($password !== '') {
            if ($password !== $confirm) {
                $error = 'รหัสผ่านไม่ตรงกัน';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $hashed, $editId);
                $stmt->execute();
                $success = 'แก้ไขข้อมูลเรียบร้อยแล้ว';
                $editMode = false;
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $editId);
            $stmt->execute();
            $success = 'แก้ไขข้อมูลเรียบร้อยแล้ว';
            $editMode = false;
        }
    } else {
        // โหมดเพิ่มใหม่
        if ($password === '' || $confirm === '') {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } elseif ($password !== $confirm) {
            $error = 'รหัสผ่านไม่ตรงกัน';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows;

            if ($exists > 0) {
                $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashed, $role);
                $stmt->execute();
                $success = 'เพิ่มบัญชีผู้ดูแลระบบเรียบร้อยแล้ว';
            }
        }
    }
}

// ดึงรายชื่อบัญชีทั้งหมด
$users = $conn->query(
    "SELECT id, username, role 
     FROM users 
     WHERE role = 'Admin' 
     ORDER BY id ASC"
);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการบัญชีผู้ดูแลระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../components/sidebar_admin.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
      style="background: linear-gradient(135deg, #2563eb, #1e40af);">
      <div class="container-fluid">
        <button class="btn btn-outline-light me-2"
          type="button"
          data-bs-toggle="offcanvas"
          data-bs-target="#adminSidebar"
          aria-controls="adminSidebar">
          ☰ เมนู
        </button>

        <span class="navbar-brand fw-bold fs-4">เพิ่มหลักสูตรอบรมอบรม</span>

        <div class="d-flex align-items-center gap-2">
          <span class="text-white small d-none d-md-block">Admin Panel</span>
        </div>
      </div>
    </nav>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">จัดการบัญชีเจ้าหน้าที่</h5>
    <a href="/admin/a_training_program.php" class="btn btn-success btn-sm">ย้อนกลับ</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <!-- ฟอร์มเพิ่ม/แก้ไข -->
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
      <h6 class="mb-0"><?= $editMode ? 'แก้ไขข้อมูลบัญชีเจ้าหน้าที่' : 'เพิ่มข้อมูลบัญชีเจ้าหน้าที่ใหม่' ?></h6>
    </div>
    <div class="card-body">
      <form method="POST">
        <?php if ($editMode): ?>
          <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
        <?php endif; ?>

        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <label class="form-label small">Username</label>
            <input type="text" name="username" class="form-control"
                   value="<?= $editMode ? htmlspecialchars($editData['username']) : '' ?>"
                   placeholder="admin" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small">
              Password <?= $editMode ? '(เว้นว่างหากไม่ต้องการเปลี่ยน)' : '' ?>
            </label>
            <input type="password" name="password" class="form-control"
                   <?= $editMode ? '' : 'required' ?>>
          </div>

          <div class="col-md-4">
            <label class="form-label small">
              Confirm Password <?= $editMode ? '(เว้นว่างหากไม่ต้องการเปลี่ยน)' : '' ?>
            </label>
            <input type="password" name="confirm" class="form-control"
                   <?= $editMode ? '' : 'required' ?>>
          </div>
        </div>


        <div class="mt-3">
          <button type="submit" class="btn btn-primary">
            <?= $editMode ? 'บันทึกการแก้ไข' : 'เพิ่มบัญชีเจ้าหน้าที่' ?>
          </button>
          <?php if ($editMode): ?>
            <a href="?" class="btn btn-secondary ms-2">ยกเลิก</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- ตารางแสดงข้อมูล -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="py-2 px-3">Username</th>
              <th class="py-2 px-3">สิทธิการใช้งาน</th>
              <th class="py-2 px-3 text-center">หมายเหตุพิเศษ</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($users->num_rows === 0): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-4">ยังไม่มีข้อมูลบัญชี</td>
              </tr>
            <?php else: ?>
              <?php while ($row = $users->fetch_assoc()): ?>
                <tr>
                  <td class="py-2 px-3"><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                  <td class="py-2 px-3">
                    <span class="badge bg-primary"><?= htmlspecialchars($row['role'] ?? 'Admin') ?></span>
                  </td>
                  <td class="py-2 px-3 text-center">
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-primary btn-sm">แก้ไข</a>
                    <?php if (isset($_SESSION['user_id']) && $row['id'] != $_SESSION['user_id']): ?>
                      <a href="?delete=<?= $row['id'] ?>" class="btn btn-warning btn-sm ms-1" 
                         onclick="return confirm('คุณต้องการลบบัญชีนี้หรือไม่?')">ลบ</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>