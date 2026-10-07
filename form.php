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
                        <option value="">-- ไม่มีวิทยฐานะ / ครูผู้ช่วย --</option>
                        <option value="ชำนาญการ">ชำนาญการ</option>
                        <option value="ชำนาญการพิเศษ">ชำนาญการพิเศษ</option>
                        <option value="เชี่ยวชาญ">เชี่ยวชาญ</option>
                        <option value="เชี่ยวชาญพิเศษ">เชี่ยวชาญพิเศษ</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">กลุ่มสาระการเรียนรู้ / ฝ่าย / กลุ่มงาน:</label>
                    <select name="department" class="form-select" required>
                        <option value="">-- เลือกกลุ่มสาระการเรียนรู้ / กลุ่มงาน / ฝ่าย --</option>
                        <optgroup label="ผู้บริหารสถานศึกษา">
                            <option value="ฝ่ายบริหารสถานศึกษา">ฝ่ายบริหารสถานศึกษา (ผู้บริหาร)</option>
                        </optgroup>
                        <optgroup label="กลุ่มงานบริหาร (ฝ่าย/งาน)">
                            <option value="กลุ่มงานบริหารวิชาการ">กลุ่มงานบริหารวิชาการ</option>
                            <option value="กลุ่มงานบริหารงบประมาณและแผนงาน">กลุ่มงานบริหารงบประมาณและแผนงาน</option>
                            <option value="กลุ่มงานบริหารงานบุคคล">กลุ่มงานบริหารงานบุคคล</option>
                            <option value="กลุ่มงานบริหารทั่วไป">กลุ่มงานบริหารทั่วไป</option>
                            <option value="กลุ่มงานกิจการนักเรียน">กลุ่มงานกิจการนักเรียน</option>
                        </optgroup>
                        <optgroup label="กลุ่มสาระการเรียนรู้">
                            <option value="กลุ่มสาระการเรียนรู้ภาษาไทย">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                            <option value="กลุ่มสาระการเรียนรู้คณิตศาสตร์">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                            <option value="กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                            <option value="กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
                            <option value="กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา">กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา</option>
                            <option value="กลุ่มสาระการเรียนรู้ศิลปะ">กลุ่มสาระการเรียนรู้ศิลปะ</option>
                            <option value="กลุ่มสาระการเรียนรู้การงานอาชีพ">กลุ่มสาระการเรียนรู้การงานอาชีพ</option>
                            <option value="กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                            <option value="กิจกรรมพัฒนาผู้เรียน">กิจกรรมพัฒนาผู้เรียน</option>
                        </optgroup>
                        <optgroup label="สายสนับสนุนและบุคลากร">
                            <option value="บุคลากรทางการศึกษา/เจ้าหน้าที่">บุคลากรทางการศึกษา / เจ้าหน้าที่</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-12">
                    <div class="p-3 bg-light rounded border border-primary-subtle">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">สังกัดกลุ่มงานที่เสนอ:</label>
                                <select name="work_group" class="form-select" required>
                                    <option value="">-- เลือกกลุ่มงาน --</option>
                                    <option value="ฝ่ายบริหารสถานศึกษา">ฝ่ายบริหารสถานศึกษา (ผู้บริหาร)</option>
                                    <option value="กลุ่มงานบริหารวิชาการ">กลุ่มงานบริหารวิชาการ</option>
                                    <option value="กลุ่มงานบริหารงบประมาณและแผนงาน">กลุ่มงานบริหารงบประมาณและแผนงาน</option>
                                    <option value="กลุ่มงานบริหารงานบุคคล">กลุ่มงานบริหารงานบุคคล</option>
                                    <option value="กลุ่มงานบริหารทั่วไป">กลุ่มงานบริหารทั่วไป</option>
                                    <option value="กลุ่มงานกิจการนักเรียน">กลุ่มงานกิจการนักเรียน</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">ชื่อ-สกุล หัวหน้ากลุ่มงาน / ผู้บังคับบัญชาชั้นต้น:</label>
                                <input type="text" name="head_group_name" class="form-control" placeholder="ระบุชื่อ-สกุล หัวหน้ากลุ่มงาน หรือเว้นขีดไว้กรณีเป็นผู้บริหาร" required>
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

                <div class="col-12">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label fw-bold text-secondary">ลักษณะช่วงเวลาการเดินทาง:</label>
                        <div class="d-flex flex-wrap gap-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="time_mode" id="tm_full" value="full" checked onchange="toggleHalfDay()">
                                <label class="form-check-label" for="tm_full">เต็มวัน</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="time_mode" id="tm_half" value="half" onchange="toggleHalfDay()">
                                <label class="form-check-label" for="tm_half">กรณีครึ่งวัน / ไป-กลับเฉพาะช่วงเวลา</label>
                            </div>
                        </div>
                        <div id="half_day_box" style="display: none;">
                            <label class="form-label small text-muted">ระบุช่วงเวลา (กรณีครึ่งวัน):</label>
                            <input type="text" name="half_day_time" id="half_day_time" class="form-control form-control-sm" placeholder="เช่น ช่วงเช้า (เวลา 08.30 - 12.00 น.) หรือ ช่วงบ่าย (เวลา 13.00 - 16.30 น.)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. รายละเอียดค่าใช้จ่ายและยานพาหนะ -->
            <div class="section-header">4. รายละเอียดค่าใช้จ่ายและยานพาหนะ</div>
            <div class="expense-box">
                <div class="fw-bold mb-2">โดยข้าพเจ้า:</div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="expense_option_no" id="exp_no" value="1">
                    <label class="form-check-label" for="exp_no">ไม่ขอเบิกค่าใช้จ่าย</label>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="expense_option_school" id="exp_school" value="1">
                    <label class="form-check-label" for="exp_school">
                        ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา (ค่ายานพาหนะเดินทาง, ค่าเบี้ยเลี้ยง, ค่าที่พัก) ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ
                    </label>
                </div>

                <div class="mb-3 ps-4 border-start border-2 border-primary">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="expense_option_specific" id="exp_specific" value="1">
                        <label class="form-check-label fw-bold" for="exp_specific">ขอเบิกเฉพาะค่าใช้จ่าย:</label>
                    </div>
                    <div class="d-flex flex-wrap gap-3 ms-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="specific_items[]" id="item_vehicle" value="ค่าพาหนะเดินทาง">
                            <label class="form-check-label" for="item_vehicle">ค่าพาหนะเดินทาง</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="specific_items[]" id="item_fuel" value="ค่าน้ำมัน">
                            <label class="form-check-label" for="item_fuel">ค่าน้ำมัน</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="specific_items[]" id="item_allowance" value="ค่าเบี้ยเลี้ยง">
                            <label class="form-check-label" for="item_allowance">ค่าเบี้ยเลี้ยง</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="specific_items[]" id="item_room" value="ค่าที่พัก">
                            <label class="form-check-label" for="item_room">ค่าที่พัก</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3 ps-4 border-start border-2 border-success">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="expense_option_vehicle" id="exp_vehicle" value="1" onchange="toggleVehicleInputs()">
                        <label class="form-check-label fw-bold" for="exp_vehicle">ไปราชการด้วย:</label>
                    </div>
                    <div class="row g-2 align-items-center ms-1">
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vehicle_select" id="v_gov" value="รถยนต์ราชการ" onchange="toggleVehicleInputs()">
                                <label class="form-check-label" for="v_gov">รถยนต์ราชการ</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vehicle_select" id="v_priv" value="รถยนต์ส่วนตัว" checked onchange="toggleVehicleInputs()">
                                <label class="form-check-label" for="v_priv">รถยนต์ส่วนตัว</label>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <input type="text" name="vehicle_license_plate" id="vehicle_license_plate" class="form-control form-control-sm" placeholder="หมายเลขทะเบียน เช่น นข 1234 ตรัง">
                        </div>
                        <div class="col-md-4 col-sm-12" id="driver_box" style="display: none;">
                            <input type="text" name="driver_name" id="driver_name" class="form-control form-control-sm border-primary" placeholder="ชื่อ-สกุล พนักงานขับรถ">
                        </div>
                    </div>
                </div>

                <div class="mb-1">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="expense_option_other" id="exp_other" value="1">
                        <label class="form-check-label fw-bold" for="exp_other">อื่น ๆ</label>
                    </div>
                    <div class="ms-3">
                        <input type="text" name="expense_other" class="form-control form-control-sm" placeholder="ระบุรายละเอียดเพิ่มเติม...">
                    </div>
                </div>
            </div>

            <!-- 5. ผู้ร่วมเดินทาง -->
            <div class="section-header d-flex justify-content-between align-items-center">
                <span>5. ผู้ร่วมเดินทาง (ถ้ามี)</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addParticipant('teacher')">+ เพิ่มครู/บุคลากร</button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="addParticipant('student')">+ เพิ่มนักเรียน</button>
                </div>
            </div>
            <div id="participant-container"></div>

            <!-- 6. ผู้มีอำนาจลงนามอนุมัติ (ผู้อำนวยการ / รักษาการในตำแหน่ง) -->
            <div class="section-header">6. ผู้มีอำนาจพิจารณาอนุมัติ</div>
            <div class="p-3 bg-light rounded border border-warning-subtle mb-3">
                <label class="form-label fw-bold text-dark mb-2">เสนออนุมัติต่อ:</label>
                <div class="d-flex flex-wrap gap-4 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sign_mode" id="sign_dir" value="director" checked onchange="toggleSignMode()">
                        <label class="form-check-label" for="sign_dir">ผู้อำนวยการโรงเรียน (ว่าที่ร้อยโทจักรเพชร์ พรมยศ)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sign_mode" id="sign_act" value="acting" onchange="toggleSignMode()">
                        <label class="form-check-label" for="sign_act">รองผู้อำนวยการ รักษาการในตำแหน่ง ผู้อำนวยการโรงเรียนฯ</label>
                    </div>
                </div>
                <div id="acting_box" style="display: none;">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">ชื่อ-สกุล รองผู้อำนวยการที่รักษาการ:</label>
                            <input type="text" name="acting_name" id="acting_name" class="form-control form-control-sm" placeholder="เช่น นายประเสริฐ ดำรงเกียรติ">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <button type="submit" class="btn btn-primary px-5 py-2 fs-5">บันทึกข้อมูลและสร้างบันทึกข้อความ</button>
                <a href="index.php" class="btn btn-secondary px-4 py-2 fs-5 ms-2">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleHalfDay() {
    const isHalf = document.getElementById('tm_half').checked;
    const box = document.getElementById('half_day_box');
    const input = document.getElementById('half_day_time');
    if (isHalf) {
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
        input.value = '';
    }
}

