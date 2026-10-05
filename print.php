<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM official_trips WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    die("ไม่พบข้อมูลเอกสาร");
}

function thai_date($date_str) {
    if (!$date_str) return "";
    $thai_months = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];
    $time = strtotime($date_str);
    $d = date('j', $time);
    $m = $thai_months[intval(date('n', $time))];
    $y = date('Y', $time) + 543;
    return "$d $m $y";
}

// แปลงคำศัพท์พาหนะ
$vehicle = $trip['vehicle_type'] ?? '';
if ($vehicle === 'personal_car') {
    $vehicle_text = 'รถยนต์ส่วนบุคคล';
} elseif ($vehicle === 'school_bus') {
    $vehicle_text = 'รถยนต์ส่วนกลางของโรงเรียน';
} elseif ($vehicle === 'public_transport') {
    $vehicle_text = 'รถโดยสารประจำทาง';
} else {
    $vehicle_text = !empty($vehicle) ? $vehicle : 'รถยนต์ส่วนบุคคล';
}

// แปลงคำศัพท์งบประมาณ
$expense = $trip['expense_type'] ?? '';
if ($expense === 'no_expense' || $expense === 'ไม่ขอเบิกงบประมาณ') {
    $expense_text = 'ไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการ';
} elseif ($expense === 'school_budget' || $expense === 'ขอเบิกจากต้นสังกัด') {
    $expense_text = 'ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการจากโรงเรียน';
} elseif ($expense === 'organizer_budget' || $expense === 'ขอเบิกจากผู้จัด') {
    $expense_text = 'ขอเบิกค่าใช้จ่ายจากหน่วยงานผู้จัด';
} else {
    $expense_text = !empty($expense) ? $expense : 'ไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการ';
}

$head_title = !empty($trip['head_department']) ? "หัวหน้า" . $trip['head_department'] : "หัวหน้าฝ่าย";
$head_name_display = !empty($trip['head_name']) ? $trip['head_name'] : '.......................................................';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุมัติไปราชการ</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 16pt;
            line-height: 1.5;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 20mm 25mm;
            margin: auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            box-sizing: border-box;
            position: relative;
        }
        .header-wrap {
            position: relative;
            text-align: center;
            height: 90px;
            margin-bottom: 10px;
        }
        .garuda {
            position: absolute;
            left: 0;
            top: 0;
            width: 65px;
            height: auto;
        }
        .title-doc {
            font-size: 26pt;
            font-weight: bold;
            line-height: 80px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .content {
            text-align: justify;
            text-indent: 2.5cm;
            margin-top: 10px;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            line-height: 1.6;
        }
        .director-box {
            margin-top: 25px;
            margin-left: auto;
            width: 55%;
            border: 1px solid #000;
            padding: 12px 15px;
            line-height: 1.6;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; min-height: auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="text-center no-print" style="margin-bottom: 15px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px;">สั่งพิมพ์เอกสาร (Print)</button>
    <a href="index.php" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; border-radius: 4px; display: inline-block;">หน้ารายการทั้งหมด</a>
    <?php if (!empty($trip['approved_file'])): ?>
        <a href="uploads/<?php echo htmlspecialchars($trip['approved_file']); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #198754; color: white; border-radius: 4px; display: inline-block;">📥 ดาวน์โหลดคำสั่งที่อนุมัติแล้ว</a>
    <?php endif; ?>
</div>

<div class="page">
    <!-- ส่วนหัวหนังสือราชการ + ครุฑ -->
    <div class="header-wrap">
        <img src="garuda.png" alt="ครุฑ" class="garuda" onerror="this.style.display='none'">
        <span class="title-doc">บันทึกข้อความ</span>
    </div>
    
    <table class="meta-table">
        <tr>
            <td colspan="2"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ โทร. 0-7528-1288</td>
        </tr>
        <tr>
            <td style="width: 50%;"><strong>ที่:</strong> <?php echo htmlspecialchars($trip['doc_number'] ?? ''); ?></td>
            <td style="width: 50%;"><strong>วันที่:</strong> <?php echo thai_date($trip['created_date'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</td>
        </tr>
    </table>
    
    <hr style="border: 0; border-top: 1.5px solid #000; margin: 5px 0 15px 0;">

    <div><strong>เรียน:</strong> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>

    <div class="content">
        ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?> <?php echo !empty($trip['academic_standing']) ? 'วิทยฐานะ ' . htmlspecialchars($trip['academic_standing']) : ''; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department'] ?? ''); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ <?php echo htmlspecialchars($trip['subject'] ?? ''); ?> ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?>
    </div>

    <div class="content">
        โดยมีกำหนดการตั้งแต่วันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?> ถึงวันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?> เดินทางโดย <?php echo htmlspecialchars($vehicle_text); ?> <?php echo !empty($trip['vehicle_license_plate']) ? 'หมายเลขทะเบียน ' . htmlspecialchars($trip['vehicle_license_plate']) : ''; ?> ในการเดินทางไปราชการครั้งนี้<?php echo htmlspecialchars($expense_text); ?>
    </div>

    <div class="content">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ส่วนลายเซ็น ซ้าย-ขวา แบบตารางล็อกตำแหน่งเป๊ะ ไม่เพี้ยน -->
    <table class="sign-table">
        <tr>
            <!-- ฝั่งซ้าย: ความเห็นและลายเซ็นหัวหน้าฝ่าย -->
            <td>
                ความเห็นของ<?php echo htmlspecialchars($head_title); ?><br>
                ...................................................................<br><br>
                ลงชื่อ.......................................................<br>
                ( <?php echo htmlspecialchars($head_name_display); ?> )<br>
                <?php echo htmlspecialchars($head_title); ?>
            </td>

            <!-- ฝั่งขวา: ลายเซ็นผู้ขออนุมัติ -->
            <td>
                <br><br>
                ลงชื่อ.......................................................<br>
                ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
                ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
            </td>
        </tr>
    </table>

    <!-- ส่วนความเห็นของผู้อำนวยการโรงเรียน -->
    <div class="director-box">
        คำสั่ง / คำอนุมัติ:<br>
        [ &nbsp; ] อนุมัติ &emsp;&emsp;&emsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก .............................<br><br>
        <div style="text-align: center;">
            ลงชื่อ.......................................................<br>
            ( ....................................................... )<br>
            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
        </div>
    </div>
</div>

</body>
</html>
