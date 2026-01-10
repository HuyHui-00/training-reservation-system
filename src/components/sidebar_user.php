<div class="offcanvas offcanvas-start" tabindex="-1" id="userSidebar" aria-labelledby="userSidebarLabel">
  <div class="offcanvas-header text-white" style="background: linear-gradient(135deg, #2563eb, #1e40af);">
    <h5 class="offcanvas-title" id="userSidebarLabel">เมนูผู้ใช้งาน</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <div class="text-center mb-4 mt-2">
        <i class="bi bi-person-circle display-1 text-primary"></i>
        <h5 class="mt-3 fw-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h5>
        <span class="badge bg-success rounded-pill">นักศึกษา</span>
    </div>

    <div class="list-group list-group-flush flex-grow-1">
      <a href="/f_training_program.php" class="list-group-item list-group-item-action border-0 py-3">
        <i class="bi bi-house-door me-3 fs-5"></i> หน้าแรก
      </a>
      <a href="/f_profile.php" class="list-group-item list-group-item-action border-0 py-3">
        <i class="bi bi-person-vcard me-3 fs-5"></i> โปรไฟล์
      </a>
      <a href="/f_history.php" class="list-group-item list-group-item-action border-0 py-3">
        <i class="bi bi-clock-history me-3 fs-5"></i> ประวัติการเข้าอบรม
      </a>
    </div>

    <div class="mt-auto mb-3">
      <a href="/logout.php" id="btnSidebarLogout" class="btn btn-danger w-100 py-2">
        <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
      </a>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById("btnSidebarLogout");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      Swal.fire({
        title: 'ยืนยันการออกจากระบบ',
        text: 'คุณต้องการออกจากระบบใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ออกจากระบบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
      }).then((result) => {
        if (result.isConfirmed) window.location.href = "/logout.php";
      });
    });
  }
});
</script>