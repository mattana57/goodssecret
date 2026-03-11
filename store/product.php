<?php
session_start();
include "connectdb.php";

$id = intval($_GET['id']);
$product = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();

$product_images = $conn->query("
    SELECT * FROM product_images 
    WHERE product_id = $id
");

$variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $id");

if(!$product){
    header("Location:index.php");
    exit();
}

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
<title><?=$product['name']?> | Goods Secret Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), 
                radial-gradient(circle at 80% 70%, #6a1b9a 0%, transparent 40%), 
                linear-gradient(135deg,#120018,#2a0845,#3d1e6d);
    color: #ffffff !important; font-family: 'Segoe UI', sans-serif; min-height: 100vh;
}
.navbar { background: rgba(26, 0, 40, 0.85); backdrop-filter: blur(15px); position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(187, 134, 252, 0.2); }
.product-card-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px; }
.product-title { font-weight: 700; font-size: 32px; color: #ffffff; text-shadow: 0 0 15px rgba(187, 134, 252, 0.5); }
.product-price { color: #00f2fe !important; font-size: 35px; font-weight: 700; text-shadow: 0 0 10px rgba(0, 242, 254, 0.4); transition: 0.3s; }
.variant-option { display: inline-block; padding: 10px 20px; margin-right: 10px; margin-bottom: 10px; border: 1.5px solid rgba(187, 134, 252, 0.3); border-radius: 12px; cursor: pointer; transition: 0.3s; background: rgba(255, 255, 255, 0.05); color: white; }
.variant-option.active { border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.5); background: rgba(0, 242, 254, 0.15); font-weight: bold; }
.qty-control { width: 140px; display: flex; align-items: center; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 10px; overflow: hidden; }
.qty-btn { background: transparent; border: none; color: white; width: 40px; height: 40px; }
#product_qty { background: transparent !important; border: none !important; color: white !important; text-align: center; width: 60px; font-weight: bold; }
.btn-neon-purple { background: rgba(187, 134, 252, 0.1); border: 1px solid #bb86fc; color: #bb86fc; font-weight: 600; border-radius: 12px; padding: 15px; width: 100%; transition: 0.3s; }
.btn-neon-purple:hover { background: #bb86fc; color: #120018; box-shadow: 0 0 20px #bb86fc; }
.btn-neon-pink { background: linear-gradient(135deg, #f107a3, #ff0080); border: none; color: white; font-weight: bold; border-radius: 12px; padding: 15px; text-decoration: none; display: block; text-align: center; transition: 0.3s; }
.btn-neon-pink:hover { transform: translateY(-3px); box-shadow: 0 0 25px #f107a3; color: white; }
.modern-btn { background: rgba(255,255,255,0.1); color:#fff; border: 1px solid rgba(255,255,255,0.2); padding: 8px 18px; border-radius: 30px; text-decoration: none; transition: 0.3s; }
.modern-btn:hover { background: #bb86fc; color:#120018; }
.badge-cart { position: absolute; top: -5px; right: -5px; background: #f107a3; color: white; font-size: 11px; padding: 2px 6px; border-radius: 50%; border: 1px solid #1a0028; }
.modal-content.custom-popup { background: rgba(26, 0, 40, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(187, 134, 252, 0.4); border-radius: 25px; color: #fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark py-3">
<div class="container">
    <a class="navbar-brand fw-bold text-white" href="index.php">🎵 Goods Secret Store</a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <form method="GET" action="index.php" class="d-flex">
            <input class="form-control me-2" style="background: rgba(255,255,255,0.1); border-radius: 20px; color:white; border:none; padding: 5px 15px;" type="search" name="search" placeholder="ค้นหาความลับ...">
            <button class="modern-btn"><i class="bi bi-search"></i></button>
        </form>
        <?php if(isset($_SESSION['user_id'])){ ?>
            <a href="profile.php" class="modern-btn"><i class="bi bi-person-circle"></i></a>
            <a href="cart.php" class="modern-btn position-relative">
                <i class="bi bi-cart"></i>
                <span id="cart-badge" class="badge-cart" style="<?= ($cart_count > 0) ? '' : 'display:none;' ?>"><?= $cart_count ?></span>
            </a>
            <a href="logout.php" class="modern-btn">ออกจากระบบ</a>
        <?php } else { ?>
            <a href="login.php" class="modern-btn">เข้าสู่ระบบ</a>
        <?php } ?>
    </div>
</div>
</nav>

<div class="container mt-5 py-4">
    <div class="row g-5">
        <div class="col-md-5">
            <div class="text-center">
                <img id="mainImage" src="images/<?= $product['image']; ?>" class="img-fluid mb-4 rounded-4 shadow-lg border border-secondary" style="max-height: 500px; object-fit: contain; background: rgba(0,0,0,0.2);">
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <?php if($product_images->num_rows > 0): ?>
                        <?php while($img = $product_images->fetch_assoc()){ ?>
                            <img src="images/<?= $img['image']; ?>" class="img-thumbnail border-secondary bg-dark" style="width: 80px; cursor: pointer;" onclick="document.getElementById('mainImage').src=this.src">
                        <?php } ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="product-card-panel shadow-lg">
                <h2 class="product-title mb-3"><?=$product['name']?></h2>
                <div id="dynamicPrice" class="product-price mb-4">฿<?=number_format($product['price'])?></div>
                <p id="dynamicDesc" style="line-height: 1.8; opacity: 0.9;"><?=$product['description']?></p>

                <?php if ($variants && $variants->num_rows > 0): ?>
                <div class="mt-4 mb-4" id="variant-container">
                    <label class="form-label small opacity-75 d-block mb-3">เลือกแบบที่คุณต้องการ (รุ่นย่อย):</label>
                    <div class="d-flex flex-wrap">
                        <?php while($v = $variants->fetch_assoc()): ?>
                            <div class="variant-option" 
                                 data-id="<?= $v['id'] ?>" 
                                 data-name="<?= $v['variant_name'] ?>" 
                                 data-price="<?= $v['price'] ?>" 
                                 data-image="images/<?= $v['variant_image'] ?>" 
                                 data-desc="<?= htmlspecialchars($v['variant_description']) ?>" 
                                 onclick="selectVariant(this)">
                                <?= $v['variant_name'] ?> (เหลือ <?= $v['stock'] ?>)
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <input type="hidden" id="selected_variant_id" value="">
                    <input type="hidden" id="selected_variant_name" value="">
                </div>
                <?php endif; ?>

                <div class="mt-4 mb-4">
                    <label class="form-label small opacity-75">ระบุจำนวน:</label>
                    <div class="qty-control">
                        <button class="qty-btn" type="button" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                        <input type="number" id="product_qty" class="form-control" value="1" min="1" readonly>
                        <button class="qty-btn" type="button" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
                    </div>
                </div>

                <div class="mt-5">
                    <?php if(isset($_SESSION['user_id'])){ ?>
                        <div class="row g-3">
                            <div class="col-6"><a href="javascript:void(0)" onclick="buyNow(<?=$product['id']?>)" class="btn btn-neon-pink text-white">สั่งซื้อทันที</a></div>
                            <div class="col-6"><button onclick="addToCart(<?=$product['id']?>)" class="btn btn-neon-purple">เพิ่มลงตะกร้า</button></div>
                        </div>
                    <?php } else { ?>
                        <a href="login.php" class="btn btn-neon-purple w-100 py-3 text-center">เข้าสู่ระบบเพื่อสั่งซื้อ</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5">
            <div class="modal-body">
                <i class="bi bi-magic mb-4" style="font-size: 4rem; color: #bb86fc; text-shadow: 0 0 20px #bb86fc;"></i>
                <h3 class="fw-bold mb-3" style="color: #00f2fe;">สำเร็จแล้ว!</h3>
                <p class="fs-5 opacity-75 mb-4"><span id="modal_variant_name"></span> ถูกเพิ่มลงตะกร้าแล้ว 🔮</p>
                <button type="button" class="btn px-5 py-2 rounded-pill text-white" style="background: linear-gradient(45deg, #7c3aed, #db2777); border:none;" data-bs-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectVariant(el) {
    document.querySelectorAll('.variant-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    
    const vid = el.getAttribute('data-id');
    const vname = el.getAttribute('data-name');
    const vprice = el.getAttribute('data-price');
    const vimg = el.getAttribute('data-image');
    const vdesc = el.getAttribute('data-desc');

    document.getElementById('selected_variant_id').value = vid;
    document.getElementById('selected_variant_name').value = vname;
    document.getElementById('dynamicPrice').innerText = '฿' + Number(vprice).toLocaleString();
    document.getElementById('dynamicDesc').innerText = vdesc;
    
    if(vimg && !vimg.includes('undefined')) {
        document.getElementById('mainImage').src = vimg;
    }
}

function changeQty(amt) {
    let q = document.getElementById('product_qty');
    let v = parseInt(q.value) + amt;
    if (v >= 1) q.value = v;
}

function addToCart(pid) {
    let qty = document.getElementById('product_qty').value;
    let vid = document.getElementById('selected_variant_id').value;
    let vname = document.getElementById('selected_variant_name').value;

    if(document.getElementById('variant-container') && vid === "") {
        alert("กรุณาเลือกแบบที่ต้องการก่อนครับ!");
        return;
    }

    fetch('add_to_cart.php?id=' + pid + '&qty=' + qty + '&variant_id=' + vid + '&ajax=1')
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('cart-badge').textContent = data.total;
            document.getElementById('cart-badge').style.display = 'block';
            document.getElementById('modal_variant_name').textContent = vname ? vname : "สินค้า";
            new bootstrap.Modal(document.getElementById('cartModal')).show();
        } else { window.location.href = 'login.php'; }
    });
}

function buyNow(pid) {
    let qty = document.getElementById('product_qty').value;
    let vid = document.getElementById('selected_variant_id').value;
    if(document.getElementById('variant-container') && vid === "") {
        alert("กรุณาเลือกแบบที่ต้องการก่อนครับ!");
        return;
    }
    window.location.href = 'add_to_cart.php?id=' + pid + '&qty=' + qty + '&variant_id=' + vid + '&action=buy';
}
</script>
</body>
</html>