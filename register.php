<?php
    require_once 'config.php';
    $error = [];    


        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $username = trim($_POST['username']);
            $fullname = trim($_POST['fullname']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            if(empty($username)||empty($fullname)||empty($email)||empty($password)||empty($confirm_password)){
                $error[] = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
            }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
                $error[] = "กรุณากรอกอีเมลให้ถูกต้อง";
            }elseif($password!==$confirm_password){
                $error[] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
            }else{
                $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $email]);
                if($stmt->rowCount() > 0 ){
                    $error[] = "ชื่อผู่้ใช้หรืออีเมลนี้ถูกใช้ไปแล้ว";
                }
            }
            if(empty($error)){
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users(username, full_name, email, password, role) VALUES (?, ?, ?, ?, 'member')";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $fullname, $email, $hashedPassword]);
                header ("Location: login.php ? register=success");
                exit();

            }   
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            min-height: 100vh;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52) !important;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
        <div class="col-lg-4 col-md-6 col-sm-8">
            <div class="card shadow-lg">
                <div class="card-header text-center text-white py-4">
                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                    <h2 class="mb-0">สมัครสมาชิก</h2>
                     <?php if (!empty($error)): // ถ ้ำมีข ้อผิดพลำด ให้แสดงข ้อควำม ?>
                    <div class="alert alert-danger">
                    <ul>
                    <?php foreach ($error as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <!-- ใช ้ htmlspecialchars เพื่อป้องกัน XSS -->
                    <!-- < ? = คือ short echo tag ?> -->
                    <!-- ถ ้ำเขียนเต็ม จะได ้แบบด ้ำนล่ำง -->
                    <?php // echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                    <?php endforeach; ?>
                    </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <form action="register.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-danger"></i>
                                </span>
                                <input type="text" name="username" id="username" class="form-control border-start-0" placeholder="ชื่อผู้ใช้" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-สกุล</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-id-card text-danger"></i>
                                </span>
                                <input type="text" name="fullname" id="fullname" class="form-control border-start-0" placeholder="ชื่อ-สกุล" value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-envelope text-danger"></i>
                                </span>
                                <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="อีเมล" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-danger"></i>
                                </span>
                                <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="รหัสผ่าน" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-check-circle text-danger"></i>
                                </span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0" placeholder="ยืนยันรหัสผ่าน" required>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                            </button>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="login.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-in-alt me-1"></i>เข้าสู่ระบบ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>