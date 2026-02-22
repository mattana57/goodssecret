<?php
session_start();
include "connectdb.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $username = $_POST['username'];
    $password = $_POST['password'];

    // [ปรับปรุง]: ดึง id, password และเพิ่มการดึง role มาด้วย เพื่อเช็คว่าเป็น Admin หรือไม่
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; // เก็บค่า role (admin หรือ user) ลง session

            // [เพิ่ม]: ถ้าเป็น admin ให้ไปหน้า index2.php (หน้าแอดมิน), ถ้าเป็น user ปกติไป index.php
            if($user['role'] == 'admin'){
                header("Location: index2.php"); 
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบผู้ใช้นี้ในระบบ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
    --primary-color: #bb86fc;
    --secondary-color: #03dac6;
    --bg-dark: #120018;
    --card-bg: rgba(255, 255, 255, 0.05);
}

body {
    background: var(--bg-dark);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
}

.login-container {
    background: var(--card-bg);
    backdrop-filter: blur(15px);
    padding: 40px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    border-radius: 10px;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    border-color: var(--primary-color);
    box-shadow: 0 0 10px rgba(187, 134, 252, 0.3);
}

.btn-brand {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 10px;
    padding: 10px;
    transition: 0.3s;
}

.btn-brand:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(187, 134, 252, 0.4);
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 38px;
    cursor: pointer;
    color: var(--primary-color);
}
</style>
</head>
<body>

<div class="login-container">
    <h3 class="text-center mb-4 fw-bold">ยินดีต้อนรับ</h3>

    <?php if($error): ?>
        <div class="alert alert-danger text-center py-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small">ชื่อผู้ใช้งาน</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label small">รหัสผ่าน</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword()"></i>
        </div>

        <br>
        <button class="btn btn-brand w-100">เข้าสู่ระบบ</button>
    </form>

    <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

    <div class="d-grid gap-2">
        <a href="google_login.php" class="btn btn-light">
            <img src="https://img.icons8.com/color/20/000000/google-logo.png" class="me-2"/> ดำเนินการต่อด้วย Google
        </a>

        <a href="facebook_login.php" class="btn btn-primary">
            <i class="bi bi-facebook me-2"></i> ดำเนินการต่อด้วย Facebook
        </a>

        <a href="line_login.php" class="btn" style="background:#06C755; color:white;">
            <i class="bi bi-line me-2"></i> ดำเนินการต่อด้วย LINE
        </a>

        <a href="x_login.php" class="btn btn-dark">
            <i class="bi bi-twitter-x me-2"></i> ดำเนินการต่อด้วย X
        </a>
    </div>

    <p class="mt-4 text-center mb-0">
        ยังไม่มีบัญชี ? <a href="register.php" class="text-info text-decoration-none">สมัครสมาชิก</a>
    </p>

    <p class="mt-2 text-center">
        <a href="index.php" class="text-white-50 small text-decoration-none">กลับหน้าแรก</a>
    </p>

</div>

<script>
function togglePassword() {
    const pwd = document.getElementById("password");
    const icon = document.querySelector(".toggle-password");
    if (pwd.type === "password") {
        pwd.type = "text";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    } else {
        pwd.type = "password";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    }
}
</script>

</body>
</html>