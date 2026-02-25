<?php
session_start();
include "connectdb.php";

// --- [ความปลอดภัย]: ตรวจสอบการเข้าถึง ---
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$show_modal = "";

// --- [Logic 1]: กดยืนยันการรับสินค้า (สัมพันธ์กับสถานะ shipped จากแอดมิน) ---
if (isset($_POST['confirm_received'])) {
    // อัปเดตสถานะเป็นสำเร็จ (delivered) ทั้งสองฝั่ง เมื่อสถานะเดิมคือ shipped
    $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id AND user_id = $user_id AND status = 'shipped'");
    header("Location: order_detail.php?id=$order_id&success=received"); 
    exit();
}

// --- [Logic 2]: อัปเดตสลิปเงินโอน ---
if (isset($_POST['update_slip']) && isset($_FILES['slip_image'])) {
    $file = $_FILES['slip_image'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $order_id . "_" . time() . "." . $ext;
        $target = "uploads/slips/" . $new_name;
        if (!is_dir("uploads/slips/")) { mkdir("uploads/slips/", 0777, true); }
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $conn->query("UPDATE orders SET slip_image = '$new_name' WHERE id = $order_id AND user_id = $user_id");
            $show_modal = "update_success";
        }
    }
}

// --- [Logic 3]: อัปเดตข้อมูลผู้รับ (ทำได้เฉพาะตอนสถานะ pending) ---
if (isset($_POST['update_shipping'])) {
    $new_fullname = $conn->real_escape_string($_POST['fullname']);
    $new_phone = $conn->real_escape_string($_POST['phone']);
    $new_address = $conn->real_escape_string($_POST['address']);
    $new_province = $conn->real_escape_string($_POST['province']);
    $new_zipcode = $conn->real_escape_string($_POST['zipcode']);

    $sql_upd = "UPDATE orders SET 
                fullname = '$new_fullname', phone = '$new_phone', address = '$new_address', 
                province = '$new_province', zipcode = '$new_zipcode' 
                WHERE id = $order_id AND user_id = $user_id AND status = 'pending'";
    if($conn->query($sql_upd)) {
        $show_modal = "update_success";
    }
}

// --- [Logic 4]: ยืนยันยกเลิกออเดอร์โดยลูกค้า ---
if (isset($_POST['confirm_cancel'])) {
    $conn->query("UPDATE orders SET status = 'cancelled', cancel_reason = 'ลูกค้ายกเลิกเอง' WHERE id = $order_id AND user_id = $user_id AND status = 'pending'");
    header("Location: profile.php?order_cancelled=1");
    exit();
}

