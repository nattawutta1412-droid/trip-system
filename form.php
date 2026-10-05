<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบคำร้องขออนุญาตไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Sarabun", sans-serif; 
            background: #f4f6f9; 
            margin: 0; 
            padding: 20px; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.08); 
        }
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #edf2f7;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecf0f1;
            color: #2c3e50;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-back:hover {
            background: #d5dbdb;
        }
        h2 { 
            text-align: center; 
            color: #2c3e50; 
            margin-top: 10px;
            margin-bottom: 25px; 
        }
        .section-title { 
            font-weight: bold; 
            font-size: 16px; 
            margin-top: 25px; 
            margin-bottom: 12px; 
            border-bottom: 2px solid #3498db; 
            padding-bottom: 5px; 
            color: #34495e; 
        }
        .row { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 15px; 
            flex-wrap: wrap; 
        }
        .col { 
            flex: 1; 
            min-width: 240px; 
        }
        label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 6px; 
            font-size: 14px; 
            color: #333; 
        }
        input[type="text"], input[type="date"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 14px; 
            outline: none;
        }
        input[type="text"]:focus, input[type="date"]:focus, select:focus {
            border-color: #3498db;
        }
        .radio-group { 
            margin-bottom: 15px; 
            font-size: 14px; 
        }
        .radio-group label { 
            font-weight: normal; 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: #27ae60; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 25px; 
            transition: background 0.2s;
        }
        .btn-submit:hover { 
            background: #219150; 
        }
        .btn-add { 
            background: #3498db; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 13px; 
            margin-top: 5px; 
        }
        .btn-add:hover { 
            background: #2980b9; 
        }
        .participant-item { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 8px; 
        }
    </style>
</head>
<body>

