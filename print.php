<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM official_trips WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("ไม่พบข้อมูลคำร้อง");
}

// ตรวจสอบว่าคำร้องถูกยกเลิกหรือไม่
if ($trip['status'] === 'cancelled') {
    die("<div style='font-family: sans-serif; text-align: center; margin-top: 80px; color: #c0392b;'>
            <h2>🚫 คำขอไปราชการนี้ถูกยกเลิกแล้ว</h2>
            <p>เหตุผล: " . htmlspecialchars($trip['cancel_reason'] ?: 'เจ้าหน้าที่ยกเลิกคำขอ') . "</p>
            <p style='color: #7f8c8d;'>ยกเลิกเมื่อ: " . $trip['cancelled_at'] . "</p>
            <br><a href='index.php' style='color: #3498db; text-decoration: none; font-weight: bold;'>← กลับไปหน้าสรุปข้อมูล (Dashboard)</a>
         </div>");
}

$part_stmt = $conn->prepare("SELECT * FROM trip_participants WHERE trip_id = ?");
$part_stmt->bind_param("i", $id);
$part_stmt->execute();
$participants = $part_stmt->get_result();

$teachers = [];
$students = [];
while ($row = $participants->fetch_assoc()) {
    if ($row['participant_type'] === 'teacher') {
        $teachers[] = $row;
    } else {
        $students[] = $row;
    }
}

function thai_date($date_str) {
    if (!$date_str || $date_str == '0000-00-00') return "................................";
    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $time = strtotime($date_str);
    $d = date('j', $time);
    $m = $months[date('n', $time)];
    $y = date('Y', $time) + 543;
    return "$d $m $y";
}

$expense_text = "โดยไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการครั้งนี้";
if ($trip['expense_type'] === 'full_claim') {
    $expense_text = "โดยขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ";
} elseif ($trip['expense_type'] === 'specific_claim') {
    $detail = !empty($trip['expense_specific_details']) ? $trip['expense_specific_details'] : "................................";
    $expense_text = "โดยขอเบิกค่าใช้จ่ายเฉพาะ " . htmlspecialchars($detail);
}

$vehicle_text = "";
if ($trip['vehicle_type'] === 'personal_car') {
    $plate = !empty($trip['vehicle_license_plate']) ? htmlspecialchars($trip['vehicle_license_plate']) : "................................";
    $vehicle_text = "และเดินทางด้วยรถยนต์ส่วนตัว หมายเลขทะเบียน " . $plate;
} elseif ($trip['vehicle_type'] === 'official_car') {
    $plate = !empty($trip['vehicle_license_plate']) ? htmlspecialchars($trip['vehicle_license_plate']) : "................................";
    $driver = !empty($trip['driver_name']) ? htmlspecialchars($trip['driver_name']) : "................................";
    $vehicle_text = "และเดินทางด้วยรถยนต์ราชการ หมายเลขทะเบียน " . $plate . " พนักงานขับรถคือ " . $driver;
} else {
    $detail = !empty($trip['driver_name']) ? htmlspecialchars($trip['driver_name']) : "................................";
    $vehicle_text = "และเดินทางด้วย " . $detail;
}

$head_name_display = !empty($trip['head_name']) ? "( " . htmlspecialchars($trip['head_name']) . " )" : "(......................................................)";
$head_dept_display = !empty($trip['head_department']) ? htmlspecialchars($trip['head_department']) : "...................";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุญาตไปราชการ</title>
    <style>
        @page { 
            size: A4; 
            margin: 15mm 20mm; 
        }
        body { 
            font-family: "TH Sarabun New", "Sarabun", -apple-system, BlinkMacSystemFont, sans-serif; 
            font-size: 16pt; 
            line-height: 1.25; 
            color: #000; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa; 
        }
        .page-container { 
            width: 210mm; 
            min-height: 297mm; 
            margin: 0 auto; 
            background: white; 
            padding: 20mm 20mm; 
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header { 
            position: relative; 
            text-align: center; 
            min-height: 1.5cm;
            margin-bottom: 10px; 
        }
        .garuda { 
            width: 1.5cm; 
            height: 1.5cm; 
            position: absolute;
            left: 0;
            top: 0;
            object-fit: contain;
        }
        .memo-title { 
            font-size: 29pt; 
            font-weight: bold; 
            line-height: 1.5cm;
            display: inline-block;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            margin-bottom: 8px; 
        }
        .info-table td { 
            font-size: 16pt; 
            vertical-align: top; 
            padding: 2px 0; 
        }
        .content { 
            text-align: justify; 
            text-justify: inter-cluster; 
            margin-top: 8px; 
            line-height: 1.35;
        }
        .indent { 
            text-indent: 2.5cm; 
        }
        .sign-area { 
            width: 100%; 
            margin-top: 25px; 
        }
        .sign-box { 
            float: right; 
            width: 320px; 
            text-align: center; 
        }
        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            border-top: 1px dashed #aaa;
            padding-top: 15px;
        }
        .approval-table td {
            vertical-align: top;
            text-align: center;
            font-size: 15pt;
            line-height: 1.4;
        }
        .opinion-lines {
            display: inline-block;
            text-align: center;
            width: 85%;
            margin: 10px auto;
        }
        .page-break { 
            page-break-before: always; 
            margin-top: 40px; 
            padding-top: 15px;
        }
        
        /* แถบปุ่มควบคุมด้านบน */
        .no-print { 
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px; 
        }
        .btn-print { 
            font-size: 16px; 
            padding: 10px 22px; 
            background: #27ae60; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 5px rgba(39, 174, 96, 0.3);
        }
        .btn-print:hover { background: #219150; }
        .btn-back-dashboard {
            font-size: 15px;
            padding: 10px 18px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 5px rgba(108, 117, 125, 0.3);
        }
        .btn-back-dashboard:hover { background: #5a6268; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .page-container { width: 100%; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="index.php" class="btn-back-dashboard">← กลับหน้าหลัก (Dashboard)</a>
    <button class="btn-print" onclick="window.print()">🖨️ พิมพ์บันทึกข้อความ / บันทึกเป็น PDF</button>
</div>

<div class="page-container">
    <div class="header">
        <img src="garuda.png" class="garuda" alt="ตราครุฑ" onerror="this.onerror=null; this.src='https://cdn.jsdelivr.net/gh/lazywasabi/thai-gov-garuda@main/garuda.png';">
        <span class="memo-title">บันทึกข้อความ</span>
    </div>

    <table class="info-table">
        <tr>
            <td colspan="2"><b>ส่วนราชการ</b> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</td>
        </tr>
        <tr>
            <td style="width: 58%;"><b>ที่</b> ............................................................</td>
            <td style="width: 42%;"><b>วันที่</b> <?php echo thai_date($trip['created_date']); ?></td>
        </tr>
        <tr>
            <td colspan="2"><b>เรื่อง</b> ขออนุญาตไปราชการ</td>
        </tr>
        <tr>
            <td colspan="2"><b>เรียน</b> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</td>
        </tr>
        <tr>
            <td colspan="2">
                <b>สิ่งที่แนบมาด้วย</b> 
                1. รายชื่อครู/นักเรียนร่วมไปราชการ (ถ้ามี)<br>
                <span style="margin-left: 95px;">2. บันทึกข้อความขออนุญาตสอนชดเชย วช.18</span>
            </td>
        </tr>
    </table>

    <div class="content">
        <p class="indent" style="margin: 0;">
            ด้วย ข้าพเจ้า <b><?php echo htmlspecialchars($trip['applicant_name']); ?></b>
            ตำแหน่ง <b><?php echo htmlspecialchars($trip['position']); ?></b>
            <?php if (!empty($trip['academic_standing'])): ?>วิทยฐานะ <b><?php echo htmlspecialchars($trip['academic_standing']); ?></b><?php endif; ?>
            สังกัด <b><?php echo htmlspecialchars($trip['department']); ?></b>
            มีความประสงค์จะขออนุญาตไปราชการ เรื่อง <b><?php echo htmlspecialchars($trip['subject']); ?></b>
            ณ <b><?php echo htmlspecialchars($trip['destination']); ?></b>
            <?php if (!empty($trip['ref_document'])): ?>ตามหนังสือเลขที่/คำสั่งที่ <b><?php echo htmlspecialchars($trip['ref_document']); ?></b><?php endif; ?>
            <?php if (!empty($trip['ref_date'])): ?> ลงวันที่ <b><?php echo thai_date($trip['ref_date']); ?></b><?php endif; ?>
            ตั้งแต่วันที่ <b><?php echo thai_date($trip['start_date']); ?></b>
            ถึงวันที่ <b><?php echo thai_date($trip['end_date']); ?></b>
            <?php if (!empty($trip['half_day_time'])): ?>(เริ่มไปราชการตั้งแต่เวลา <b><?php echo htmlspecialchars($trip['half_day_time']); ?></b>)<?php endif; ?>
            <?php echo $expense_text; ?>
            <?php echo $vehicle_text; ?>
        </p>

        <p class="indent" style="margin-top: 14px;">จึงเรียนมาเพื่อโปรดพิจารณา</p>
    </div>

    <!-- ลายเซ็นผู้ยื่นคำร้อง -->
    <div class="sign-area" style="overflow: hidden;">
        <div class="sign-box">
            ลงชื่อ............................................................<br>
            ( <?php echo htmlspecialchars($trip['applicant_name']); ?> )<br>
            ตำแหน่ง <?php echo htmlspecialchars($trip['position']); ?>
        </div>
    </div>

    <div style="clear: both;"></div>

    <!-- ตารางลงนามหัวหน้างาน และ รอง ผอ. -->
    <table class="approval-table">
        <tr>
            <td style="width: 50%; padding-right: 15px;">
                <b>ความเห็นหัวหน้างาน</b><br>
                <div class="opinion-lines">
                    ...........................................................................<br>
                    ...........................................................................
                </div>
                ลงชื่อ........................................................<br>
                <?php echo $head_name_display; ?><br>
                ตำแหน่ง หัวหน้ากลุ่มบริหารงาน<?php echo $head_dept_display; ?>
            </td>
            <td style="width: 50%; padding-left: 15px;">
                <b>ความเห็นรองผู้อำนวยการกลุ่มบริหารงานบุคคล</b><br>
                <div class="opinion-lines">
                    ...........................................................................<br>
                    ...........................................................................
                </div>
                ลงชื่อ........................................................<br>
                (นางโรสนาร์นีย์ บุญณะ)<br>
                ตำแหน่ง รองผู้อำนวยการกลุ่มบริหารงานบุคคล
            </td>
        </tr>
    </table>

    <!-- ส่วนผู้อำนวยการ -->
    <div style="margin-top: 30px; text-align: center; font-size: 15pt; line-height: 1.5;">
        <b>ความเห็นผู้อำนวยการสถานศึกษา</b><br>
        [ &nbsp; ] อนุมัติ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] ไม่อนุมัติ<br><br>
        ลงชื่อ........................................................<br>
        (ว่าที่ร้อยโทจักรเพชร์ พรมยศ)<br>
        ตำแหน่ง ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
    </div>

    <!-- หน้าเอกสารแนบรายชื่อผู้ร่วมเดินทาง -->
    <?php if (count($teachers) > 0 || count($students) > 0): ?>
    <div class="page-break">
        <div style="text-align: center; font-weight: bold; font-size: 18pt; margin-bottom: 20px;">
            รายชื่อครู/นักเรียนร่วมไปราชการ<br>
            <span style="font-size: 16pt; font-weight: normal;">เรื่อง <?php echo htmlspecialchars($trip['subject']); ?></span>
        </div>

        <?php if (count($teachers) > 0): ?>
        <p style="font-weight: bold; margin-bottom: 6px;">ครู</p>
        <ol style="margin-top: 0; padding-left: 25px;">
            <?php foreach ($teachers as $t): ?>
                <li style="margin-bottom: 6px;">
                    <?php echo htmlspecialchars($t['full_name']); ?> 
                    &nbsp;&nbsp;&nbsp;&nbsp;ตำแหน่ง/วิทยฐานะ: <?php echo htmlspecialchars($t['info_detail']); ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>

        <?php if (count($students) > 0): ?>
        <p style="font-weight: bold; margin-top: 20px; margin-bottom: 6px;">นักเรียน</p>
        <ol style="margin-top: 0; padding-left: 25px;">
            <?php foreach ($students as $s): ?>
                <li style="margin-bottom: 6px;">
                    <?php echo htmlspecialchars($s['full_name']); ?> 
                    &nbsp;&nbsp;&nbsp;&nbsp;ระดับชั้น: <?php echo htmlspecialchars($s['info_detail']); ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>