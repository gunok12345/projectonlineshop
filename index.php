<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>
<body>
    <H1>ยินดีต้อนรับสู่หน้าหลัก</H1>
    <p>ชื่อผู้ใช้ของคุณคือ: <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</p>
    <a class="btn btn-danger" href  ="logout.php">ออกจากระบบ</a>

</body>
</html>