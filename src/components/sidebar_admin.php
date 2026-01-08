<!-- components/sidebar_admin.php -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="adminSidebar">
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title fw-semibold">ADMIN PANEL</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body">

    <!-- เมนูหลัก -->
    <div class="list-group list-group-flush">

      <a href="/admin/a_training_program.php"
         class="list-group-item list-group-item-action bg-dark text-white border-secondary">
        หน้าหลัก
      </a>

      <a href="/admin/a_add_admin.php"
         class="list-group-item list-group-item-action bg-dark text-white border-secondary">
        จัดการบัญชีผู้ใช้
      </a>

      <a href="/admin/a_add_training.php"
         class="list-group-item list-group-item-action bg-dark text-white border-secondary">
        เพิ่มหลักสูตรอบรม
      </a>

    </div>

    <hr class="border-secondary my-4">

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link text-danger px-0" href="/logout.php" id="adminLogoutBtn">
          ออกจากระบบ
        </a>
      </li>
    </ul>

  </div>
</div>

<style>
  #adminSidebar {
    width: 260px;
    max-width: 80%;
  }

  /* ปรับ hover ให้สุภาพ */
  #adminSidebar .list-group-item:hover {
    background-color: #1f2937;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const logoutBtn = document.getElementById('adminLogoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      Swal.fire({
        title: 'ยืนยันการออกจากระบบ',
        text: 'คุณต้องการออกจากระบบใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ออกจากระบบ',
        cancelButtonText: 'ยกเลิก'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '/logout.php';
        }
      });
    });
  }
});
</script>
