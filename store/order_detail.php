<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: profile.php"); exit(); }

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$show_modal = "";

// --- [Logic ใหม่]: กดยืนยันการรับสินค้าโดยลูกค้า ---
if (isset($_POST['confirm_received'])) {
    $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id AND user_id = $user_id AND status = 'shipped'");
    header("Location: order_detail.php?id=$order_id&success=received"); exit();
}

// --- [ฟังก์ชันเดิม]: อัปเดตสลิปและข้อมูลจัดส่ง ---
if (isset($_POST['update_slip']) && isset($_FILES['slip_image'])) {
    $file = $_FILES['slip_image'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $order_id . "_" . time() . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], "uploads/slips/" . $new_name)) {
            $conn->query("UPDATE orders SET slip_image = '$new_name' WHERE id = $order_id AND user_id = $user_id");
            $show_modal = "update_success";
        }
    }
}

// ดึงข้อมูลออเดอร์ล่าสุด
$order_q = $conn->query("SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("ไม่พบข้อมูลออเดอร์"); }

$items_q = $conn->query("SELECT od.*, p.name AS p_name, p.image AS p_image, pv.variant_name, pv.variant_image 
                         FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดบิล #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #120018; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .invoice-card { background: rgba(26, 0, 40, 0.8); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .status-badge { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; padding: 6px 20px; border-radius: 50px; font-weight: bold; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px #f107a3; }
        .product-item { border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 0; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="mb-0">รายละเอียดคำสั่งซื้อ</h3>
            <span class="status-badge">
                <?php 
                    if($order['status'] == 'pending') echo 'รอตรวจสอบ';
                    elseif($order['status'] == 'processing') echo 'กำลังเตรียมจัดส่ง';
                    elseif($order['status'] == 'shipped') echo 'อยู่ระหว่างจัดส่ง';
                    elseif($order['status'] == 'delivered') echo 'จัดส่งสำเร็จแล้ว';
                    elseif($order['status'] == 'cancelled') echo 'ยกเลิกแล้ว';
                ?>
            </span>
        </div>

        <?php if($order['status'] == 'cancelled' && !empty($order['cancel_reason'])): ?>
            <div class="alert alert-danger bg-dark border-danger text-white rounded-4 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>เหตุผลจากทางร้าน:</strong> <?= htmlspecialchars($order['cancel_reason']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-6 border-end border-white border-opacity-10">
                <h6 class="text-secondary small mb-3 text-uppercase">ที่อยู่จัดส่ง</h6>
                <p class="mb-1 fw-bold"><?= htmlspecialchars($order['fullname']) ?></p>
                <p class="mb-1 small opacity-75"><?= htmlspecialchars($order['address']) ?></p>
                <p class="small opacity-75"><?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['zipcode']) ?></p>
                <p class="mb-0 small">โทร: <?= htmlspecialchars($order['phone']) ?></p>
            </div>
            <div class="col-md-6 ps-md-4">
                <h6 class="text-secondary small mb-3 text-uppercase">การชำระเงิน</h6>
                <p class="mb-1">หมายเลขบิล: <span class="text-info fw-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                <p class="mb-3 small">วิธีชำระ: <?= $order['payment_method'] == 'bank' ? 'โอนเงินผ่านธนาคาร' : 'เก็บเงินปลายทาง' ?></p>
                <?php if($order['payment_method'] == 'bank' && !empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" width="120" class="rounded border border-info" style="cursor:zoom-in" onclick="window.open(this.src)">
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="text-secondary small mb-3 text-uppercase">รายการสินค้า</h6>
            <?php while($item = $items_q->fetch_assoc()): ?>
                <div class="product-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="images/<?= $item['variant_image'] ?: $item['p_image'] ?>" width="50" class="rounded me-3">
                        <div>
                            <strong class="d-block"><?= $item['p_name'] ?></strong>
                            <small class="text-info"><?= $item['variant_name'] ?: 'ทั่วไป' ?> x <?= $item['quantity'] ?></small>
                        </div>
                    </div>
                    <span class="fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="d-flex justify-content-between pt-3 border-top border-white border-opacity-10 mb-5">
            <h5 class="fw-bold">ยอดรวมสุทธิ</h5>
            <h4 class="fw-bold text-neon-pink">฿<?= number_format($order['total_price']) ?></h4>
        </div>

        <div class="mt-4">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 border border-success rounded-4 bg-success bg-opacity-10 text-center shadow-lg">
                    <h5 class="text-success mb-3"><i class="bi bi-check-circle-fill"></i> ได้รับสินค้าเรียบร้อยแล้วใช่ไหม?</h5>
                    <form method="POST">
                        <button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill fw-bold py-2">ยืนยันการรับสินค้า</button>
                    </form>
                </div>
            <?php elseif($order['status'] == 'pending'): ?>
                <form method="POST" onsubmit="return confirm('คุณแน่ใจว่าต้องการยกเลิกคำสั่งซื้อนี้?')">
                    <button type="submit" name="confirm_cancel" class="btn btn-outline-danger w-100 rounded-pill py-3">ยกเลิกคำสั่งซื้อนี้</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>