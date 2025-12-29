<!-- components/sidebar_admin.php -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="adminSidebar">
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title fw-semibold">ADMIN PANEL</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body">

    <!-- เมนูหลัก -->
    <div class="list-group list-group-flush">

      <a href="a_training_program.php"
         class="list-group-item list-group-item-action bg-dark text-white border-secondary">
        หน้าหลัก
      </a>

      <a href="a_teacher_search.php"
         class="list-group-item list-group-item-action bg-dark text-white border-secondary">
        ค้นหารายชื่ออาจารย์
      </a>

    </div>

    <hr class="border-secondary my-4">

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link text-danger px-0" href="logout.php">
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
