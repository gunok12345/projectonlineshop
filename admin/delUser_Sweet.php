<?php
require '../config.php';
require 'auth_admin.php'; // ตรวจสอบสิทธิ์ admin

// ตรวจสอบการส่งข้อมูลจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['u_id'])) {
    $user_id = $_POST['u_id'];

    // sql ลบผู้ใช้จากฐานข้อมูล
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'member'");
    $stmt->execute([$user_id]);

    // ส่งผลลัพธ์กลับไปยังหน้าผู้ดูแล
    header("Location: users.php");
    exit;
}
?>
