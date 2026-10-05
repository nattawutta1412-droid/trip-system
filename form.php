<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบคำร้องขออนุญาตไปราชการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="py-4">
<div class="container">
    <div class="card card-custom p-4 mx-auto" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary mb-0">แบบคำร้องขออนุญาตไปราชการ</h3>
            <a href="history.php" class="btn btn-outline-secondary">ดูประวัติคำร้อง</a>
        </div>
        
        <form action="save.php" method="POST">
            <h5 class="text-secondary border-bottom pb-2 mb-3">1. ข้อมูลทั่วไปและผู้ขออนุญาต</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">วันที่ยื่นคำร้อง <span class="text-danger">*</span></label>
                    <input type="date" name="created_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">สังกัด / กลุ่มงาน / กลุ่มสาระฯ <span class="text-danger">*</span></label>
                    <input type="text" name="department" class="form-control" required placeholder="เช่น กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ชื่อ - สกุล ผู้ขออนุญาต <span class="text-danger">*</span></label>
                    <input type="text" name="applicant_name" class="form-control" required placeholder="นาย/นาง/นางสาว...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ตำแหน่ง <span class="text-danger">*</span></label>
                    <input type="text" name="position" class="form-control" required placeholder="เช่น ครู">
                </div>
                <div class="col-md-4">
                    <label class="form-label">วิทยฐานะ</label>
                    <input type="text" name="academic_standing" class="form-control" placeholder="เช่น ชำนาญการ (ถ้ามี)">
                </div>
            </div>

            <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">2. รายละเอียดการไปราชการ</h5>
            <div class="mb-3">
                <label class="form-label">มีความประสงค์จะขออนุญาตไปราชการ เรื่อง <span class="text-danger">*</span></label>
                <input type="text" name="subject" class="form-control" required placeholder="ระบุภารกิจหรือหัวข้องาน">
            </div>
            <div class="mb-3">
                <label class="form-label">สถานที่ ณ <span class="text-danger">*</span></label>
                <input type="text" name="destination" class="form-control" required placeholder="ระบุสถานที่ปลายทางและจังหวัด">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">ตามหนังสือเลขที่ / คำสั่งที่</label>
                    <input type="text" name="ref_document" class="form-control" placeholder="ระบุเลขที่หนังสือ (ถ้ามี)">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ลงวันที่ (ลว.)</label>
                    <input type="date" name="ref_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ตั้งแต่วันที่ <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ถึงวันที่ <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">กรณีครึ่งวัน เริ่มตั้งแต่เวลา</label>
                    <input type="text" name="half_day_time" class="form-control" placeholder="เช่น 08.30 - 12.00 น.">
                </div>
            </div>

            <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">3. ค่าใช้จ่ายและยานพาหนะ</h5>
            <div class="mb-3">
                <label class="form-label fw-bold">การเบิกค่าใช้จ่าย:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expense_type" value="ไม่ขอเบิกค่าใช้จ่าย" id="exp1" checked>
                    <label class="form-check-label" for="exp1">ไม่ขอเบิกค่าใช้จ่าย</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expense_type" value="ขอเบิกค่าใช้จ่ายตามสิทธิ" id="exp2">
                    <label class="form-check-label" for="exp2">ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือนอกงบประมาณของสถานศึกษา</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expense_type" value="ขอเบิกเฉพาะ" id="exp3">
                    <label class="form-check-label" for="exp3">ขอเบิกเฉพาะ</label>
                </div>
                <input type="text" name="expense_specific_details" class="form-control mt-2" placeholder="กรณีเลือกขอเบิกเฉพาะ ระบุ เช่น ค่าพาหนะเดินทาง, ค่าน้ำมัน, ค่าเบี้ยเลี้ยง">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">ยานพาหนะเดินทาง</label>
                    <select name="vehicle_type" class="form-select" required>
                        <option value="รถยนต์ส่วนตัว">รถยนต์ส่วนตัว</option>
                        <option value="รถยนต์ราชการ">รถยนต์ราชการ</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">หมายเลขทะเบียน</label>
                    <input type="text" name="vehicle_license_plate" class="form-control" placeholder="เช่น กข 1234 ตรัง">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">พนักงานขับรถ / ระบุอื่นๆ</label>
                    <input type="text" name="driver_name" class="form-control" placeholder="ระบุชื่อพนักงานขับรถ (ถ้ามี)">
                </div>
            </div>

            <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">4. สิ่งที่แนบมาด้วย: รายชื่อผู้ร่วมเดินทาง (ถ้ามี)</h5>
            
            <div class="mb-3">
                <label class="form-label fw-bold">รายชื่อครูร่วมเดินทาง</label>
                <div id="teacher_list">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" name="teachers[]" class="form-control" placeholder="ชื่อ-สกุล ครู"></div>
                        <div class="col-6"><input type="text" name="teacher_details[]" class="form-control" placeholder="ตำแหน่ง / วิทยฐานะ"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTeacherRow()">+ เพิ่มครู</button>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">รายชื่อนักเรียนร่วมเดินทาง</label>
                <div id="student_list">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" name="students[]" class="form-control" placeholder="ชื่อ-สกุล นักเรียน"></div>
                        <div class="col-6"><input type="text" name="student_details[]" class="form-control" placeholder="ระดับชั้น เช่น ม.3/1"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStudentRow()">+ เพิ่มนักเรียน</button>
            </div>

            <div class="text-center pt-3">
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">บันทึกคำร้อง</button>
            </div>
        </form>
    </div>
</div>

<script>
function addTeacherRow() {
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2';
    div.innerHTML = '<div class="col-6"><input type="text" name="teachers[]" class="form-control" placeholder="ชื่อ-สกุล ครู"></div><div class="col-6"><input type="text" name="teacher_details[]" class="form-control" placeholder="ตำแหน่ง / วิทยฐานะ"></div>';
    document.getElementById('teacher_list').appendChild(div);
}
function addStudentRow() {
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2';
    div.innerHTML = '<div class="col-6"><input type="text" name="students[]" class="form-control" placeholder="ชื่อ-สกุล นักเรียน"></div><div class="col-6"><input type="text" name="student_details[]" class="form-control" placeholder="ระดับชั้น เช่น ม.3/1"></div>';
    document.getElementById('student_list').appendChild(div);
}
</script>
</body>
</html>
