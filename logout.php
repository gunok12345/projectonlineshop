<?php
session_start(); // เริ่มต้น
session_unset(); //     
session_destroy(); // 
header("Location: login.php"); // 
exit; // หยุดการทำงานของสคริปต์หลังจากเปลี่ยนเส้นทาง
?>
