<?php
session_start(); // เริ่ม session
require_once 'config.php'; // เชื่อมต่อฐานข้อมูล
if (!isset($_GET['id'])) { // ตรวจสอบว่ามีการส่ง ID ของสินค้าเข้ามาหรือไม่
header("Location: index.php");
exit;
}
$product_id = $_GET['id'];
$stmt = $conn->prepare("SELECT p.*, c.category_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
echo "<h3>ไม่พบสินค้าที่คุณต้องการ</h3>";
exit;
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายละเอียดสินค้า</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<a href="index.php" class="btn btn-secondary mb-3">← กลับหน้ารายการสินค้า</a>
<div class="card">
<div class="card-body">
<h3 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h3>
<h6 class="text-muted">หมวดหมู่: <?= htmlspecialchars($product['category_name']) ?></h6>
<p class="card-text mt-3"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
<p><strong>ราคา:</strong> <?= number_format($product['price'], 2) ?> บาท</p>
<p><strong>คงเหลือ:</strong> <?= $product['stock'] ?> ชิ้น</p>
<?php if ($isLoggedIn): ?>
<form action="cart.php" method="post" class="mt-3">
<input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
<label for="quantity">จำนวน:</label>
<input type="number" name="quantity" id="quantity" value="1" min="1" max="<?=
$product['stock'] ?>" required>
<button type="submit" class="btn btn-success">เพิ่มในตะกร้า</button>
</form>
<?php else: ?>
<div class="alert alert-info mt-3">กรุณาเข้าสู่ระบบเพื่อสั่งซื้อสินค้า</div>
<?php endif; ?>
</div>
</div>
</body>
</html>