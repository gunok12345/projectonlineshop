<?php
require_once '../config.php';
// ตรวจสอบสิทธิ์ admin
require_once 'auth_admin.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แผงควบคุมผู้ดูแลระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        background-color: #f8f9fa;
    }
    .dashboard-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
</style>
</head>
<body class="container py-5">

    <div class="text-center mb-5">
        <h2 class="fw-bold">📊 ระบบผู้ดูแลระบบ</h2>
        <p class="text-muted">ยินดีต้อนรับ, <span class="fw-semibold text-primary"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <a href="products.php" class="text-decoration-none">
                <div class="card dashboard-card text-center p-4 border-primary">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h5 class="mt-3 text-dark">จัดการสินค้า</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="orders.php" class="text-decoration-none">
                <div class="card dashboard-card text-center p-4 border-success">
                    <i class="bi bi-cart-check fs-1 text-success"></i>
                    <h5 class="mt-3 text-dark">จัดการคำสั่งซื้อ</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="users.php" class="text-decoration-none">
                <div class="card dashboard-card text-center p-4 border-warning">
                    <i class="bi bi-people fs-1 text-warning"></i>
                    <h5 class="mt-3 text-dark">จัดการสมาชิก</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="categories.php" class="text-decoration-none">
                <div class="card dashboard-card text-center p-4 border-dark">
                    <i class="bi bi-tags fs-1 text-dark"></i>
                    <h5 class="mt-3 text-dark">จัดการหมวดหมู่</h5>
                </div>
            </a>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="../logout.php" class="btn btn-outline-secondary">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>

</body>
</html>
