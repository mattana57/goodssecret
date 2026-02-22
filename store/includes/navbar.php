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
    background: #ff4d4d; color: white; font-size: 10px;
    padding: 2px 6px; border-radius: 50%; border: 2px solid #5b21b6;
}
.account-icon-btn {
    background: rgba(255,255,255,0.1);
    color: #fff;
    padding: 8px 12px;
    border-radius: 50%;
    font-size: 20px;
    transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.2);
}
.account-icon-btn:hover {
    background: #bb86fc;
    color: #120018;
    box-shadow: 0 0 10px #bb86fc;
}
/* [เพิ่ม]: สไตล์ปุ่มแอดมินให้เด่นนิดนึง */
.admin-btn {
    background: #03dac6;
    color: #000;
    font-weight: bold;
    font-size: 14px;
    padding: 5px 15px;
    border-radius: 20px;
    text-decoration: none;
    transition: 0.3s;
}
.admin-btn:hover {
    background: #fff;
    box-shadow: 0 0 15px #03dac6;
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
                
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){ ?>
                    <a href="index2.php" class="admin-btn">
                        <i class="bi bi-speedometer2"></i> จัดการระบบ
                    </a>
                <?php } ?>

                <a href="profile.php" class="account-icon-btn" title="บัญชีของฉัน">
                    <i class="bi bi-person-circle"></i>
                </a>

                <a href="cart.php" class="icon-btn">
                    🛒
                    <span id="cart-badge" class="badge-cart" style="<?= ($cart_count > 0) ? '' : 'display:none;' ?>">
                        <?= $cart_count ?>
                    </span>
                </a>

                <a href="logout.php" class="btn btn-outline-light btn-sm">ออกซิ</a>
            
            <?php } else { ?>
                <a href="login.php" class="btn btn-light btn-sm fw-bold">เข้าสู่ระบบ</a>
            <?php } ?>
        </div>
    </div>
</nav>