<?php
session_start(); // เริ่ม session

require_once 'config.php'; // เชื่อมต่อฐานข้อมูล
$isLoggedIn = isset($_SESSION['user_id']);// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่

$stmt = $conn->query("SELECT p.*,c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>
<body class = "container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <H1>รายการสินค้า</H1>

        <div>
        <?php
    if ($isLoggedIn) : ?>
        <span class = "me-3">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?> (<?=
    $_SESSION['role'] ?>)</span>
    <a href="profile.php" class="btn btn-info">ข้อมูลส่วนตัว</a>
    <a href="cart.php" class="btn btn-warning">ดูตะกร้าสินค้า</a>
    <a href="logout.php" class="btn btn-danger">ออกจากระบบ</a>
    <?php else: ?>
    <a href="login.php" class="btn btn-success">เข้าสู่ระบบ</a>
    <a href="register.php" class="btn btn-primary">สมัครสมาชิก</a>
        <?php endif; ?>
        </div>
    <!---รายการสินค้าที่จะแสดง--->
    </div>
   <div class="row">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
    <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($product['category_name'])
    ?></h6>
    <p class="card-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    <p><strong>ราคา:</strong> <?= number_format($product['price'], 2) ?> บาท</p>
    <?php if ($isLoggedIn): ?>
    <form action="cart.php" method="post" class="d-inline">
    <input type="hidden" name="product_id" value="<?= $รหัสสินค้า ?>">
    <input type="hidden" name="quantity" value="1">
    <button type="submit" class="btn btn-sm btn-success">เพิ่มในตะกร้า</button>
    </form>
    <?php else: ?>
    <small class="text-muted">เข้าสู่ระบบสู่เพื่อสั่งสินค้า </small>
    <?php endif; ?>
    <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-primary floatend">ดูรายละเอียด</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>


</body>
</html>