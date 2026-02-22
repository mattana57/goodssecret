<?php
session_start();
include "connectdb.php";

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลที่อยู่จากตาราง users มาแสดงอัตโนมัติ
$user_info_q = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_info = $user_info_q->fetch_assoc();

// [จุดแก้ไข]: ดึงข้อมูลสินค้าในตะกร้าพร้อมชื่อรูปแบบ (Variant) โดยการ JOIN ตาราง product_variants
$sql = "SELECT cart.*, products.name, products.price, pv.variant_name 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
        LEFT JOIN product_variants pv ON cart.variant_id = pv.id 
        WHERE cart.user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: cart.php"); 
    exit();
}

$grand_total = 0;
$items = [];
while($row = $result->fetch_assoc()) {
    $grand_total += ($row['price'] * $row['quantity']);
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - Goods Secret</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #0f172a;
            color: #ffffff;
            background-image: radial-gradient(circle at top right, #3d1263, transparent), 
                              radial-gradient(circle at bottom left, #1e1b4b, transparent);
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(187, 134, 252, 0.2);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }
        .text-neon-pink {
            color: #f107a3;
            text-shadow: 0 0 10px rgba(241, 7, 163, 0.5);
        }
        .text-neon-purple {
            color: #bb86fc;
            text-shadow: 0 0 10px rgba(187, 134, 252, 0.5);
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 10px;
            padding: 12px;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #bb86fc;
            color: white;
            box-shadow: 0 0 0 0.25 rbg(187, 134, 252, 0.25);
        }
        .payment-option {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        .payment-option:hover {
            border-color: #bb86fc;
            background: rgba(187, 134, 252, 0.05);
        }
        .btn-check:checked + .payment-option {
            border-color: #f107a3;
            background: rgba(241, 7, 163, 0.1);
        }
        .btn-confirm {
            background: linear-gradient(135deg, #f107a3, #ff0080);
            border: none;
            color: white;
            font-weight: bold;
            padding: 15px;
            border-radius: 50px;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(241, 7, 163, 0.4);
        }
        .btn-confirm:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
        }
        .btn-cancel {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px;
            border-radius: 50px;
            width: 100%;
            margin-top: 10px;
        }
        .qr-card {
            background: white;
            padding: 15px;
            border-radius: 15px;
            display: inline-block;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-neon-pink">Checkout</h2>
        <p class="text-secondary">กรุณาตรวจสอบข้อมูลการจัดส่งและเลือกวิธีการชำระเงิน</p>
    </div>

    <form action="process_order.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="glass-panel h-100">
                    <h4 class="mb-4 text-neon-purple"><i class="bi bi-geo-alt me-2"></i>ข้อมูลการจัดส่ง</h4>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label opacity-75">ชื่อ-นามสกุล ผู้รับ</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user_info['fullname'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label opacity-75">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label opacity-75">จังหวัด</label>
                            <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($user_info['province'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label opacity-75">ที่อยู่โดยละเอียด</label>
                            <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($user_info['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label opacity-75">รหัสไปรษณีย์</label>
                            <input type="text" name="zipcode" class="form-control" value="<?= htmlspecialchars($user_info['zipcode'] ?? '') ?>" required>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 text-neon-purple"><i class="bi bi-credit-card me-2"></i>วิธีชำระเงิน</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="payment_method" id="bank_transfer" value="bank_transfer" checked>
                            <label class="payment-option w-100" for="bank_transfer">
                                <i class="bi bi-bank fs-3 mb-2 d-block"></i>
                                <strong>โอนเงินผ่านธนาคาร</strong>
                                <p class="small opacity-50 mb-0 text-white">ชำระผ่าน QR Code หรือเลขบัญชี</p>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="payment_method" id="cod" value="cod">
                            <label class="payment-option w-100" for="cod">
                                <i class="bi bi-truck fs-3 mb-2 d-block"></i>
                                <strong>เก็บเงินปลายทาง</strong>
                                <p class="small opacity-50 mb-0 text-white">รอชำระเงินเมื่อได้รับสินค้า</p>
                            </label>
                        </div>
                    </div>

                    <div id="bank-details" class="mt-4 p-4 rounded-4" style="background: rgba(255,255,255,0.05);">
                        <div class="text-center">
                            <div class="qr-card mb-3">
                                <img src="https://promptpay.io/0624021798.png" width="200">
                            </div>
                            <h5>สแกนเพื่อชำระเงิน</h5>
                            <p class="text-neon-purple fw-bold mb-1">ธนาคารกสิกรไทย (K-Bank)</p>
                            <p class="mb-3">เลขบัญชี: 123-4-56789-0<br>ชื่อบัญชี: บจก. กู้ดส์ ซีเคร็ท สโตร์</p>
                            
                            <div class="text-start mt-4">
                                <label class="form-label opacity-75">แนบสลิปการโอนเงิน</label>
                                <input type="file" name="slip_image" id="slip_input" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="glass-panel">
                    <h4 class="mb-4 text-neon-pink"><i class="bi bi-receipt me-2"></i>สรุปรายการ</h4>
                    
                    <div class="order-items mb-4">
                        <?php foreach($items as $item): ?>
                        <div class="d-flex justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-white"><?= htmlspecialchars($item['name']) ?></span>
                                <?php if(!empty($item['variant_name'])): ?>
                                    <span class="text-neon-purple small"><i class="bi bi-info-circle me-1"></i>แบบ: <?= htmlspecialchars($item['variant_name']) ?></span>
                                <?php endif; ?>
                                <span class="text-secondary small">จำนวน: <?= $item['quantity'] ?> ชิ้น</span>
                            </div>
                            <span class="text-neon-pink fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75">ยอดรวมสินค้า</span>
                        <span>฿<?= number_format($grand_total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75">ค่าจัดส่ง</span>
                        <span class="text-success fw-bold">ฟรี</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4 pt-3 border-top border-secondary border-opacity-25">
                        <span class="h4">ยอดชำระสุทธิ</span>
                        <span class="h3 fw-bold text-neon-pink">฿<?= number_format($grand_total) ?></span>
                    </div>

                    <button type="submit" class="btn btn-confirm">
                        ยืนยันการสั่งซื้อสินค้า <i class="bi bi-check-circle ms-2"></i>
                    </button>
                    
                    <a href="cart.php" class="btn btn-cancel text-decoration-none d-block text-center">
                        <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปหน้าตะกร้าสินค้า
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const bankTransfer = document.getElementById('bank_transfer');
    const cod = document.getElementById('cod');
    const bankDetails = document.getElementById('bank-details');
    const slipInput = document.getElementById('slip_input');

    cod.addEventListener('change', function() {
        if(this.checked) {
            bankDetails.style.display = 'none';
            slipInput.required = false;
        }
    });

    bankTransfer.addEventListener('change', function() {
        if(this.checked) {
            bankDetails.style.display = 'block';
            slipInput.required = true;
        }
    });
</script>
</body>
</html>