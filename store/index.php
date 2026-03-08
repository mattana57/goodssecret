<?php
session_start();
include "connectdb.php";

$category_slug = $_GET['category'] ?? "";
$search = $_GET['search'] ?? "";
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
if($category_slug && $category_slug != "all"){ $sql .= " AND c.slug = '".$conn->real_escape_string($category_slug)."'"; }
if($search){ $sql .= " AND p.name LIKE '%".$conn->real_escape_string($search)."%'"; }

$showLanding = (!$category_slug && !$search);
if(!$showLanding){ $products = $conn->query($sql); }

// ดึงสินค้าแนะนำ
$recommended = $conn->query("SELECT * FROM products WHERE is_hot=1 LIMIT 8"); 
// ดึงสินค้ามาใหม่ เฉพาะที่ไม่ใช่สินค้าแนะนำ เพื่อไม่ให้ซ้ำกัน
$newArrival = $conn->query("SELECT * FROM products WHERE is_hot=0 ORDER BY id DESC LIMIT 8"); 

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $q_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $u_id");
    $cart_count = $q_count->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Goods Secret Store | Ultra Modern</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), radial-gradient(circle at 80% 70%, #6a1b9a 0%, transparent 40%), linear-gradient(135deg,#120018,#2a0845,#3d1e6d); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .navbar { background: rgba(26, 0, 40, 0.85) !important; backdrop-filter: blur(15px); position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(187, 134, 252, 0.2); }
        .product-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(15px); border-radius: 20px; transition: 0.4s; overflow: hidden; }
        .product-card:hover { transform: translateY(-12px); border-color: #bb86fc; box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #ff0080); border: none; color: white; border-radius: 12px; }
        .section-title { border-left: 5px solid #f107a3; padding-left: 15px; margin-bottom: 30px; text-shadow: 0 0 10px rgba(241, 7, 163, 0.8); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🎵 Goods Secret Store</a>
        <div class="ms-auto d-flex gap-3">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="btn btn-outline-light position-relative">
                    <i class="bi bi-cart"></i>
                    <?php if($cart_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $cart_count ?></span><?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-outline-danger">ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light">เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-5">
    <?php if($showLanding): ?>
        <h4 class="section-title">⭐ สินค้าแนะนำ</h4>
        <div class="row mb-5">
            <?php while($p = $recommended->fetch_assoc()): ?>
            <div class="col-md-3 mb-4">
                <div class="product-card p-3 text-center h-100">
                    <img src="images/<?= $p['image'] ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/200'">
                    <h6 class="text-white text-truncate"><?= $p['name'] ?></h6>
                    <p class="text-info fw-bold">฿<?= number_format($p['price']) ?></p>
                    <div class="d-flex gap-2">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline-info w-50 py-2">ลงตะกร้า</button>
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink w-50 py-2 text-decoration-none">สั่งซื้อ</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <h4 class="section-title">🆕 สินค้ามาใหม่</h4>
        <div class="row mb-5">
            <?php while($p = $newArrival->fetch_assoc()): ?>
            <div class="col-md-3 mb-4">
                <div class="product-card p-3 text-center h-100">
                    <img src="images/<?= $p['image'] ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/200'">
                    <h6 class="text-white text-truncate"><?= $p['name'] ?></h6>
                    <p class="text-info fw-bold">฿<?= number_format($p['price']) ?></p>
                    <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-neon-pink w-100 py-2">ลงตะกร้า</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addToCart(pid) {
    fetch('add_to_cart.php?id=' + pid + '&ajax=1')
    .then(r => r.json())
    .then(d => { if(d.status === 'success') location.reload(); else window.location.href='login.php'; });
}
</script>
</body>
</html>