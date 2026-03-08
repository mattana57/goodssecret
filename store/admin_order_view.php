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
    
    $cancel_sql = "";
    if($new_status === 'cancelled') {
        $cancel_sql = ", cancel_by = 'admin', cancel_reason = '$cancel_reason'";
    } else {
        $cancel_sql = ", cancel_reason = ''";
    }

    $sql_update = "UPDATE orders SET status = '$new_status' $cancel_sql WHERE id = $order_id";
    if ($conn->query($sql_update)) {
        echo json_encode(["status" => "success"]); exit();
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]); exit();
    }
}

$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();
if (!$order) { die("<div class='text-white p-5 text-center'><h3>ไม่พบข้อมูลบิลนี้</h3><a href='admin_dashboard.php'>กลับหน้าหลัก</a></div>"); }

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
    <title>จัดการบิล #<?= $order_id ?> | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <style>
        :root {
            --neon-pink: #f107a3;
            --neon-cyan: #00f2fe;
            --neon-purple: #bb86fc;
            --dark-bg: #0c001c;
        }

        body { 
            background: var(--dark-bg); 
            color: #ffffff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: radial-gradient(circle at top right, #3d1263, transparent), 
                              radial-gradient(circle at bottom left, #1e0036, transparent);
            min-height: 100vh;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(187, 134, 252, 0.2);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: 0.3s;
        }

        .text-neon-pink { color: var(--neon-pink); text-shadow: 0 0 10px rgba(241, 7, 163, 0.5); }
        .text-neon-cyan { color: var(--neon-cyan); text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        
        .order-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            border: 1px solid currentColor;
        }

        .status-pending { color: #ffcc00; }
        .status-processing { color: var(--neon-cyan); }
        .status-shipped { color: var(--neon-purple); }
        .status-delivered { color: #00ff88; }
        .status-cancelled { color: #ff4d4d; }

        .btn-action {
            border-radius: 15px;
            padding: 12px 20px;
            font-weight: 700;
            transition: 0.3s;
            border: 1.5px solid transparent;
        }

        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 15px;
            border: 2px solid rgba(187, 134, 252, 0.3);
        }

        .table { --bs-table-bg: transparent; color: #fff; border-color: rgba(255,255,255,0.1); }
        
        .swal2-popup { 
            background: #1a0028 !important; 
            border: 2px solid var(--neon-purple) !important; 
            border-radius: 30px !important; 
        }

        .custom-hr { border-color: rgba(187, 134, 252, 0.2); opacity: 1; margin: 25px 0; }
        
        .slip-preview {
            border-radius: 20px;
            border: 2px dashed var(--neon-cyan);
            padding: 10px;
            background: rgba(0, 242, 254, 0.05);
            cursor: pointer;
            transition: 0.3s;
        }
        .slip-preview:hover { background: rgba(0, 242, 254, 0.1); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>กลับหน้าหลัก
        </a>
        <div class="text-end">
            <h2 class="text-neon-pink fw-bold mb-0">บิล #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
            <span class="order-badge status-<?= $order['status'] ?> mt-2 d-inline-block">
                <?= $order['status'] ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-panel mb-4">
                <h5 class="text-neon-cyan mb-4"><i class="bi bi-person-badge me-2"></i>ข้อมูลผู้รับ</h5>
                <p class="mb-1 fs-5 fw-bold"><?= htmlspecialchars($order['fullname']) ?></p>
                <p class="text-white-50 mb-3 small"><?= htmlspecialchars($order['email']) ?></p>
                <div class="p-3 rounded-4 bg-black bg-opacity-30 border border-white border-opacity-10">
                    <p class="mb-1 small"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($order['phone']) ?></p>
                    <p class="mb-0 small"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($order['address']) ?> <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['zipcode']) ?></p>
                </div>
            </div>

            <div class="glass-panel">
                <h5 class="text-neon-purple mb-4"><i class="bi bi-credit-card me-2"></i>การชำระเงิน</h5>
                <p class="mb-3">ช่องทาง: <b class="text-neon-pink"><?= strtoupper($order['payment_method']) ?></b></p>
                
                <?php if($order['payment_method'] == 'bank' && !empty($order['slip_image'])): ?>
                    <div class="slip-preview text-center" onclick="window.open('slips/<?= $order['slip_image'] ?>')">
                        <img src="slips/<?= $order['slip_image'] ?>" class="img-fluid rounded-3 shadow">
                        <p class="mt-2 mb-0 small text-neon-cyan fw-bold">คลิกเพื่อดูสลิปขนาดใหญ่</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 opacity-50 border border-white border-opacity-10 rounded-4">
                        <i class="bi bi-truck-flatbed fs-1 d-block mb-2"></i>
                        <span>เก็บเงินปลายทาง</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if($order['status'] == 'cancelled'): ?>
                <div class="alert bg-danger bg-opacity-10 border-danger text-danger rounded-4 mb-4 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>คำสั่งซื้อนี้ถูกยกเลิกโดย <?= ($order['cancel_by'] == 'admin') ? 'แอดมิน' : 'ลูกค้า' ?></strong>
                    <p class="mb-0 mt-1 small">เหตุผล: <?= htmlspecialchars($order['cancel_reason'] ?: 'ไม่ระบุเหตุผล') ?></p>
                </div>
            <?php endif; ?>

            <div class="glass-panel mb-4">
                <h5 class="text-white mb-4">รายการสินค้า</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-white-50 small">
                                <th>รูปสินค้า</th>
                                <th>ชื่อสินค้า / รุ่น</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">รวมสุทธิ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items_q->fetch_assoc()): 
                                $display_img = !empty($item['variant_image']) ? $item['variant_image'] : $item['image'];
                            ?>
                            <tr>
                                <td><img src="images/<?= htmlspecialchars($display_img) ?>" class="product-img"></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                    <small class="text-neon-cyan"><?= $item['variant_name'] ?: 'รุ่นมาตรฐาน' ?></small>
                                </td>
                                <td class="text-center">x <?= $item['quantity'] ?></td>
                                <td class="text-end fw-bold text-neon-pink">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end border-0 pt-4">ยอดรวมทั้งหมด:</td>
                                <td class="text-end border-0 pt-4"><h4 class="text-neon-cyan fw-bold mb-0">฿<?= number_format($order['total_price']) ?></h4></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="glass-panel" style="border-color: var(--neon-cyan);">
                <h5 class="text-neon-cyan mb-4 fw-bold"><i class="bi bi-gear-fill me-2"></i>จัดการสถานะคำสั่งซื้อ</h5>
                <div class="d-flex flex-wrap gap-3">
                    <button onclick="updateStatus('processing')" class="btn btn-action btn-outline-info flex-grow-1 <?= $order['status']=='processing' ? 'active' : '' ?>">
                        <i class="bi bi-check2-circle me-1"></i> ยืนยันรายการ
                    </button>
                    <button onclick="updateStatus('shipped')" class="btn btn-action btn-outline-primary flex-grow-1 <?= $order['status']=='shipped' ? 'active' : '' ?>">
                        <i class="bi bi-box-seam me-1"></i> ส่งสินค้าแล้ว
                    </button>
                    <button onclick="updateStatus('delivered')" class="btn btn-action btn-outline-success flex-grow-1 <?= $order['status']=='delivered' ? 'active' : '' ?>">
                        <i class="bi bi-house-check me-1"></i> สำเร็จงาน
                    </button>
                    <button onclick="cancelByAdmin()" class="btn btn-action btn-outline-danger flex-grow-1">
                        <i class="bi bi-x-octagon me-1"></i> ยกเลิกบิล
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ปรับสีปุ่ม SweetAlert ให้เข้าธีม
    const swalConfig = {
        background: '#1a0028',
        color: '#fff',
        confirmButtonColor: '#f107a3',
        cancelButtonColor: '#444'
    };

    function updateStatus(newStatus) {
        Swal.fire({
            ...swalConfig,
            title: 'ยืนยันการเปลี่ยนสถานะ?',
            text: `เปลี่ยนสถานะออเดอร์เป็น [${newStatus.toUpperCase()}]`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ใช่, เปลี่ยนเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(window.location.href, { ajax_status: 1, status: newStatus }, function(res) {
                    Swal.fire({...swalConfig, title: 'สำเร็จ!', icon: 'success'}).then(() => window.location.reload());
                });
            }
        });
    }

    function cancelByAdmin() {
        Swal.fire({
            ...swalConfig,
            title: 'ยกเลิกออเดอร์',
            text: 'กรุณาระบุเหตุผลการยกเลิกโดยแอดมิน:',
            input: 'text',
            inputPlaceholder: 'เช่น สินค้าหมดชั่วคราว, ข้อมูลไม่ครบ...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยันยกเลิก',
            cancelButtonText: 'กลับไปก่อน'
        }).then((result) => {
            if (result.isConfirmed) {
                if (!result.value) {
                    Swal.fire({...swalConfig, title: 'กรุณาระบุเหตุผล', icon: 'error'});
                    return;
                }
                $.post(window.location.href, { ajax_status: 1, status: 'cancelled', reason: result.value }, function() {
                    Swal.fire({...swalConfig, title: 'ยกเลิกเรียบร้อย', icon: 'success'}).then(() => window.location.reload());
                });
            }
        });
    }
</script>
</body>
</html>