<?php
session_start();
require_once 'config.php';

$departments = [
    'กลุ่มบริหารสถานศึกษา',
    'กลุ่มสาระการเรียนรู้ภาษาไทย',
    'กลุ่มสาระการเรียนรู้คณิตศาสตร์',
    'กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี',
    'กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม',
    'กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา',
    'กลุ่มสาระการเรียนรู้ศิลปะ',
    'กลุ่มสาระการเรียนรู้การงานอาชีพ',
    'กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ',
    'กลุ่มกิจกรรมพัฒนาผู้เรียน'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มขออนุมัติเดินทางไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .section-title { font-weight: 700; color: #0d6efd; margin-top: 20px; margin-bottom: 12px; }
        .card { border-radius: 12px; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 900px;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary">ระบบขออนุมัติเดินทางไปราชการ</h3>
        <p class="text-muted">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</p>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4">
            <form action="save.php" method="POST">
                
                <!-- 1. ข้อมูลผู้ขออนุมัติ -->
                <div class="section-title">1. ข้อมูลผู้ขออนุมัติ</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ชื่อ-สกุล (ผู้ขออนุมัติ): <span class="text-danger">*</span></label>
                        <input type="text" name="applicant_name" class="form-control" placeholder="เช่น นายสมหมาย ใจดี" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ตำแหน่ง:</label>
                        <input type="text" name="position" class="form-control" value="ครู" placeholder="เช่น ครู, ครูผู้ช่วย">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">วิทยฐานะ:</label>
                        <input type="text" name="academic_standing" class="form-control" placeholder="เช่น ชำนาญการ, ชำนาญการพิเศษ (ถ้ามี)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">กลุ่มสาระการเรียนรู้ / กลุ่มงาน / สังกัด: <span class="text-danger">*</span></label>
                        <select name="department" class="form-select" required>
                            <option value="">-- เลือกกลุ่มสาระฯ / สังกัด --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">กลุ่มงานที่เสนอ:</label>
                        <input type="text" name="proposed_group" class="form-control" value="กลุ่มงานบริหารวิชาการ">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ชื่อผู้ช่วยผู้อำนวยการ:</label>
                        <input type="text" name="head_name" class="form-control" value="นางศุภลักษณ์ ดำชุม">
                    </div>
                </div>

                <!-- 2. รายละเอียดการเดินทาง -->
                <div class="section-title">2. รายละเอียดการเดินทาง</div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">วัตถุประสงค์ (ไปราชการเพื่อ): <span class="text-danger">*</span></label>
                        <textarea name="topic" class="form-control" rows="2" placeholder="เช่น เข้าร่วมการอบรมเชิงปฏิบัติการการจัดการเรียนรู้เชิงรุก..." required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">สถานที่ปลายทาง: <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" placeholder="เช่น โรงเรียนสภาราชินี จังหวัดตรัง" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">หนังสืออ้างอิง (ถ้ามี):</label>
                        <input type="text" name="ref_doc" class="form-control" placeholder="เช่น ศธ 04238.12/...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ลงวันที่ของหนังสืออ้างอิง:</label>
                        <input type="date" name="ref_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ตั้งแต่วันที่: <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ถึงวันที่: <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ช่วงเวลา (กรณีไปครึ่งวัน):</label>
                        <input type="text" name="time_period" class="form-control" placeholder="เช่น เวลา 08.30 - 12.00 น.">
                    </div>
                </div>

                <!-- รายชื่อผู้ร่วมเดินทางไปราชการ (หน้าที่ 2 แนบท้าย) -->
                <div class="card p-3 mb-3 border bg-light rounded-3">
                    <label class="form-label fw-bold text-primary mb-1">
                        <i class="bi bi-people-fill me-1"></i> รายชื่อผู้ร่วมเดินทางไปราชการ (หน้าที่ 2 แนบท้าย)
                    </label>
                    <div class="form-text text-muted mb-2">
                        หากมีผู้ร่วมเดินทาง ให้พิมพ์ <strong>1 ท่าน ต่อ 1 บรรทัด</strong> (เช่น <code>นายสมชาย สดใส ตำแหน่ง ครู</code>)
                    </div>
                    <textarea name="companions" class="form-control bg-white" rows="3" placeholder="นายสมหมาย จริงใจ ตำแหน่ง ครู&#10;นางสาวพิมพา รักดี ตำแหน่ง ครูผู้ช่วย"></textarea>
                </div>

                <!-- 3. ข้อมูลการเบิกจ่ายงบประมาณ -->
                <div class="section-title">3. ข้อมูลการเบิกจ่ายงบประมาณ</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ประเภทงบประมาณ:</label>
                        <select name="budget_type" class="form-select">
                            <option value="ไม่ขอเบิกงบประมาณ" selected>ไม่ขอเบิกงบประมาณ</option>
                            <option value="เงินงบประมาณสถานศึกษา">เงินงบประมาณสถานศึกษา</option>
                            <option value="เงินนอกงบประมาณ">เงินนอกงบประมาณ</option>
                            <option value="งบประมาณจากหน่วยงานผู้จัด">งบประมาณจากหน่วยงานผู้จัด</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">รายการค่าใช้จ่ายที่ขอเบิก (กรณีขอเบิก):</label>
                        <div class="d-flex flex-wrap gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="expense_types[]" value="ค่าน้ำมัน" id="exp1">
                                <label class="form-check-label small" for="exp1">ค่าน้ำมันเชื้อเพลิง</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="expense_types[]" value="ค่าเบี้ยเลี้ยง" id="exp2">
                                <label class="form-check-label small" for="exp2">ค่าเบี้ยเลี้ยง</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="expense_types[]" value="ค่าที่พัก" id="exp3">
                                <label class="form-check-label small" for="exp3">ค่าที่พัก</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="expense_types[]" value="ค่ายานพาหนะเดินทาง" id="exp4">
                                <label class="form-check-label small" for="exp4">ค่าพาหนะ/รถรับจ้าง</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. ยานพาหนะในการเดินทาง -->
                <div class="section-title">4. ยานพาหนะในการเดินทาง</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ยานพาหนะ:</label>
                        <input type="text" name="vehicle_type" class="form-control" placeholder="เช่น รถยนต์ส่วนบุคคล, รถโดยสารประจำทาง" value="รถยนต์ส่วนบุคคล">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">หมายเลขทะเบียน:</label>
                        <input type="text" name="vehicle_license" class="form-control" placeholder="เช่น กข 1234 ตรัง">
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-medium">
                        <i class="bi bi-send me-1"></i> ส่งคำร้องขออนุมัติ
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
