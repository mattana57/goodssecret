<?php
session_start();
include "connectdb.php";

/* ================= ดึงข้อมูล (คงเดิม 100%) ================= */
$category_slug = $_GET['category'] ?? "";
$search = $_GET['search'] ?? "";
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "SELECT products.*, categories.name as category_name, categories.slug FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE 1";
if($category_slug && $category_slug != "all"){ $sql .= " AND categories.slug = '".$conn->real_escape_string($category_slug)."'"; }
if($search){ $sql .= " AND products.name LIKE '%".$conn->real_escape_string($search)."%'"; }

$showLanding = (!$category_slug && !$search);
if(!$showLanding){ $products = $conn->query($sql); }

$recommended = $conn->query("SELECT * FROM products WHERE featured=1 LIMIT 8");
$newArrival = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
$discountProducts = $conn->query("SELECT * FROM products WHERE discount > 0 LIMIT 8");

/* --- ส่วนที่เพิ่ม: ดึงจำนวนสินค้ามาโชว์ที่บาร์ --- */
$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $q_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $u_id");
    $r_count = $q_count->fetch_assoc();
    $cart_count = $r_count['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Goods Secret Store | Ultra Modern</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* --- Neon Glassmorphism Theme (คงเดิมของคุณ) --- */
body {
    background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), 
                radial-gradient(circle at 80% 70%, #6a1b9a 0%, transparent 40%), 
                linear-gradient(135deg,#120018,#2a0845,#3d1e6d);
    color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh;
}

.navbar { 
    background: rgba(26, 0, 40, 0.85) !important; 
    backdrop-filter: blur(15px); 
    position: sticky; top: 0; z-index: 1000; 
    border-bottom: 1px solid rgba(187, 134, 252, 0.2); 
}

.modern-btn { 
    background: rgba(255,255,255,0.1); color:#fff; 
    border: 1px solid rgba(255,255,255,0.2); 
    padding: 8px 20px; border-radius: 30px; 
    text-decoration: none; transition: 0.3s; backdrop-filter: blur(5px);
}
.modern-btn:hover, .active-category { background: #bb86fc; color: #120018; border-color: #bb86fc; box-shadow: 0 0 15px #bb86fc; }

.product-card {
    background: rgba(255, 255, 255, 0.03); 
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px); border-radius: 20px; 
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer; position: relative; overflow: hidden;
}
.product-card:hover {
    transform: translateY(-12px); background: rgba(255, 255, 255, 0.08);
    border-color: #bb86fc; box-shadow: 0 15px 30px rgba(0,0,0,0.5), 0 0 20px rgba(187, 134, 252, 0.3);
}

.btn-neon-purple {
    background: rgba(187, 134, 252, 0.1); border: 1px solid #bb86fc; color: #bb86fc;
    font-weight: 600; border-radius: 12px; transition: 0.3s;
}
.btn-neon-purple:hover { background: #bb86fc; color: #120018; box-shadow: 0 0 15px #bb86fc; }

.btn-neon-pink {
    background: linear-gradient(135deg, #f107a3, #ff0080); border: none; color: white;
    font-weight: bold; border-radius: 12px; transition: 0.3s;
}
.btn-neon-pink:hover { transform: scale(1.05); box-shadow: 0 0 20px #f107a3; color: white; }

.section-title { border-left: 5px solid #f107a3; padding-left: 15px; margin-bottom: 30px; font-weight: 700; color: #ffffff; text-shadow: 0 0 10px rgba(241, 7, 163, 0.8); }
.badge-cart { position: absolute; top: -5px; right: -5px; background: #f107a3; color: white; font-size: 10px; padding: 3px 7px; border-radius: 50%; font-weight: bold; }

.search-input {
    background: #c6a9cdd5 !important;
    border: 1px solid rgba(187, 134, 252, 0.5) !important;
    color: #ffffff !important;
    border-radius: 25px !important;
    padding-left: 20px !important;
    transition: all 0.3s ease;
}

.modal-content.custom-popup {
    background: rgba(26, 0, 40, 0.85);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(187, 134, 252, 0.3);
    border-radius: 25px;
    color: #fff;
}

.neon-icon {
    font-size: 4rem; color: #bb86fc;
    text-shadow: 0 0 10px #bb86fc, 0 0 20px #bb86fc;
    animation: neon-glow 1.5s ease-in-out infinite alternate;
}

@keyframes neon-glow {
    from { opacity: 0.8; transform: scale(1); }
    to { opacity: 1; transform: scale(1.1); text-shadow: 0 0 20px #f107a3, 0 0 30px #f107a3; color: #f107a3; }
}

.btn-neon-close {
    background: linear-gradient(135deg, #bb86fc, #7c3aed);
    border: none; border-radius: 30px; padding: 10px 40px;
    font-weight: 600; color: white; transition: 0.3s;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark py-3">
<div class="container">
    <a class="navbar-brand fw-bold text-white" href="index.php">🎵 Goods Secret Store</a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <form method="GET" class="d-flex d-none d-md-flex">
            <input class="form-control me-2 search-input" type="search" name="search" placeholder="ค้นหาความสินค้า...">
            <button class="modern-btn"><i class="bi bi-search"></i></button>
        </form>
        
        <?php if(isset($_SESSION['user_id'])){ ?>
            <a href="profile.php" class="modern-btn" title="บัญชีของฉัน">
                <i class="bi bi-person-circle"></i> บัญชีของฉัน
            </a>

            <a href="cart.php" class="modern-btn position-relative">
                <i class="bi bi-cart"></i>
                <span id="cart-badge" class="badge-cart" style="<?= ($cart_count > 0) ? '' : 'display:none;' ?>">
                    <?= $cart_count ?>
                </span>
            </a>
            <a href="logout.php" class="modern-btn">ออกจากระบบ</a>
        <?php } else { ?>
            <a href="login.php" class="modern-btn">เข้าสู่ระบบ</a>
            <a href="register.php" class="modern-btn">สมัครสมาชิก</a>
        <?php } ?>
    </div>
</div>
</nav>

<div class="container mt-4">
    <div id="mainBanner" class="carousel slide carousel-fade shadow-lg rounded-4 overflow-hidden" data-bs-ride="carousel" data-bs-interval="3500">
        <div class="carousel-inner">
            <div class="carousel-item active"><img src="images/BN1.png" class="d-block w-100" style="height:420px; object-fit:cover;"></div>
            <div class="carousel-item"><img src="images/BN2.png" class="d-block w-100" style="height:420px; object-fit:cover;"></div>
        </div>
    </div>
</div>

<div class="container text-center mt-4">
    <a href="index.php?category=all" class="modern-btn m-1 <?= ($category_slug=='all' || $category_slug=='')?'active-category':'' ?>">ทั้งหมด</a>
    <?php while($cat = $categories->fetch_assoc()){ ?>
        <a href="index.php?category=<?= $cat['slug']; ?>" class="modern-btn m-1 <?= ($category_slug==$cat['slug'])?'active-category':'' ?>"><?= $cat['name']; ?></a>
    <?php } ?>
</div>

<div class="container my-5">
    <?php if($showLanding){ ?>
        <h4 class="section-title">⭐ สินค้าแนะนำ</h4>
        <div class="row mb-5">
            <?php while($p = $recommended->fetch_assoc()){ ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card p-3 text-center h-100" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                    <img src="images/<?= $p['image']; ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;">
                    <h6 class="text-truncate px-2 text-white"><?= $p['name']; ?></h6>
                    <p class="text-info fw-bold mb-3"><?= number_format($p['price']); ?> บาท</p>
                    <div class="mt-auto d-flex gap-2 p-2" onclick="event.stopPropagation();">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-neon-purple btn-sm w-50 py-2">ลงตะกร้า</button>
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink btn-sm w-50 py-2 d-flex align-items-center justify-content-center text-decoration-none">สั่งซื้อ</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <h4 class="section-title">🆕 สินค้ามาใหม่</h4>
        <div class="row mb-5">
            <?php while($p = $newArrival->fetch_assoc()){ ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card p-3 text-center h-100" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                    <img src="images/<?= $p['image']; ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;">
                    <h6 class="text-truncate px-2 text-white"><?= $p['name']; ?></h6>
                    <p class="text-info fw-bold mb-3"><?= number_format($p['price']); ?> บาท</p>
                    <div class="mt-auto d-flex gap-2 p-2" onclick="event.stopPropagation();">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-neon-purple btn-sm w-50 py-2">ลงตะกร้า</button>
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink btn-sm w-50 py-2 d-flex align-items-center justify-content-center text-decoration-none">สั่งซื้อ</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <h4 class="section-title">🔍 ผลลัพธ์การค้นหา</h4>
        <div class="row">
            <?php while($p = $products->fetch_assoc()){ ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card p-3 text-center h-100" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                    <img src="images/<?= $p['image']; ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;">
                    <h6 class="text-truncate px-2 text-white"><?= $p['name']; ?></h6>
                    <p class="text-info fw-bold mb-3"><?= number_format($p['price']); ?> บาท</p>
                    <div class="mt-auto d-flex gap-2 p-2" onclick="event.stopPropagation();">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-neon-purple btn-sm w-50 py-2">ลงตะกร้า</button>
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink btn-sm w-50 py-2 d-flex align-items-center justify-content-center text-decoration-none">สั่งซื้อ</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup">
            <div class="modal-body text-center py-5">
                <div class="mb-4"><i class="bi bi-magic neon-icon"></i></div>
                <h3 class="fw-bold mb-3" style="color: #00f2fe;">เพิ่มสินค้าสำเร็จ!</h3>
                <p class="fs-5 opacity-75 mb-4">สินค้าชิ้นนี้ถูกเพิ่มลงในตะกร้าของคุณแล้ว 🔮</p>
                <button type="button" class="btn btn-neon-close" data-bs-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addToCart(productId) {
    fetch('add_to_cart.php?id=' + productId + '&ajax=1')
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            const badge = document.getElementById('cart-badge');
            if(badge) { badge.textContent = data.total; badge.style.display = 'block'; }
            var myModal = new bootstrap.Modal(document.getElementById('cartModal'));
            myModal.show();
        } else { window.location.href = 'login.php'; }
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>