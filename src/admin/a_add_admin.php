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
    $stmt = $conn->prepare("SELECT id, username FROM user WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editData = $result->fetch_assoc();
        $editMode = true;
    }
}

// ======== บันทึกข้อมูล ========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');
    $editId   = (int)($_POST['edit_id'] ?? 0);

    if ($username === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้';
    } elseif ($editId > 0) {
        // โหมดแก้ไข
        if ($password !== '') {
            // แก้ไขทั้ง username และ password
            if ($password !== $confirm) {
                $error = 'รหัสผ่านไม่ตรงกัน';
            } else {
                $stmt = $conn->prepare("UPDATE user SET username = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $password, $editId);
                $stmt->execute();
                $success = 'แก้ไขข้อมูลเรียบร้อยแล้ว';
                $editMode = false;
            }
        } else {
            // แก้ไขเฉพาะ username
            $stmt = $conn->prepare("UPDATE user SET username = ? WHERE id = ?");
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
            // ตรวจสอบ username ซ้ำ
            $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows;

            if ($exists > 0) {
                $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว';
            } else {
                $stmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $password);
                $stmt->execute();
                $success = 'เพิ่มบัญชีผู้ดูแลระบบเรียบร้อยแล้ว';
            }
        }
    }
}

// ดึงรายชื่อบัญชีทั้งหมด
$users = $conn->query("SELECT id, username FROM user ORDER BY id ASC");
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการบัญชีผู้ดูแลระบบ</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.table-actions {
    white-space: nowrap;
}
.card-form {
    position: sticky;
    top: 20px;
}
</style>
</head>

<body class="bg-light">

<?php include __DIR__ . '/../components/sidebar_admin.php'; ?>

<!-- ===== Navbar ===== -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background: linear-gradient(135deg, #2563eb, #1e40af);">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#adminSidebar">
      ☰ เมนู
    </button>
    <span class="navbar-brand fw-bold fs-4">จัดการบัญชีผู้ดูแลระบบ</span>
  </div>
</nav>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">จัดการบัญชี Admin</h4>
    <a href="/admin/a_training_program.php" class="btn btn-outline-secondary btn-sm">กลับ</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- แสดงในแถวเดียว -->
  <div class="row g-3">
    
    <!-- คอลัมน์ซ้าย: ฟอร์ม -->
    <div class="col-lg-4 col-md-5">
      <div class="card shadow-sm card-form">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <?= $editMode ? '✏️ แก้ไขบัญชี' : '➕ เพิ่มบัญชีใหม่' ?>
          </h5>
        </div>
        <div class="card-body">
          <form method="POST">
            
            <?php if ($editMode): ?>
              <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text"
                     name="username"
                     class="form-control"
                     value="<?= $editMode ? htmlspecialchars($editData['username']) : '' ?>"
                     required>
            </div>

            <div class="mb-3">
              <label class="form-label">
                Password <?= $editMode ? '(เว้นว่างหากไม่ต้องการเปลี่ยน)' : '' ?>
              </label>
              <input type="password"
                     name="password"
                     class="form-control"
                     <?= $editMode ? '' : 'required' ?>>
            </div>

            <div class="mb-3">
              <label class="form-label">ยืนยัน Password</label>
              <input type="password"
                     name="confirm"
                     class="form-control"
                     <?= $editMode ? '' : 'required' ?>>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <?= $editMode ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มบัญชี' ?>
              </button>
              
              <?php if ($editMode): ?>
                <a href="?" class="btn btn-secondary">ยกเลิก</a>
              <?php endif; ?>
            </div>

          </form>
        </div>
      </div>
    </div>

    <!-- คอลัมน์ขวา: ตาราง -->
    <div class="col-lg-8 col-md-7">
      <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">📋 รายชื่อบัญชีทั้งหมด</h5>
        </div>
        <div class="card-body">
          
          <?php if ($admins->num_rows === 0): ?>
            <div class="alert alert-info">ยังไม่มีข้อมูลบัญชี</div>
          <?php else: ?>
            
            <div class="table-responsive">
              <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width: 60px;" class="text-center">ID</th>
                    <th>Username</th>
                    <th style="width: 100px;" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $admins->fetch_assoc()): ?>
                    <tr>
                      <td class="text-center"><?= $row['id'] ?></td>
                      <td>
                        <strong><?= htmlspecialchars($row['username']) ?></strong>
                        <?php if ($row['id'] == $_SESSION['user_id']): ?>
                          <span class="badge bg-success ms-2">ตัวคุณ</span>
                        <?php endif; ?>
                      </td>
                      <td class="table-actions text-center">
                        <a href="?edit=<?= $row['id'] ?>" 
                           class="btn btn-warning btn-sm">
                          ✏️ แก้ไข
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>