<div class="container">
    <!-- แถบปุ่มย้อนกลับด้านบน -->
    <div class="top-nav">
        <a href="index.php" class="btn-back">← กลับหน้าสรุปข้อมูล (Dashboard)</a>
        <span style="font-size: 13px; color: #7f8c8d;">แบบฟอร์มคำร้องออนไลน์</span>
    </div>

    <h2>แบบขออนุญาตไปราชการ<br><small style="font-size: 14px; color: #7f8c8d;">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</small></h2>
    
    <form action="save.php" method="POST">
        <!-- 1. ข้อมูลผู้ขอ -->
        <div class="section-title">1. ข้อมูลผู้ขออนุญาต</div>
        <div class="row">
            <div class="col">
                <label>วันที่ยื่นคำร้อง *</label>
                <input type="date" name="created_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col">
                <label>ชื่อ - สกุล ผู้ขออนุญาต (ข้าพเจ้า) *</label>
                <input type="text" name="applicant_name" placeholder="นาย/นาง/นางสาว..." required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>ตำแหน่ง *</label>
                <input type="text" name="position" placeholder="เช่น ครู, พนักงานราชการ" required>
            </div>
            <div class="col">
                <label>วิทยฐานะ</label>
                <input type="text" name="academic_standing" placeholder="เช่น ชำนาญการพิเศษ (ถ้ามี)">
            </div>
            <div class="col">
                <label>สังกัด (กลุ่มสาระ/กลุ่มงาน) *</label>
                <input type="text" name="department" placeholder="เช่น กลุ่มสาระการเรียนรู้วิทยาศาสตร์ฯ" required>
            </div>
        </div>

        <!-- 2. หัวหน้ากลุ่มงานที่เสนอเรื่อง -->
        <div class="section-title">2. หัวหน้ากลุ่มงานที่เสนอพิจารณา</div>
        <div class="row">
            <div class="col">
                <label>ชื่อ - สกุล หัวหน้ากลุ่มงาน</label>
                <input type="text" name="head_name" placeholder="เช่น นายสมชาย ใจดี (ถ้าเว้นว่างจะแสดงเป็นเส้นประ)">
            </div>
            <div class="col">
                <label>กลุ่มบริหารงาน</label>
                <input type="text" name="head_department" placeholder="เช่น บริหารวิชาการ, บริหารทั่วไป">
            </div>
        </div>

        <!-- 3. รายละเอียดการไปราชการ -->
        <div class="section-title">3. รายละเอียดการไปราชการ</div>
        <div class="row">
            <div class="col">
                <label>เรื่องที่ไปราชการ *</label>
                <input type="text" name="subject" placeholder="ระบุเรื่องหรือหัวข้อการอบรม/ประชุม/แข่งขัน" required>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>สถานที่ ณ *</label>
                <input type="text" name="destination" placeholder="ระบุสถานที่จัด" required>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>ตามหนังสือเลขที่ / คำสั่งที่</label>
                <input type="text" name="ref_document" placeholder="ระบุเลขที่หนังสืออ้างอิง">
            </div>
            <div class="col">
                <label>ลงวันที่ (ลว.)</label>
                <input type="date" name="ref_date">
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>ตั้งแต่วันที่ *</label>
                <input type="date" name="start_date" required>
            </div>
            <div class="col">
                <label>ถึงวันที่ *</label>
                <input type="date" name="end_date" required>
            </div>
            <div class="col">
                <label>กรณีไปครึ่งวัน ระบุเวลา</label>
                <input type="text" name="half_day_time" placeholder="เช่น 08.30 - 12.00 น.">
            </div>
        </div>

        <!-- 4. การเบิกค่าใช้จ่าย -->
        <div class="section-title">4. การขอเบิกค่าใช้จ่าย</div>
        <div class="radio-group">
            <label><input type="radio" name="expense_type" value="no_expense" checked> ไม่ขอเบิกค่าใช้จ่าย</label>
            <label><input type="radio" name="expense_type" value="full_claim"> ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณ</label>
            <label><input type="radio" name="expense_type" value="specific_claim"> ขอเบิกเฉพาะ (ระบุ)</label>
            <input type="text" name="expense_specific_details" placeholder="เช่น ค่าเบี้ยเลี้ยง, ค่าน้ำมัน, ค่าที่พัก">
        </div>

        <!-- 5. ยานพาหนะ -->
        <div class="section-title">5. ยานพาหนะที่ใช้เดินทาง</div>
        <div class="radio-group">
            <label><input type="radio" name="vehicle_type" value="personal_car" checked> รถยนต์ส่วนตัว</label>
            <div style="margin-left: 25px; margin-bottom: 10px;">
                <input type="text" name="vehicle_license_plate" placeholder="หมายเลขทะเบียนรถส่วนตัว">
            </div>

            <label><input type="radio" name="vehicle_type" value="official_car"> รถยนต์ราชการ</label>
            <div style="margin-left: 25px; margin-bottom: 10px;">
                <input type="text" name="official_car_plate" placeholder="หมายเลขทะเบียนรถราชการ">
                <input type="text" name="driver_name" placeholder="ชื่อพนักงานขับรถ" style="margin-top: 5px;">
            </div>

            <label><input type="radio" name="vehicle_type" value="other"> อื่น ๆ</label>
            <div style="margin-left: 25px;">
                <input type="text" name="vehicle_other_detail" placeholder="ระบุ เช่น รถตู้โดยสารประจำทาง">
            </div>
        </div>

        <!-- 6. รายชื่อผู้ร่วมเดินทาง -->
        <div class="section-title">6. รายชื่อผู้ร่วมไปราชการ (ถ้ามี)</div>
        <label>ครูที่ร่วมเดินทาง:</label>
        <div id="teacher_list">
            <div class="participant-item">
                <input type="text" name="teacher_name[]" placeholder="ชื่อ-นามสกุล">
                <input type="text" name="teacher_pos[]" placeholder="ตำแหน่ง / วิทยฐานะ">
            </div>
        </div>
        <button type="button" class="btn-add" onclick="addTeacher()">+ เพิ่มครูร่วมเดินทาง</button>

        <br><br>
        <label>นักเรียนที่ร่วมเดินทาง:</label>
        <div id="student_list">
            <div class="participant-item">
                <input type="text" name="student_name[]" placeholder="ชื่อ-นามสกุลนักเรียน">
                <input type="text" name="student_grade[]" placeholder="ระดับชั้น เช่น ม.3/1">
            </div>
        </div>
        <button type="button" class="btn-add" onclick="addStudent()">+ เพิ่มนักเรียนร่วมเดินทาง</button>

        <button type="submit" class="btn-submit">💾 บันทึกและส่งคำร้อง</button>
    </form>
</div>

<script>
function addTeacher() {
    let div = document.createElement('div');
    div.className = 'participant-item';
    div.innerHTML = '<input type="text" name="teacher_name[]" placeholder="ชื่อ-นามสกุล"><input type="text" name="teacher_pos[]" placeholder="ตำแหน่ง / วิทยฐานะ">';
    document.getElementById('teacher_list').appendChild(div);
}
function addStudent() {
    let div = document.createElement('div');
    div.className = 'participant-item';
    div.innerHTML = '<input type="text" name="student_name[]" placeholder="ชื่อ-นามสกุลนักเรียน"><input type="text" name="student_grade[]" placeholder="ระดับชั้น เช่น ม.3/1">';
    document.getElementById('student_list').appendChild(div);
}
</script>

</body>
</html>