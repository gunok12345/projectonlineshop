<?php
session_start();
require '../config.php'; // เชื่อมต่อฐานข้อมูล
require_once 'auth_admin.php';
// ลบสมำชกิ
if (isset($_GET['delete'])) {
$user_id = $_GET['delete'];
// ป้องกันลบตัวเอง
if ($user_id != $_SESSION['user_id']) {
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'member'");
$stmt->execute([$user_id]);
}
header("Location: users.php");
exit;
}
// ดึงข้อมูลสมาชิก
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดกำรสมาชิก</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>จัดกำรสมาชิก</h2>
<a href="index.php" class="btn btn-secondary mb-3">← กลับหน้าผู้ดูแล</a>
<?php if (count($users) === 0): ?>
<div class="alert alert-warning">ยังไม่มีสมาชิกในระบบ</div>
<?php else: ?>
<table class="table table-bordered">
<thead>
<tr>
<th>ชื่อผู้ใช้</th> ้
<th>ชื่อ-นามสกุล</th>
<th>อีเมล</th>
<th>วันที่สมัคร</th>
<th>จัดการ</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $user): ?>
<tr>
<td><?= htmlspecialchars($user['username']) ?></td>
<td><?= htmlspecialchars($user['full_name']) ?></td>
<td><?= htmlspecialchars($user['email']) ?></td>
<td><?= $user['created_at'] ?></td>
<td>
<a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-warning">แก้ไข
</a>
<a href="users.php?delete=<?= $user['user_id'] ?>" class="btn btn-sm btn-danger"
onclick="return confirm('คุณต้องการลบสมาชิกนี้หรือไม่?')">ลบ</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</body>
</html>