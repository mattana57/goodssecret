<?php
session_start();
include "connectdb.php";

$category_slug = $_GET['category'] ?? "";
$search = $_GET['search'] ?? ""; 
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "SELECT products.*, categories.name as category_name, categories.slug,
        (SELECT MIN(price) FROM product_variants WHERE product_id = products.id) as min_v_price,
        (SELECT SUM(stock) FROM product_variants WHERE product_id = products.id) as total_v_stock
        FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE 1";

if($category_slug && $category_slug != "all"){ $sql .= " AND categories.slug = '".$conn->real_escape_string($category_slug)."'"; }
if($search){ $sql .= " AND products.name LIKE '%".$conn->real_escape_string($search)."%'"; }

$showLanding = (!$category_slug && !$search);
if(!$showLanding){ $products = $conn->query($sql); }

$recommended = $conn->query("SELECT p.*, (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_v_price FROM products p WHERE p.is_hot=1 LIMIT 8");
$newArrival = $conn->query("SELECT p.*, (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_v_price FROM products p WHERE p.is_hot=0 ORDER BY p.id DESC LIMIT 8");

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
    <title>Goods Secret Store | Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
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
        .product-card:hover { transform: translateY(-12px); border-color: #bb86fc; box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
        .btn-neon-pink {
            background: linear-gradient(135deg, #f107a3, #ff0080); border: none; color: white;
            font-weight: bold; border-radius: 12px; transition: 0.3s;
        }
        .section-title { border-left: 5px solid #f107a3; padding-left: 15px; margin-bottom: 30px; font-weight: 700; }
        
        /* --- [ส่วนที่เพิ่มใหม่] สไตล์ปุ่มติดต่อแอดมิน --- */
        .admin-contact-float {
            position: fixed; bottom: 30px; right: 30px; z-index: 2000;
            display: flex; flex-direction: column; align-items: flex-end; gap: 15px;
        }
        .contact-trigger {
            width: 65px; height: 65px; border-radius: 50%;
            background: linear-gradient(135deg, #f107a3, #bb86fc);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #fff; cursor: pointer;
            box-shadow: 0 0 20px rgba(241, 7, 163, 0.5);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .contact-trigger:hover { transform: scale(1.1) rotate(15deg); box-shadow: 0 0 30px #f107a3; }
        .contact-menu { display: none; flex-direction: column; gap: 10px; animation: fadeInUp 0.3s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .contact-link {
            background: rgba(26, 0, 40, 0.9); backdrop-filter: blur(10px);
            border: 1px solid rgba(241, 7, 163, 0.4); padding: 12px 25px;
            border-radius: 30px; color: #fff; text-decoration: none;
            display: flex; align-items: center; gap: 12px; font-weight: 600;
            transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .contact-link:hover { transform: translateX(-10px); background: #f107a3; border-color: #fff; color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="index.php">🎵 Goods Secret Store</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <form method="GET" class="d-flex d-none d-md-flex">
                <input class="form-control me-2 bg-dark text-white border-secondary" type="search" name="search" placeholder="ค้นหาความลับ..." value="<?= htmlspecialchars($search) ?>">
                <button class="modern-btn"><i class="bi bi-search"></i></button>
            </form>
            <?php if(isset($_SESSION['user_id'])){ ?>
                <a href="profile.php" class="modern-btn"><i class="bi bi-person-circle"></i> บัญชีของฉัน</a>
                <a href="cart.php" class="modern-btn position-relative">
                    <i class="bi bi-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $cart_count ?></span>
                    <?php endif; ?>
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
    <div id="mainBanner" class="carousel slide carousel-fade shadow-lg rounded-4 overflow-hidden" data-bs-ride="carousel">
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
            <?php while($p = $recommended->fetch_assoc()){ 
                $price_display = ($p['min_v_price'] > 0) ? "เริ่ม ฿".number_format($p['min_v_price']) : "฿".number_format($p['price']);
            ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card p-3 text-center h-100" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                    <img src="images/<?= $p['image']; ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;">
                    <h6 class="text-truncate px-2 text-white fw-bold mt-2"><?= $p['name']; ?></h6>
                    <p class="text-info fw-bold mb-3"><?= $price_display ?> บาท</p>
                    <div class="mt-auto d-flex gap-2 p-2" onclick="event.stopPropagation();">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline-info btn-sm w-50 py-2 rounded-pill">ลงตะกร้า</button>
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink btn-sm w-50 py-2 text-decoration-none rounded-pill">สั่งซื้อ</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <h4 class="section-title">🔍 ผลการค้นหา</h4>
        <div class="row">
            <?php if($products->num_rows > 0){ ?>
                <?php while($p = $products->fetch_assoc()){ 
                    $price_display = ($p['min_v_price'] > 0) ? "เริ่ม ฿".number_format($p['min_v_price']) : "฿".number_format($p['price']);
                ?>
                <div class="col-md-3 mb-4">
                    <div class="card product-card p-3 text-center h-100" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                        <img src="images/<?= $p['image']; ?>" class="img-fluid mb-2 rounded-4" style="height:200px; object-fit:cover;">
                        <h6 class="text-truncate px-2 text-white fw-bold mt-2"><?= $p['name']; ?></h6>
                        <p class="text-info fw-bold mb-3"><?= $price_display ?> บาท</p>
                        <div class="mt-auto d-flex gap-2 p-2" onclick="event.stopPropagation();">
                            <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline-info btn-sm w-50 py-2 rounded-pill">ลงตะกร้า</button>
                            <a href="add_to_cart.php?id=<?= $p['id'] ?>&action=buy" class="btn btn-neon-pink btn-sm w-50 py-2 text-decoration-none rounded-pill">สั่งซื้อ</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <?php } else { echo "<div class='text-center py-5'>ไม่พบสินค้าที่ตรงกับเงื่อนไข</div>"; } ?>
        </div>
    <?php } ?>
</div>

<div class="admin-contact-float">
    <div class="contact-menu" id="contactMenu">
        <a href="https://line.me/ti/p/~YOUR_LINE_ID" target="_blank" class="contact-link">
            <i class="bi bi-line text-success fs-4"></i> ติดต่อทาง Line
        </a>
        <a href="https://m.me/YOUR_PAGE_NAME" target="_blank" class="contact-link">
            <i class="bi bi-messenger text-primary fs-4"></i> Messenger
        </a>
    </div>
    <div class="contact-trigger" onclick="toggleContactMenu()">
        <i class="bi bi-chat-dots-fill"></i>
    </div>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: rgba(26, 0, 40, 0.95); backdrop-filter: blur(20px); border-radius: 30px;">
            <div class="modal-body text-center py-5">
                <i class="bi bi-magic fs-1 mb-3" style="color: #00f2fe;"></i>
                <h3 class="fw-bold mb-3" style="color: #00f2fe;">เพิ่มสินค้าสำเร็จ!</h3>
                <p class="fs-5 opacity-75 mb-4 text-white">สินค้าถูกเพิ่มลงในตะกร้าแล้ว 🔮</p>
                <button type="button" class="btn btn-neon-pink px-5 py-2 rounded-pill" data-bs-dismiss="modal">ตกลง</button>
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
                location.reload(); 
            } else { window.location.href = 'login.php'; }
        });
    }

    function toggleContactMenu() {
        const menu = document.getElementById('contactMenu');
        menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
    }

    window.onclick = function(event) {
        if (!event.target.closest('.admin-contact-float')) {
            document.getElementById('contactMenu').style.display = 'none';
        }
    }
</script>
</body>
</html>