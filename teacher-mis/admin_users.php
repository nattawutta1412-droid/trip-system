<?php
session_start();
require_once 'config.php';

// 1. ตรวจสอบและสร้างตาราง admins อัตโนมัติ
$create_table_sql = "CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'เจ้าหน้าที่',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create_table_sql);

// ตรวจสอบบัญชีตั้งต้น (admin / 123456)
$check_admin = $conn->query("SELECT id FROM `admins` LIMIT 1");
if ($check_admin && $check_admin->num_rows == 0) {
    $def_user = 'admin';
    $def_pass = password_hash('123456', PASSWORD_DEFAULT);
    $def_name = 'ผู้ดูแลระบบหลัก';
    $def_role = 'ผู้ดูแลระบบ';
    $stmt_init = $conn->prepare("INSERT INTO `admins` (username, password, fullname, role) VALUES (?, ?, ?, ?)");
    $stmt_init->bind_param("ssss", $def_user, $def_pass, $def_name, $def_role);
    $stmt_init->execute();
}

$message = "";
$message_type = "";

// 2. จัดการการเพิ่มผู้ใช้ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $role     = trim($_POST['role'] ?? 'เจ้าหน้าที่');

    if (!empty($username) && !empty($password) && !empty($fullname)) {
        $check_user = $conn->prepare("SELECT id FROM `admins` WHERE username = ?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        if ($check_user->get_result()->num_rows > 0) {
            $message = "Username นี้ถูกใช้งานแล้ว กรุณาเลือกชื่ออื่น";
            $message_type = "danger";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt_add = $conn->prepare("INSERT INTO `admins` (username, password, fullname, role) VALUES (?, ?, ?, ?)");
            $stmt_add->bind_param("ssss", $username, $hashed_pass, $fullname, $role);
            if ($stmt_add->execute()) {
                $message = "เพิ่มผู้ใช้งาน {$fullname} เรียบร้อยแล้ว";
                $message_type = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
                $message_type = "danger";
            }
        }
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        $message_type = "warning";
    }
}

// 3. จัดการการรีเซ็ตรหัสผ่าน (Reset Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $target_id    = intval($_POST['user_id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');

    if ($target_id > 0 && !empty($new_password)) {
        $hashed_new_pass = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_reset = $conn->prepare("UPDATE `admins` SET password = ? WHERE id = ?");
        $stmt_reset->bind_param("si", $hashed_new_pass, $target_id);
        if ($stmt_reset->execute()) {
            $message = "รีเซ็ตรหัสผ่านเรียบร้อยแล้ว";
            $message_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "กรุณาระบุรหัสผ่านใหม่ที่ต้องการตั้ง";
        $message_type = "warning";
    }
}

// 4. จัดการการลบผู้ใช้
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $count_res = $conn->query("SELECT COUNT(*) AS total FROM `admins`");
    $total_admins = $count_res->fetch_assoc()['total'] ?? 1;

    if ($total_admins <= 1) {
        $message = "ไม่สามารถลบได้ ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน";
        $message_type = "danger";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM `admins` WHERE id = ?");
        $stmt_del->bind_param("i", $del_id);
        if ($stmt_del->execute()) {
            $message = "ลบผู้ใช้งานเรียบร้อยแล้ว";
            $message_type = "success";
        }
    }
}

// ดึงรายชื่อผู้ดูแลระบบทั้งหมด
$admins_list = $conn->query("SELECT id, username, fullname, role, created_at FROM `admins` ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งานและรีเซ็ตรหัสผ่าน - ระบบหลังบ้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 12px; }
        .table th { background-color: #f8f9fa; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 980px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary mb-0"><i class="bi bi-shield-lock-fill me-2"></i>จัดการผู้ใช้งานและรีเซ็ตรหัสผ่าน</h4>
            <div class="text-muted small">ระบบบริหารจัดการบัญชีผู้ดูแลระบบและเจ้าหน้าที่</div>
        </div>
        <div>
            <a href="admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ฟอร์มเพิ่มผู้ใช้งาน -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-plus-fill me-1 text-primary"></i> เพิ่มผู้ใช้งานใหม่</h6>
                </div>
                <div class="card-body p-3">
                    <form action="admin_users.php" method="POST">
                        <input type="hidden" name="action" value="add_user">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">ชื่อ-สกุล: <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control" placeholder="เช่น นายสมหมาย ชำนาญงาน" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username: <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="เช่น sommai_admin" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">รหัสผ่าน: <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="กำหนดรหัสผ่าน" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">บทบาท / สิทธิ์การใช้งาน:</label>
                            <select name="role" class="form-select">
                                <option value="เจ้าหน้าที่">เจ้าหน้าที่งานบุคคล</option>
                                <option value="ผู้ช่วยผู้อำนวยการ">ผู้ช่วยผู้อำนวยการ</option>
                                <option value="ผู้ดูแลระบบ">ผู้ดูแลระบบสูงสุด (Admin)</option>
                            </select>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary fw-medium">
                                <i class="bi bi-save me-1"></i> บันทึกผู้ใช้งาน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ตารางแสดงรายชื่อพร้อมปุ่มรีเซ็ตรหัสผ่าน -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-1 text-primary"></i> รายชื่อผู้ดูแลระบบในปัจจุบัน</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 30%;">ชื่อ-สกุล</th>
                                    <th style="width: 25%;">Username</th>
                                    <th style="width: 25%;">สิทธิ์</th>
                                    <th class="text-center" style="width: 20%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admins_list && $admins_list->num_rows > 0): ?>
                                    <?php while ($adm = $admins_list->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <strong><?php echo htmlspecialchars($adm['fullname']); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($adm['username']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo htmlspecialchars($adm['role']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <!-- ปุ่มเปิด Modal รีเซ็ตรหัสผ่าน -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-warning me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#resetModal" 
                                                        data-user-id="<?php echo $adm['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($adm['username']); ?>"
                                                        title="รีเซ็ตรหัสผ่าน">
                                                    <i class="bi bi-key-fill"></i>
                                                </button>

                                                <!-- ปุ่มลบผู้ใช้งาน -->
                                                <a href="admin_users.php?delete=<?php echo $adm['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('ยืนยันที่จะลบผู้ใช้งาน <?php echo htmlspecialchars($adm['username']); ?> หรือไม่?');"
                                                   title="ลบผู้ใช้">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">ยังไม่มีข้อมูลผู้ใช้งาน</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal สำหรับรีเซ็ตรหัสผ่าน -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="admin_users.php" method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="modal_user_id" value="">

                <div class="modal-header bg-warning bg-opacity-10 border-0">
                    <h5 class="modal-title fw-bold" id="resetModalLabel">
                        <i class="bi bi-key-fill text-warning me-1"></i> รีเซ็ตรหัสผ่าน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3">กำหนดรหัสผ่านใหม่สำหรับผู้ใช้: <strong id="modal_username_display" class="text-primary"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รหัสผ่านใหม่: <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="พิมพ์รหัสผ่านใหม่ที่ต้องการ" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning fw-medium">
                        <i class="bi bi-check-circle me-1"></i> ยืนยันเปลี่ยนรหัสผ่าน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// สคริปต์ส่งค่า ID และ Username เข้า Modal
const resetModal = document.getElementById('resetModal');
if (resetModal) {
    resetModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const username = button.getAttribute('data-username');

        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_username_display').textContent = username;
    });
}
</script>
</body>
</html>
