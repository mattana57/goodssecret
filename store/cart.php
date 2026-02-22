<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- [ส่วนที่แทรกเพิ่ม 1]: ระบบจัดการจำนวนสินค้า (บวกลบ) ---
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

// --- [ส่วนที่แทรกเพิ่ม 2]: แก้ไขฟังก์ชันลบเดิมให้เช็ค variant_id ด้วย เพื่อไม่ให้ลบผิดแบบ ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $v_id = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0;
    $conn->query("DELETE FROM cart WHERE user_id = $user_id AND product_id = $del_id AND variant_id = $v_id");
    header("Location: cart.php");
    exit();
}

// --- [ส่วนที่แทรกเพิ่ม 3]: แก้ไข Query ให้ดึงชื่อแบบสินค้า (variant_name) มาแสดงด้วย ---
$sql = "SELECT cart.*, products.name, products.price, products.image, pv.variant_name 
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
    <title>ตะกร้าสินค้า - Goods Secret</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* --- [โค้ด CSS เดิมของคุณทั้งหมด] --- */
        body {
            background-color: #0f172a;
            color: #ffffff !important;
            background-image: radial-gradient(circle at top right, #3d1263, transparent), 
                              radial-gradient(circle at bottom left, #1e1b4b, transparent);
            min-height: 100vh;
        }
        h2, h4, h5 {
            color: #ffffff !important;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }
        .table { color: #ffffff !important; }
        .table thead th {
            color: #bb86fc !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2) !important;
            text-transform: uppercase;
            font-size: 0.95rem;
            letter-spacing: 1px;
        }
        .fw-bold.text-white, .fs-5.text-white { color: #ffffff !important; }
        .text-secondary-bright { color: #e2e8f0 !important; }
        .text-neon-cyan {
            color: #00f2fe !important; 
            text-shadow: 0 0 12px rgba(0, 242, 254, 0.6);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(187, 134, 252, 0.3) !important; 
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 15px rgba(187, 134, 252, 0.1) !important; 
            padding: 25px;
            transition: all 0.3s ease;
        }
        .glass-panel:hover {
            border-color: rgba(187, 134, 252, 0.6) !important;
            transform: translateY(-5px);
        }
        .table { --bs-table-bg: transparent !important; background-color: transparent !important; }
        .product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-checkout {
            background: linear-gradient(135deg, #f107a3, #ff0080) !important;
            border: none !important; color: #ffffff !important; font-weight: 700;
            padding: 15px 30px; border-radius: 50px; transition: 0.4s;
            box-shadow: 0 5px 20px rgba(241, 7, 163, 0.5); width: 100%;
        }
        .btn-checkout:hover { transform: translateY(-5px); filter: brightness(1.2); }
        .btn-remove { color: rgba(255, 255, 255, 0.4); font-size: 1.2rem; transition: 0.3s; cursor: pointer; }
        .btn-remove:hover { color: #ff4d4d; transform: rotate(15deg) scale(1.2); }
        .modal-content.delete-popup { background: rgba(40, 0, 10, 0.9); backdrop-filter: blur(15px); border: 1px solid rgba(255, 77, 77, 0.3); border-radius: 25px; color: #fff; }

        /* --- [ส่วนที่แทรกเพิ่ม]: สไตล์สำหรับปุ่มบวกลบ --- */
        .qty-btn { color: #bb86fc; font-size: 1.2rem; text-decoration: none; transition: 0.2s; }
        .qty-btn:hover { color: #00f2fe; transform: scale(1.1); }
        .qty-box { background: rgba(255,255,255,0.1); border: 1px solid rgba(187,134,252,0.3); border-radius: 8px; min-width: 40px; display: inline-block; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
    <h2 class="mb-5 text-white"><i class="bi bi-cart3 text-neon-cyan me-3"></i>ตะกร้าสินค้าของคุณ</h2>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-panel">
                <?php if($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ข้อมูลสินค้า</th>
                                <th class="text-end">ราคา</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">รวม</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            while($row = $result->fetch_assoc()): 
                                $subtotal = $row['price'] * $row['quantity'];
                                $grand_total += $subtotal;
                            ?>
                            <tr style="vertical-align: middle;">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="images/<?= $row['image'] ?>" class="product-img me-3">
                                        <div>
                                            <div class="fw-bold text-white fs-5"><?= $row['name'] ?></div>
                                            <?php if(!empty($row['variant_name'])): ?>
                                                <div class="text-info small">แบบ: <?= $row['variant_name'] ?></div>
                                            <?php endif; ?>
                                            <div class="text-secondary-bright small">ID: #<?= $row['product_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end text-white">฿<?= number_format($row['price']) ?></td>
                                
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <a href="cart.php?action=decrease&product_id=<?= $row['product_id'] ?>&variant_id=<?= $row['variant_id'] ?>" class="qty-btn">
                                            <i class="bi bi-dash-circle"></i>
                                        </a>
                                        <span class="qty-box py-1 text-white"><?= $row['quantity'] ?></span>
                                        <a href="cart.php?action=increase&product_id=<?= $row['product_id'] ?>&variant_id=<?= $row['variant_id'] ?>" class="qty-btn">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                    </div>
                                </td>
                                
                                <td class="text-end fw-bold text-neon-cyan">฿<?= number_format($subtotal) ?></td>
                                <td class="text-end">
                                    <a href="javascript:void(0)" class="btn-remove" onclick="showDeleteModal(<?= $row['product_id'] ?>, <?= $row['variant_id'] ?>)">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x display-1 opacity-25"></i>
                        <p class="mt-4 fs-4 text-secondary-bright">ยังไม่มีสินค้าในตะกร้า</p>
                        <a href="index.php" class="btn btn-outline-info rounded-pill px-4 mt-2">เริ่มช้อปปิ้ง</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-panel p-3">
                <h4 class="mb-4 text-white">สรุปการสั่งซื้อ</h4>
                <div class="d-flex justify-content-between mb-3 fs-5">
                    <span class="text-secondary-bright">ยอดรวม</span>
                    <span class="text-white">฿<?= number_format($grand_total ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 fs-5">
                    <span class="text-secondary-bright">ค่าส่ง</span>
                    <span class="text-success fw-bold">ฟรี</span>
                </div>
                <hr class="my-4 border-secondary opacity-50">
                <div class="d-flex justify-content-between mb-5">
                    <span class="h4 text-white">รวมสุทธิ</span>
                    <span class="h3 fw-bold text-neon-cyan">฿<?= number_format($grand_total ?? 0) ?></span>
                </div>
                
                <?php if ($grand_total > 0): ?>
                    <a href="checkout.php" class="btn btn-checkout mb-4 text-decoration-none d-flex align-items-center justify-content-center">
                        ชำระเงิน <i class="bi bi-arrow-right-circle ms-2"></i>
                    </a>
                <?php else: ?>
                    <button class="btn btn-checkout mb-4" disabled style="opacity: 0.5; cursor: not_allowed;">
                        ชำระเงิน (ไม่มีสินค้า) <i class="bi bi-arrow-right-circle ms-2"></i>
                    </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-link w-100 text-secondary-bright text-decoration-none text-center d-block">เลือกซื้อสินค้าต่อ</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content delete-popup">
            <div class="modal-body text-center py-5">
                <div class="mb-4"><i class="bi bi-trash3 neon-delete-icon" style="font-size: 4rem; color: #ff4d4d;"></i></div>
                <h3 class="fw-bold mb-3" style="color: #ff4d4d;">ยืนยันการลบ?</h3>
                <p class="fs-5 opacity-75 mb-4 text-white">ลบสินค้าชิ้นนี้ออกจากตะกร้า?</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger rounded-pill px-4 text-decoration-none">ยืนยัน</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // [แทรกเพิ่ม]: ฟังก์ชันลบให้รองรับ variantId
    function showDeleteModal(productId, variantId) {
        const deleteUrl = 'cart.php?delete_id=' + productId + '&variant_id=' + variantId;
        document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
        var myModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        myModal.show();
    }
</script>
</body>
</html>