<?php
session_start();

// ตั้งรหัสผ่าน Admin สำหรับเข้าจัดการระบบ
$ADMIN_USERNAME = "admin";
$ADMIN_PASSWORD = "ykr12345; // **สามารถเปลี่ยนรหัสผ่านนี้ตามต้องการ**

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['teacher_admin_logged_in'] = true;
        header("Location: admin_upload.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบจัดการข้อมูลบุคลากร - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f0f2f5; }
        .login-card { max-width: 420px; border-radius: 16px; border: none; }
        .logo-img { width: 75px; height: auto; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

<div class="card login-card shadow-sm p-4 w-100 mx-3">
    <div class="text-center mb-4">
        <img src="../logo.png" alt="โลโก้โรงเรียน" class="logo-img mb-2">
        <h5 class="fw-bold mb-1">เข้าสู่ระบบหลังบ้าน</h5>
        <span class="text-muted small">ระบบสารสนเทศบุคลากร (Teacher MIS)</span>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">
        <div class="mb-3">
            <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username)</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" required autofocus placeholder="admin">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">รหัสผ่าน (Password)</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-medium mb-2">
            เข้าสู่ระบบ
        </button>
        <a href="index.php" class="btn btn-light w-100 text-muted small">กลับหน้าหลัก</a>
    </form>
</div>

</body>
</html>
