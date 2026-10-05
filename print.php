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
            line-height: 1.6;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm 20mm 20mm 25mm;
            margin: auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            box-sizing: border-box;
            position: relative;
        }
        .header {
            text-align: center;
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .meta-line { margin-bottom: 8px; }
        .content {
            text-indent: 2.5cm;
            margin-top: 15px;
            text-align: justify;
        }
        .sign-area {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .sign-box {
            width: 48%;
            text-align: center;
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
    <a href="form.php" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; border-radius: 4px; display: inline-block;">กลับหน้าฟอร์ม</a>
    <?php if (!empty($trip['attached_file'])): ?>
        <a href="uploads/<?php echo htmlspecialchars($trip['attached_file']); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #198754; color: white; border-radius: 4px; display: inline-block;">📎 ดูไฟล์แนบ</a>
    <?php endif; ?>
</div>

<div class="page">
    <div class="header">บันทึกข้อความ</div>
    
    <div class="meta-line"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ โทร. 0-7528-1288</div>
    <div style="display: flex; justify-content: space-between;" class="meta-line">
        <div><strong>ที่:</strong> <?php echo htmlspecialchars($trip['doc_number']); ?></div>
        <div><strong>วันที่:</strong> <?php echo thai_date($trip['created_date']); ?></div>
    </div>
    <div class="meta-line"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</div>
    <hr style="border: 0.5px solid #000; margin: 10px 0 20px 0;">

    <div class="meta-line"><strong>เรียน:</strong> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>

    <div class="content">
        ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name']); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position']); ?> <?php echo !empty($trip['academic_standing']) ? 'วิทยฐานะ ' . htmlspecialchars($trip['academic_standing']) : ''; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department']); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ <?php echo htmlspecialchars($trip['subject']); ?> ณ <?php echo htmlspecialchars($trip['destination']); ?>
    </div>

    <div class="content" style="text-indent: 2.5cm; margin-top: 10px;">
        โดยมีกำหนดการตั้งแต่วันที่ <?php echo thai_date($trip['start_date']); ?> ถึงวันที่ <?php echo thai_date($trip['end_date']); ?> เดินทางโดย <?php echo htmlspecialchars($trip['vehicle_type']); ?> <?php echo !empty($trip['vehicle_license_plate']) ? 'หมายเลขทะเบียน ' . htmlspecialchars($trip['vehicle_license_plate']) : ''; ?> ในการเดินทางไปราชการครั้งนี้<?php echo htmlspecialchars($trip['expense_type']); ?>
    </div>

    <div class="content" style="text-indent: 2.5cm; margin-top: 10px;">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ส่วนลายเซ็น 2 ฝั่ง -->
    <div class="sign-area">
        <div class="sign-box">
            ความเห็นของหัวหน้ากลุ่มสาระฯ/หัวหน้ากลุ่มงาน<br>
            ...................................................................<br><br>
            ลงชื่อ.......................................................<br>
            ( <?php echo htmlspecialchars(!empty($trip['head_name']) ? $trip['head_name'] : '.......................................................'); ?> )<br>
            หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้ากลุ่มงาน
        </div>

        <div class="sign-box">
            <br><br>
            ลงชื่อ.......................................................<br>
            ( <?php echo htmlspecialchars($trip['applicant_name']); ?> )<br>
            ตำแหน่ง <?php echo htmlspecialchars($trip['position']); ?>
        </div>
    </div>

    <div style="margin-top: 40px; border: 1px solid #000; padding: 15px; width: 60%; margin-left: auto;">
        คำสั่ง / คำอนุมัติ:<br>
        [ &nbsp; ] อนุมัติ &emsp;&emsp;&emsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก .....................................<br><br>
        ลงชื่อ.......................................................<br>
        ( ....................................................... )<br>
        ตำแหน่ง ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
    </div>
</div>

</body>
</html>
