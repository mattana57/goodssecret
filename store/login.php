<?php
session_start();
include "connectdb.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            header("Location: index.php");
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
background: linear-gradient(135deg,#2a0845,#6a1b9a,#3d1e6d);
height:100vh;
display:flex;
justify-content:center;
align-items:center;
font-family:'Segoe UI',sans-serif;
}

.card{
background:rgba(255,255,255,0.05);
backdrop-filter:blur(12px);
border:none;
padding:40px;
width:400px;
box-shadow:0 0 40px rgba(187,134,252,.4);
color:#fff;
}

.card h2,
.card label,
.card p,
.card a{
color:#fff !important;
}

.form-control{
background:#2a0845;
border:1px solid #bb86fc;
color:#fff;
}

.form-control::placeholder{
color:#ccc;
}

.btn-brand{
background:#E0BBE4;
color:#2a0845;
font-weight:600;
}

.btn-brand:hover{
background:#d39ddb;
}

.password-wrapper{
    position: relative;
}

.password-wrapper i{
    position: absolute;
    right: 15px;
    top: 70%;
    transform: translateY(-50%);
    cursor: pointer;
}
</style>
</head>
<body>

<div class="card">
<h2 class="text-center mb-4">เข้าสู่ระบบ</h2>

<?php if($error){ ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">
<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" autofocus required>
</div>
<!--
<div class="mb-3">
<label>รหัสผ่าน</label>
<input type="password" name="password" class="form-control" required>
<i class="bi bi-eye-slash toggle-password" data-target="confirm_password"></i>
</div> -->

<div class="password-wrapper">
    <label>รหัสผ่าน</label>
    <input type="password" name="password" class="form-control" required>
    <i class="bi bi-eye-slash toggle-password" data-target="password"></i>
</div>
<br>
<button class="btn btn-brand w-100">เข้าสู่ระบบ</button>
</form>

<hr class="my-4">

<div class="d-grid gap-2">

<a href="google_login.php" class="btn btn-light">
<img src="https://img.icons8.com/color/20/000000/google-logo.png"/>
</a>

<a href="facebook_login.php" class="btn btn-primary">
<i class="bi bi-facebook"></i>
 ดำเนินการต่อด้วย Facebook
</a>

<a href="line_login.php" class="btn" style="background:#06C755;color:white;">
 ดำเนินการต่อด้วย LINE
</a>

<a href="x_login.php" class="btn btn-dark">
 ดำเนินการต่อด้วย X
</a>

</div>

<p class="mt-3 text-center">
ยังไม่มีบัญชี ? <a href="register.php">สมัครสมาชิก</a>
</p>

<p class="mt-3 text-center">
<a href="index.php">กลับหน้าแรก</a>
</p>

</div>

<script>
document.querySelectorAll(".toggle-password").forEach(icon=>{
    icon.addEventListener("click", function(){
        let input = document.getElementById(this.dataset.target);

        if(input.type === "password"){
            input.type = "text";
            this.classList.remove("bi-eye-slash");
            this.classList.add("bi-eye");
        }else{
            input.type = "password";
            this.classList.remove("bi-eye");
            this.classList.add("bi-eye-slash");
        }
    });
});
</script>


</body>
</html>
