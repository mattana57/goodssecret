<?php
session_start();
include "connectdb.php";

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); exit(); 
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- [Logic ส่วนจัดการสถานะ]: รองรับ AJAX เพื่อความรวดเร็วในการใช้งาน ---
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

// ดึงข้อมูลออเดอร์และรายการสินค้า (ดึง p.image มาร่วมด้วย)
$order_q = $conn->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
$order = $order_q->fetch_assoc();

if (!$order) { die("<div class='text-white p-5 text-center'><h3>ไม่พบข้อมูลออเดอร์</h3></div>"); }

$items_q = $conn->query("SELECT od.*, p.name, p.image, pv.variant_name FROM order_details od 
                         JOIN products p ON od.product_id = p.id 
                         LEFT JOIN product_variants pv ON od.variant_id = pv.id 
                         WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Order Detail #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <style>
        body { 
            background: #0c001c; 
            color: #ffffff; 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        /* ดีไซน์การ์ดโปร่งแสง (Glassmorphism) */
        .glass-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(15px); 
            border-radius: 25px; 
            padding: 30px; 
            border: 1px solid rgba(187, 134, 252, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px #f107a3; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
        
        .slip-preview { 
            width: 100%; 
            border-radius: 20px; 
            border: 2px solid rgba(0, 242, 254, 0.3); 
            transition: 0.3s; 
            cursor: pointer;
        }
        .slip-preview:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(0, 242, 254, 0.5); }
        
        /* สไตล์รูปสินค้าในตาราง */
        .product-img-td {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 15px;
            border: 1px solid rgba(187, 134, 252, 0.3);
        }
        
        /* ปุ่มสถานะ Step-by-Step */
        .status-btn { 
            border-radius: 50px; 
            font-weight: bold; 
            padding: 12px 25px; 
            transition: 0.4s;
            border-width: 2px;
        }
        .status-btn.active { box-shadow: 0 0 15px currentColor; }
        
        /* สไตล์ตารางสินค้า */
        .table-custom { color: #fff !important; }
        .table-custom thead th { color: rgba(255,255,255,0.5); font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table-custom td { border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 10px; }

        /* ป๊อปอัพนีออน */
        .modal-neon .modal-content {
            background: rgba(26, 0, 40, 0.98);
            border: 2px solid #bb86fc;
            border-radius: 30px;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="admin_dashboard.php?tab=orders" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
        </a>
        <div class="text-end">
            <h2 class="text-neon-pink mb-1 fw-bold">ORDER DETAILS</h2>
            <p class="text-neon-cyan mb-0 fs-5">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card mb-4">
                <h5 class="text-neon-cyan mb-4 fw-bold"><i class="bi bi-person-badge me-2"></i>ข้อมูลผู้รับ</h5>
                <div class="mb-3">
                    <label class="small opacity-50 d-block">ชื่อ-นามสกุล</label>
                    <span class="fs-5 fw-bold"><?= htmlspecialchars($order['fullname']) ?></span>
                </div>
                <div class="mb-3">
                    <label class="small opacity-50 d-block">เบอร์โทรศัพท์</label>
                    <span class="fs-5 text-neon-purple"><?= htmlspecialchars($order['phone']) ?></span>
                </div>
                <div class="mb-0">
                    <label class="small opacity-50 d-block">ที่อยู่จัดส่ง</label>
                    <p class="small mb-0 opacity-75"><?= htmlspecialchars($order['address']) ?> <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['zipcode']) ?></p>
                </div>
            </div>

            <div class="glass-card">
                <h5 class="text-neon-purple mb-4 fw-bold"><i class="bi bi-wallet2 me-2"></i>หลักฐานการชำระเงิน</h5>
                <?php if(!empty($order['slip_image'])): ?>
                    <img src="uploads/slips/<?= $order['slip_image'] ?>" class="slip-preview shadow-lg" onclick="window.open(this.src)">
                    <p class="text-center mt-3 small opacity-50">คลิกที่รูปเพื่อขยายใหญ่</p>
                <?php else: ?>
                    <div class="text-center py-5 opacity-25 border border-dashed rounded-4">
                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                        <span>ไม่มีสลิปการโอนเงิน</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card mb-4">
                <h5 class="text-white mb-4 fw-bold"><i class="bi bi-cart-check me-2"></i>รายการสั่งซื้อ</h5>
                <div class="table-responsive">
                    <table class="table table-custom w-100">
                        <thead>
                            <tr>
                                <th colspan="2">สินค้า</th> <th class="text-center">จำนวน</th>
                                <th class="text-end">ราคารวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items_q->fetch_assoc()): ?>
                            <tr class="align-middle">
                                <td style="width: 80px;">
                                    <?php if(!empty($item['image'])): ?>
                                        <img src="images/<?= htmlspecialchars($item['image']) ?>" class="product-img-td" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <div class="product-img-td d-flex align-items-center justify-content-center bg-dark opacity-50">
                                            <i class="bi bi-box text-white-50"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold d-block text-white fs-6"><?= htmlspecialchars($item['name']) ?></span>
                                    <small class="text-neon-cyan"><?= $item['variant_name'] ?: 'ไม่มีรุ่นย่อย' ?></small>
                                </td>
                                <td class="text-center fs-5 fw-bold"><?= $item['quantity'] ?></td>
                                <td class="text-end text-neon-purple fw-bold fs-5">฿<?= number_format($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end border-0 pt-4 fw-bold fs-5">ยอดรวมสุทธิ:</td>
                                <td class="text-end border-0 pt-4 h3 text-neon-pink fw-bold">฿<?= number_format($order['total_price']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="glass-card" style="border: 2px solid rgba(0, 242, 254, 0.4);">
                <h5 class="text-neon-cyan mb-4 fw-bold"><i class="bi bi-gear-fill me-2"></i>จัดการสถานะออเดอร์ (Step-by-Step)</h5>
                
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" onclick="askUpdate('processing')" 
                        class="btn status-btn <?= $order['status']=='processing' ? 'btn-primary active' : 'btn-outline-primary' ?> px-3">
                        <i class="bi bi-check2-circle me-1"></i> 1. ยืนยันยอดเงิน
                    </button>
                    
                    <button type="button" onclick="askUpdate('shipped')" 
                        class="btn status-btn <?= $order['status']=='shipped' ? 'btn-info text-dark active' : 'btn-outline-info' ?> px-3">
                        <i class="bi bi-truck me-1"></i> 2. จัดส่งสินค้า
                    </button>

                    <button type="button" onclick="askUpdate('delivered')" 
                        class="btn status-btn <?= $order['status']=='delivered' ? 'btn-success active' : 'btn-outline-success' ?> px-3">
                        <i class="bi bi-flag-fill me-1"></i> 3. ปิดงาน (สำเร็จ)
                    </button>

                    <button type="button" onclick="$('#cancelBox').slideToggle()" 
                        class="btn status-btn btn-outline-danger px-3 ms-auto">
                        <i class="bi bi-x-circle me-1"></i> ยกเลิกออเดอร์
                    </button>
                </div>

                <div id="cancelBox" style="display:none;" class="mb-3">
                    <label class="small text-danger mb-1">ระบุเหตุผลที่ยกเลิกบิลนี้:</label>
                    <div class="input-group">
                        <input type="text" id="reason_text" class="form-control bg-dark text-white border-danger" placeholder="เช่น ไม่พบยอดเงินโอน, สินค้าหมด..." value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>">
                        <button class="btn btn-danger" type="button" onclick="askUpdate('cancelled')">ยืนยันการยกเลิก</button>
                    </div>
                </div>
                
                <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <span class="opacity-50">สถานะปัจจุบัน:</span>
                    <span class="badge rounded-pill bg-secondary px-4 py-2 text-uppercase fs-6 shadow"><?= $order['status'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-neon" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center py-4">
            <div class="modal-body">
                <i id="mIcon" class="bi bi-question-circle text-neon-cyan display-1 mb-4 d-block"></i>
                <h3 class="fw-bold mb-3" id="mTitle">ยืนยันการทำรายการ</h3>
                <p class="opacity-75 mb-4 px-3 fs-5" id="mMessage">คุณแน่ใจหรือไม่ที่จะเปลี่ยนสถานะออเดอร์นี้?</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" id="confirmActionBtn" class="btn btn-primary px-4 rounded-pill shadow" style="background: linear-gradient(135deg, #00f2fe, #bb86fc); border:none; color:#000; font-weight:bold;">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ฟังก์ชันเปิด Modal ยืนยันแทน confirm() แบบบ้านๆ
    function askUpdate(newStatus) {
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        $('#mMessage').html('ต้องการเปลี่ยนสถานะออเดอร์เป็น <b class="text-neon-cyan">[' + newStatus + ']</b> ใช่หรือไม่?');
        
        if(newStatus === 'cancelled') {
            $('#mIcon').attr('class', 'bi bi-exclamation-octagon text-danger display-1 mb-4 d-block');
        } else {
            $('#mIcon').attr('class', 'bi bi-arrow-repeat text-neon-cyan display-1 mb-4 d-block');
        }

        modal.show();

        $('#confirmActionBtn').off('click').on('click', function() {
            modal.hide();
            $.post(window.location.href, { 
                ajax_status: 1, 
                status: newStatus, 
                reason: $('#reason_text').val() 
            }, function(res) {
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