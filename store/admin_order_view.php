<?php
session_start();
include "connectdb.php";

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลออเดอร์ ลูกค้า และข้อมูลการจัดส่งแบบละเอียด
$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();

if (!$order) { die("<div style='color:white; background:#0c001c; height:100vh; display:flex; justify-content:center; align-items:center;'><h3>ไม่พบข้อมูลคำสั่งซื้อ</h3></div>"); }

// ดึงรายการสินค้าในบิล
$items_q = $conn->query("SELECT od.*, p.name, p.image, pv.variant_name FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");

// จัดการการเปลี่ยนสถานะโดยแอดมิน
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $cancel_reason = $conn->real_escape_string($_POST['cancel_reason'] ?? '');
    
    // อัปเดตสถานะและเหตุผลการยกเลิก
    $conn->query("UPDATE orders SET status = '$new_status', cancel_reason = '$cancel_reason' WHERE id = $order_id");
    header("Location: admin_order_view.php?id=$order_id&success=1"); exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการบิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 20px; padding: 25px; }
        .status-badge { padding: 8px 16px; border-radius: 50px; font-weight: bold; }
        .pending { background: #ffc107; color: #000; } 
        .processing { background: #00f2fe; color: #000; }
        .shipped { background: #bb86fc; color: #fff; } 
        .delivered { background: #198754; color: #fff; }
        .cancelled { background: #dc3545; color: #fff; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.5); }
        .slip-img { max-width: 100%; border-radius: 15px; border: 2px solid #00f2fe; cursor: pointer; transition: 0.3s; }
        .slip-img:hover { transform: scale(1.02); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill px-4"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        <h2 class="text-neon-pink mb-0">รายละเอียดคำสั่งซื้อ #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card mb-4 border-info">
                <h5 class="text-info border-bottom border-secondary pb-2 mb-3"><i class="bi bi-geo-alt"></i> ข้อมูลผู้รับ</h5>
                <p class="mb-1"><strong>ชื่อ:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                <p class="mb-1"><strong>อีเมล:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p class="mb-1"><strong>เบอร์โทร:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                <hr class="border-secondary opacity-25">
                <p class="mb-1 small"><strong>ที่อยู่:</strong> <?= htmlspecialchars($order['address']) ?></p>
                <p class="mb-1 small"><strong>จังหวัด:</strong> <?= htmlspecialchars($order['province']) ?> <strong>ไปรษณีย์:</strong> <?= htmlspecialchars($order['zipcode']) ?></p>
            </div>
            
            <div class="glass-card border-warning">
                <h5 class="text-warning border-bottom border-secondary pb-2 mb-3"><i class="bi bi-cash-stack"></i> หลักฐานการชำระเงิน</h5>
                <p><strong>วิธี:</strong> <?= $order['payment_method'] == 'bank' ? 'โอนเงินผ่านธนาคาร' : 'เก็บเงินปลายทาง' ?></p>
                <?php if($order['payment_method'] == 'bank' && !empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-img mt-2" onclick="window.open(this.src)">
                <?php elseif($order['payment_method'] == 'bank'): ?>
                    <div class="alert alert-warning small py-2 mt-2"><i class="bi bi-exclamation-circle"></i> ลูกค้ายังไม่แนบสลิป</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card mb-4">
                <h5 class="text-info border-bottom border-secondary pb-2 mb-3"><i class="bi bi-cart"></i> สินค้าในบิลนี้</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead><tr class="text-secondary small"><th>รูป</th><th>สินค้า</th><th>รุ่น</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr></thead>
                        <tbody>
                            <?php while($item = $items_q->fetch_assoc()): ?>
                            <tr class="align-middle">
                                <td><img src="images/<?= $item['image'] ?>" width="45" class="rounded"></td>
                                <td><?= $item['name'] ?></td>
                                <td><small class="text-info"><?= $item['variant_name'] ?: 'ทั่วไป' ?></small></td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-end fw-bold">ยอดรวมสุทธิ:</td><td class="text-end h4 text-neon-pink fw-bold">฿<?= number_format($order['total_price']) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="glass-card bg-black border-neon-cyan">
                <h5 class="text-neon-cyan mb-4"><i class="bi bi-gear-fill"></i> จัดการสถานะออเดอร์</h5>
                <form method="POST">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="small opacity-75">เปลี่ยนสถานะเป็น:</label>
                            <select name="status" class="form-select bg-dark text-white border-secondary mt-1" onchange="toggleCancelBox(this.value)">
                                <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>รอตรวจสอบ</option>
                                <option value="processing" <?= $order['status']=='processing'?'selected':'' ?>>ตรวจสอบคำสั่งซื้อสำเร็จ</option>
                                <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>จัดส่งสินค้าสำเร็จ</option>
                                <option value="delivered" <?= $order['status']=='delivered'?'selected':'' ?>>จัดส่งสำเร็จ</option>
                                <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>ยกเลิกคำสั่งซื้อ</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cancelBox" style="display: <?= $order['status'] == 'cancelled' ? 'block' : 'none' ?>;">
                            <label class="small text-danger">เหตุผลการยกเลิก:</label>
                            <input type="text" name="cancel_reason" class="form-control bg-dark text-white border-danger mt-1" value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>" placeholder="แจ้งเหตุผลให้ลูกค้าทราบ...">
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="update_status" class="btn btn-success rounded-pill px-5 py-2 fw-bold shadow-lg">บันทึกการอัปเดต</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCancelBox(val) {
        document.getElementById('cancelBox').style.display = (val === 'cancelled') ? 'block' : 'none';
    }
</script>
</body>
</html>