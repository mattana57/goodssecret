<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: profile.php"); exit(); }

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$show_modal = "";

// --- [Logic ใหม่]: ยืนยันการรับสินค้าจากลูกค้า ---
if (isset($_POST['confirm_received'])) {
    $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id AND user_id = $user_id AND status = 'shipped'");
    header("Location: order_detail.php?id=$order_id&received=1"); exit();
}

// Logic แก้สลิปและที่อยู่ (ฟังก์ชันเดิมพี่ครบ 100%)
if (isset($_POST['update_slip']) && isset($_FILES['slip_image'])) {
    if ($_FILES['slip_image']['error'] === 0) {
        $new_name = "slip_" . $order_id . "_" . time() . "." . pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['slip_image']['tmp_name'], "uploads/slips/" . $new_name)) {
            $conn->query("UPDATE orders SET slip_image = '$new_name' WHERE id = $order_id AND user_id = $user_id");
            $show_modal = "update_success";
        }
    }
}

if (isset($_POST['update_shipping'])) {
    $fn = $conn->real_escape_string($_POST['fullname']); $ph = $conn->real_escape_string($_POST['phone']); 
    $ad = $conn->real_escape_string($_POST['address']); $pr = $conn->real_escape_string($_POST['province']); $zp = $conn->real_escape_string($_POST['zipcode']);
    $conn->query("UPDATE orders SET fullname='$fn', phone='$ph', address='$ad', province='$pr', zipcode='$zp' WHERE id=$order_id AND user_id=$user_id AND status='pending'");
    $show_modal = "update_success";
}

$order_q = $conn->query("SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("ไม่พบข้อมูลออเดอร์"); }

$items_q = $conn->query("SELECT od.*, p.name AS p_name, p.image AS p_image, pv.variant_name, pv.variant_image 
                         FROM order_details od JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดบิล #<?= $order_id ?> | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), linear-gradient(135deg,#120018,#2a0845,#3d1e6d); color: #fff; min-height: 100vh; }
        .invoice-card { background: rgba(26, 0, 40, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .status-pill { background: rgba(187, 134, 252, 0.1); color: #bb86fc; border: 1px solid #bb86fc; padding: 5px 25px; border-radius: 50px; font-weight: bold; }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); color: #fff; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">รายละเอียดคำสั่งซื้อ</h3>
            <span class="status-pill">
                <?php 
                    if($order['status'] == 'pending') echo 'รอตรวจสอบ';
                    elseif($order['status'] == 'processing') echo 'กำลังเตรียมจัดส่ง';
                    elseif($order['status'] == 'shipped') echo 'อยู่ระหว่างขนส่ง';
                    elseif($order['status'] == 'delivered') echo 'สำเร็จแล้ว';
                    elseif($order['status'] == 'cancelled') echo 'ยกเลิกแล้ว';
                ?>
            </span>
        </div>

        <?php if($order['status'] == 'cancelled' && !empty($order['cancel_reason'])): ?>
            <div class="alert alert-danger bg-dark border-danger text-white rounded-4 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>เหตุผลที่ยกเลิก:</strong> <?= htmlspecialchars($order['cancel_reason']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-white border-opacity-10">
                <form method="POST">
                    <h6 class="text-neon-purple mb-4 fw-bold small text-uppercase">ข้อมูลจัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="small opacity-50">ผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-5"><label class="small opacity-50">โทร</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <div class="col-md-6"><label class="small opacity-50">จังหวัด</label><input type="text" name="province" class="form-control" value="<?= htmlspecialchars($order['province']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-6"><label class="small opacity-50">ไปรษณีย์</label><input type="text" name="zipcode" class="form-control" value="<?= htmlspecialchars($order['zipcode']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <?php if($order['status'] == 'pending'): ?><div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-1">บันทึกที่อยู่ใหม่</button></div><?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-lg-6 ps-lg-4">
                <h6 class="text-neon-purple mb-4 fw-bold small text-uppercase">การชำระเงิน</h6>
                <div class="p-3 rounded-4 bg-white bg-opacity-5">
                    <p class="mb-1 small opacity-50">บิลเลขที่: <span class="text-neon-cyan fw-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="mb-3 small fw-bold">วิธีชำระ: <?= $order['payment_method'] == 'bank' ? 'โอนเงิน' : 'ปลายทาง' ?></p>
                    <?php if(!empty($order['slip_image'])): ?>
                        <div class="text-center border-top border-white border-opacity-10 pt-3">
                            <img src="uploads/slips/<?= $order['slip_image'] ?>" width="120" class="rounded border border-info" onclick="window.open(this.src)" style="cursor:zoom-in">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h6 class="text-neon-purple mb-4 fw-bold small text-uppercase">รายการที่สั่ง</h6>
            <?php while($item = $items_q->fetch_assoc()): 
                $img_path = !empty($item['variant_image']) ? $item['variant_image'] : $item['p_image'];
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-white border-opacity-10">
                <div class="d-flex align-items-center">
                    <img src="images/<?= htmlspecialchars($img_path) ?>" style="width:60px; height:60px; object-fit:cover; border-radius:10px;" class="me-3">
                    <div>
                        <span class="d-block fw-bold"><?= htmlspecialchars($item['p_name']) ?></span>
                        <small class="text-info"><?= $item['variant_name'] ?: 'ทั่วไป' ?> x <?= $item['quantity'] ?></small>
                    </div>
                </div>
                <span class="fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between pt-4"><h5 class="fw-bold">ยอดสุทธิ</h5><h4 class="fw-bold" style="color:#f107a3">฿<?= number_format($order['total_price']) ?></h4></div>
        </div>

        <div class="mt-4">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 border border-success rounded-4 bg-success bg-opacity-10 text-center shadow-lg">
                    <h5 class="text-success mb-3"><i class="bi bi-check-circle-fill"></i> ได้รับสินค้าเรียบร้อยแล้วใช่ไหม?</h5>
                    <form method="POST"><button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill fw-bold py-2">ยืนยันการรับสินค้า</button></form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>