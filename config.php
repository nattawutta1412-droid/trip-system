<?php
$host = "sql106.infinityfree.com"; 
$user = "if0_43087663";            
$pass = "รหัสผ่านบัญชี InfinityFree ของคุณ"; 
$db   = "if0_43087663_trip";       

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>