<?php
require_once '../config.php';
require_once 'auth_admin.php'; 

// เพิ่มสินค้าใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
$name = trim($_POST['product_name']);
$description = trim($_POST['description']);
$price = floatval($_POST['price']); // floatval() ใชแปลงเป็น float
$stock = intval($_POST['stock']); // intval() ใชแ้ปลงเป็น integer
$category_id = intval($_POST['category_id']);
// ค่ำที่ได ้จำกฟอร์มเป็น string เสมอ
if ($name && $price > 0) { // ตรวจสอบชอื่ และรำคำสนิ คำ้
        $imageName = null;
        if (!empty($_FILES['product_image']['name'])) {
        $file = $_FILES['product_image'];
        $allowed = ['image/jpeg', 'image/png'];
        if (in_array($file['type'], $allowed)) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $imageName = 'product_' . time() . '.' . $ext;
        $path = __DIR__ . '/../product_images/' . $imageName;
        move_uploaded_file($file['tmp_name'], $path);
        }
        }
        $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, stock, category_id, image)
        VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName]);






// $stmt = $conn->prepare("INSERT INTO products ( product_name, description , price, stock, category_id) VALUES (?, ?, ?, ?, ?)");
// $stmt->execute([$name,$description,$price,$stock,$category_id]);
header("Location: products.php");
exit;
}
// ถ ้ำเขียนให ้อ่ำนง่ำยขึ้น สำมำรถเขียนแบบด ้ำนล่ำง
// if (!empty($name) && $price > 0) {
// // ผำ่ นเงอื่ นไข: มชี อื่ สนิคำ้ และ รำคำมำกกวำ่ 0
// }
}
// ลบสินค้า
// if (isset($_GET['delete'])) {
// $product_id = $_GET['delete'];
// $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
// $stmt->execute([$product_id]);
// header("Location: products.php");
// exit;
// }
// ลบสนิ คำ้ (ลบไฟลร์ปู ดว้ย)
        if (isset($_GET['delete'])) {
        $product_id = (int)$_GET['delete']; // แคสต์เป็น int
        // 1) ดงึชอื่ ไฟลร์ปู จำก DB ก่อน
        $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $imageName = $stmt->fetchColumn(); // null ถ ้ำไม่มีรูป
        // 2) ลบใน DB ด ้วย Transaction
        try {
        $conn->beginTransaction();
        $del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $del->execute([$product_id]);
        $conn->commit();
        } catch (Exception $e) {
        $conn->rollBack();
        // ใส่ flash message หรือ log ได ้ตำมต ้องกำร
        header("Location: products.php");
        exit;
        }
        // 3) ลบไฟล์รูปหลัง DB ลบส ำเร็จ
        if ($imageName) {
        $baseDir = realpath(__DIR__ . '/../product_images'); // โฟลเดอร์เก็บรูป
        $filePath = realpath($baseDir . '/' . $imageName);
        // กัน path traversal: ต ้องอยู่ใต้ $baseDir จริง ๆ
        if ($filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
        @unlink($filePath); // ใช ้@ กัน warning ถำ้ลบไมส่ ำเร็จ
        }
        }
        header("Location: products.php");
        exit;
        }
// ดึงรายการสินค้า
$stmt = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON
p.category_id = c.category_id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ดึงหมวดหมู่
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการสินค้า</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h2><i class="fas fa-box me-2"></i>จัดการสินค้า</h2>
        <a href="index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-1"></i>กลับหน้าผู้ดูแล</a>
        
        <!-- ฟอร์มเพิ่มสินค้าใหม่ -->
        <h5 class="mt-4"><i class="fas fa-plus-circle me-1"></i>เพิ่มสินค้าใหม่</h5>
        <form method="post" " enctype="multipart/form-data" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="product_name" class="form-control" placeholder="ชื่อสินค้า" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="price" class="form-control" placeholder="ราคา" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="stock" class="form-control" placeholder="จำนวน" required>
            </div>
            <div class="col-md-4">
                <select name="category_id" class="form-select" required>
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <textarea name="description" class="form-control" placeholder="รายละเอียดสินค้า" rows="2"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">รูปสินค้า(jpg, png)</label>
                <input type="file" name="product_image" class="form-control">
            </div>
            <div class="col-12">
                <button type="submit" name="add_product" class="btn btn-primary"><i class="fas fa-plus me-1"></i>เพิ่มสินค้า</button>
            </div>
        </form>
        
        <!-- แสดงรายการสินค้า, แก้ไข, ลบ -->
        <h5><i class="fas fa-list me-1"></i>รายการสินค้า</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>หมวดหมู่</th>
                        <th>ราคา</th>
                        <th>คงเหลือ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                        <td><?= htmlspecialchars($p['category_name']) ?></td>
                        <td><?= number_format($p['price'], 2) ?> บาท</td>
                        <td><?= $p['stock'] ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-product-id="<?= $p['product_id'] ?>">
                                <i class="fas fa-trash-alt"></i> ลบ
                            </a>
                            <a href="edit_products.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณต้องการลบสินค้านี้หรือไม่?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <a id="deleteProductLink" href="#" class="btn btn-danger">ยืนยันการลบ</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            const deleteProductLink = document.getElementById('deleteProductLink');

            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const productId = button.getAttribute('data-product-id');
                    deleteProductLink.href = 'products.php?delete=' + productId;
                });
            }
        });
    </script>
</body>
</html>