// --- [Data Retrieval]: ดึงข้อมูลออเดอร์และรายการสินค้า ---
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
    <title>รายละเอียดบิล #<?= $order_id ?> | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { 
            background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), linear-gradient(135deg,#120018,#2a0845,#3d1e6d); 
            color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; 
        }
        .invoice-card { background: rgba(26, 0, 40, 0.75); backdrop-filter: blur(20px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
        .status-pill { background: rgba(187, 134, 252, 0.1); color: #bb86fc; border: 1px solid #bb86fc; padding: 8px 25px; border-radius: 50px; font-weight: bold; }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); color: #fff; border-radius: 12px; }
        .payment-info-box { background: rgba(255, 255, 255, 0.03); border-radius: 20px; padding: 25px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .slip-preview { max-width: 200px; border-radius: 15px; border: 2px solid rgba(0, 242, 254, 0.3); cursor: pointer; }
        .product-img-mini { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
        .modal-content.custom-popup { background: rgba(26, 0, 40, 0.95); border-radius: 25px; color: #fff; border: 1px solid #bb86fc; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">สถานะคำสั่งซื้อ</h3>
            <span class="status-pill">
                <?php 
                    if($order['status'] == 'pending') echo '<i class="bi bi-clock"></i> รอตรวจสอบ';
                    elseif($order['status'] == 'processing') echo '<i class="bi bi-box-seam"></i> กำลังเตรียมจัดส่ง';
                    elseif($order['status'] == 'shipped') echo '<i class="bi bi-truck"></i> อยู่ระหว่างจัดส่ง';
                    elseif($order['status'] == 'delivered') echo '<i class="bi bi-check-circle-fill"></i> สำเร็จแล้ว';
                    elseif($order['status'] == 'cancelled') echo '<i class="bi bi-x-circle"></i> ยกเลิกแล้ว';
                ?>
            </span>
        </div>

        <?php if($order['status'] == 'cancelled' && !empty($order['cancel_reason'])): ?>
            <div class="alert alert-danger bg-dark border-danger text-white rounded-4 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>เหตุผลจากทางร้าน:</strong> <?= htmlspecialchars($order['cancel_reason']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-secondary border-opacity-25">
                <form method="POST">
                    <h6 class="text-neon-purple mb-4 text-uppercase small"><i class="bi bi-geo-alt me-2"></i>ข้อมูลจัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="small opacity-50">ชื่อผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-5"><label class="small opacity-50">เบอร์โทร</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <div class="col-md-6"><label class="small opacity-50">จังหวัด</label><input type="text" name="province" class="form-control" value="<?= htmlspecialchars($order['province']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-6"><label class="small opacity-50">รหัสไปรษณีย์</label><input type="text" name="zipcode" class="form-control" value="<?= htmlspecialchars($order['zipcode']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <?php if($order['status'] == 'pending'): ?>
                        <div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-1">บันทึกข้อมูลจัดส่งใหม่</button></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="col-lg-6 ps-lg-4">
                <h6 class="text-neon-purple mb-4 text-uppercase small"><i class="bi bi-cash-stack me-2"></i>ข้อมูลบิลและการชำระเงิน</h6>
                <div class="payment-info-box">
                    <p class="mb-1 small opacity-50">หมายเลขบิล: <span class="text-neon-cyan fw-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="mb-3 small fw-bold">วิธีชำระ: <?= $order['payment_method'] == 'bank' ? 'โอนเงินผ่านธนาคาร' : 'เก็บเงินปลายทาง' ?></p>
                    
                    <?php if($order['payment_method'] == 'bank'): ?>
                        <div class="text-center pt-3 mt-3 border-top border-white border-opacity-10">
                            <?php if(!empty($order['slip_image'])): ?>
                                <p class="small opacity-50 mb-2">หลักฐานปัจจุบัน (คลิกเพื่อดูรูปใหญ่):</p>
                                <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview mb-3" onclick="window.open(this.src)">
                            <?php endif; ?>

                            <?php if($order['status'] == 'pending'): ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="file" name="slip_image" id="slipInput" hidden accept="image/*" onchange="previewImage(this)">
                                    <label for="slipInput" class="custom-file-upload mb-2" style="background: rgba(187, 134, 252, 0.1); border: 1px dashed #bb86fc; border-radius: 12px; padding: 15px; cursor: pointer; display: block;">
                                        <i class="bi bi-cloud-arrow-up fs-4 d-block"></i>
                                        <span id="fileName"><?= empty($order['slip_image']) ? 'แนบสลิปเงินโอน' : 'เปลี่ยนรูปสลิปใหม่' ?></span>
                                    </label>
                                    <div id="previewContainer" style="display:none;" class="mb-3">
                                        <img id="imagePreview" class="slip-preview">
                                        <button type="submit" name="update_slip" class="btn btn-sm btn-success w-100 rounded-pill mt-2">ยืนยันการบันทึกสลิป</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-5 p-4 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
            <h6 class="text-neon-purple mb-4 text-uppercase small opacity-75">สินค้าในบิลนี้</h6>
            <?php while($item = $items_q->fetch_assoc()): 
                $img_path = !empty($item['variant_image']) ? $item['variant_image'] : $item['p_image'];
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-white border-opacity-10">
                <div class="d-flex align-items-center">
                    <img src="images/<?= htmlspecialchars($img_path) ?>" class="product-img-mini me-3">
                    <div>
                        <span class="d-block fw-bold fs-5"><?= htmlspecialchars($item['p_name']) ?></span>
                        <?php if(!empty($item['variant_name'])): ?><small class="text-neon-purple">รุ่น: <?= htmlspecialchars($item['variant_name']) ?></small><br><?php endif; ?>
                        <small class="opacity-50">จำนวน: <?= $item['quantity'] ?> ชิ้น</small>
                    </div>
                </div>
                <span class="text-neon-cyan fw-bold fs-5">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between pt-3">
                <span class="h5 fw-bold mb-0">ยอดรวมสุทธิ</span>
                <span class="h4 fw-bold mb-0" style="color:#f107a3; text-shadow: 0 0 10px #f107a3;">฿<?= number_format($order['total_price']) ?></span>
            </div>
        </div>

        <div class="mt-4">
            <?php if($order['status'] == 'shipped'): ?>
                <div class="p-4 border border-success rounded-4 bg-success bg-opacity-10 text-center shadow-lg">
                    <h5 class="text-success mb-3"><i class="bi bi-check-circle-fill"></i> ได้รับสินค้าเรียบร้อยแล้วใช่ไหม?</h5>
                    <p class="small opacity-75 mb-4">หากตรวจสอบความถูกต้องแล้ว กรุณายืนยันการรับเพื่อจบออเดอร์</p>
                    <form method="POST">
                        <button type="submit" name="confirm_received" class="btn btn-success px-5 rounded-pill py-2 fw-bold shadow">ยืนยันการรับสินค้า</button>
                    </form>
                </div>
            <?php elseif($order['status'] == 'pending'): ?>
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-3" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">ยกเลิกคำสั่งซื้อนี้</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5"><div class="modal-body">
            <i class="bi bi-exclamation-triangle text-warning display-1 mb-4"></i>
            <h3 class="text-neon-purple fw-bold mb-3">ยืนยันการยกเลิก?</h3>
            <p class="opacity-75 mb-4">บิลหมายเลข #<?= $order_id ?> จะถูกยกเลิกถาวรและไม่สามารถย้อนคืนได้</p>
            <form method="POST"><div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ไม่ใช่</button>
                <button type="submit" name="confirm_cancel" class="btn btn-danger px-4 rounded-pill">ยืนยันยกเลิก</button>
            </div></form>
        </div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('previewContainer').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>