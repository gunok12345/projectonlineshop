<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../config.php';
require_once 'auth_admin.php';

// ---- Admin Guard ----
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// ---- CSRF Token ----
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- Flash messages ----
$flash_success = null;
$flash_error   = null;

// ---- Add product ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $flash_error = "ไม่สามารถยืนยันแบบฟอร์ม (CSRF)";
  } else {
    $name        = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = is_numeric($_POST['price'] ?? null) ? (float)$_POST['price'] : 0.0;
    $stock       = is_numeric($_POST['stock'] ?? null) ? (int)$_POST['stock'] : 0;
    $category_id = is_numeric($_POST['category_id'] ?? null) ? (int)$_POST['category_id'] : 0;

    if ($name === '' || $price <= 0 || $stock < 0 || $category_id <= 0) {
      $flash_error = "กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อ, ราคา>0, จำนวน≥0 และเลือกหมวดหมู่)";
    } else {
      $stmt = $conn->prepare(
        "INSERT INTO products (product_name, description, price, stock, category_id) VALUES (?, ?, ?, ?, ?)"
      );
      $stmt->execute([$name, $description, $price, $stock, $category_id]);
      $flash_success = "เพิ่มสินค้าเรียบร้อย";
      $_POST = [];
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate token
    }
  }
}

// ---- Delete product via POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $flash_error = "ไม่สามารถยืนยันการลบ (CSRF)";
  } else {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if ($product_id > 0) {
      $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
      $stmt->execute([$product_id]);
      $flash_success = "ลบสินค้าเรียบร้อย";
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
  }
}

// ---- Load categories & products ----
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query(
  "SELECT p.*, c.category_name
   FROM products p
   LEFT JOIN categories c ON p.category_id = c.category_id
   ORDER BY p.created_at DESC, p.product_id DESC"
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เพิ่มสินค้า • แผงผู้ดูแล</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
<body>

<!-- Topbar -->
<div class="topbar py-3 mb-4">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-bag-plus text-white fs-4"></i>
      <h1 class="h5 title mb-0">เพิ่มสินค้า</h1>
    </div>
    <a href="index.php" class="btn btn-light btn-sm"><i class="bi bi-speedometer2"></i> แผงผู้ดูแล</a>
  </div>
</div>

<div class="container" style="max-width: 1040px;">

  <!-- Alerts (pretty toast-style) -->
  <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3">
    <?php if (!empty($flash_success)): ?>
      <div class="toast align-items-center text-bg-success border-0 show" role="status">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash_success) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="toast align-items-center text-bg-danger border-0 show" role="status">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flash_error) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Add Form -->
  <div class="card clean mb-4">
    <div class="card-header py-3">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square"></i>
        <span class="fw-semibold">ฟอร์มเพิ่มสินค้า</span>
      </div>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3 floating">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="add">

        <div class="col-md-6 form-floating">
          <input type="text" name="product_name" id="product_name" class="form-control" placeholder="ชื่อสินค้า" required
                 value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" maxlength="150">
          <label for="product_name">ชื่อสินค้า *</label>
        </div>

        <div class="col-md-3 form-floating">
          <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" placeholder="ราคา" required
                 value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
          <label for="price">ราคา (บาท) *</label>
        </div>

        <div class="col-md-3 form-floating">
          <input type="number" min="0" name="stock" id="stock" class="form-control" placeholder="จำนวนคงเหลือ" required
                 value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
          <label for="stock">จำนวนคงเหลือ *</label>
        </div>

        <div class="col-md-6 form-floating">
          <select name="category_id" id="category_id" class="form-select" required>
            <option value="">— เลือกหมวดหมู่ —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['category_id'] ?>"
                <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label for="category_id">หมวดหมู่ *</label>
        </div>

        <div class="col-12 form-floating">
          <textarea name="description" id="description" class="form-control" style="height: 120px"
                    placeholder="รายละเอียดเพิ่มเติม..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          <label for="description">รายละเอียดสินค้า</label>
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end pt-2">
          <button type="reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> ล้าง</button>
          <button type="submit" class="btn btn-gradient text-white"><i class="bi bi-plus-circle"></i> เพิ่มสินค้า</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Products Table -->
  <div class="card clean">
    <div class="card-header py-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-list-ul"></i>
          <span class="fw-semibold">รายการสินค้า</span>
        </div>
        <span class="muted small">ทั้งหมด <?= count($products) ?> รายการ</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-borderless align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ชื่อสินค้า</th>
            <th>หมวดหมู่</th>
            <th class="text-end">ราคา</th>
            <th class="text-end">คงเหลือ</th>
            <th class="text-center" style="width: 160px;">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="5" class="text-center muted py-4">ยังไม่มีสินค้า</td></tr>
          <?php else: foreach ($products as $p): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></td>
              <td>
                <?php if (!empty($p['category_name'])): ?>
                  <span class="badge badge-cat"><?= htmlspecialchars($p['category_name']) ?></span>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-end"><?= number_format((float)$p['price'], 2) ?> บาท</td>
              <td class="text-end"><?= (int)$p['stock'] ?></td>
              <td class="text-center">
                <a href="edit_product.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil-square"></i> แก้ไข
                </a>
                <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบสินค้านี้?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash3"></i> ลบ
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted small mt-3"><i class="bi bi-info-circle"></i> เคล็ดลับ: ใช้ชื่อสินค้าที่ค้นหาง่าย และตรวจสอบหมวดหมู่ให้ถูกต้องก่อนบันทึก</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
