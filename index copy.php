<?php
    session_start();
    require_once 'config.php';
    $isLoggedIn = isset($_SESSION['user_id']);

    $stmt = $conn->query("SELECT p.*, c.category_name
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
  body { background:#f8f9fa; }
  .card-product { transition:.2s; }
  .card-product:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,.15); }
  .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .price { font-size:1.1rem; font-weight:700; }
</style>
</head>
<body class="container mt-4">
    <div id="success-message" class="success-message">
        <i class="fa-solid fa-check-circle mr-2"></i>เพิ่มสินค้าลงในตะกร้าแล้ว!
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>รายการสินค้า</h1>
            <div>
                <?php if ($isLoggedIn): ?>
                    <span class="me-3">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?> (<?=
                    $_SESSION['role'] ?>)</span>
                    <a href="profile.php" class="btn btn-info">ข้อมูลส่วนตัว</a>
                    <a href="cart.php" class="btn btn-warning">ดูตะกร้า</a>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-success">เข้าสู่ระบบ</a>
                    <a href="register.php" class="btn btn-primary">สมัครสมาชิก</a>
                    <?php endif; ?>
            </div>
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
                                            <form action="cart.php" method="post" class="d-inline" onsubmit="showSuccessMessage(event)">
                                                <input type="hidden" name="product_id" value="<?= $product['product_id']?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-sm btn-success">เพิ่มในตะกร้า</button>
                                            </form>
                                    <?php else: ?>
                                <small class="text-muted">เข้าสู่ระบบเพื่อสั่งซื้อ</small>
                            <?php endif; ?>
                        <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-primary float-end">ดูรายละเอียด</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSuccessMessage(event) {
            event.preventDefault();
            const message = document.getElementById('success-message');
            message.classList.add('show');
            setTimeout(() => {
                message.classList.remove('show');
                event.target.submit();
            }, 2000);
        }
    </script>
</body>
</html>
