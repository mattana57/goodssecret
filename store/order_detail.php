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

// --- [ฟังก์ชัน]: อัปเดตข้อมูลผู้รับ ---
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

// --- [ฟังก์ชัน]: ยกเลิกออเดอร์ ---
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
        .invoice-card { 
            background: rgba(26, 0, 40, 0.75); backdrop-filter: blur(20px); 
            border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; 
        }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
        .status-pill { background: rgba(187, 134, 252, 0.1); color: #bb86fc; border: 1px solid #bb86fc; padding: 5px 20px; border-radius: 50px; }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); color: #fff; border-radius: 12px; }
        .payment-info-box { background: rgba(255, 255, 255, 0.03); border-radius: 20px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .slip-preview { max-width: 250px; border-radius: 15px; border: 2px solid rgba(0, 242, 254, 0.3); cursor: pointer; transition: 0.3s; }
        .slip-preview:hover { transform: scale(1.05); border-color: #00f2fe; }
        .modal-content.custom-popup { background: rgba(26, 0, 40, 0.95); backdrop-filter: blur(25px); border: 1px solid rgba(187, 134, 252, 0.4); border-radius: 25px; color: #fff; }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); color: #fff; border: none; font-weight: bold; border-radius: 12px; padding: 10px 30px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="invoice-card mx-auto" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="profile.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <h3 class="text-neon-cyan mb-0">รายละเอียดบิลความลับ</h3>
            <span class="status-pill fw-bold">
                <?= $order['status'] == 'pending' ? 'รอการตรวจสอบ' : ($order['status'] == 'cancelled' ? 'ยกเลิกแล้ว' : 'สำเร็จ') ?>
            </span>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-6 border-end border-secondary border-opacity-25">
                <form method="POST">
                    <h6 class="text-neon-purple opacity-75 mb-4 text-uppercase"><i class="bi bi-geo-alt me-2"></i>ข้อมูลจัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="small opacity-50 mb-1">ชื่อผู้รับ</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($order['fullname']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-5">
                            <label class="small opacity-50 mb-1">เบอร์ติดต่อ</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($order['phone']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-12">
                            <label class="small opacity-50 mb-1">ที่อยู่โดยละเอียด</label>
                            <textarea name="address" class="form-control" rows="2" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>><?= htmlspecialchars($order['address']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small opacity-50 mb-1">จังหวัด</label>
                            <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($order['province']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="small opacity-50 mb-1">รหัสไปรษณีย์</label>
                            <input type="text" name="zipcode" class="form-control" value="<?= htmlspecialchars($order['zipcode']) ?>" <?= $order['status'] != 'pending' ? 'disabled' : '' ?>>
                        </div>
                        <?php if($order['status'] == 'pending'): ?>
                        <div class="col-12 text-end"><button type="submit" name="update_shipping" class="btn btn-sm btn-outline-info rounded-pill mt-2">บันทึกข้อมูลใหม่</button></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="col-lg-6 ps-lg-4">
                <h6 class="text-neon-purple opacity-75 mb-4 text-uppercase"><i class="bi bi-credit-card me-2"></i>ข้อมูลการชำระเงิน</h6>
                <div class="payment-info-box">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="opacity-75">ช่องทางที่เลือก:</span>
                        <span class="fw-bold text-neon-cyan">
                            <?= $order['payment_method'] == 'bank' ? 'โอนเงินผ่านธนาคาร' : 'เก็บเงินปลายทาง' ?>
                        </span>
                    </div>
                    
                    <?php if($order['payment_method'] == 'bank'): ?>
                        <div class="text-center mt-3">
                            <p class="small opacity-50 mb-2">หลักฐานการโอนเงิน (สลิป):</p>
                            <?php if(!empty($order['slip_image'])): ?>
                                <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview img-fluid" data-bs-toggle="modal" data-bs-target="#slipModal">
                            <?php else: ?>
                                <div class="p-4 border border-dashed border-secondary rounded-3 opacity-50 small">
                                    <i class="bi bi-image mb-2 d-block fs-3"></i>
                                    ไม่พบรูปภาพสลิปในระบบ
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-3 opacity-50 small border border-secondary border-opacity-25 rounded-3">
                            <i class="bi bi-truck mb-2 d-block fs-3"></i>
                            ชำระเงินกับพนักงานจัดส่งเมื่อได้รับสินค้า
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-4 small opacity-50">
                    <p class="mb-1">รหัสออเดอร์: #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></p>
                    <p class="mb-0">สั่งซื้อเมื่อ: <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="mb-5 p-4 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
            <h6 class="text-neon-purple mb-4 text-uppercase small opacity-75">รายการสินค้าในบิลนี้</h6>
            <?php while($item = $items_q->fetch_assoc()): ?>
            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-white border-opacity-10">
                <span><?= $item['name'] ?> <span class="text-neon-purple opacity-75 ms-2">x <?= $item['quantity'] ?></span></span>
                <span class="text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endwhile; ?>
            <div class="d-flex justify-content-between pt-3">
                <span class="h5 fw-bold mb-0">ราคาสุทธิ</span>
                <span class="h4 fw-bold mb-0" style="color:#f107a3; text-shadow: 0 0 10px #f107a3;">฿<?= number_format($order['total_price']) ?></span>
            </div>
        </div>

        <div class="d-flex gap-3">
            <?php if($order['status'] == 'pending'): ?>
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-3" data-bs-toggle="modal" data-bs-target="#confirmCancelModal">ยกเลิกคำสั่งซื้อนี้</button>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-outline-info w-100 rounded-pill py-3">ย้อนกลับ</a>
        </div>
    </div>
</div>

<div class="modal fade" id="slipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0 text-center">
                <img src="uploads/slips/<?= $order['slip_image'] ?>" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh;">
                <button type="button" class="btn btn-light rounded-pill mt-3 px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5">
            <div class="modal-body">
                <i class="bi bi-exclamation-triangle text-warning display-1 mb-4"></i>
                <h3 class="text-neon-purple fw-bold mb-3">ยืนยันการยกเลิก?</h3>
                <p class="opacity-75 mb-4">คุณแน่ใจหรือไม่ที่จะยกเลิกคำสั่งซื้อรายการนี้? การกระทำนี้ไม่สามารถย้อนคืนได้</p>
                <form method="POST">
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ไม่ยกเลิก</button>
                        <button type="submit" name="confirm_cancel" class="btn btn-danger px-4 rounded-pill">ยืนยันยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5">
            <div class="modal-body">
                <i class="bi bi-check-circle text-neon-pink display-1 mb-4"></i>
                <h3 class="text-neon-purple fw-bold mb-3">บันทึกสำเร็จ!</h3>
                <p class="opacity-75 mb-4">ข้อมูลผู้รับและที่อยู่ได้รับการอัปเดตเรียบร้อยแล้ว ✨</p>
                <button type="button" class="btn btn-neon-pink px-5" data-bs-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($show_modal === "update_success"): ?>
            var myModal = new bootstrap.Modal(document.getElementById('successUpdateModal'));
            myModal.show();
        <?php endif; ?>
    });
</script>
</body>
</html>