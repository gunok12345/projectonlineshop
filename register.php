<?php
session_start();
require_once 'config.php';
$errors = []; // กำหนดตัวแปรสำหรับเก็บข้อความผิดพลาด
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ตรวจสอบความถูกต้อง
    if (empty($username) || empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {// ตรวจสอบอีเมลถุกต้องหรือไม่
        $errors[] = "กรุณากรอกอีเมลให้ถูกต้อง";
    } elseif ($password !== $confirm_password) { //ตรวจสอบรหัสผ่านตรงกันหรือไม่
        $errors[] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    }else{
        // ตรวจสอบชื่อผู้ใช้หรืออีเมลที่ใช้ไปแล้วหรือไม่
     $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
     $stmt = $conn->prepare($sql);
     $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $errors[] = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว";
        }

    }
    if (empty($errors)) { // ถ้าไม่มีข้อผิดพลาด
        // นำข้อมลไปบันทึกในฐานข้อมูล
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users(username, full_name, email, password, role) VALUES (?, ?, ?, ?, 'member')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username, $name, $email, $hashedPassword]);
    //ถ้าบันทึกสำเร็จ ให้เปลี่ยนเส้นทางไปหน้า login
    header("Location: login.php?register=success");
    exit(); //หยุดการทำงานของสคริปต์หลังจากเปลี่ยนเส้นทาง
    
}
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $name, $email, $hashedPassword]);
        echo "สมัครสมาชิกสำเร็จ";
    } catch (PDOException $e) {
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
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
<body>
    
    <div class="container mt-5 d-flex flex-column justify-content-center align-items-center" style="min-height: 80vh; position: relative; z-index: 1;">
        <div class="shop-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="Shop Logo">
            <span class="shop-title">OnlineShop</span>
        </div>
        <div class="card shadow-lg p-4 fade-in" style="width: 100%; max-width: 420px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold" style="color:#ff7043"><i class="bi bi-person-plus"></i> สมัครสมาชิก</h2>
                <!--เขียน if, foreach แบบย่อ (alternative syntax)
        ใช ้: แทน { เพื่อเปิดเงื่อนไข
        ใช ้< ?php endforeach; ?> แทน } เพื่อปิดลูป
        ใช ้< ?php endif; ?> แทน } เพื่อปิดเงื่อนไข if -->
        <?php if (!empty($errors)): // ถ ้ำมีข ้อผิดพลำด ให้แสดงข ้อควำม ?>
        <div class="alert alert-danger">
<ul>
<?php foreach ($errors as $e): ?>
<li><?= htmlspecialchars($e) ?></li>
<!--ใช ้ htmlspecialchars เพื่อป้องกัน XSS -->
<!-- < ? = คือ short echo tag ?> -->
<!-- ถ ้ำเขียนเต็ม จะได ้แบบด ้ำนล่ำง -->
<?php // echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>
            </div>
            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>

 
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">ชื่อ-สกุล</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="ชื่อ-สกุล" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="อีเมล" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน">
                </div>
                <button type="submit" class="btn btn-danger w-100 mb-2"><i class="bi bi-person-plus-fill"></i> สมัครสมาชิก</button>
                <a href="login.php" class="btn btn-outline-secondary w-100">เข้าสู่ระบบ</a>
            </form>
        </div>
    </div>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script scr="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'online_shop';

$dns = "mysql:host=$host;dbname=$database";

try {
    // $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn = new PDO($dns, $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "PDO Connected successfully";
} catch(PDOException $e) {
    echo "PDO Connection failed: " . $e->getMessage();
}
?>

