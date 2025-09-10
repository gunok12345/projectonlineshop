<?php
session_start();
require_once 'config.php'; // $conn = PDO

$isLoggedIn = isset($_SESSION['user_id']);

// CSRF สำหรับฟอร์มตะกร้า
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// ดึงหมวดหมู่ (สำหรับฟิลเตอร์)
$catStmt = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// รับค่าค้นหา/กรอง
$q   = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);

// สร้าง where
$where  = "WHERE 1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (p.product_name LIKE :kw OR p.description LIKE :kw)";
    $params[':kw'] = "%{$q}%";
}
if ($cat > 0) {
    $where .= " AND p.category_id = :cat";
    $params[':cat'] = $cat;
}

// ดึงสินค้า
$sql = "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    $where
    ORDER BY p.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Helpers =====

// แปลง path ภายในโปรเจ็กต์ -> URL ถูกต้อง (เผื่อเว็บรันใน subfolder)
function web_path(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return ($base === '/' || $base === '\\') ? '/' . ltrim($rel, '/')
         : ($base === '' ? '' : $base) . '/' . ltrim($rel, '/');
}

// เลือกรูปสินค้า (ลำดับ: id.jpg -> image_url -> image -> Unsplash)
function product_image_url(array $p): string {
    // 1) ไฟล์ตรงกับ product_id: uploads/products/{id}.jpg
    if (!empty($p['product_id'])) {
        $id = (int)$p['product_id'];
        $fsIdJpg = __DIR__ . "/uploads/products/{$id}.jpg";
        if (is_file($fsIdJpg)) {
            return web_path("uploads/products/{$id}.jpg");
        }
        // รองรับสกุลยอดนิยมเพิ่มเติม (png, jpeg, webp)
        $exts = ['png','jpeg','webp'];
        foreach ($exts as $ext) {
            $fs = __DIR__ . "/uploads/products/{$id}.{$ext}";
            if (is_file($fs)) return web_path("uploads/products/{$id}.{$ext}");
        }
    }

    // 2) ลิงก์ภายนอกจาก DB
    if (!empty($p['image_url']) && filter_var($p['image_url'], FILTER_VALIDATE_URL)) {
        return $p['image_url'];
    }

    // 3) ไฟล์จากคอลัมน์ image ใน uploads/products/
    if (!empty($p['image'])) {
        $fileName = basename($p['image']);            // ป้องกัน path traversal
        $fsPath   = __DIR__ . '/uploads/products/' . $fileName;
        if (is_file($fsPath)) {
            return web_path('uploads/products/' . $fileName);
        }
    }

    // 4) ไม่มีรูปเลย -> ใช้ Unsplash ตามชื่อ/หมวด
    $name = trim((string)($p['product_name'] ?? ''));
    $cat  = trim((string)($p['category_name'] ?? ''));
    // แมปคำไทย/อังกฤษ -> คีย์เวิร์ด
    $map = [
        'โทรศัพท์'=>'smartphone,phone','มือถือ'=>'smartphone,phone','iphone'=>'iphone smartphone','samsung'=>'samsung phone',
        'โน้ตบุ๊ค'=>'laptop','แล็ปท็อป'=>'laptop','คอมพิวเตอร์'=>'desktop computer',
        'หูฟัง'=>'headphones','ลำโพง'=>'speaker','กล้อง'=>'camera dslr','ทีวี'=>'television',
        'เสื้อ'=>'t shirt clothing','กางเกง'=>'pants clothing','รองเท้า'=>'shoes sneakers',
        'กระเป๋า'=>'bag handbag','นาฬิกา'=>'watch',
        'เฟอร์นิเจอร์'=>'furniture living room','โต๊ะ'=>'table furniture','เก้าอี้'=>'chair furniture','โซฟา'=>'sofa furniture','ครัว'=>'kitchen cookware',
        'อาหาร'=>'food dish','กาแฟ'=>'coffee','ขนม'=>'dessert',
        'เครื่องสำอาง'=>'cosmetics makeup','สกินแคร์'=>'skincare',
        'ของเล่น'=>'toy',
    ];
    $kw = [];
    $hay = mb_strtolower($name.' '.$cat, 'UTF-8');
    foreach ($map as $needle=>$val) {
        if (mb_stripos($hay, mb_strtolower($needle,'UTF-8'), 0, 'UTF-8') !== false) {
            $kw[] = $val;
        }
    }
    if (empty($kw)) $kw[] = trim($name.' '.$cat);
    $query = urlencode(implode(', ', array_unique(array_filter($kw))));
    return "https://source.unsplash.com/640x400/?{$query}";
}

// fallback รูป (ต้องมีไฟล์นี้)
$fallbackUrl = web_path('assets/no-image.png');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>หน้าหลัก | ร้านค้า</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<style>
  body { background:#f8f9fa; }
  .card-product { transition:.2s; }
  .card-product:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,.15); }
  .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .price { font-size:1.1rem; font-weight:700; }
</style>
</head>
<body class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3">📦 รายการสินค้า</h1>
  <div>
    <?php if ($isLoggedIn): ?>
      <span class="me-2">ยินดีต้อนรับ, <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
      <a href="profile.php" class="btn btn-info btn-sm"><i class="bi bi-person"></i> โปรไฟล์</a>
      <a href="cart.php" class="btn btn-warning btn-sm"><i class="bi bi-cart3"></i> ตะกร้า</a>
      <a href="logout.php" class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right"></i> ออกระบบ</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-success btn-sm">เข้าสู่ระบบ</a>
      <a href="register.php" class="btn btn-primary btn-sm">สมัครสมาชิก</a>
    <?php endif; ?>
  </div>
</div>

<!-- ค้นหา/กรอง -->
<form class="row g-2 mb-4" method="get" action="index.php">
  <div class="col-sm-4">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="ค้นหาสินค้า...">
  </div>
  <div class="col-sm-3">
    <select name="cat" class="form-select">
      <option value="0">ทุกหมวดหมู่</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['category_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-primary"><i class="bi bi-search"></i> ค้นหา</button>
    <?php if ($q !== '' || $cat > 0): ?>
      <a href="index.php" class="btn btn-outline-secondary">ล้าง</a>
    <?php endif; ?>
  </div>
</form>

<!-- สินค้า -->
<div class="row">
<?php if (empty($products)): ?>
  <div class="alert alert-warning">ไม่พบสินค้า</div>
<?php else: ?>
  <?php foreach ($products as $product): ?>
    <div class="col-12 col-sm-6 col-lg-4 mb-4">
      <div class="card h-100 card-product">
        <img src="<?= htmlspecialchars(product_image_url($product)) ?>"
             class="card-img-top"
             alt="<?= htmlspecialchars($product['product_name']) ?>"
             referrerpolicy="no-referrer"
             onerror="this.onerror=null;this.src='<?= htmlspecialchars($fallbackUrl) ?>';">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
          <?php if (!empty($product['category_name'])): ?>
            <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
          <?php endif; ?>
          <p class="card-text line-clamp-2"><?= htmlspecialchars($product['description'] ?? '') ?></p>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="price text-primary"><?= number_format((float)$product['price'], 2) ?> บาท</span>
            <a href="product_detail.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-eye"></i> ดูรายละเอียด
            </a>
          </div>
          <?php if ($isLoggedIn): ?>
            <form action="cart.php" method="post" class="mt-2">
              <input type="hidden" name="csrf" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn btn-success w-100 btn-sm">
                <i class="bi bi-cart-plus"></i> เพิ่มลงตะกร้า
              </button>
            </form>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
          <small class="text-muted">
            เพิ่มเมื่อ: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($product['created_at']))) ?>
          </small>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