function toggleVehicleInputs() {
    const isVehicleChecked = document.getElementById('exp_vehicle').checked;
    const isGov = document.getElementById('v_gov').checked;
    const driverBox = document.getElementById('driver_box');
    const driverInput = document.getElementById('driver_name');

    if (isVehicleChecked && isGov) {
        driverBox.style.display = 'block';
    } else {
        driverBox.style.display = 'none';
        driverInput.value = '';
    }
}

function toggleSignMode() {
    const isActing = document.getElementById('sign_act').checked;
    const box = document.getElementById('acting_box');
    const input = document.getElementById('acting_name');
    if (isActing) {
        box.style.display = 'block';
        input.required = true;
    } else {
        box.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

let pIndex = 0;
function addParticipant(type) {
    pIndex++;
    const container = document.getElementById('participant-container');
    const typeLabel = type === 'teacher' ? 'ครู / บุคลากร' : 'นักเรียน';
    const detailLabel = type === 'teacher' ? 'ตำแหน่ง / กลุ่มสาระฯ' : 'ชั้น / เลขที่';
    const detailPlaceholder = type === 'teacher' ? 'เช่น ครู กลุ่มสาระฯ คณิตศาสตร์' : 'เช่น ม.4/1 เลขที่ 5';

    const item = document.createElement('div');
    item.className = 'participant-item';
    item.id = `participant_${pIndex}`;
    item.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-2">
                <span class="badge ${type === 'teacher' ? 'bg-primary' : 'bg-success'}">${typeLabel}</span>
                <input type="hidden" name="participants[${pIndex}][type]" value="${type}">
            </div>
            <div class="col-md-5">
                <input type="text" name="participants[${pIndex}][name]" class="form-control form-control-sm" placeholder="ชื่อ-สกุล" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="participants[${pIndex}][detail]" class="form-control form-control-sm" placeholder="${detailPlaceholder}">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeParticipant(${pIndex})">ลบ</button>
            </div>
        </div>
    `;
    container.appendChild(item);
}

function removeParticipant(id) {
    const el = document.getElementById(`participant_${id}`);
    if (el) el.remove();
}
</script>

</body>
</html>
