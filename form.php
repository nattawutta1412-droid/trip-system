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
            <div class="section-header">1. ข้อมูลบันทึกข้อความ</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">เลขที่หนังสือ / ที่:</label>
                    <input type="text" name="doc_number" class="form-control" placeholder="เช่น ศธ 04xxx/..." required>
                </div>
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
                    <input type="text" name="position" class="form-control" placeholder="เช่น ครู, ครูผู้ช่วย" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">วิทยฐานะ (ถ้ามี):</label>
                    <input type="text" name="academic_standing" class="form-control" placeholder="เช่น ชำนาญการ (ถ้าไม่มีเว้นว่างได้)">
                </div>
                <div class="col-md-6">
                    <label class="form-label">กลุ่มสาระการเรียนรู้ / งานสังกัด:</label>
                    <input type="text" name="department" class="form-control" placeholder="เช่น กลุ่มสาระฯ ภาษาต่างประเทศ" required>
                </div>

                <!-- ส่วนกลุ่มงานและหัวหน้ากลุ่มงาน -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded border border-primary-subtle">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">สังกัดกลุ่มงานที่เสนอ:</label>
                                <select name="work_group" class="form-select" required>
                                    <option value="">-- เลือกกลุ่มงาน --</option>
                                    <option value="กลุ่มงานบริหารวิชาการ">กลุ่มงานบริหารวิชาการ</option>
                                    <option value="กลุ่มงานบริหารงบประมาณและแผนงาน">กลุ่มงานบริหารงบประมาณและแผนงาน</option>
                                    <option value="กลุ่มงานบริหารงานบุคคล">กลุ่มงานบริหารงานบุคคล</option>
                                    <option value="กลุ่มงานบริหารทั่วไป">กลุ่มงานบริหารทั่วไป</option>
                                    <option value="กลุ่มงานกิจการนักเรียน">กลุ่มงานกิจการนักเรียน</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">ชื่อ-สกุล หัวหน้ากลุ่มงาน:</label>
                                <input type="text" name="head_group_name" class="form-control" placeholder="ระบุคำนำหน้าและชื่อ-สกุล หัวหน้ากลุ่มงาน" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. รายละเอียดการเดินทาง -->
            <div class="section-header">3. รายละเอียดการเดินทางไปราชการ</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">ไปราชการเพื่อ (วัตถุประสงค์):</label>
                    <textarea name="subject" class="form-control" rows="2" placeholder="ระบุภารกิจ เช่น เข้าร่วมการประชุมเชิงปฏิบัติการ..." required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">สถานที่ปลายทาง:</label>
                    <input type="text" name="destination" class="form-control" placeholder="เช่น โรงแรมไดมอนด์พลาซ่า อำเภอเมือง จังหวัดสุราษฎร์ธานี" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">หนังสืออ้างอิง (ถ้ามี):</label>
                    <input type="text" name="ref_document" class="form-control" placeholder="เช่น หนังสือ สพม.ตรัง กระบี่ ที่...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ลงวันที่ของหนังสืออ้างอิง:</label>
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
                    <label class="form-label">เวลาเดินทาง (กรณีครึ่งวัน / ไป-กลับ):</label>
                    <input type="text" name="half_day_time" class="form-control" placeholder="เช่น ช่วงเช้า, เวลา 08.30 - 12.00 น.">
                </div>
                <div class="col-md-6">
                    <label class="form-label">การเบิกค่าใช้จ่าย:</label>
                    <select name="expense_type" class="form-select" required>
                        <option value="ไม่ขอเบิกงบประมาณ">ไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการ</option>
                        <option value="ขอเบิกจากต้นสังกัด">ขอเบิกค่าใช้จ่ายจากโรงเรียน</option>
                        <option value="ขอเบิกจากผู้จัด">ขอเบิกค่าใช้จ่ายจากหน่วยงานผู้จัด</option>
                    </select>
                </div>
            </div>

            <!-- 4. ยานพาหนะ -->
            <div class="section-header">4. พาหนะที่ใช้เดินทาง</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">ยานพาหนะ:</label>
                    <select name="vehicle_type" class="form-select" required>
                        <option value="รถยนต์ส่วนบุคคล">รถยนต์ส่วนบุคคล</option>
                        <option value="รถยนต์ส่วนกลางของสถานศึกษา">รถยนต์ส่วนกลางของโรงเรียน</option>
                        <option value="รถโดยสารประจำทาง">รถโดยสารประจำทาง</option>
                        <option value="เครื่องบินโดยสาร">เครื่องบินโดยสาร</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">หมายเลขทะเบียนรถ (ถ้ามี):</label>
                    <input type="text" name="vehicle_license_plate" class="form-control" placeholder="เช่น กข 1234 ตรัง">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <button type="submit" class="btn btn-primary px-5 py-2 fs-5">บันทึกข้อมูลและสร้างบันทึกข้อความ</button>
                <a href="index.php" class="btn btn-secondary px-4 py-2 fs-5 ms-2">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
