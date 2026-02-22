<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- [ส่วนที่แทรกเพิ่ม]: ระบบจัดการจำนวนสินค้า (บวกลบ) ---
if (isset($_GET['action']) && isset($_GET['product_id'])) {
    $p_id = intval($_GET['product_id']);
    $v_id = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0;
    
    if ($_GET['action'] == 'increase') {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $p_id AND variant_id = $v_id");
    } elseif ($_GET['action'] == 'decrease') {
        $conn->query("UPDATE cart SET quantity = GREATEST(1, quantity - 1) WHERE user_id = $user_id AND product_id = $p_id AND variant_id = $v_id");
    }
    header("Location: cart.php");
    exit();
}

// --- [ส่วนที่แทรกเพิ่ม]: แก้ไขฟังก์ชันลบเดิมให้เช็ค variant_id ด้วย ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $del_variant = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0;
    $conn->query("DELETE FROM cart WHERE user_id = $user_id AND product_id = $del_id AND variant_id = $del_variant");
    header("Location: cart.php");
    exit();
}

// [ปรับ SQL]: เชื่อมตารางรุ่นย่อยเพื่อดึงชื่อรุ่นและรูปเฉพาะรุ่นมาโชว์
$sql = "SELECT cart.*, products.name, products.price, 
        IFNULL(pv.variant_image, products.image) AS display_image, 
        pv.variant_name 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
        LEFT JOIN product_variants pv ON cart.variant_id = pv.id 
        WHERE cart.user_id = $user_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #120018; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .cart-container { max-width: 1000px; margin: 50px auto; padding: 20px; }
        .cart-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 20px; padding: 25px; border: 1px solid rgba(187, 134, 252, 0.2); transition: 0.3s; }
        .cart-card:hover { border-color: #bb86fc; box-shadow: 0 0 20px rgba(187, 134, 252, 0.2); }
        .product-img { width: 100px; height: 100px; object-fit: cover; border-radius: 15px; border: 2px solid rgba(187, 134, 252, 0.3); }
        .btn-neon { background: #bb86fc; color: #120018; font-weight: bold; border-radius: 10px; transition: 0.3s; }
        .btn-neon:hover { background: #d39ddb; box-shadow: 0 0 15px #bb86fc; transform: translateY(-2px); }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 5px #bb86fc; }
        .delete-popup { background: rgba(26, 0, 40, 0.95); border: 1px solid #ff4d4d; border-radius: 25px; }
    </style>
</head>
<body>

<div class="container cart-container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold"><i class="bi bi-cart3 me-2 text-neon-purple"></i>ตะกร้าสินค้าของคุณ</h2>
        <a href="index.php" class="btn btn-outline-light rounded-pill btn-sm px-4">เลือกสินค้าเพิ่ม</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php $total = 0; while($item = $result->fetch_assoc()): 
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
            ?>
            <div class="cart-card mb-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <img src="images/<?= htmlspecialchars($item['display_image']) ?>" class="product-img me-4">
                    <div>
                        <p class="fw-bold mb-1 fs-5"><?= htmlspecialchars($item['name']) ?></p>
                        <?php if(!empty($item['variant_name'])): ?>
                            <small class="text-neon-purple d-block mb-1">รุ่น: <?= htmlspecialchars($item['variant_name']) ?></small>
                        <?php endif; ?>
                        <p class="text-info mb-0 fw-bold">฿<?= number_format($item['price']) ?></p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-4">
                    <div class="btn-group border border-secondary rounded-pill overflow-hidden shadow-sm">
                        <a href="cart.php?action=decrease&product_id=<?= $item['product_id'] ?>&variant_id=<?= $item['variant_id'] ?>" class="btn btn-sm text-white px-3 border-0">-</a>
                        <span class="px-3 py-1 bg-white bg-opacity-10 fw-bold"><?= $item['quantity'] ?></span>
                        <a href="cart.php?action=increase&product_id=<?= $item['product_id'] ?>&variant_id=<?= $item['variant_id'] ?>" class="btn btn-sm text-white px-3 border-0">+</a>
                    </div>
                    <button class="btn btn-outline-danger btn-sm rounded-circle p-2" onclick="showDeleteModal(<?= $item['product_id'] ?>, <?= $item['variant_id'] ?>)">
                        <i class="bi bi-trash3 fs-5"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="cart-card sticky-top" style="top: 20px;">
                <h4 class="fw-bold mb-4">สรุปรายการคำสั่งซื้อ</h4>
                <div class="d-flex justify-content-between mb-3 fs-5">
                    <span class="opacity-75">ยอดรวมสินค้า</span>
                    <span class="text-neon-purple fw-bold">฿<?= number_format($total) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 fs-5">
                    <span class="opacity-75">ค่าจัดส่ง</span>
                    <span class="text-success fw-bold">ฟรี</span>
                </div>
                <hr class="opacity-20 my-4">
                <div class="d-flex justify-content-between mb-4 fs-4 fw-bold">
                    <span>ราคาสุทธิ</span>
                    <span class="text-neon-purple">฿<?= number_format($total) ?></span>
                </div>
                <a href="checkout.php" class="btn btn-neon w-100 py-3 fs-5 shadow-lg">ดำเนินการชำระเงิน</a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="text-center py-5 cart-card">
            <i class="bi bi-cart-x display-1 opacity-20 mb-4 text-neon-purple"></i>
            <h3>ตะกร้าของคุณยังว่างเปล่า</h3>
            <p class="opacity-50 fs-5">ไปเลือกสินค้าที่คุณชอบมาใส่ไว้ที่นี่สิ</p>
            <a href="index.php" class="btn btn-neon px-5 py-3 mt-4 fs-5">กลับไปหน้าแรก</a>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content delete-popup">
            <div class="modal-body text-center py-5">
                <div class="mb-4"><i class="bi bi-trash3 neon-delete-icon" style="font-size: 4rem; color: #ff4d4d;"></i></div>
                <h3 class="fw-bold mb-3" style="color: #ff4d4d;">ยืนยันการลบ?</h3>
                <p class="fs-5 opacity-75 mb-4 text-white">ต้องการเอาสินค้าชิ้นนี้ออกจากตะกร้าหรือไม่?</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger rounded-pill px-4 text-decoration-none">ยืนยันการลบ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showDeleteModal(productId, variantId) {
        document.getElementById('confirmDeleteBtn').href = `cart.php?delete_id=${productId}&variant_id=${variantId}`;
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }
</script>
</body>
</html>