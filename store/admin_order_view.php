<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $cancel_reason = $conn->real_escape_string($_POST['reason'] ?? '');
    $sql_update = "UPDATE orders SET status = '$new_status', cancel_reason = '$cancel_reason' WHERE id = $order_id";
    if ($conn->query($sql_update)) {
        echo json_encode(["status" => "success"]); exit();
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]); exit();
    }
}

$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("<div class='text-white p-5'>ไม่พบข้อมูล</div>"); }

$items_q = $conn->query("SELECT od.*, p.name, p.image, pv.variant_name, pv.variant_image 
                         FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการบิล #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <style>
        body { background: #0c001c; color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 25px; border: 1px solid rgba(187, 134, 252, 0.2); }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px #f107a3; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .step-btn { transition: 0.3s; border-radius: 50px; font-weight: bold; padding: 10px 20px; }
        .modal-neon .modal-content { background: rgba(26, 0, 40, 0.95); backdrop-filter: blur(15px); border: 2px solid #bb86fc; border-radius: 30px; color: #fff; }
        .product-img-td { width: 65px; height: 65px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
        .alert-neon-danger { background: rgba(255, 77, 77, 0.1); border: 1px solid #ff4d4d; color: #ff4d4d; border-radius: 15px; box-shadow: 0 0 10px rgba(255, 77, 77, 0.2); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill px-4"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        <h2 class="text-neon-pink mb-0 fw-bold">บิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card mb-4 border-info">
                <h5 class="text-info border-bottom border-white border-opacity-10 pb-2 mb-3">ข้อมูลผู้รับ</h5>
                <p class="mb-1 fw-bold fs-5"><?= htmlspecialchars($order['fullname']) ?></p>
                <p class="small mb-1 text-white-50">โทร: <?= htmlspecialchars($order['phone']) ?></p>
                <p class="small mb-0 text-white-50">ที่อยู่: <?= htmlspecialchars($order['address']) ?> <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['zipcode']) ?></p>
            </div>

            <div class="glass-card border-warning">
                <h5 class="text-warning border-bottom border-white border-opacity-10 pb-2 mb-3">การชำระเงิน</h5>
                <p class="mb-3">วิธีชำระ: <span class="fw-bold <?= ($order['payment_method'] == 'cod') ? 'text-warning' : 'text-neon-cyan' ?>"><?= strtoupper($order['payment_method']) ?></span></p>
                <?php if($order['payment_method'] == 'bank' && !empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="w-100 rounded-3 border border-info" onclick="window.open(this.src)" style="cursor:pointer">
                <?php else: ?>
                    <div class="text-center py-4 opacity-75"><i class="bi bi-truck fs-1 d-block mb-2 text-warning"></i><span class="small d-block fw-bold">เก็บเงินปลายทาง</span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <?php if($order['status'] == 'cancelled'): ?>
                <div class="alert alert-neon-danger mb-4">
                    <i class="bi bi-x-circle-fill me-2"></i> <strong>ออเดอร์นี้ถูกยกเลิก:</strong> 
                    <?= htmlspecialchars($order['cancel_reason'] ?: 'ไม่ได้ระบุเหตุผล') ?>
                </div>
            <?php endif; ?>

            <div class="glass-card mb-4">
                <table class="table table-dark table-hover mb-0">
                    <thead><tr class="small text-white-50"><th colspan="2">สินค้า</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr></thead>
                    <tbody>
                        <?php while($item = $items_q->fetch_assoc()): 
                            $display_img = !empty($item['variant_image']) ? $item['variant_image'] : $item['image'];
                        ?>
                        <tr class="align-middle border-bottom border-white border-opacity-10">
                            <td style="width: 80px;"><img src="images/<?= htmlspecialchars($display_img) ?>" class="product-img-td"></td>
                            <td><span class="fw-bold d-block"><?= htmlspecialchars($item['name']) ?></span><small class="text-neon-cyan"><?= $item['variant_name'] ?: 'ไม่มีรุ่นย่อย' ?></small></td>
                            <td class="text-center">x <?= $item['quantity'] ?></td>
                            <td class="text-end text-neon-cyan fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="glass-card bg-black border-neon-cyan shadow-lg">
                <h5 class="text-neon-cyan mb-4 fw-bold"><i class="bi bi-patch-check-fill"></i> จัดการสถานะออเดอร์</h5>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" onclick="askUpdate('processing')" class="btn <?= $order['status']=='processing' ? 'btn-primary shadow' : 'btn-outline-primary' ?> step-btn px-3">1. ยืนยันยอด/บิล</button>
                    <button type="button" onclick="askUpdate('shipped')" class="btn <?= $order['status']=='shipped' ? 'btn-info text-dark shadow' : 'btn-outline-info' ?> step-btn px-3">2. ส่งสินค้าแล้ว</button>
                    <button type="button" onclick="askUpdate('delivered')" class="btn <?= $order['status']=='delivered' ? 'btn-success shadow' : 'btn-outline-success' ?> step-btn px-3">3. สำเร็จ (ปิดงาน)</button>
                    <button type="button" onclick="$('#reasonBox').slideToggle()" class="btn <?= $order['status']=='cancelled' ? 'btn-danger shadow' : 'btn-outline-danger' ?> step-btn ms-auto"><i class="bi bi-x-circle"></i> ยกเลิกบิล</button>
                </div>
                <div id="reasonBox" style="<?= $order['status'] == 'cancelled' ? 'display:block;' : 'display:none;' ?>" class="mt-3">
                    <div class="input-group">
                        <input type="text" id="reason_text" class="form-control bg-dark text-white border-danger" placeholder="ระบุเหตุผลที่ยกเลิก..." value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>">
                        <button class="btn btn-danger" type="button" onclick="askUpdate('cancelled')">บันทึกเหตุผล</button>
                    </div>
                </div>
                <p class="small opacity-50 mb-0 mt-3 border-top border-secondary pt-2">สถานะปัจจุบัน: <span class=\"badge bg-secondary\"><?= strtoupper($order['status']) ?></span></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-neon" id="neonConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center py-4 shadow-lg"><div class="modal-body">
        <i id="pIcon" class="bi bi-question-circle text-neon-cyan display-1 mb-4 d-block"></i>
        <h3 class="fw-bold mb-3">ยืนยันการดำเนินการ</h3>
        <p class="opacity-75 mb-4 px-3 fs-5" id="pMessage"></p>
        <div class="d-flex gap-3 justify-content-center mt-4">
            <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" id="pConfirmBtn" class="btn btn-primary px-4 rounded-pill shadow fw-bold" style="background: linear-gradient(135deg, #00f2fe, #bb86fc); border:none; color:#000;">ตกลง</button>
        </div>
    </div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function askUpdate(newStatus) {
        const reason = $('#reason_text').val();
        const modal = new bootstrap.Modal(document.getElementById('neonConfirmModal'));
        $('#pMessage').html('ต้องการเปลี่ยนสถานะเป็น <b class="text-neon-cyan">[' + newStatus + ']</b> ใช่หรือไม่?');
        $('#pIcon').attr('class', newStatus === 'cancelled' ? 'bi bi-exclamation-octagon text-danger display-1 mb-4 d-block' : 'bi bi-arrow-repeat text-neon-cyan display-1 mb-4 d-block');
        modal.show();
        $('#pConfirmBtn').off('click').on('click', function() {
            modal.hide();
            $.post(window.location.href, { ajax_status: 1, status: newStatus, reason: reason }, function(res) {
                try { const data = JSON.parse(res); if(data.status === 'success') { window.location.reload(); } } catch(e) { window.location.reload(); }
            });
        });
    }
</script>
</body>
</html>