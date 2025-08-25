<?php
session_start();
require_once 'config.php';
$errors = ''; // กำหนดตัวแปรสำหรับเก็บข้อความผิดพลาด
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $usernameoremail = trim($_POST['username_or_email']);
    $password = trim($_POST['password']);
    
    //เอาค่าที่รับมาจากฟอมไปตรวจสอบว่ามีในฐานข้อมูลหรือไม่
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usernameoremail, $usernameoremail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
       if ($user['role'] === 'admin') {
            // ถ้าเป็นผู้ดูแลระบบ ให้เริ่ม session และเปลี่ยนเส้นทางไปยังหน้า admin
            header("Location: admin/index.php");
        } else {
            // ถ้าไม่ใช่ผู้ดูแลระบบ เข้าหน้าหลัก
            header("location: index.php");
        }
        exit();
    } else {
        $errors = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"; // ถ้าไม่พบผู้ใช้หรือรหัสผ่านไม่ถูกต้อง ให้แสดงข้อความผิดพลาด
    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f7fa 0%, #fffde4 100%);
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('https://www.transparenttextures.com/patterns/diamond-upholstery.png');
            opacity: 0.12;
            z-index: 0;
        }
        .shop-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .shop-logo img {
            height: 48px;
            margin-right: 12px;
        }
        .shop-title {
            font-size: 2rem;
            font-weight: bold;
            color: #009688;
            letter-spacing: 2px;
            text-shadow: 0 2px 8px rgba(0,150,136,0.08);
        }
        .fade-in {
            animation: fadeInUp 1s ease;
        }
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(40px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .card {
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            background: rgba(255,255,255,0.98);
            border: 2px solid #ffe082;
        }
        .btn-danger {
            background: linear-gradient(90deg, #ffb300 0%, #ff7043 100%);
            border: none;
            color: #fff;
            font-weight: 500;
            letter-spacing: 1px;
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .btn-danger:hover {
            background: linear-gradient(90deg, #ff7043 0%, #ffb300 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px rgba(255,112,67,0.15);
        }
        .btn-outline-secondary {
            border-color: #009688;
            color: #009688;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .btn-outline-secondary:hover {
            background: #009688;
            color: #fff;
        }
        .form-label {
            font-weight: 500;
            color: #ff7043;
        }
        .form-control:focus {
            border-color: #ffb300;
            box-shadow: 0 0 0 0.2rem rgba(255,179,0,.15);
        }
    </style>
</head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>
<body>

<?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
<div class="alert alert-success">สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
<?php endif; ?>
    
    <div class="container mt-5 d-flex flex-column justify-content-center align-items-center" style="min-height: 80vh; position: relative; z-index: 1;">
        <div class="shop-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="Shop Logo">
            <span class="shop-title">OnlineShop</span>
        </div>
        <div class="card shadow-lg p-4 fade-in" style="width: 100%; max-width: 420px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold" style="color:#ff7043"><i class="bi bi-person-plus"></i> เข้าสู่ระบบ</h2>
             
            </div>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="username_or_email" class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
            <input type="text" name="username_or_email" id="username_or_email" class="form-control" required>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" name="password" id="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-danger w-100 mb-2"><i class="bi bi-person-plus-fill"></i> เข้าสู่ระบบ</button>
                <a href="register.php" class="btn btn-outline-secondary w-100">สมัครสมาชิก</a>
            </form>
        </div>
    </div>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script scr="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>