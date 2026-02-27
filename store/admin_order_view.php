<?php
session_start();
include "connectdb.php";

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- [Logic ส่วนจัดการสถานะ]: รองรับการกดเปลี่ยนแบบ Step-by-Step ---
if (isset($_POST['ajax_status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $cancel_reason = $conn->real_escape_string($_POST['reason'] ?? '');
    
    $sql_update = "UPDATE orders SET status = '$new_status', cancel_reason = '$cancel_reason' WHERE id = $order_id";
    
    if ($conn->query($sql_update)) {
        echo json_encode(["status" => "success", "new_val" => $new_status]); 
        exit();
    }
}

// ดึงข้อมูลออเดอร์ ลูกค้า และที่อยู่จัดส่งละเอียด
$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();

if (!$order) { die("<div style='color:white; background:#0c001c; height:100vh; display:flex; justify-content:center; align-items:center;'><h3>ไม่พบข้อมูลคำสั่งซื้อ</h3></div>"); }

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
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.5); }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .slip-img { max-width: 100%; border-radius: 15px; border: 2px solid #00f2fe; cursor: pointer; }
        .step-btn { transition: 0.3s; border-radius: 50px; font-weight: bold; padding: 10px 20px; }
        
        /* --- ดีไซน์ป๊อปอัพนีออนใหม่ --- */
        .modal-neon .modal-content {
            background: rgba(26, 0, 40, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid #bb86fc;
            border-radius: 30px;
            color: #fff;
        }
        .btn-confirm-neon {
            background: linear-gradient(135deg, #00f2fe, #bb86fc);
            border: none; color: #000; font-weight: bold;
            border-radius: 50px; padding: 10px 30px; transition: 0.3s;
        }
        .btn-confirm-neon:hover { box-shadow: 0 0 15px #bb86fc; transform: translateY(-2px); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill px-4"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        <h2 class="text-neon-pink mb-0">รายละเอียดบิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card mb-4 border-info">
                <h5 class="text-info border-bottom border-secondary pb-2 mb-3">ข้อมูลผู้รับ</h5>
                <p class="mb-1 fw-bold"><?= htmlspecialchars($order['fullname']) ?></p>
                <p class="mb-1 small">โทร: <?= htmlspecialchars($order['phone']) ?></p>
                <hr class="border-secondary opacity-25">
                <p class="mb-1 small"><strong>ที่อยู่:</strong> <?= htmlspecialchars($order['address']) ?></p>
                <p class="mb-1 small"><strong>จังหวัด:</strong> <?= htmlspecialchars($order['province'] ?? '-') ?></p>
                <p class="mb-1 small"><strong>รหัสไปรษณีย์:</strong> <?= htmlspecialchars($order['zipcode'] ?? '-') ?></p>
            </div>
            
            <div class="glass-card border-warning">
                <h5 class="text-warning border-bottom border-secondary pb-2 mb-3">หลักฐานการชำระเงิน</h5>
                <?php if(!empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-img mt-2" onclick="window.open(this.src)">
                <?php else: ?>
                    <div class="alert alert-secondary small py-2 mt-2">ไม่มีสลิปการโอนเงิน</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card mb-4">
                <table class="table table-dark table-hover mb-0">
                    <thead><tr class="text-secondary small"><th>สินค้า</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr></thead>
                    <tbody>
                        <?php while($item = $items_q->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><?= $item['name'] ?><br><small class="text-info"><?= $item['variant_name'] ?: '-' ?></small></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2" class="text-end fw-bold">ยอดรวมสุทธิ:</td><td class="text-end h4 text-neon-pink fw-bold">฿<?= number_format($order['total_price']) ?></td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="glass-card bg-black border-neon-cyan shadow-lg">
                <h5 class="text-neon-cyan mb-4"><i class="bi bi-patch-check-fill"></i> ขั้นตอนการจัดการออเดอร์</h5>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" onclick="askUpdate('processing')" class="btn <?= $order['status']=='processing' ? 'btn-primary shadow' : 'btn-outline-primary' ?> step-btn px-3">
                        <i class="bi bi-check2-circle"></i> 1. ยืนยันยอดเงิน
                    </button>
                    <button type="button" onclick="askUpdate('shipped')" class="btn <?= $order['status']=='shipped' ? 'btn-info text-dark shadow' : 'btn-outline-info' ?> step-btn px-3">
                        <i class="bi bi-truck"></i> 2. ส่งสินค้าแล้ว
                    </button>
                    <button type="button" onclick="askUpdate('delivered')" class="btn <?= $order['status']=='delivered' ? 'btn-success shadow' : 'btn-outline-success' ?> step-btn px-3">
                        <i class="bi bi-flag-fill"></i> 3. ปิดงาน (สำเร็จ)
                    </button>
                    <button type="button" onclick="toggleCancelBox()" class="btn <?= $order['status']=='cancelled' ? 'btn-danger shadow' : 'btn-outline-danger' ?> step-btn px-3 ms-auto">
                        <i class="bi bi-x-circle"></i> ยกเลิกบิล
                    </button>
                </div>

                <div id="cancelReasonInput" style="display:none;" class="mb-3 animate__animated animate__fadeIn">
                    <label class="small text-danger mb-1">ระบุเหตุผลที่ยกเลิก:</label>
                    <div class="input-group">
                        <input type="text" id="reason_text" class="form-control bg-dark text-white border-danger" placeholder="ระบุเหตุผล..." value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>">
                        <button class="btn btn-danger" type="button" onclick="askUpdate('cancelled')">ยืนยันยกเลิก</button>
                    </div>
                </div>
                <p class="small opacity-50 mb-0 mt-3 border-top border-secondary pt-2">สถานะปัจจุบัน: <span class="badge bg-secondary text-uppercase"><?= $order['status'] ?></span></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-neon" id="neonConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center py-4">
            <div class="modal-body">
                <i id="pIcon" class="bi bi-question-circle text-neon-cyan display-1 mb-4 d-block"></i>
                <h3 class="fw-bold mb-3">ยืนยันการดำเนินการ</h3>
                <p class="opacity-75 mb-4 px-3 fs-5" id="pMessage">คุณแน่ใจหรือไม่ที่จะเปลี่ยนสถานะ?</p>
                <div class="d-flex gap-3 justify-content-center mt-2">
                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" id="pConfirmBtn" class="btn btn-confirm-neon shadow">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleCancelBox() { $('#cancelReasonInput').slideToggle(); }

    // ฟังก์ชันใหม่: เรียก Modal แทน confirm()
    function askUpdate(newStatus) {
        const reason = $('#reason_text').val();
        const modal = new bootstrap.Modal(document.getElementById('neonConfirmModal'));
        
        // ปรับข้อความตามสถานะ
        $('#pMessage').html('ต้องการเปลี่ยนสถานะออเดอร์เป็น <b class="text-neon-cyan">[' + newStatus + ']</b> ใช่หรือไม่?');
        
        if(newStatus === 'cancelled') {
            $('#pIcon').attr('class', 'bi bi-exclamation-octagon text-danger display-1 mb-4 d-block');
        } else {
            $('#pIcon').attr('class', 'bi bi-arrow-repeat text-neon-cyan display-1 mb-4 d-block');
        }

        modal.show();

        // เมื่อกดตกลงในป๊อปอัพ
        $('#pConfirmBtn').off('click').on('click', function() {
            modal.hide();
            $.post(window.location.href, { ajax_status: 1, status: newStatus, reason: reason }, function(res) {
                try {
                    const data = JSON.parse(res);
                    if(data.status === 'success') { window.location.reload(); }
                } catch(e) { window.location.reload(); }
            });
        });
    }
</script>
</body>
</html>