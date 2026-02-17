<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include_once "connectdb.php";

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $q_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $u_id");
    $r_count = $q_count->fetch_assoc();
    $cart_count = $r_count['total'] ?? 0;
}
?>

<style>
/* --- โค้ดเดิมของคุณ (คงไว้ทั้งหมด) --- */
.navbar-custom { background: linear-gradient(135deg,#2d0b4e,#5b21b6); padding: 15px 0; color: #fff; }
.logo { font-size: 22px; font-weight: 600; color: #fff; }
.icon-btn { 
    background: #c084fc; padding: 8px 12px; border-radius: 50%; 
    text-decoration: none; color: #fff; font-size: 18px; 
    transition: 0.3s; position: relative; display: inline-block;
}
.badge-cart {
    position: absolute; top: -5px; right: -5px;
    background: #ff4d4d; color: white; font-size: 11px;
    padding: 2px 6px; border-radius: 50%; font-weight: bold;
    border: 2px solid #2d0b4e;
}
.logout-btn, .login-btn, .register-btn {
    background: #c084fc; padding: 8px 18px; border-radius: 20px;
    text-decoration: none; color: #fff; font-weight: 500; transition: 0.3s;
}
.logout-btn:hover, .login-btn:hover, .register-btn:hover, .icon-btn:hover { background: #a855f7; }

/* --- ส่วนที่เพิ่ม: ปรับไอคอนบัญชีให้เหมือนปุ่มตะกร้า --- */
.account-icon-btn {
    background: rgba(255, 255, 255, 0.15); 
    padding: 8px 12px; 
    border-radius: 50%; 
    text-decoration: none; 
    color: #fff; 
    font-size: 18px; 
    transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.2);
}
.account-icon-btn:hover {
    background: #bb86fc;
    color: #120018;
    box-shadow: 0 0 10px #bb86fc;
}
</style>

<nav class="navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" style="text-decoration: none;"><div class="logo">🎵 Goods Secret Store</div></a>
        
        <div class="nav-right d-flex align-items-center gap-3">
            <form action="index.php" method="GET" class="d-flex">
                <input class="form-control me-2" type="search" name="search" placeholder="ค้นหาความลับ...">
            </form>

            <?php if(isset($_SESSION['user_id'])){ ?>
                
                <a href="profile.php" class="account-icon-btn" title="บัญชีของฉัน">
                    <i class="bi bi-person-circle"></i>
                </a>

                <a href="cart.php" class="icon-btn">
                    🛒
                    <span id="cart-badge" class="badge-cart" style="<?= ($cart_count > 0) ? '' : 'display:none;' ?>">
                        <?= $cart_count ?>
                    </span>
                </a>

                <a href="logout.php" class="logout-btn">ออกจากระบบ</a>

            <?php } else { ?>
                <a href="login.php" class="login-btn">เข้าสู่ระบบ</a>
                <a href="register.php" class="register-btn">สมัครสมาชิก</a>
            <?php } ?>
        </div>
    </div>
</nav>