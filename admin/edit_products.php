<?php

require_once '../config.php';
require_once 'auth_admin.php'; 


// ตรวจสอบวำ่ ไดส้ ง่ id สนิ คำ้มำหรอื ไม่
if (!isset($_GET['id'])) {
header("Location: products.php");
exit;
}

$product_id = $_GET['id'];

//ดึงข้อมูลสินค้า
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
echo "<h3> ไม่พบข้อมูลสินค้า </h3>";
exit;
}

// ดึงหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// เมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$name = trim($_POST['product_name']);
$description = trim($_POST['description']);
$price = (float)$_POST['price'];
$stock = (int)$_POST['stock'];
$category_id = (int)$_POST['category_id'];

// ค่ำรูปเดิมจำกฟอร์ม
$oldImage = $_POST['old_image'] ?? null;
$removeImage = isset($_POST['remove_image']); // true/false
if ($name && $price > 0) {

// เตรียมตัวแปรรูปที่จะบันทึก
$newImageName = $oldImage; // default: คงรูปเดิมไว้
// 1) ถ ้ำมีติ๊ก "ลบรูปเดิม" → ตั้งให้เป็น null
if ($removeImage) {
$newImageName = null;
}
// 2) ถ ้ำมีอัปโหลดไฟล์ใหม่ → ตรวจแลว้เซฟไฟลแ์ละตัง้ชอื่ ใหมท่ ับคำ่
if (!empty($_FILES['product_image']['name'])) {
$file = $_FILES['product_image'];
// ตรวจชนิดไฟล์แบบง่ำย (แนะน ำ: ตรวจ MIME จริงด ้วย finfo)
$allowed = ['image/jpeg', 'image/png'];
if (in_array($file['type'], $allowed, true) && $file['error'] === UPLOAD_ERR_OK) {
// สรำ้งชอื่ ไฟลใ์หม่
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$newImageName = 'product_' . time() . '.' . $ext;
$uploadDir = realpath(__DIR__ . '/../product_images');
$destPath = $uploadDir . DIRECTORY_SEPARATOR . $newImageName;
// ย้ำยไฟล์อัปโหลด
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
// ถ ้ำย้ำยไม่ได ้ อำจตั้ง flash message แลว้คงใชรู้ปเดมิ ไว ้
$newImageName = $oldImage;
}
}
}

// อัปเดต DB
$sql = "UPDATE products
SET product_name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ?
WHERE product_id = ?";
$args = [$name, $description, $price, $stock, $category_id, $newImageName, $product_id];
$stmt = $conn->prepare($sql);
$stmt->execute($args);
// ลบไฟล์เก่ำในดิสก์ ถ ้ำ:
// - มีรูปเดิม ($oldImage) และ
// - เกดิ กำรเปลยี่ นรปู (อัปโหลดใหมห่ รอื สั่งลบรปู เดมิ)
if (!empty($oldImage) && $oldImage !== $newImageName) {
$baseDir = realpath(__DIR__ . '/../product_images');
$filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $oldImage);
if ($filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
@unlink($filePath);
}
}
header("Location: products.php");
exit;
}
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
  :root{
    --brand-1:#4f46e5; /* indigo-600 */
    --brand-2:#06b6d4; /* cyan-500 */
    --ink:#0f172a;     /* slate-900 */
    --muted:#64748b;  /* slate-500 */
  }
  body{
    background:
      radial-gradient(55rem 35rem at 85% -10%, rgba(6,182,212,.18), transparent 60%),
      radial-gradient(50rem 33rem at -10% 0%, rgba(79,70,229,.18), transparent 60%),
      #0b1020;
    min-height:100vh;
  }
  .topbar{
    background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
  }
  .topbar .title{
    color:#fff; letter-spacing:.3px;
  }
  .card.clean{
    border: 1px solid rgba(148,163,184,.2);
    border-radius: 1rem;
    box-shadow: 0 12px 36px rgba(2,6,23,.35);
    overflow: hidden;
  }
  .card-header{
    background: rgba(248,250,252,.6);
    border-bottom: 1px solid rgba(148,163,184,.25);
    backdrop-filter: blur(4px);
  }
  .floating .form-control,
  .floating .form-select{
    background-color: #fff;
  }
  .btn-gradient{
    background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
    border:0;
    box-shadow: 0 6px 18px rgba(6,182,212,.25);
  }
  .table thead th{
    border-bottom: 1px solid rgba(148,163,184,.35)!important;
  }
  .table-hover tbody tr:hover{
    background-color: rgba(2,6,23,.035);
  }
  .badge-cat{
    background: #eef2ff; color:#3730a3;
    border:1px solid #c7d2fe;
  }
  .muted{ color: #94a3b8; }
  /* Toast */
  .toast-container{
    z-index: 1080;
  }
</style>
</head>
<body class="container mt-4">
    <div class="card shadow-lg p-4">
        <h2><i class="fas fa-edit me-2"></i>แก้ไขสินค้า</h2>
        <a href="products.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-1"></i>กลับไปยังรายการสินค้า</a>
        
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">ชื่อสินค้า</label>
                <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">ราคา</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">จำนวนในคลัง</label>
                <input type="number" name="stock" class="form-control" value="<?= $product['stock']?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">หมวดหมู่</label>
                <select name="category_id" class="form-select" required>
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= ($product['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12">
                <label class="form-label">รายละเอียดสินค้า</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            
            <!-- แสดงรูปเดิม + เก็บค่าเก่า -->
            <div class="col-md-6">
                <label class="form-label d-block">รูปปัจจุบัน</label>
                <?php if (!empty($product['image'])): ?>
                <img src="../product_images/<?= htmlspecialchars($product['image']) ?>" width="120" height="120" class="rounded mb-2">
                <?php else: ?>
                <span class="text-muted d-block mb-2">ไม่มีรูป</span>
                <?php endif; ?>
                <input type="hidden" name="old_image" value="<?= htmlspecialchars($product['image']) ?>">
            </div>
            
            <!-- อัปโหลดรูปใหม่ (ทางเลือก) -->
            <div class="col-md-6">
                <label class="form-label">อัปโหลดรูปใหม่ (jpg, png)</label>
                <input type="file" name="product_image" class="form-control">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                    <label class="form-check-label" for="remove_image">ลบรูปเดิม</label>
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</body>
</html>
