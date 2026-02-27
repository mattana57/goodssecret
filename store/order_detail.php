<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$show_modal = "";

// --- [Logic]: ยืนยันรับสินค้า (เปลี่ยน shipped -> delivered) ---
if (isset($_POST['confirm_received'])) {
    $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id AND user_id = $user_id AND status = 'shipped'");
    header("Location: order_detail.php?id=$order_id&success=received"); 
    exit();
}

// --- [Logic]: อัปเดตสลิป ---
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

// --- [Logic]: อัปเดตที่อยู่ (เฉพาะ pending) ---
if (isset($_POST['update_shipping'])) {
    $sql_upd = "UPDATE orders SET 
                fullname = '".$conn->real_escape_string($_POST['fullname'])."', 
                phone = '".$conn->real_escape_string($_POST['phone'])."', 
                address = '".$conn->real_escape_string($_POST['address'])."', 
                province = '".$conn->real_escape_string($_POST['province'])."', 
                zipcode = '".$conn->real_escape_string($_POST['zipcode'])."' 
                WHERE id = $order_id AND user_id = $user_id AND status = 'pending'";
    if($conn->query($sql_upd)) { $show_modal = "update_success"; }
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
    <title>บิล #<?= $order_id ?> | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 30px; padding: 40px; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px #f107a3; }
        .form-control { background: rgba(0, 0, 0, 0.3) !important; border: 1px solid rgba(187, 134, 252, 0.3) !important; color: #fff !important; }
        /* แก้กรอบขาวให้เป็นนีออน */
        .custom-alert-success { background: rgba(0, 255, 136, 0.05); border: 1px solid #00ff88; color: #00ff88; border-radius: 15px; box-shadow: 0 0 15px rgba(0, 255, 136, 0.2); }
        .modal-content { background: #1a0028 !important; border: 2px solid #bb86fc !important; border-radius: 25px; color: #fff; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="glass-card mx-auto" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">   รายละเอียดบิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h3>
            <span class="badge rounded-pill border border-info px-3 py-2 text-uppercase"><?= $order['status'] ?></span>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert custom-alert-success text-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> ดำเนินการสำเร็จเรียบร้อยแล้ว</div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-6 border-end border-white border-opacity-10 pe-md-4">
                <form method="POST">
                    <h6 class="text-neon-pink mb-3 small fw-bold">ข้อมูลการจัดส่ง</h6>
                    <div class="row g-2">
                        <div class="col-12"><label class="small opacity-50">ชื่อผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">เบอร์โทร</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <?php if($order['status'] == 'pending'): ?><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-2">อัปเดตที่อยู่</button><?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-6 ps-md-4">
                <h6 class="text-neon-pink mb-3 small fw-bold">การชำระเงิน</h6>
                <div class="p-3 rounded-4 bg-black bg-opacity-30 border border-white border-opacity-10">
                    <p class="small mb-2">วิธีชำระ: <b><?= $order['payment_method'] == 'bank' ? 'โอนเงิน' : 'เก็บปลายทาง' ?></b></p>
                    <?php if($order['payment_method'] == 'bank'): ?>
                        <div class="text-center mt-3">
                            <?php if(!empty($order['slip_image'])): ?>
                                <img src="uploads/slips/<?= $order['slip_image'] ?>" class="w-50 rounded-3 border border-info mb-3" onclick="window.open(this.src)" style="cursor:pointer">
                            <?php endif; ?>
                            <?php if($order['status'] == 'pending'): ?>
                                <form method="POST" enctype="multipart/form-data"><input type="file" name="slip_image" onchange="this.form.submit()" hidden id="s"><label for="s" class="btn btn-sm btn-outline-purple w-100 rounded-pill">แนบสลิปใหม่</label><input type="hidden" name="update_slip"></form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-dark small">
                <thead><tr><th>สินค้า</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr></thead>
                <tbody>
                    <?php while($item = $items_q->fetch_assoc()): ?>
                    <tr class="align-middle border-bottom border-white border-opacity-10">
                        <td class="d-flex align-items-center">
                            <img src="images/<?= htmlspecialchars($item['variant_image'] ?: $item['p_image']) ?>" width="50" height="50" class="rounded me-2" style="object-fit:cover;">
                            <span><?= htmlspecialchars($item['p_name']) ?><br><small class="text-neon-cyan"><?= $item['variant_name'] ?></small></span>
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 custom-alert-success text-center shadow-lg">
                    <h5 class="fw-bold mb-3"><i class="bi bi-box2-heart"></i> ได้รับสินค้าแล้วใช่ไหม?</h5>
                    <p class="small opacity-75 mb-4">หากตรวจสอบแล้วถูกต้อง กรุณากดยืนยันเพื่อจบออเดอร์ครับ</p>
                    <form method="POST"><button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill fw-bold">ยืนยันการรับสินค้า</button></form>
                </div>
            <?php elseif($order['status'] == 'delivered'): ?>
                <div class="alert custom-alert-success text-center py-3 fw-bold"><i class="bi bi-patch-check-fill me-2"></i> ออเดอร์นี้เสร็จสมบูรณ์แล้ว ขอบคุณที่อุดหนุนครับ</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>