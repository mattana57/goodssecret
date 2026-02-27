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

// Logic อื่นๆ (แนบสลิป, แก้ที่อยู่, ยกเลิก) คงเดิมตามไฟล์ที่พี่ให้มา
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
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        
        /* --- ดีไซน์หลัก Glassmorphism (ยุบรวม CSS ให้กระชับ) --- */
        .glass-panel { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(15px); 
            border-radius: 25px; 
            border: 1px solid rgba(187, 134, 252, 0.2); 
            padding: 30px;
        }
        
        /* สีและเอฟเฟกต์นีออน */
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
        .border-neon-purple { border: 1px solid #bb86fc !important; box-shadow: 0 0 10px rgba(187, 134, 252, 0.3); }
        .border-neon-cyan { border: 1px solid #00f2fe !important; box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); }
        .border-neon-green { border: 1px solid #00ff88 !important; box-shadow: 0 0 10px rgba(0, 255, 136, 0.3); }

        /* ปรับกรอบ Input ไม่ให้ขาว */
        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(187, 134, 252, 0.3) !important;
            color: #fff !important;
            border-radius: 10px;
        }
        .form-control:focus { border-color: #00f2fe !important; box-shadow: 0 0 10px rgba(0, 242, 254, 0.5) !important; }
        
        /* สไตล์ตาราง */
        .table-dark { background: transparent !important; color: #fff !important; }
        .table-dark td, .table-dark th { border-color: rgba(255,255,255,0.05) !important; }

        /* --- [จุดที่แก้ไข]: ปรับ Alert Box (กรอบเขียว/แดง) ให้เข้าธีม --- */
        .custom-alert {
            background: rgba(0, 255, 136, 0.05) !important; /* พื้นหลังจางๆ */
            border: 1px solid #00ff88 !important; /* เส้นขอบนีออนเขียว */
            color: #00ff88 !important; /* ตัวหนังสือเขียว */
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.2);
        }
        .custom-alert-danger {
            background: rgba(255, 77, 77, 0.05) !important;
            border: 1px solid #ff4d4d !important; /* เส้นขอบนีออนแดง */
            color: #ff4d4d !important;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(255, 77, 77, 0.2);
        }

        /* --- [จุดที่แก้ไข]: ปรับ Modal (ป๊อปอัพ) ให้เข้าธีมมืด --- */
        .custom-modal .modal-content {
            background: #1a0028 !important;
            border: 2px solid #bb86fc !important; /* เส้นขอบนีออนม่วง */
            border-radius: 20px;
            color: #fff;
            box-shadow: 0 0 25px rgba(187, 134, 252, 0.4);
        }
    </style>
</head>
<body>
<div class="container py-5 px-3">
    <div class="glass-panel mx-auto" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0 FW-BOLD">รายละเอียดคำสั่งซื้อ</h3>
            <span class="badge rounded-pill border-neon-purple px-4 py-2 text-uppercase" style="color: #bb86fc; background: rgba(187, 134, 252, 0.1);"><?= $order['status'] ?></span>
        </div>

        <?php if(isset($_GET['success']) && $_GET['success'] == 'received'): ?>
            <div class="alert custom-alert text-center mb-4 shadow" role="alert">
                <i class="bi bi-patch-check-fill me-2"></i> ขอบคุณที่ยืนยันการรับสินค้า! ออเดอร์เสร็จสมบูรณ์แล้วครับ
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-secondary border-opacity-25 pe-lg-4">
                <form method="POST"><h6 class="text-neon-purple mb-4 small fw-bold">ข้อมูลผู้รับ & สถานที่จัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="small opacity-50 mb-1">ชื่อผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-5"><label class="small opacity-50 mb-1">เบอร์โทร</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50 mb-1">ที่อยู่จัดส่ง</label><textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <?php if($order['status'] == 'pending'): ?><div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-cyan rounded-pill px-3 mt-1">บันทึกที่อยู่ใหม่</button></div><?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-lg-6 ps-lg-4"><h6 class="text-neon-purple mb-4 small fw-bold">ข้อมูลการโอนเงิน (แนบสลิป)</h6>
                <div class="p-3 bg-black bg-opacity-30 rounded-3 border border-secondary border-opacity-25">
                    <p class="small mb-1 text-white-50">หมายเลขบิล: <span class="text-neon-cyan fw-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="small mb-3 FW-BOLD">วิธีชำระ: <?= ($order['payment_method'] == 'cod') ? 'เก็บเงินปลายทาง' : 'โอนเงินผ่านธนาคาร' ?></p>
                    <?php if($order['payment_method'] == 'bank'): ?>
                        <div class="text-center pt-2">
                            <?php if(!empty($order['slip_image'])): ?><img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview mb-3" onclick="window.open(this.src)"><?php endif; ?>
                            <?php if($order['status'] == 'pending'): ?>
                                <form method="POST" enctype="multipart/form-data"><input type="file" name="slip_image" onchange="this.form.submit()" hidden id="s"><label for="s" class="btn btn-sm btn-outline-purple w-100 rounded-pill py-2">แนบสลิปใบใหม่</label><input type="hidden" name="update_slip"></form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
            <h6 class="text-neon-purple mb-4 small opacity-75 FW-BOLD">รายการสินค้า</h6>
            <table class="table table-dark table-hover mb-0 small">
                <tbody>
                    <?php while($item = $items_q->fetch_assoc()): ?>
                    <tr class="align-middle border-bottom border-secondary border-opacity-10">
                        <td><img src="images/<?= htmlspecialchars($item['variant_image'] ?: $item['p_image']) ?>" width="55" class="rounded me-3"></td>
                        <td><span class="fw-bold d-block text-white"><?= htmlspecialchars($item['p_name']) ?></span><small class="opacity-50"><?= $item['variant_name'] ?: 'ไม่มีรุ่นย่อย' ?></small></td>
                        <td class="text-center text-white-50"><?= $item['quantity'] ?> ชิ้น</td>
                        <td class="text-end text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between pt-4 mt-1"><span class="h5 fw-bold text-white-bright">ยอดรวมสุทธิ</span><span class="h3 fw-bold text-neon-pink">฿<?= number_format($order['total_price']) ?></span></div>
        </div>

        <div class="mt-4 pt-2">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 border-neon-green rounded-4 bg-success bg-opacity-5 text-center shadow">
                    <h5 class="text-neon-green mb-3 fw-bold"><i class="bi bi-check-circle-fill"></i> ได้รับสินค้าเรียบร้อยแล้วใช่ไหม?</h5>
                    <p class="small opacity-75 mb-4 text-white-bright">หากตรวจสอบความถูกต้องแล้ว กรุณายืนยันการรับเพื่อเสร็จสิ้นรายการ</p>
                    <form method="POST"><button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill py-2 fw-bold shadow">ยืนยันการรับสินค้า</button></form>
                </div>
            <?php elseif($order['status'] == 'delivered'): ?>
                <div class="alert custom-alert rounded-4 text-center py-3 fw-bold mb-0 shadow">
                    <i class="bi bi-patch-check-fill me-2"></i> รายการสั่งซื้อนี้เสร็จสมบูรณ์แล้ว ขอบคุณที่ใช้บริการครับ
                </div>
            <?php elseif($order['status'] == 'pending'): ?>
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">ยกเลิกคำสั่งซื้อ</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center py-5 shadow-lg"><div class="modal-body">
        <i class="bi bi-exclamation-triangle text-warning display-1 mb-4 d-block"></i>
        <h3 class="fw-bold mb-3 text-neon-purple">ยืนยันการยกเลิก?</h3>
        <p class="opacity-75 mb-4 text-white-bright">คุณต้องการยกเลิกบิล #<?= $order_id ?> ใช่หรือไม่?<br>รายการนี้จะถูกยกเลิกถาวร</p>
        <form method="POST"><div class="d-flex gap-2 justify-content-center pt-2">
            <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ไม่ใช่</button>
            <button type="submit" name="confirm_cancel" class="btn btn-danger px-4 rounded-pill shadow">ยืนยันยกเลิก</button>
        </div></form>
    </div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>