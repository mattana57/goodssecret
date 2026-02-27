<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: profile.php"); exit(); }

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$show_modal = "";

// --- [Logic 1]: กดยืนยันการรับสินค้า (ลูกค้ากดเปลี่ยนจาก shipped เป็น delivered) ---
if (isset($_POST['confirm_received'])) {
    $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id AND user_id = $user_id AND status = 'shipped'");
    header("Location: order_detail.php?id=$order_id&success=received"); 
    exit();
}

// (Logic 2, 3, 4 คงเดิมตามไฟล์ที่พี่ให้มา)
if (isset($_POST['update_slip']) && isset($_FILES['slip_image'])) {
    $file = $_FILES['slip_image'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $order_id . "_" . time() . "." . $ext;
        if (!is_dir("uploads/slips/")) { mkdir("uploads/slips/", 0777, true); }
        if (move_uploaded_file($file['tmp_name'], "uploads/slips/" . $new_name)) {
            $conn->query("UPDATE orders SET slip_image = '$new_name' WHERE id = $order_id AND user_id = $user_id");
            $show_modal = "update_success";
        }
    }
}
if (isset($_POST['update_shipping'])) {
    $sql_upd = "UPDATE orders SET fullname = '".$conn->real_escape_string($_POST['fullname'])."', phone = '".$conn->real_escape_string($_POST['phone'])."', address = '".$conn->real_escape_string($_POST['address'])."', province = '".$conn->real_escape_string($_POST['province'])."', zipcode = '".$conn->real_escape_string($_POST['zipcode'])."' WHERE id = $order_id AND user_id = $user_id AND status = 'pending'";
    if($conn->query($sql_upd)) { $show_modal = "update_success"; }
}
if (isset($_POST['confirm_cancel'])) {
    $conn->query("UPDATE orders SET status = 'cancelled', cancel_reason = 'ลูกค้ายกเลิกเอง' WHERE id = $order_id AND user_id = $user_id AND status = 'pending'");
    header("Location: profile.php?order_cancelled=1"); exit();
}

$order_q = $conn->query("SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("ไม่พบข้อมูล"); }
$items_q = $conn->query("SELECT od.*, p.name AS p_name, p.image AS p_image, pv.variant_name, pv.variant_image FROM order_details od JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดบิล #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), linear-gradient(135deg,#120018,#2a0845,#3d1e6d); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .invoice-card { background: rgba(26, 0, 40, 0.75); backdrop-filter: blur(20px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
        .status-pill { background: rgba(187, 134, 252, 0.1); color: #bb86fc; border: 1px solid #bb86fc; padding: 8px 25px; border-radius: 50px; font-weight: bold; }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); color: #fff; border-radius: 12px; }
        .slip-preview { max-width: 200px; border-radius: 15px; border: 2px solid #00f2fe; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">สถานะคำสั่งซื้อ</h3>
            <span class="status-pill text-uppercase"><?= $order['status'] ?></span>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-secondary border-opacity-25">
                <form method="POST"><h6 class="text-neon-purple mb-4 small">ข้อมูลจัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="small opacity-50">ชื่อผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-5"><label class="small opacity-50">เบอร์โทร</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <?php if($order['status'] == 'pending'): ?><div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-1">บันทึกใหม่</button></div><?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-lg-6 ps-lg-4"><h6 class="text-neon-purple mb-4 small">บิลและการชำระเงิน</h6>
                <div class="p-3 bg-white bg-opacity-5 rounded-4">
                    <p class="small mb-1">หมายเลขบิล: <span class="text-neon-cyan fw-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="small mb-3 fw-bold">วิธีชำระ: <?= $order['payment_method'] == 'bank' ? 'โอนเงิน' : 'เก็บปลายทาง' ?></p>
                    <?php if($order['payment_method'] == 'bank'): ?>
                        <div class="text-center pt-2">
                            <?php if(!empty($order['slip_image'])): ?><img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview mb-3" onclick="window.open(this.src)"><?php endif; ?>
                            <?php if($order['status'] == 'pending'): ?>
                                <form method="POST" enctype="multipart/form-data"><input type="file" name="slip_image" onchange="this.form.submit()" hidden id="s"><label for="s" class="btn btn-sm btn-outline-purple w-100 rounded-pill">แนบสลิปใหม่</label><input type="hidden" name="update_slip"></form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.02);">
            <h6 class="text-neon-purple mb-4 small opacity-75">สินค้าในบิลนี้</h6>
            <?php while($item = $items_q->fetch_assoc()): ?>
            <div class="d-flex justify-content-between mb-3 border-bottom border-white border-opacity-10 pb-2">
                <div class="d-flex align-items-center"><img src="images/<?= htmlspecialchars($item['variant_image'] ?: $item['p_image']) ?>" width="60" class="rounded me-3"><div><span class="fw-bold d-block"><?= htmlspecialchars($item['p_name']) ?></span><small class="opacity-50"><?= $item['quantity'] ?> ชิ้น</small></div></div>
                <span class="text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between pt-2"><span class="h5 fw-bold">รวมสุทธิ</span><span class="h4 fw-bold text-neon-pink">฿<?= number_format($order['total_price']) ?></span></div>
        </div>

        <div class="mt-4">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 border border-success rounded-4 bg-success bg-opacity-10 text-center shadow-lg">
                    <h5 class="text-success mb-3 fw-bold"><i class="bi bi-check-circle-fill"></i> สินค้าถึงมือคุณแล้วใช่ไหม?</h5>
                    <p class="small opacity-75 mb-4">หากตรวจสอบความถูกต้องแล้ว กรุณายืนยันการรับเพื่อเสร็จสิ้นออเดอร์</p>
                    <form method="POST"><button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill py-2 fw-bold shadow">ยืนยันการรับสินค้า</button></form>
                </div>
            <?php elseif($order['status'] == 'delivered'): ?>
                <div class="alert alert-success rounded-4 text-center py-3 fw-bold"><i class="bi bi-patch-check-fill me-2"></i> รายการสั่งซื้อนี้เสร็จสมบูรณ์แล้ว</div>
            <?php elseif($order['status'] == 'pending'): ?>
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-3" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">ยกเลิกคำสั่งซื้อ</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content custom-popup text-center py-5"><div class="modal-body"><i class="bi bi-exclamation-triangle text-warning display-1 mb-4"></i><h3 class="fw-bold mb-3">ยืนยันการยกเลิก?</h3><p class="opacity-75 mb-4">บิล #<?= $order_id ?> จะถูกยกเลิกถาวร</p><form method="POST"><div class="d-flex gap-2 justify-content-center"><button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ไม่ใช่</button><button type="submit" name="confirm_cancel" class="btn btn-danger px-4 rounded-pill">ยืนยันยกเลิก</button></div></form></div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>