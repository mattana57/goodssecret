<?php
session_start();
include "connectdb.php";

/* ================= 1. ดึงข้อมูลให้สอดคล้องกับ Database ================= */
$category_slug = $_GET['category'] ?? "";
$search = $_GET['search'] ?? "";

// ดึงหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// SQL หลักสำหรับแสดงสินค้า (กรณีมีการกรองหรือค้นหา)
$sql = "SELECT p.*, c.name as category_name, c.slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1";

if($category_slug && $category_slug != "all"){ 
    $sql .= " AND c.slug = '".$conn->real_escape_string($category_slug)."'"; 
}
if($search){ 
    $sql .= " AND p.name LIKE '%".$conn->real_escape_string($search)."%'"; 
}

$showLanding = (!$category_slug && !$search);
if(!$showLanding){ 
    $products = $conn->query($sql); 
}

// ดึงสินค้าแนะนำ (ปรับให้ตรงกับคอลัมน์ is_hot ใน DB)
$recommended = $conn->query("SELECT * FROM products WHERE is_hot=1 LIMIT 8");

// ดึงสินค้ามาใหม่
$newArrival = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT 8");

// ดึงสินค้าลดราคา (เช็คจากคอลัมน์ discount)
$discountProducts = $conn->query("SELECT * FROM products WHERE discount > 0 LIMIT 8");

/* --- ส่วนนับจำนวนสินค้าในตะกร้า --- */
$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $q_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $u_id");
    $row_count = $q_count->fetch_assoc();
    $cart_count = $row_count['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Secret Store | อาณาจักรสินค้าลับ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* คง CSS เดิมของคุณไว้ 100% เพื่อรักษาหน้าตาเว็บ */
        body { background-color: #0c001c; color: white; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: rgba(12, 0, 28, 0.8) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0, 242, 254, 0.3); }
        .product-card { 
            background: rgba(255, 255, 255, 0.05); 
            border: 1px solid rgba(187, 134, 252, 0.2); 
            border-radius: 20px; transition: 0.3s; overflow: hidden;
        }
        .product-card:hover { transform: translateY(-10px); border-color: #00f2fe; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }
        .product-img { width: 100%; height: 250px; object-fit: cover; }
        .price-tag { color: #00f2fe; font-size: 1.4rem; font-weight: bold; }
        .btn-neon { background: linear-gradient(45deg, #00f2fe, #bb86fc); border: none; color: #0c001c; font-weight: bold; border-radius: 50px; }
        /* ... ส่วน CSS อื่นๆ คงเดิม ... */
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php">
            <span style="color: #00f2fe;">GOODS</span> <span style="color: #bb86fc;">SECRET</span>
        </a>
        </div>
</nav>

<div class="container mt-5">
    <?php if($showLanding){ ?>
        <h2 class="mb-4" style="color: #00f2fe;"><i class="bi bi-stars me-2"></i>สินค้าแนะนำสำหรับคุณ</h2>
        <div class="row g-4 mb-5">
            <?php while($row = $recommended->fetch_assoc()){ ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100 p-3">
                    <a href="product_detail.php?id=<?= $row['id'] ?>">
                        <img src="images/<?= $row['image'] ?>" class="product-img rounded-4 mb-3" onerror="this.src='https://via.placeholder.com/300'">
                    </a>
                    <h5 class="text-truncate"><?= $row['name'] ?></h5>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="price-tag">฿<?= number_format($row['price']) ?></span>
                        <button onclick="addToCart(<?= $row['id'] ?>)" class="btn btn-neon btn-sm px-3">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <h2 class="mb-4" style="color: #bb86fc;"><i class="bi bi-lightning-charge me-2"></i>สินค้ามาใหม่ล่าสุด</h2>
        <div class="row g-4 mb-5">
            <?php while($row = $newArrival->fetch_assoc()){ ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100 p-3">
                    <img src="images/<?= $row['image'] ?>" class="product-img rounded-4 mb-3">
                    <h5 class="text-truncate"><?= $row['name'] ?></h5>
                    <div class="price-tag mb-2">฿<?= number_format($row['price']) ?></div>
                    <button onclick="addToCart(<?= $row['id'] ?>)" class="btn btn-outline-info w-100 rounded-pill btn-sm">เพิ่มลงตะกร้า</button>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <h2 class="mb-4">ผลการค้นหา: "<?= htmlspecialchars($search ?: $category_slug) ?>"</h2>
        <div class="row g-4">
            <?php while($row = $products->fetch_assoc()){ ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100 p-3">
                    <img src="images/<?= $row['image'] ?>" class="product-img rounded-4 mb-3">
                    <h5><?= $row['name'] ?></h5>
                    <div class="price-tag">฿<?= number_format($row['price']) ?></div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function addToCart(productId) {
        // ฟังก์ชันเดิมที่คุณทำไว้
        fetch('add_to_cart.php?id=' + productId + '&ajax=1')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('cart-badge').textContent = data.count;
                var myModal = new bootstrap.Modal(document.getElementById('cartModal'));
                myModal.show();
            }
        });
    }
</script>
</body>
</html>