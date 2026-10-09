<?php
require_once 'config.php';

// ตั้งค่า Header สำหรับส่งออกไฟล์ Microsoft Word (.doc)
$filename = "teacher_summary_report_" . date('Ymd_His') . ".doc";
header("Content-Type: application/vnd.ms-word; charset=UTF-8");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");

// 1. ดึงข้อมูลสถิติ
$total_teachers = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];
$dept_stats = $conn->query("SELECT department, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY department ORDER BY count DESC");
$academic_stats = $conn->query("SELECT IF(academic_standing IS NULL OR academic_standing='', 'ไม่มีวิทยฐานะ/ครูผู้ช่วย', academic_standing) as standing, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY standing ORDER BY count DESC");

// 2. ดึงรายชื่อบุคลากรทั้งหมด
$teachers = $conn->query("SELECT * FROM teachers WHERE status='ปฏิบัติหน้าที่' ORDER BY id ASC");
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title>รายงานสรุปข้อมูลบุคลากร</title>
<style>
    body {
        font-family: 'TH Sarabun PSK', 'TH Sarabun New', 'Cordia New', sans-serif;
        font-size: 16pt;
        line-height: 1.25;
        color: #000;
    }
    h2, h3, h4 {
        margin: 4px 0;
        text-align: center;
        font-weight: bold;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 15px;
    }
    th, td {
        border: 1px solid #333;
        padding: 5px 8px;
        font-size: 14pt;
    }
    th {
        background-color: #f2f2f2;
        text-align: center;
        font-weight: bold;
    }
    .section-title {
        font-weight: bold;
        font-size: 16pt;
        margin-top: 15px;
        margin-bottom: 5px;
    }
</style>
</head>
<body>

    <!-- หัวเรื่องรายงาน -->
    <h2>รายงานสรุปข้อมูลสารสนเทศและทำเนียบบุคลากรทางการศึกษา</h2>
    <h3>โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</h3>
    <p class="text-center" style="font-size: 14pt; margin-bottom: 20px;">
        ข้อมูล ณ วันที่ <?php echo date('d/m/Y'); ?>
    </p>

    <!-- 1. ภาพรวม -->
    <div class="section-title">1. ข้อมูลภาพรวม</div>
    <p style="margin-left: 20px;">
        จำนวนข้าราชการครูและบุคลากรทางการศึกษาที่ปฏิบัติหน้าที่ทั้งหมด: <strong><?php echo number_format($total_teachers); ?></strong> คน
    </p>

    <!-- 2. สรุปตามกลุ่มสาระฯ -->
    <div class="section-title">2. สรุปสถิติจำนวนแยกตามกลุ่มสาระการเรียนรู้ / กลุ่มงาน</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">ลำดับ</th>
                <th style="width: 60%;">กลุ่มสาระการเรียนรู้ / กลุ่มงาน</th>
                <th style="width: 25%;">จำนวน (คน)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $d_no = 1;
            while ($dp = $dept_stats->fetch_assoc()): 
            ?>
                <tr>
                    <td class="text-center"><?php echo $d_no++; ?></td>
                    <td><?php echo htmlspecialchars($dp['department']); ?></td>
                    <td class="text-center"><?php echo $dp['count']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- 3. สรุปตามวิทยฐานะ -->
    <div class="section-title">3. สรุปสถิติจำนวนแยกตามวิทยฐานะ</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">ลำดับ</th>
                <th style="width: 60%;">วิทยฐานะ</th>
                <th style="width: 25%;">จำนวน (คน)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $s_no = 1;
            while ($ac = $academic_stats->fetch_assoc()): 
            ?>
                <tr>
                    <td class="text-center"><?php echo $s_no++; ?></td>
                    <td><?php echo htmlspecialchars($ac['standing']); ?></td>
                    <td class="text-center"><?php echo $ac['count']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- 4. ทำเนียบรายชื่อบุคลากร -->
    <div class="section-title" style="page-break-before: always;">4. รายชื่อทำเนียบบุคลากรทั้งหมด</div>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">ลำดับ</th>
                <th style="width: 26%;">ชื่อ - นามสกุล</th>
                <th style="width: 14%;">ตำแหน่ง</th>
                <th style="width: 16%;">วิทยฐานะ</th>
                <th style="width: 22%;">กลุ่มสาระฯ / สังกัด</th>
                <th style="width: 14%;">เบอร์โทรศัพท์</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            while ($t = $teachers->fetch_assoc()): 
            ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                    <td><?php echo htmlspecialchars($t['academic_standing'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($t['phone_number'] ?: '-'); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
