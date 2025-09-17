<?php
// users.php

require '../config.php';            // ต้องมี $conn = new PDO(...);
require_once 'auth_admin.php';      // ตรวจสอบสิทธิ์แอดมิน (ให้มี session_start() ด้วย)

// เผื่อบางโปรเจกต์ยังไม่ session_start() ใน auth_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ปรับโหมด error ของ PDO ให้ชัด
if ($conn instanceof PDO) {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// ---- CSRF token ----
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// ---- ลบสมาชิก (POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['csrf'])) {
    // ตรวจสอบโทเค็น
    if (hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $user_id = (int) $_POST['delete_id'];

        // กันลบตัวเอง
        if ($user_id !== (int)($_SESSION['user_id'] ?? 0)) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :uid AND role = 'member'");
            $stmt->execute([':uid' => $user_id]);
        }
    }
    header("Location: users.php");
    exit;
}

// ---- ค้นหา + แบ่งหน้า ----
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE role = 'member'";
$params = [];

if ($q !== '') {
    $where .= " AND (username LIKE :kw OR full_name LIKE :kw OR email LIKE :kw)";
    $params[':kw'] = "%{$q}%";
}

// นับทั้งหมด
$sqlCount = "SELECT COUNT(*) FROM users $where";
$stmt = $conn->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// ดึงข้อมูลสมาชิกสำหรับตาราง
$perPage = (int)$perPage;
$offset  = (int)$offset;

$sql = "SELECT user_id, username, full_name, email, created_at
        FROM users
        $where
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อสมาชิกทั้งหมด (เพื่อโชว์บนสุด)
$stmt = $conn->prepare("SELECT username FROM users WHERE role='member' ORDER BY username ASC");
$stmt->execute();
$allUsernames = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ฟังก์ชันสร้างลิงก์เพจ
function build_page_link($q, $pageNum) {
    $query = array_filter([
        'q'    => $q !== '' ? $q : null,
        'page' => $pageNum > 1 ? $pageNum : null
    ]);
    return 'users.php' . (empty($query) ? '' : '?' . http_build_query($query));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#f8f9fa; }
  .card-hover { transition:transform .15s, box-shadow .15s; }
  .card-hover:hover { transform: translateY(-3px); box-shadow:0 6px 16px rgba(0,0,0,.12); }
  .table thead th { white-space: nowrap; }
</style>
</head>
<body class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>จัดการสมาชิก</h2>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับหน้าผู้ดูแล</a>
  </div>

  <!-- กล่องโชว์ชื่อสมาชิกทั้งหมด -->
  <div class="card card-hover mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3"><i class="bi bi-list-ul"></i> รายชื่อสมาชิกทั้งหมด</h5>
      <?php if (empty($allUsernames)): ?>
        <div class="text-muted">ยังไม่มีสมาชิกในระบบ</div>
      <?php else: ?>
        <p class="mb-0">
          <?= implode(', ', array_map('htmlspecialchars', $allUsernames)) ?>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- ค้นหา -->
  <div class="card card-hover mb-3">
    <div class="card-body">
      <form class="row g-2" method="get" action="users.php">
        <div class="col-md-6">
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="ค้นหา: ชื่อผู้ใช้ / ชื่อ-นามสกุล / อีเมล">
        </div>
        <div class="col-md-6 d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-search"></i> ค้นหา</button>
          <a href="users.php" class="btn btn-outline-secondary">ล้างค้นหา</a>
        </div>
      </form>
    </div>
  </div>

  <!-- สรุปผล -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted">พบสมาชิกทั้งหมด <span class="fw-semibold"><?= number_format($total) ?></span> รายการ</div>
    <div class="badge bg-light text-dark">หน้า <?= $page ?> / <?= $pages ?></div>
  </div>

  <?php if ($total === 0): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-circle"></i> ยังไม่มีสมาชิกในระบบ</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle bg-white">
        <thead class="table-light">
          <tr>
            <th>ชื่อผู้ใช้</th>
            <th>ชื่อ-นามสกุล</th>
            <th>อีเมล</th>
            <th>วันที่สมัคร</th>
            <th class="text-center" style="width:140px;">จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
            <td><?= htmlspecialchars($u['full_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
            <td><?= htmlspecialchars($u['created_at'] ?? '') ?></td>
            <td class="text-center">
              <a href="edit_user.php?id=<?= (int)$u['user_id'] ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil-square"></i> แก้ไข
              </a>
              <button type="button"
                      class="delete-button btn btn-danger btn-sm"
                      data-user-id="<?= (int)$u['user_id'] ?>">
                <i class="bi bi-trash3"></i> ลบ
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= build_page_link($q, max(1, $page - 1)) ?>">«</a>
        </li>
        <?php
          $start = max(1, $page - 2);
          $end   = min($pages, $page + 2);
          for ($i = $start; $i <= $end; $i++):
        ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= build_page_link($q, $i) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= build_page_link($q, min($pages, $page + 1)) ?>">»</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// โทเค็นสำหรับส่งกลับไปเช็คฝั่ง PHP
const CSRF_TOKEN = '<?= $csrf ?>';

function showDeleteConfirmation(userId) {
  Swal.fire({
    title: 'คุณแน่ใจหรือไม่?',
    text: 'คุณจะไม่สามารถเรียกคืนข้อมูลกลับได้!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
  }).then((result) => {
    if (result.isConfirmed) {
      // สร้างฟอร์ม POST กลับมาที่หน้านี้
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'users.php';

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'delete_id';
      idInput.value = String(userId);
      form.appendChild(idInput);

      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = 'csrf';
      csrfInput.value = CSRF_TOKEN;
      form.appendChild(csrfInput);

      document.body.appendChild(form);
      form.submit();
    }
  });
}

document.querySelectorAll('.delete-button').forEach((btn) => {
  btn.addEventListener('click', () => {
    const userId = btn.getAttribute('data-user-id');
    showDeleteConfirmation(userId);
  });
});
</script>
</body>
</html>
