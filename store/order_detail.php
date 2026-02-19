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

// --- [ฟังก์ชันใหม่ที่เพิ่มเข้าไป]: อัปเดตสลิปเงินโอน ---
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

// --- [ฟังก์ชันเดิม]: อัปเดตข้อมูลผู้รับ ---
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

// --- [ฟังก์ชันเดิม]: ยืนยันยกเลิกออเดอร์ ---
if (isset($_POST['confirm_cancel'])) {
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id AND user_id = $user_id AND status = 'pending'");
    header("Location: profile.php?order_cancelled=1");
    exit();
}

$order_q = $conn->query("SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("ไม่พบข้อมูลออเดอร์"); }

$items_q = $conn->query("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = $order_id");
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
        .status-pill { background: rgba(187, 134, 252, 0.1); color: #bb86fc; border: 1px solid #bb86fc; padding: 5px 20px; border-radius: 50px; }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); color: #fff; border-radius: 12px; }
        .payment-info-box { background: rgba(255, 255, 255, 0.03); border-radius: 20px; padding: 25px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .slip-preview { max-width: 200px; border-radius: 15px; border: 2px solid rgba(0, 242, 254, 0.3); cursor: pointer; transition: 0.3s; }
        .custom-file-upload { background: rgba(187, 134, 252, 0.1); border: 1px dashed #bb86fc; border-radius: 12px; padding: 15px; cursor: pointer; display: block; text-align: center; }
        .modal-content.custom-popup { background: rgba(26, 0, 40, 0.95); border-radius: 25px; color: #fff; border: 1px solid #bb86fc; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">รายละเอียดบิลความลับ</h3>
            <span class="status-pill fw-bold"><?= $order['status'] == 'pending' ? 'รอตรวจสอบ' : ($order['status'] == 'cancelled' ? 'ยกเลิกแล้ว' : 'สำเร็จ') ?></span>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-secondary border-opacity-25">
                <form method="POST">
                    <h6 class="text-neon-purple mb-4 text-uppercase small"><i class="bi bi-geo-alt me-2"></i>ข้อมูลจัดส่ง (แก้ไขได้ถ้ายังไม่ส่ง)</h6>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="small opacity-50">ชื่อผู้รับ</label><input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-5"><label class="small opacity-50">เบอร์โทร</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-12"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea></div>
                        <div class="col-md-6"><input type="text" name="province" class="form-control" placeholder="จังหวัด" value="<?= htmlspecialchars($order['province']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <div class="col-md-6"><input type="text" name="zipcode" class="form-control" placeholder="รหัสไปรษณีย์" value="<?= htmlspecialchars($order['zipcode']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>></div>
                        <?php if($order['status'] == 'pending'): ?>
                        <div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-1">บันทึกข้อมูลจัดส่งใหม่</button></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="col-lg-6 ps-lg-4">
                <h6 class="text-neon-purple mb-4 text-uppercase small"><i class="bi bi-credit-card me-2"></i>การชำระเงิน</h6>
                <div class="payment-info-box">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="opacity-75">วิธีชำระ:</span>
                        <span class="fw-bold text-neon-cyan"><?= $order['payment_method'] == 'bank' ? 'โอนเงินผ่านธนาคาร' : 'เก็บเงินปลายทาง' ?></span>
                    </div>

                    <?php if($order['payment_method'] == 'bank'): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center">
                                <?php if(!empty($order['slip_image'])): ?>
                                    <p class="small opacity-50 mb-2">สลิปปัจจุบัน:</p>
                                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview mb-3" onclick="window.open(this.src)">
                                <?php endif; ?>

                                <?php if($order['status'] == 'pending'): ?>
                                    <label for="slipInput" class="custom-file-upload mb-2">
                                        <i class="bi bi-cloud-arrow-up fs-4 d-block"></i>
                                        <span id="fileName"><?= empty($order['slip_image']) ? 'แนบสลิปเงินโอน' : 'เปลี่ยนรูปสลิปใหม่' ?></span>
                                    </label>
                                    <input type="file" name="slip_image" id="slipInput" hidden accept="image/*" onchange="previewImage(this)">
                                    <div id="previewContainer" style="display:none;" class="mb-3">
                                        <p class="small text-info mb-1">รูปตัวอย่างใหม่:</p>
                                        <img id="imagePreview" class="slip-preview">
                                    </div>
                                    <button type="submit" name="update_slip" id="saveSlipBtn" class="btn btn-sm w-100 rounded-pill" style="display:none; background: linear-gradient(45deg, #f107a3, #bb86fc); color: white; border:none; padding:10px;">บันทึกสลิปใหม่</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center p-3 opacity-50 small border border-secondary rounded-3"><i class="bi bi-truck d-block fs-2 mb-2"></i>ชำระเงินสดเมื่อได้รับสินค้า</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-5 p-4 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
            <h6 class="text-neon-purple mb-4 text-uppercase small opacity-75">สินค้าในบิลนี้</h6>
            <?php while($item = $items_q->fetch_assoc()): ?>
            <div class="d-flex justify-content-between mb-3 pb-2 border-bottom border-white border-opacity-10">
                <span><?= $item['name'] ?> <small class="opacity-50">x <?= $item['quantity'] ?></small></span>
                <span class="text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between pt-3"><span class="h5 fw-bold">ยอดรวมสุทธิ</span><span class="h4 fw-bold" style="color:#f107a3; text-shadow: 0 0 10px #f107a3;">฿<?= number_format($order['total_price']) ?></span></div>
        </div>

        <div class="d-flex gap-3 mt-4">
            <?php if($order['status'] == 'pending'): ?>
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-3" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">ยกเลิกคำสั่งซื้อนี้</button>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-outline-info w-100 rounded-pill py-3">ย้อนกลับ</a>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5"><div class="modal-body">
            <i class="bi bi-exclamation-triangle text-warning display-1 mb-4"></i>
            <h3 class="text-neon-purple fw-bold mb-3">ยืนยันการยกเลิก?</h3>
            <p class="opacity-75 mb-4">คุณต้องการยกเลิกบิลหมายเลข #<?= $order_id ?> ใช่หรือไม่?</p>
            <form method="POST"><div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ไม่ใช่</button>
                <button type="submit" name="confirm_cancel" class="btn btn-danger px-4 rounded-pill">ยืนยันยกเลิก</button>
            </div></form>
        </div></div>
    </div>
</div>

<div class="modal fade" id="successUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5"><div class="modal-body">
            <i class="bi bi-check-circle text-neon-pink display-1 mb-4"></i>
            <h3 class="text-neon-purple fw-bold mb-3">สำเร็จแล้ว!</h3>
            <p class="opacity-75 mb-4">ระบบทำการอัปเดตข้อมูลของคุณเรียบร้อย ✨</p>
            <button type="button" class="btn btn-neon-pink px-5 rounded-pill" data-bs-dismiss="modal">ตกลง</button>
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
                document.getElementById('saveSlipBtn').style.display = 'block';
                document.getElementById('fileName').innerText = input.files[0].name;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($show_modal === "update_success"): ?>
            new bootstrap.Modal(document.getElementById('successUpdateModal')).show();
        <?php endif; ?>
    });
</script>
</body>
</html>