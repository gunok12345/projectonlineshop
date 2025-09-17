
<?php
require '../config.php';
require 'auth_admin.php'; 
// TODO-1: เชื่อมต่อฐานข้อมูลด้วย PDO
// TODO-2: การ์ดสิทธิ์(Admin Guard)
// แนวทาง: ถ้า !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' -> redirect ไป ../login.php แล้ว exit;
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
header("Location: ../login.php");
exit;
}
// TODO-3: ตรวจว่ำมีพำรำมิเตอร์ id มำจริงไหม (ผ่ำน GET)
// แนวทำง: ถ ้ำไม่มี -> redirect ไป users.php
if (!isset($_GET['id'])) {
header("Location: users.php");
exit;
}
// TODO-4: ดึงค่ำ id และ "แคสต์เป็น int" เพื่อควำมปลอดภัย
$user_id = (int)$_GET['id'];
// ดงึขอ้ มลู สมำชกิทจี่ ะถกู แกไ้ข
/*
TODO-5: เตรียม/รัน SELECT (เฉพำะ role = 'member')
SQL แนะน ำ:
SELECT * FROM users WHERE user_id = ? AND role = 'member'
- ใช ้prepare + execute([$user_id])
- fetch(PDO::FETCH_ASSOC) แล้วเก็บใน $user
*/
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'member'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// TODO-6: ถ ้ำไม่พบข ้อมูล -> แสดงข ้อควำมและ exit;
if (!$user) {
echo "<h3>ไมพ่ บสมำชกิ</h3>";
exit;
}
// ========== เมอื่ ผใู้ชก้ด Submit ฟอร์ม ==========
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// TODO-7: รับค่ำ POST + trim
$username = trim($_POST['username']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);

$password = $_POST['password'];
$confirm = $_POST['confirm_password'];
// TODO-8: ตรวจควำมครบถ ้วน และตรวจรูปแบบ email
if ($username === '' || $email === '') {
$error = "กรุณำกรอกข ้อมูลให้ครบถ ้วน";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$error = "รูปแบบอีเมลไม่ถูกต ้อง";
}
// TODO-9: ถ ้ำ validate ผ่ำน ใหต้ รวจสอบซ ้ำ (username/email ชนกับคนอนื่ ทไี่ มใ่ ชต่ ัวเองหรือไม่)
// SQL แนะน ำ:
// SELECT 1 FROM users WHERE (username = ? OR email = ?) AND user_id != ?
if (!$error) {
$chk = $conn->prepare("SELECT 1 FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
$chk->execute([$username, $email, $user_id]);
if ($chk->fetch()) {
$error = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบ";
}
}







// ตรวจรหัสผ่ำน (กรณีต้องกำรเปลี่ยน)
// เงื่อนไข: อนุญำตให้ปล่อยว่ำงได ้ (คือไม่เปลี่ยนรหัสผ่ำน)
$updatePassword = false;
$hashed = null;
if (!$error && ($password !== '' || $confirm !== '')) {
// TODO: นศ.เตมิกตกิ ำ เชน่ ยำว >= 6 และรหัสผ่ำนตรงกัน
if (strlen($password) < 6) {
$error = "รหัสผ่ำนต ้องยำวอย่ำงน้อย 6 อักขระ";
} elseif ($password !== $confirm) {
$error = "รหัสผ่ำนใหม่กับยืนยันรหัสผ่ำนไม่ตรงกัน";
} else {
// แฮชรหัสผ่ำน
$hashed = password_hash($password, PASSWORD_DEFAULT);
$updatePassword = true;
}
}
// สร ้ำง SQL UPDATE แบบยืดหยุ่น (ถ ้ำไม่เปลี่ยนรหัสผ่ำนจะไม่แตะ field password)
if (!$error) {
if ($updatePassword) {
// อัปเดตรวมรหัสผ่ำน
$sql = "UPDATE users
SET username = ?, full_name = ?, email = ?, password = ?
WHERE user_id = ?";
$args = [$username, $full_name, $email, $hashed, $user_id];
} else {
// อัปเดตเฉพำะข ้อมูลทั่วไป
$sql = "UPDATE users
SET username = ?, full_name = ?, email = ?
WHERE user_id = ?";
$args = [$username, $full_name, $email, $user_id];
}
$upd = $conn->prepare($sql);
$upd->execute($args);
header("Location: users.php");
exit;
}
// เขียน update แบบปกต:ิ ถำ้ไมซ่ ้ำ -> ท ำ UPDATE
// if (!$error) {
// $upd = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ? WHERE user_id = ?");
// $upd->execute([$username, $full_name, $email, $user_id]);
// // TODO-11: redirect กลับหน้ำ users.php หลังอัปเดตส ำเร็จ
// header("Location: users.php");
// exit;
// }


// TODO-10: ถำ้ไมซ่ ้ำ -> ท ำ UPDATE
// SQL แนะน ำ:
// UPDATE users SET username = ?, full_name = ?, email = ? WHERE user_id = ?

// OPTIONAL: อัปเดตค่ำ $user เพอื่ สะทอ้ นคำ่ ทชี่ อ่ งฟอรม์ (หำกมีerror)
$user['username'] = $username;
$user['full_name'] = $full_name;
$user['email'] = $email;
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขข้อมูลสมาชิก</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>แก้ไขข้อมูลสมาชิก</h2>
<a href="users.php" class="btn btn-secondary mb-3">← กลับหน้ารายชื่อสมาชิก</a>
<?php if (isset($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" class="row g-3">
<div class="col-md-6">
<label class="form-label">ชื่อผู้ใช้</label>
<input type="text" name="username" class="form-control" required value="<?=
htmlspecialchars($user['username']) ?>">
</div>
<div class="col-md-6">
<label class="form-label">ชื่อ - นามสกุล</label>
<input type="text" name="full_name" class="form-control" value="<?=
htmlspecialchars($user['full_name']) ?>">
</div>
<div class="col-md-6">
<label class="form-label">อีเมล</label>
<input type="email" name="email" class="form-control" required value="<?=
htmlspecialchars($user['email']) ?>">
</div>
<div class="col-12">
<button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
</div>
<div class="col-md-6">
<label class="form-label">รหัสผ่านใหม่ <small class="text-muted">(ถ้าไม่ต้องการเปลี่ยน ให้เว้นว่าง)
</small></label>
<input type="password" name="password" class="form-control">
</div>
<div class="col-md-6">
<label class="form-label">ยืนยันรหัสผ่ำนใหม่</label>
<input type="password" name="confirm_password" class="form-control">
</div>
</form>
</body>
</html>
