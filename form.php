<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบขออนุมัติเดินทางไปราชการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 800px;">
    <div class="card p-4">
        <h4 class="text-center mb-4 fw-bold">แบบฟอร์มขออนุมัติเดินทางไปราชการ</h4>
        <!-- จุดสำคัญ: ต้องมี enctype="multipart/form-data" เสมอสำหรับการอัปโหลดไฟล์ -->
        <form action="save.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">เลขที่หนังสือ / ที่:</label>
                    <input type="text" name="doc_number" class="form-control" placeholder="เช่น ศธ 04xxx/..." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ลงวันที่:</label>
                    <input type="date" name="created_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">ชื่อ-สกุล ผู้ขออนุมัติ:</label>
                    <input type="text" name="applicant_name" class="form-control" placeholder="เช่น นายสมศักดิ์ รักเรียน" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ตำแหน่ง:</label>
                    <input type="text" name="position" class="form-control" placeholder="เช่น ครู" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">วิทยฐานะ (ถ้ามี):</label>
                    <input type="text" name="academic_standing" class="form-control" placeholder="เช่น ชำนาญการ">
                </div>
                <div class="col-md-6">
                    <label class="form-label">กลุ่มสาระการเรียนรู้ / กลุ่มงาน:</label>
                    <input type="text" name="department" class="form-control" placeholder="เช่น กลุ่มสาระฯ วิทยาศาสตร์และเทคโนโลยี" required>
                </div>

                <!-- ช่องกรอกชื่อหัวหน้าฝ่าย -->
                <div class="col-md-12">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label fw-bold text-primary">ชื่อ-สกุล หัวหน้าฝ่าย / หัวหน้ากลุ่มสาระการเรียนรู้:</label>
                        <input type="text" name="head_name" class="form-control" placeholder="ระบุชื่อ-สกุล เช่น นางสาวใจดี สุขสมบัติ" required>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">เรื่องขออนุมัติไปราชการเพื่อ:</label>
                    <input type="text" name="subject" class="form-control" placeholder="เช่น เข้าร่วมการประชุมเชิงปฏิบัติการ..." required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">สถานที่ไปราชการ (ปลายทาง):</label>
                    <input type="text" name="destination" class="form-control" placeholder="เช่น โรงแรมไดมอนด์พลาซ่า อ.เมือง จ.สุราษฎร์ธานี" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">หนังสืออ้างอิง (ถ้ามี):</label>
                    <input type="text" name="ref_document" class="form-control" placeholder="เช่น หนังสือ สพม.ตรัง กระบี่ ที่...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ลงวันที่ (ของหนังสืออ้างอิง):</label>
                    <input type="date" name="ref_date" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">ตั้งแต่วันที่:</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ถึงวันที่:</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">เวลาเดินทาง (กรณีครึ่งวัน):</label>
                    <input type="text" name="half_day_time" class="form-control" placeholder="เช่น ช่วงเช้า หรือระบุเวลา">
                </div>
                <div class="col-md-6">
                    <label class="form-label">การเบิกค่าใช้จ่าย:</label>
                    <select name="expense_type" class="form-select">
                        <option value="ไม่ขอเบิกงบประมาณ">ไม่ขอเบิกงบประมาณ</option>
                        <option value="ขอเบิกจากต้นสังกัด">ขอเบิกจากต้นสังกัด</option>
                        <option value="ขอเบิกจากผู้จัด">ขอเบิกจากผู้จัด</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">พาหนะเดินทาง:</label>
                    <input type="text" name="vehicle_type" class="form-control" placeholder="เช่น รถยนต์ส่วนบุคคล, รถโดยสารประจำทาง" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ทะเบียนรถ (ถ้ามี):</label>
                    <input type="text" name="vehicle_license_plate" class="form-control" placeholder="เช่น กข 1234 ตรัง">
                </div>

                <!-- ช่องอัปโหลดไฟล์เอกสารแนบ (PDF / รูปภาพ) -->
                <div class="col-md-12">
                    <div class="p-3 bg-light rounded border border-secondary border-dashed">
                        <label class="form-label fw-bold text-success">แนบเอกสารคำสั่ง / หนังสือเชิญ (PDF หรือ รูปภาพ):</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,image/*">
                        <small class="text-muted">* รองรับไฟล์ PDF, JPG, PNG ขนาดไม่เกิน 10MB</small>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary px-5 py-2">บันทึกข้อมูลและส่งเอกสาร</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
