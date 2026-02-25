<?php
session_start();
include "connectdb.php";

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- [ส่วนที่แก้ไข]: รองรับทั้งการกดบันทึกปกติ และ AJAX (แก้ปัญหาหมุนค้าง) ---
if (isset($_POST['update_status']) || isset($_POST['ajax_status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $cancel_reason = $conn->real_escape_string($_POST['reason'] ?? $_POST['cancel_reason'] ?? '');
    
    $sql_update = "UPDATE orders SET status = '$new_status', cancel_reason = '$cancel_reason' WHERE id = $order_id";
    
    if ($conn->query($sql_update)) {
        // ถ้าส่งมาแบบ AJAX ให้ส่ง JSON กลับไปบอก Javascript ว่าเสร็จแล้ว
        if (isset($_POST['ajax_status'])) {
            echo json_encode(["status" => "success"]); exit();
        }
        // ถ้ากดปุ่มบันทึกแบบปกติ ให้ Refresh หน้า
        header("Location: admin_order_view.php?id=$order_id&success=1"); exit();
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
        .slip-img { max-width: 100%; border-radius: 15px; border: 2px solid #00f2fe; cursor: pointer; }
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
                <h5 class="text-neon-cyan mb-4"><i class="bi bi-gear-fill"></i> จัดการสถานะและเหตุผล</h5>
                <form id="statusForm" method="POST">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="small opacity-75">เปลี่ยนสถานะเป็น:</label>
                            <select name="status" id="statusSelect" class="form-select bg-dark text-white border-secondary mt-1">
                                <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : '' ?>>รอตรวจสอบ (ลูกค้าเห็น: รอตรวจสอบ)</option>
                                <option value="processing" <?= ($order['status'] == 'processing') ? 'selected' : '' ?>>ตรวจสอบสำเร็จ (ลูกค้าเห็น: กำลังจัดส่ง)</option>
                                <option value="shipped" <?= ($order['status'] == 'shipped') ? 'selected' : '' ?>>จัดส่งสินค้าแล้ว (ลูกค้าได้รับปุ่มยืนยัน)</option>
                                <option value="delivered" <?= ($order['status'] == 'delivered') ? 'selected' : '' ?>>จัดส่งสำเร็จ (สมบูรณ์)</option>
                                <option value="cancelled" <?= ($order['status'] == 'cancelled') ? 'selected' : '' ?>>ยกเลิกคำสั่งซื้อ</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cancelBox" style="display: <?= ($order['status'] == 'cancelled') ? 'block' : 'none' ?>;">
                            <label class="small text-danger">เหตุผลการยกเลิก:</label>
                            <input type="text" name="cancel_reason" id="cancel_reason" class="form-control bg-dark text-white border-danger mt-1" value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>" placeholder="แจ้งลูกค้าทราบ...">
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="button" onclick="saveStatus()" id="saveBtn" class="btn btn-success rounded-pill px-5 py-2 fw-bold shadow">บันทึกการอัปเดต</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $('#statusSelect').change(function() { $('#cancelBox').toggle($(this).val() === 'cancelled'); });

    function saveStatus() {
        const s = $('#statusSelect').val();
        const r = $('#cancel_reason').val();
        const btn = $('#saveBtn');
        
        btn.html('<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...').prop('disabled', true);

        // ส่งค่าไปที่ PHP เดิมในหน้านี้
        $.post(window.location.href, { 
            ajax_status: 1, 
            status: s, 
            reason: r 
        }, function(res) {
            try {
                const data = JSON.parse(res);
                if(data.status === 'success') {
                    btn.html('บันทึกสำเร็จ!').addClass('btn-info').removeClass('btn-success');
                    setTimeout(() => { 
                        btn.html('บันทึกการอัปเดต').prop('disabled', false).addClass('btn-success').removeClass('btn-info'); 
                    }, 2000);
                }
            } catch(e) {
                // ถ้า PHP พ่น error หรือไม่มี JSON กลับมา ให้รีโหลดหน้าไปเลยกันค้าง
                window.location.reload();
            }
        }).fail(function() {
            window.location.reload();
        });
    }
</script>
</body>
</html>