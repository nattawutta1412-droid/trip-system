<?php
session_start();

// ถ้าล็อกอินอยู่แล้วให้พาไปหน้า admin.php ทันที
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // กำหนดบัญชีผู้ดูแลระบบ (สามารถเปลี่ยนได้ที่นี่)
    $valid_user = "admin";
    $valid_pass = "ykr12345";

    if ($username === $valid_user && $password === $valid_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin.php");
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
    <title>เข้าสู่ระบบจัดการหลังบ้าน - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 420px;
            padding: 35px 30px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <img src="logo.png" alt="Logo" style="height: 65px; margin-bottom: 12px;" onerror="this.src='https://placehold.co/65x65?text=YKR';">
        <h4 class="fw-bold mb-1">เข้าสู่ระบบหลังบ้าน</h4>
        <small class="text-muted">ระบบบริหารการขออนุญาตไปราชการ โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</small>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label small fw-bold">ชื่อผู้ใช้งาน (Username)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">รหัสผ่าน (Password)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 shadow-sm">
            <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
        </button>

        <div class="text-center">
            <a href="index.php" class="text-decoration-none small text-secondary">
                <i class="bi bi-arrow-left"></i> กลับสู่หน้ารายการหลัก
            </a>
        </div>
    </form>
</div>

</body>
</html>
