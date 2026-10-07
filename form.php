<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบขออนุมัติเดินทางไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; color: #333; }
        .form-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; margin-top: 25px; margin-bottom: 40px; }
        .section-header { border-bottom: 2px solid #e9ecef; padding-bottom: 8px; margin-bottom: 20px; margin-top: 15px; font-weight: 600; color: #0d6efd; }
        .expense-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 18px; }
        .participant-item { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px;">
    <div class="form-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark">แบบขออนุมัติเดินทางไปราชการ</h3>
            <p class="text-muted">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</p>
        </div>

        <form action="save.php" method="POST">
            <!-- 1. ข้อมูลบันทึกข้อความ -->
            <div class="section-header">1. วันที่ยื่นคำร้อง</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">ลงวันที่:</label>
                    <input type="date" name="created_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <!-- 2. ข้อมูลผู้ขออนุมัติและกลุ่มงาน -->
            <div class="section-header">2. ข้อมูลผู้ขออนุมัติและกลุ่มงานต้นสังกัด</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">ชื่อ-สกุล ผู้ขออนุมัติ:</label>
                    <input type="text" name="applicant_name" class="form-control" placeholder="เช่น นายสมศักดิ์ รักเรียน" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ตำแหน่ง:</label>
                    <input type="text" name="position" class="form-control" placeholder="เช่น ครู, ครูผู้ช่วย, รองผู้อำนวยการสถานศึกษา" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">วิทยฐานะ:</label>
                    <select name="academic_standing" class="form-select">
                        <option value="">-- ไม่มีวิทยฐานะ / ครู
