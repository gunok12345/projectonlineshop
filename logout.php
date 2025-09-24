<?php
session_start(); // เริ่มต ้น session เพอื่ จัดกำรกำรเขำ้สรู่ ะบบ
session_unset(); // ล้ำงค่ำใน session
session_destroy(); // ท ำลำย session ทั้งหมด
header("Location: login.php"); // เปลยี่ นเสน้ ทำงไปยังหนำ้ login.php
exit; // หยดุ กำรท ำงำนของสครปิ ตห์ ลังจำกเปลยี่ นเสน้ ทำง
?>