<?php
session_start();
include "connectdb.php";

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- [ส่วนใหม่]: รองรับการอัปเดตสถานะแบบ AJAX ---
if (isset($_POST['ajax_update_status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $reason = $conn->real_escape_string($_POST['reason'] ?? '');
    
    // อัปเดตลงฐานข้อมูล
    $conn->query("UPDATE orders SET status = '$new_status', cancel_reason = '$reason' WHERE id = $order_id");
    echo "success"; exit(); // ตอบกลับเพื่อให้ฝั่ง Javascript รู้ว่าบันทึกแล้ว
}

// ดึงข้อมูลออเดอร์ (ต้องดึงข้อมูลใหม่อีกครั้งเพื่อแสดงค่าล่าสุด)
$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();

if (!$order) { die("ไม่พบข้อมูลออเดอร์"); }

$items_q = $conn->query("SELECT od.*, p.name, p.image, pv.variant_name FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการบิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <style>
        body { background: #0c001c; color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 25px; border: 1px solid rgba(187, 134, 252, 0.2); }
        .status-badge { padding: 8px 16px; border-radius: 50px; font-weight: bold; }
        .pending { background: #ffc107; color: #000; } .processing { background: #00f2fe; color: #000; }
        .shipped { background: #bb86fc; color: #fff; } .delivered { background: #198754; color: #fff; }
        .cancelled { background: #dc3545; color: #fff; }
        .save-indicator { display: none; color: #00f2fe; font-size: 0.8rem; margin-left: 10px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill"><i class="bi bi-arrow-left"></i> กลับ</a>
        <h2 class="text-info">รายละเอียดคำสั่งซื้อ #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card mb-4">
                <h5 class="text-info border-bottom border-secondary pb-2 mb-3">ผู้รับ: <?= htmlspecialchars($order['fullname']) ?></h5>
                <p class="small mb-1">โทร: <?= htmlspecialchars($order['phone']) ?></p>
                <p class="small">ที่อยู่: <?= htmlspecialchars($order['address']) ?> <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['zipcode']) ?></p>
            </div>
            <div class="glass-card">
                <h5 class="text-warning border-bottom border-secondary pb-2 mb-3">การชำระเงิน</h5>
                <?php if(!empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="w-100 rounded border border-info" onclick="window.open(this.src)" style="cursor:zoom-in">
                <?php else: ?>
                    <p class="text-secondary small">ไม่มีสลิปการโอน</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card mb-4">
                <table class="table table-dark table-hover mb-0">
                    <thead><tr class="text-secondary small"><th>รูป</th><th>สินค้า</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr></thead>
                    <tbody>
                        <?php while($item = $items_q->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $item['image'] ?>" width="40" class="rounded"></td>
                            <td><?= $item['name'] ?> <br><small class="text-info"><?= $item['variant_name'] ?: '-' ?></small></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end text-info">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="glass-card bg-black border-info">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-info mb-0">ปรับสถานะปัจจุบัน <span id="saveStatus" class="save-indicator"><i class="bi bi-cloud-check"></i> บันทึกแล้ว</span></h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <select id="ajaxStatus" class="form-select bg-dark text-white border-secondary">
                            <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : '' ?>>รอตรวจสอบ</option>
                            <option value="processing" <?= ($order['status'] == 'processing') ? 'selected' : '' ?>>ตรวจสอบสำเร็จ (ลูกค้ารอจัดส่ง)</option>
                            <option value="shipped" <?= ($order['status'] == 'shipped') ? 'selected' : '' ?>>จัดส่งแล้ว (ลูกค้าได้รับปุ่มยืนยัน)</option>
                            <option value="delivered" <?= ($order['status'] == 'delivered') ? 'selected' : '' ?>>จัดส่งสำเร็จ (สมบูรณ์)</option>
                            <option value="cancelled" <?= ($order['status'] == 'cancelled') ? 'selected' : '' ?>>ยกเลิกคำสั่งซื้อ</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="ajaxReason" class="form-control bg-dark text-white border-danger" 
                               value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>" 
                               placeholder="เหตุผลการยกเลิก (ถ้ามี)" 
                               style="display: <?= ($order['status'] == 'cancelled') ? 'block' : 'none' ?>;">
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button id="btnForceSave" class="btn btn-info px-5 rounded-pill fw-bold shadow-sm">บันทึกสถานะ</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // แสดง/ซ่อน ช่องเหตุผล
    $('#ajaxStatus').change(function() {
        if ($(this).val() === 'cancelled') {
            $('#ajaxReason').fadeIn();
        } else {
            $('#ajaxReason').fadeOut();
        }
    });

    // ระบบบันทึกสถานะ (AJAX) เพื่อไม่ให้เด้งกลับ
    $('#btnForceSave').click(function() {
        const status = $('#ajaxStatus').val();
        const reason = $('#ajaxReason').val();
        const btn = $(this);

        btn.html('<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...').prop('disabled', true);

        $.post(window.location.href, {
            ajax_update_status: 1,
            status: status,
            reason: reason
        }, function(response) {
            if (response === 'success') {
                btn.html('บันทึกสำเร็จ!').addClass('btn-success').removeClass('btn-info');
                $('#saveStatus').fadeIn().delay(2000).fadeOut();
                setTimeout(() => {
                    btn.html('บันทึกสถานะ').prop('disabled', false).removeClass('btn-success').addClass('btn-info');
                }, 2000);
            }
        });
    });
</script>
</body>
</html>