<?php
session_start();

if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    session_unset();
    session_destroy();

    echo "<script>  
            localStorage.setItem('logout_success', '1');
            window.location.href = 'f_training_program.php';
          </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ยืนยันออกจากระบบ</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.modal-box {
    animation: fadeZoom .35s ease;
}

@keyframes fadeZoom {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

.btn-cancel:hover {
    animation: shake .4s ease;
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    50% { transform: translateX(4px); }
    75% { transform: translateX(-3px); }
    100% { transform: translateX(0); }
}

.btn-confirm {
    transition: 0.3s;
}

.btn-confirm:active {
    transform: scale(0.95);
    box-shadow: 0 0 12px rgba(255,0,0,0.6);
}

@media (max-width: 576px) {
    .modal-box {
        padding: 20px 16px;
        margin: 0 12px;
    }

    .modal-box h4 {
        font-size: 20px;
    }

    .modal-box p {
        font-size: 14px;
    }

    .modal-box .btn {
        font-size: 15px;
        padding: 10px;
    }
}
</style>
</head>

<body class="bg-light">

<div class="d-flex justify-content-center align-items-center min-vh-100 px-2">

    <div class="card shadow-lg p-4 modal-box" style="max-width: 380px; width:100%;">
        <h4 class="text-center mb-3">ออกจากระบบ</h4>
        <p class="text-center text-secondary mb-4">
            คุณต้องการออกจากระบบใช่หรือไม่?
        </p>

        <form method="post">
            <div class="d-flex gap-2">
                <button type="submit" name="confirm" value="yes"
                        class="btn btn-danger w-50 btn-confirm"
                        onclick="buttonEffect(this)">
                    ออกจากระบบ
                </button>

                <button type="button"
                        class="btn btn-secondary w-50 btn-cancel"
                        onclick="goBack()">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function buttonEffect(btn) {
    btn.style.transform = "scale(0.92)";
    setTimeout(() => btn.style.transform = "scale(1)", 150);
}

function goBack() {
    history.back();
}

document.addEventListener("DOMContentLoaded", () => {
    if (localStorage.getItem("logout_success") === "1") {

        Swal.fire({
            icon: 'success',
            title: 'ออกจากระบบแล้ว',
            text: 'คุณได้ออกจากระบบสำเร็จ',
            timer: 1500,
            showConfirmButton: false
        });

        localStorage.removeItem("logout_success");
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
