<?php
// ================== BOOTSTRAP ADMIN: EDIT MEMBER (PRETTY & SAFE) ==================
require '../config.php';
require 'auth_admin.php';

// ---- Session (เผื่อ auth_admin.php ไม่ได้ start) ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Admin Guard ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ---- CSRF Token (สร้างครั้งแรก) ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- ตรวจพารามิเตอร์ id ----
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}
$user_id = (int)$_GET['id'];

// ---- ดึงข้อมูลสมาชิก (เฉพาะ role = member) ----
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'member'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h3 style='font-family:system-ui,Segoe UI,Roboto'>ไม่พบสมาชิก</h3>";
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- ตรวจ CSRF ----
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "ไม่สามารถยืนยันความถูกต้องของแบบฟอร์ม (CSRF)";
    }

    // ---- รับค่า ----
    $username   = trim($_POST['username'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // ---- Validate เบื้องต้น ----
    if (!$error) {
        if ($username === '' || $email === '') {
            $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
        }
    }

    // ---- ตรวจซ้ำ username/email (ไม่นับตัวเอง) ----
    if (!$error) {
        $chk = $conn->prepare("SELECT 1 FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $chk->execute([$username, $email, $user_id]);
        if ($chk->fetch()) {
            $error = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
        }
    }

    // ---- ตรวจรหัสผ่าน (กรณีเปลี่ยน) ----
    $updatePassword = false;
    $hashed = null;
    if (!$error && ($password !== '' || $confirm !== '')) {
        if (strlen($password) < 6) {
            $error = "รหัสผ่านต้องยาวอย่างน้อย 6 อักขระ";
        } elseif ($password !== $confirm) {
            $error = "รหัสผ่านใหม่กับยืนยันรหัสผ่านไม่ตรงกัน";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updatePassword = true;
        }
    }

    // ---- อัปเดต ----
    if (!$error) {
        if ($updatePassword) {
            $sql  = "UPDATE users SET username = ?, full_name = ?, email = ?, password = ? WHERE user_id = ?";
            $args = [$username, $full_name, $email, $hashed, $user_id];
        } else {
            $sql  = "UPDATE users SET username = ?, full_name = ?, email = ? WHERE user_id = ?";
            $args = [$username, $full_name, $email, $user_id];
        }
        $upd = $conn->prepare($sql);
        $upd->execute($args);

        // ป้องกันการกดรีเฟรชซ้ำ
        header("Location: users.php?updated=1");
        exit;
    }

    // ---- กรณี error: เติมค่ากลับไปในฟอร์ม ----
    $user['username']  = $username;
    $user['full_name'] = $full_name;
    $user['email']     = $email;
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="auto">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขข้อมูลสมาชิก</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root{
    --brand-grad: linear-gradient(135deg,#5a67d8 0%, #805ad5 35%, #d53f8c 100%);
  }
  body{
    background:
      radial-gradient(1200px 600px at -10% -10%, rgba(123,97,255,.08), transparent 60%),
      radial-gradient(800px 400px at 110% 10%, rgba(255,99,164,.08), transparent 60%),
      #0f172a;
    background-attachment: fixed;
  }
  .page-header{
    background: var(--brand-grad);
    color:#fff;
    border-radius: 1.25rem;
    padding: 2rem 1.5rem;
    box-shadow: 0 20px 40px rgba(0,0,0,.25);
  }
  .card-elev{
    border:0;
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
  }
  .form-label .text-muted{ font-weight:400; }
  .input-icon .bi{
    position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
    pointer-events: none;
  }
  .input-icon input{
    padding-left: 2.4rem;
  }
  .btn-primary{
    border-radius: .9rem;
  }
  .badge-soft{
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color:#fff;
  }
  /* ช่วยการอ่านในโหมดสว่าง */
  @media (prefers-color-scheme: light){
    body{ background:#f6f7fb; }
    .page-header{ box-shadow: 0 12px 24px rgba(80,72,180,.25); }
  }
</style>
</head>
<body>
<div class="container py-4 py-md-5">
  <!-- Header -->
  <div class="page-header mb-4">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <div class="d-flex align-items-center gap-3">
        <span class="badge rounded-pill badge-soft"><i class="bi bi-shield-lock"></i> Admin</span>
        <h1 class="h3 mb-0">แก้ไขข้อมูลสมาชิก</h1>
      </div>
      <a href="users.php" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> กลับหน้ารายชื่อสมาชิก
      </a>
    </div>
  </div>

  <!-- Content Card -->
  <div class="row justify-content-center">
    <div class="col-12 col-xl-10 col-xxl-8">
      <div class="card card-elev">
        <div class="card-body p-4 p-md-5">
          <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <div><?= htmlspecialchars($error) ?></div>
            </div>
          <?php endif; ?>

          <form method="post" class="row g-4" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="col-md-6">
              <label class="form-label">ชื่อผู้ใช้</label>
              <div class="position-relative input-icon">
                <i class="bi bi-person"></i>
                <input type="text" name="username" class="form-control form-control-lg" required
                       value="<?= htmlspecialchars($user['username']) ?>" placeholder="username">
              </div>
              <div class="form-text">ควรเป็น A-Z, 0-9, _ และยาว 3–20 ตัวอักษร</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">ชื่อ - นามสกุล</label>
              <div class="position-relative input-icon">
                <i class="bi bi-card-text"></i>
                <input type="text" name="full_name" class="form-control form-control-lg"
                       value="<?= htmlspecialchars($user['full_name']) ?>" placeholder="สมชาย ใจดี">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">อีเมล</label>
              <div class="position-relative input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" class="form-control form-control-lg" required
                       value="<?= htmlspecialchars($user['email']) ?>" placeholder="name@example.com">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                รหัสผ่านใหม่
                <small class="text-muted">(ถ้าไม่ต้องการเปลี่ยน ให้เว้นว่าง)</small>
              </label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="form-text">อย่างน้อย 6 ตัวอักษร</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="confirm_password" id="confirm" class="form-control" placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" id="toggleConfirm">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
              <a href="users.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle"></i> ยกเลิก
              </a>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save"></i> บันทึกการแก้ไข
              </button>
            </div>
          </form>

        </div>
      </div>

      <!-- Tips -->
      <div class="text-muted small mt-3">
        <i class="bi bi-info-circle"></i>
        หมายเหตุ: หากไม่กรอกรหัสผ่านใหม่ ระบบจะคงรหัสผ่านเดิมไว้โดยอัตโนมัติ
      </div>
    </div>
  </div>
</div>

<script>
  // toggle password visibility
  const togglePassword = document.getElementById('togglePassword');
  const toggleConfirm  = document.getElementById('toggleConfirm');
  const passInput      = document.getElementById('password');
  const confirmInput   = document.getElementById('confirm');

  function toggle(input, btn){
    const icon = btn.querySelector('i');
    if (input.type === 'password'){
      input.type = 'text';
      icon.classList.replace('bi-eye','bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('bi-eye-slash','bi-eye');
    }
  }
  togglePassword?.addEventListener('click', () => toggle(passInput, togglePassword));
  toggleConfirm?.addEventListener('click', () => toggle(confirmInput, toggleConfirm));
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
