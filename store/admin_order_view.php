<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

$order_id = intval($_GET['id']);

// ดึงข้อมูลออเดอร์และลูกค้า
$order_q = $conn->query("SELECT o.*, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();

if (!$order) { die("ไม่พบข้อมูลคำสั่งซื้อ"); }

// ดึงรายการสินค้าในบิล
$items_q = $conn->query("SELECT od.*, p.name, p.image, pv.variant_name FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");

// จัดการการเปลี่ยนสถานะ
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$new_status' WHERE id = $order_id");
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
        .processing { background: #17a2b8; color: #fff; }
        .shipped { background: #0d6efd; color: #fff; }
        .delivered { background: #198754; color: #fff; }
        .cancelled { background: #dc3545; color: #fff; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.5); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        <h2 class="text-neon-pink">รายละเอียดคำสั่งซื้อ #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card">
                <h5 class="text-info border-bottom border-secondary pb-2"><i class="bi bi-person-vcard"></i> ข้อมูลลูกค้า</h5>
                <p><strong>ชื่อ:</strong> <?= $order['fullname'] ?></p>
                <p><strong>เบอร์โทร:</strong> <?= $order['phone'] ?></p>
                <p><strong>ที่อยู่:</strong> <?= nl2br($order['address']) ?></p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card">
                <h5 class="text-info border-bottom border-secondary pb-2"><i class="bi bi-box-seam"></i> รายการสินค้า</h5>
                <table class="table table-dark">
                    <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>รุ่น</th><th class="text-center">จำนวน</th><th class="text-end">ราคา</th></tr></thead>
                    <tbody>
                        <?php while($item = $items_q->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $item['image'] ?>" width="40" class="rounded"></td>
                            <td><?= $item['name'] ?></td>
                            <td><small class="text-secondary"><?= $item['variant_name'] ?: 'ทั่วไป' ?></small></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end fw-bold">ยอดรวมทั้งหมด:</td><td class="text-end h4 text-neon-pink">฿<?= number_format($order['total_price']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="col-12">
            <div class="glass-card bg-black border-info">
                <form method="POST" class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">สถานะ: <span class="status-badge <?= $order['status'] ?>"><?= strtoupper($order['status']) ?></span></h5>
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>รอตรวจสอบ</option>
                            <option value="processing" <?= $order['status']=='processing'?'selected':'' ?>>กำลังแพ็คของ</option>
                            <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>จัดส่งแล้ว</option>
                            <option value="delivered" <?= $order['status']=='delivered'?'selected':'' ?>>จัดส่งสำเร็จ</option>
                            <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="update_status" class="btn btn-success w-100">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>