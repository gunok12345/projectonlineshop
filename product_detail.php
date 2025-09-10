<?php
session_start();
require_once 'config.php'; // $conn = PDO

// ---- ตรวจสอบและรับค่า id แบบปลอดภัย ----
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$product_id = (int)$_GET['id'];

// ---- ดึงข้อมูลสินค้า (ใช้ named placeholder) ----
$sql = "SELECT p.*, c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "<!doctype html><meta charset='utf-8'><div style='font:16px/1.6 sans-serif;padding:24px'>ไม่พบสินค้าที่คุณต้องการ</div>";
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
$stock = (int)($product['stock'] ?? 0);

// ---- CSRF สำหรับเพิ่มลงตะกร้า ----
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// ---- Helper: สร้าง URL จาก path ภายใน (รองรับเว็บที่อยู่ใน subfolder) ----
function web_path(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return ($base === '/' || $base === '\\') ? '/' . ltrim($rel, '/')
         : ($base === '' ? '' : $base) . '/' . ltrim($rel, '/');
}

// ---- เลือกรูปสินค้า: uploads/products/{id}.(jpg|png|jpeg|webp) -> image_url -> image -> Unsplash ----
function product_image_url(array $p): string {
    $id = (int)($p['product_id'] ?? 0);
    if ($id > 0) {
        $exts = ['jpg','png','jpeg','webp'];
        foreach ($exts as $ext) {
            $fs = __DIR__ . "/uploads/products/{$id}.{$ext}";
            if (is_file($fs)) return web_path("uploads/products/{$id}.{$ext}");
        }
    }
    if (!empty($p['image_url']) && filter_var($p['image_url'], FILTER_VALIDATE_URL)) {
        return $p['image_url'];
    }
    if (!empty($p['image'])) {
        $file = 'uploads/products/' . basename($p['image']);
        if (is_file(__DIR__ . '/' . $file)) return web_path($file);
    }
    // Unsplash ตามชื่อ/หมวด
    $query = urlencode(trim(($p['product_name'] ?? '') . ' ' . ($p['category_name'] ?? '')));
    return "https://source.unsplash.com/960x640/?{$query}";
}

$fallbackUrl = web_path('assets/no-image.png');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['product_name']) ?> | รายละเอียดสินค้า</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#f8f9fa; }
  .card { border: none; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
  .product-title { font-weight: 700; }
  .price { font-size: 1.4rem; font-weight: 800; }
  .img-wrap { background:#fff; border-radius:.75rem; overflow:hidden; }
  .qty-input { max-width: 140px; }
</style>
</head>
<body class="container py-4">

<!-- Breadcrumb / Back -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-2">
    <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
    <li class="breadcrumb-item"><a href="index.php?cat=<?= (int)($product['category_id'] ?? 0) ?>"><?= htmlspecialchars($product['category_name'] ?? 'หมวดหมู่') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['product_name']) ?></li>
  </ol>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> กลับหน้ารายการสินค้า</a>
</nav>

<!-- Product -->
<div class="card">
  <div class="row g-0">
    <div class="col-lg-6 p-3 p-lg-4">
      <div class="img-wrap">
        <img src="<?= htmlspecialchars(product_image_url($product)) ?>"
             class="img-fluid w-100"
             alt="<?= htmlspecialchars($product['product_name']) ?>"
             referrerpolicy="no-referrer"
             onerror="this.onerror=null;this.src='<?= htmlspecialchars($fallbackUrl) ?>';">
      </div>
    </div>
    <div class="col-lg-6 p-3 p-lg-4">
      <h1 class="h3 product-title mb-2"><?= htmlspecialchars($product['product_name']) ?></h1>
      <?php if (!empty($product['category_name'])): ?>
        <div class="mb-2">
          <span class="badge text-bg-secondary"><i class="bi bi-tags"></i> <?= htmlspecialchars($product['category_name']) ?></span>
        </div>
      <?php endif; ?>

      <div class="mb-3 text-muted">
        เพิ่มเมื่อ: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($product['created_at'] ?? 'now'))) ?>
      </div>

      <p class="mt-3"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

      <div class="d-flex align-items-center gap-3 my-3">
        <div class="price text-primary"><?= number_format((float)$product['price'], 2) ?> บาท</div>
        <?php if ($stock > 0): ?>
          <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> มีสินค้า (<?= $stock ?> ชิ้น)</span>
        <?php else: ?>
          <span class="badge text-bg-danger"><i class="bi bi-x-circle"></i> สินค้าหมด</span>
        <?php endif; ?>
      </div>

      <?php if ($isLoggedIn): ?>
        <form action="cart.php" method="post" class="row gy-2 gx-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">

          <div class="col-auto">
            <label for="quantity" class="form-label">จำนวน</label>
            <input type="number"
                   class="form-control qty-input"
                   name="quantity" id="quantity"
                   value="<?= $stock > 0 ? 1 : 0 ?>"
                   min="1" max="<?= max(1, $stock) ?>"
                   <?= $stock > 0 ? '' : 'disabled' ?>
                   required>
          </div>

          <div class="col-auto">
            <button type="submit" class="btn btn-success"
                    <?= $stock > 0 ? '' : 'disabled' ?>>
              <i class="bi bi-cart-plus"></i> เพิ่มในตะกร้า
            </button>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-info mt-3">
          <i class="bi bi-info-circle"></i> กรุณาเข้าสู่ระบบเพื่อสั่งซื้อสินค้า
          <div class="mt-2">
            <a href="login.php" class="btn btn-primary btn-sm">เข้าสู่ระบบ</a>
            <a href="register.php" class="btn btn-outline-primary btn-sm">สมัครสมาชิก</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- ปุ่มแชร์เล็กๆ -->
      <div class="mt-4">
        <span class="text-muted me-2">แชร์:</span>
        <a class="btn btn-outline-secondary btn-sm" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode((isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" rel="noopener">
          <i class="bi bi-facebook"></i>
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="https://twitter.com/intent/tweet?url=<?= urlencode((isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($product['product_name']) ?>" target="_blank" rel="noopener">
          <i class="bi bi-twitter-x"></i>
        </a>
        <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<i class=&quot;bi bi-clipboard-check&quot;></i> คัดลอกแล้ว';">
          <i class="bi bi-clipboard"></i> คัดลอกลิงก์
        </button>
      </div>

    </div>
  </div>
</div>

<div class="text-center mt-4">
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับหน้ารายการสินค้า</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
