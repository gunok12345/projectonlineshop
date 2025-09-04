<?php
session_start();
require_once '../config.php';
// ตรวจสอบสิทธิ์admin
require_once 'auth_admin.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แผงควบคุมผู้ดูแลระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>ระบบผู้ดูแลระบบ</h2>
<p class="mb-4">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?></p>
<div class="row">
<div class="col-md-4 mb-3">
<a href="products.php" class="btn btn-primary w-100">จัดการสินค้า</a>
</div>
<div class="col-md-4 mb-3">
<a href="orders.php" class="btn btn-success w-100">จัดการคำสั่งซื้อ</a>
</div>
<div class="col-md-4 mb-3">
<a href="users.php" class="btn btn-warning w-100">จัดการสมาชิก</a>
</div>
<div class="col-md-4 mb-3">
<a href="categories.php" class="btn btn-dark w-100">จัดการหมวดหมู่</a>
</div>
</div>
<a href="../logout.php" class="btn btn-secondary mt-3">ออกจากระบบ</a>
</body>
</html>
