<?php
require_once 'config.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$trip = $conn->query("SELECT * FROM official_trips WHERE id = $id")->fetch_assoc();
if (!$trip) { die("ไม่พบข้อมูลคำร้อง"); }

$participants = $conn->query("SELECT * FROM trip_participants WHERE trip_id = $id");
$teachers = [];
$students = [];
while($p = $participants->fetch_assoc()) {
    if ($p['participant_type'] === 'teacher') $teachers[] = $p;
    else $students[] = $p;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความ - <?php echo htmlspecialchars($trip['applicant_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; font-size: 16pt; line-height: 1.6; margin: 0; padding: 20px; background: #e9ecef; }
        .page { background: white; width: 210mm; min-height: 297mm; padding: 25mm 20mm 20mm 25mm; margin: 0 auto 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); box-sizing: border-box; }
        .text-center { text-align: center; }
        .header-title { font-size: 28pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .checkbox-box { display: inline-block; width: 14px; height: 14px; border: 1px solid black; margin-right: 6px; text-align: center; line-height: 12px; font-size: 12pt; }
        @media print {
            body { background: none; padding: 0; }
            .page { box-shadow: none; margin: 0; page-break-after: always; width: 100%; min-height: 100%; padding: 20mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print text-center" style="margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 24px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 6px; font-weight: bold;">🖨️ สั่งพิมพ์ / บันทึกเป็น PDF</button>
    <a href="history.php" style="margin-left: 10px; padding: 10px 18px; font-size: 16px; text-decoration: none; background: #6c757d; color: white; border-radius: 6px;">กลับหน้ารายการ</a>
</div>

<!-- หน้าที่ 1 -->
<div class="page">
    <div class="header-title">บันทึกข้อความ</div>
    <p><b>ส่วนราชการ</b> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</p>
    <p><b>ที่</b> ............................................................ <b>วันที่</b> <?php echo $trip['created_date']; ?></p>
    <p><b>เรื่อง</b> ขออนุญาตไปราชการ</p>
    <p><b>เรียน</b> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</p>
    <p><b>สิ่งที่แนบมาด้วย</b> 1. รายชื่อครู/นักเรียนร่วมไปราชการ (ถ้ามี)<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2. บันทึกข้อความขออนุญาตสอนชดเชย วช.18</p>
    
    <p style="text-indent: 2.5cm; text-align: justify;">
        ด้วย ข้าพเจ้า <b><?php echo htmlspecialchars($trip['applicant_name']); ?></b> 
        ตำแหน่ง <b><?php echo htmlspecialchars($trip['position']); ?></b> 
        <?php if(!empty($trip['academic_standing'])): ?>วิทยฐานะ <b><?php echo htmlspecialchars($trip['academic_standing']); ?></b><?php endif; ?> 
        สังกัด <b><?php echo htmlspecialchars($trip['department']); ?></b>
        มีความประสงค์จะขออนุญาตไปราชการ เรื่อง <b><?php echo htmlspecialchars($trip['subject']); ?></b>
        สถานที่ ณ <b><?php echo htmlspecialchars($trip['destination']); ?></b>
        <?php if(!empty($trip['ref_document'])): ?>
            ตามหนังสือเลขที่/คำสั่งที่ <b><?php echo htmlspecialchars($trip['ref_document']); ?></b> 
            ลว <b><?php echo $trip['ref_date']; ?></b>
        <?php endif; ?>
        ตั้งแต่วันที่ <b><?php echo $trip['start_date']; ?></b> ถึงวันที่ <b><?php echo $trip['end_date']; ?></b>
        <?php if(!empty($trip['half_day_time'])): ?> (กรณีไปราชการครึ่งวัน เริ่มไปราชการตั้งแต่เวลา <b><?php echo htmlspecialchars($trip['half_day_time']); ?></b>)<?php endif; ?>
    </p>

    <p style="margin-left: 1cm; margin-bottom: 5px;">โดยข้าพเจ้า</p>
    <div style="margin-left: 2cm;">
        <span class="checkbox-box"><?php echo ($trip['expense_type'] === 'ไม่ขอเบิกค่าใช้จ่าย') ? '✓' : ''; ?></span> ไม่ขอเบิกค่าใช้จ่าย<br>
        <span class="checkbox-box"><?php echo ($trip['expense_type'] === 'ขอเบิกค่าใช้จ่ายตามสิทธิ') ? '✓' : ''; ?></span> ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา (ค่าพาหนะเดินทาง, ค่าเบี้ยเลี้ยง, ค่าที่พัก) ตามระเบียบกระทรวงการคลังว่าด้วย ค่าใช้จ่ายในการเดินทางไปราชการ<br>
        <span class="checkbox-box"><?php echo ($trip['expense_type'] === 'ขอเบิกเฉพาะ') ? '✓' : ''; ?></span> ขอเบิกเฉพาะ <b><?php echo htmlspecialchars($trip['expense_specific_details']); ?></b>
    </div>

    <p style="margin-left: 1cm; margin-top: 15px; margin-bottom: 5px;">ไปราชการด้วย</p>
    <div style="margin-left: 2cm;">
        <span class="checkbox-box"><?php echo ($trip['vehicle_type'] === 'รถยนต์ส่วนตัว') ? '✓' : ''; ?></span> รถยนต์ส่วนตัว หมายเลขทะเบียน <b><?php echo htmlspecialchars($trip['vehicle_license_plate']); ?></b><br>
        <span class="checkbox-box"><?php echo ($trip['vehicle_type'] === 'รถยนต์ราชการ') ? '✓' : ''; ?></span> รถยนต์ราชการ หมายเลขทะเบียน <b><?php echo htmlspecialchars($trip['vehicle_license_plate']); ?></b> พนักงานขับรถ ระบุ <b><?php echo htmlspecialchars($trip['driver_name']); ?></b><br>
        <span class="checkbox-box"><?php echo ($trip['vehicle_type'] === 'อื่นๆ') ? '✓' : ''; ?></span> อื่นๆ ระบุ <b><?php echo htmlspecialchars($trip['driver_name']); ?></b>
    </div>

    <p style="text-indent: 2.5cm; margin-top: 25px;">จึงเรียนมาเพื่อโปรดพิจารณา</p>
    
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="text-align: center;">
                ลงชื่อ........................................................<br>
                (<?php echo htmlspecialchars($trip['applicant_name']); ?>)<br>
                ตำแหน่ง <?php echo htmlspecialchars($trip['position']); ?>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; border-top: 1px dotted #ccc; padding-top: 10px;">
        ความเห็นหัวหน้างาน<br>
        ........................................................................................................................................<br>
        ลงชื่อ........................................................ ตำแหน่ง หัวหน้ากลุ่มบริหารงาน..........................................
    </div>
</div>

<!-- หน้าที่ 2 -->
<div class="page">
    <div style="text-align: center; margin-bottom: 30px;">- ๒ -</div>
    
    <div style="margin-bottom: 60px;">
        <b>ความเห็นรองผู้อำนวยการกลุ่มบริหารงานบุคคล</b><br>
        ........................................................................................................................................................................<br><br><br>
        <div style="text-align: center; width: 320px; margin-left: auto;">
            ลงชื่อ........................................................<br>
            (นางโรสนาร์นีย์ บุญณะ)<br>
            ตำแหน่ง รองผู้อำนวยการกลุ่มบริหารงานบุคคล
        </div>
    </div>

    <div>
        <b>ความเห็นผู้อำนวยการสถานศึกษา</b><br>
        ........................................................................................................................................................................<br><br><br>
        <div style="text-align: center; width: 320px; margin-left: auto;">
            ลงชื่อ........................................................<br>
            (ว่าที่ร้อยโท จักเพชร พรหมยศ)<br>
            ตำแหน่ง ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
        </div>
    </div>
</div>

<!-- หน้าที่ 3: แสดงเมื่อมีรายชื่อแนบ -->
<?php if(count($teachers) > 0 || count($students) > 0): ?>
<div class="page">
    <div style="text-align: center; margin-bottom: 20px;">- ๓ -</div>
    <div class="text-center" style="font-weight: bold; font-size: 18pt; margin-bottom: 20px;">รายชื่อครู/นักเรียนร่วมไปราชการ</div>
    <p><b>เรื่อง</b> <?php echo htmlspecialchars($trip['subject']); ?></p>
    
    <?php if(count($teachers) > 0): ?>
    <b>ครู</b>
    <ol style="margin-top: 5px;">
        <?php foreach($teachers as $t): ?>
            <li style="margin-bottom: 6px;"><?php echo htmlspecialchars($t['full_name']); ?> &nbsp;&nbsp;&nbsp; ตำแหน่ง/วิทยฐานะ: <?php echo htmlspecialchars($t['info_detail']); ?></li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if(count($students) > 0): ?>
    <br>
    <b>นักเรียน</b>
    <ol style="margin-top: 5px;">
        <?php foreach($students as $s): ?>
            <li style="margin-bottom: 6px;"><?php echo htmlspecialchars($s['full_name']); ?> &nbsp;&nbsp;&nbsp; ระดับชั้น: <?php echo htmlspecialchars($s['info_detail']); ?></li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
