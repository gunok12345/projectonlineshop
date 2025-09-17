<?php
// categories.php
require '../config.php';
require 'auth_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Admin guard ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- PDO error mode ---
if ($conn instanceof PDO) {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// --- CSRF token ---
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// --- Actions (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            throw new Exception('CSRF token ไม่ถูกต้อง');
        }

        // เพิ่มหมวดหมู่
        if (isset($_POST['add_category'])) {
            $category_name = trim($_POST['category_name'] ?? '');
            if ($category_name === '') {
                throw new Exception('กรุณากรอกชื่อหมวดหมู่');
            }
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (:name)");
            $stmt->execute([':name' => $category_name]);
            $_SESSION['success'] = 'เพิ่มหมวดหมู่เรียบร้อย';
        }

        // แก้ไขหมวดหมู่
        if (isset($_POST['update_category'])) {
            $category_id = (int)($_POST['category_id'] ?? 0);
            $new_name    = trim($_POST['new_name'] ?? '');
            if ($category_id <= 0 || $new_name === '') {
                throw new Exception('ข้อมูลไม่ครบถ้วนสำหรับอัปเดตหมวดหมู่');
            }
            $stmt = $conn->prepare("UPDATE categories SET category_name = :name WHERE category_id = :id");
            $stmt->execute([':name' => $new_name, ':id' => $category_id]);
            $_SESSION['success'] = 'อัปเดตหมวดหมู่เรียบร้อย';
        }

        // ลบหมวดหมู่ (กันลบถ้ามีสินค้าอ้างอิง)
        if (isset($_POST['delete_category'])) {
            $category_id = (int)($_POST['category_id'] ?? 0);
            if ($category_id <= 0) {
                throw new Exception('ไม่พบรหัสหมวดหมู่ที่ต้องการลบ');
            }
            $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
            $stmt->execute([':id' => $category_id]);
            $inUse = (int)$stmt->fetchColumn();

            if ($inUse > 0) {
                $_SESSION['error'] = "ไม่สามารถลบได้: ยังมีสินค้าอยู่ในหมวดนี้จำนวน {$inUse} รายการ";
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = :id");
                $stmt->execute([':id' => $category_id]);
                $_SESSION['success'] = 'ลบหมวดหมู่เรียบร้อย';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    // PRG pattern
    header("Location: categories.php");
    exit;
}

// --- ดึงหมวดหมู่ทั้งหมด ---
$stmt = $conn->query("SELECT category_id, category_name AS name FROM categories ORDER BY category_id ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการหมวดหมู่สินค้า • แผงผู้ดูแล</title>
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
  .title{ color:#fff; letter-spacing:.3px; }
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
  .btn-gradient{
    background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
    border:0;
    box-shadow: 0 6px 18px rgba(6,182,212,.25);
  }
  .table thead th{
    border-bottom: 1px solid rgba(148,163,184,.35)!important;
  }
  .table-hover tbody tr:hover{ background-color: rgba(2,6,23,.035); }
  .muted{ color:#94a3b8; }
  .badge-cat{
    background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe;
  }
  .toast-container{ z-index:1080; }
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar py-3 mb-4">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-folder2-open text-white fs-4"></i>
      <h1 class="h5 title mb-0">จัดการหมวดหมู่สินค้า</h1>
    </div>
    <a href="index.php" class="btn btn-light btn-sm"><i class="bi bi-speedometer2"></i> แผงผู้ดูแล</a>
  </div>
</div>

<div class="container" style="max-width: 1040px;">

  <!-- Toast alerts -->
  <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3">
    <?php if (!empty($_SESSION['success'])): ?>
      <div class="toast align-items-center text-bg-success border-0 show">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
      <div class="toast align-items-center text-bg-danger border-0 show">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
  </div>

  <!-- Add category -->
  <div class="card clean mb-4">
    <div class="card-header py-3">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-plus-square"></i>
        <span class="fw-semibold">เพิ่มหมวดหมู่</span>
      </div>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-8">
          <label class="form-label">ชื่อหมวดหมู่ใหม่</label>
          <input type="text" name="category_name" class="form-control" placeholder="เช่น เสื้อผ้า, อุปกรณ์ไอที" required>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" name="add_category" class="btn btn-gradient text-white w-100">
            <i class="bi bi-plus-circle"></i> เพิ่มหมวดหมู่
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Categories list -->
  <div class="card clean">
    <div class="card-header py-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-list-ul"></i>
          <span class="fw-semibold">รายการหมวดหมู่</span>
        </div>
        <span class="muted small">ทั้งหมด <?= count($categories) ?> รายการ</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-borderless align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:55%">ชื่อหมวดหมู่</th>
            <th style="width:30%">แก้ไขชื่อ</th>
            <th style="width:15%" class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="3" class="text-center muted py-4">ยังไม่มีหมวดหมู่</td></tr>
          <?php else: foreach ($categories as $cat): ?>
            <tr>
              <td class="fw-semibold">
                <span class="badge badge-cat me-2">ID #<?= (int)$cat['category_id'] ?></span>
                <?= htmlspecialchars($cat['name']) ?>
              </td>
              <td>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                  <input type="text" name="new_name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" required>
                  <button type="submit" name="update_category" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil-square"></i> แก้ไข
                  </button>
                </form>
              </td>
              <td class="text-center">
                <form method="post" class="d-inline" onsubmit="return confirm('ต้องการลบหมวดหมู่นี้หรือไม่?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                  <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
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

  <p class="muted small mt-3"><i class="bi bi-info-circle"></i> เคล็ดลับ: ตั้งชื่อหมวดหมู่ให้สื่อความหมายและไม่ซ้ำกัน เพื่อค้นหาและจัดกลุ่มสินค้าได้ง่าย</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
