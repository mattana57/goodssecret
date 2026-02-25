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
    <title>รายละเอียดบิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .order-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 20px; padding: 25px; }
        .status-badge { padding: 8px 16px; border-radius: 50px; font-weight: bold; font-size: 0.9rem; }
        .pending { background: #ffc107; color: #000; } .processing { background: #17a2b8; color: #fff; }
        .shipped { background: #0d6efd; color: #fff; } .delivered { background: #198754; color: #fff; }
        .cancelled { background: #dc3545; color: #fff; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.5); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        <h2 class="text-neon-pink mb-0">รายละเอียดคำสั่งซื้อ #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="order-card h-100">
                <h5 class="text-info border-bottom border-secondary pb-2"><i class="bi bi-person-circle"></i> ข้อมูลลูกค้า</h5>
                <p class="mb-1"><strong>ชื่อ:</strong> <?= $order['fullname'] ?></p>
                <p class="mb-1"><strong>อีเมล:</strong> <?= $order['email'] ?></p>
                <p class="mb-1"><strong>เบอร์โทร:</strong> <?= $order['phone'] ?></p>
                <hr class="border-secondary">
                <h5 class="text-info pb-2"><i class="bi bi-geo-alt-fill"></i> ที่อยู่จัดส่ง</h5>
                <p class="small opacity-75"><?= nl2br($order['address']) ?></p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="order-card h-100">
                <h5 class="text-info border-bottom border-secondary pb-2"><i class="bi bi-cart-fill"></i> รายการสินค้า</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr class="text-secondary"><th>สินค้า</th><th class="text-center">รุ่น</th><th class="text-center">จำนวน</th><th class="text-end">ราคา</th></tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items_q->fetch_assoc()): ?>
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="images/<?= $item['image'] ?>" width="40" class="rounded me-2">
                                        <span><?= $item['name'] ?></span>
                                    </div>
                                </td>
                                <td class="text-center text-info"><?= $item['variant_name'] ?: '-' ?></td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end text-neon-pink">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">ยอดรวมสุทธิ:</td>
                                <td class="text-end h4 text-neon-pink">฿<?= number_format($order['total_price']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="order-card bg-dark">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="text-info mb-0">สถานะปัจจุบัน: 
                            <span class="status-badge <?= $order['status'] ?>">
                                <?php 
                                    if($order['status'] == 'pending') echo '<i class="bi bi-clock"></i> รอตรวจสอบ';
                                    if($order['status'] == 'processing') echo '<i class="bi bi-box-seam"></i> กำลังแพ็คของ';
                                    if($order['status'] == 'shipped') echo '<i class="bi bi-truck"></i> จัดส่งสินค้าแล้ว';
                                    if($order['status'] == 'delivered') echo '<i class="bi bi-check-circle-fill"></i> จัดส่งสำเร็จ';
                                    if($order['status'] == 'cancelled') echo '<i class="bi bi-x-circle"></i> ยกเลิก';
                                ?>
                            </span>
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" class="d-flex gap-2">
                            <select name="status" class="form-select bg-black text-white border-secondary">
                                <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>รอตรวจสอบ</option>
                                <option value="processing" <?= $order['status']=='processing'?'selected':'' ?>>กำลังแพ็คของ</option>
                                <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>จัดส่งสินค้าแล้ว</option>
                                <option value="delivered" <?= $order['status']=='delivered'?'selected':'' ?>>จัดส่งสำเร็จ</option>
                                <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>ยกเลิก</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-success px-4">บันทึกการเปลี่ยนสถานะ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>