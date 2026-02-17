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

// ดึงข้อมูลสินค้าในตะกร้าเพื่อคำนวณยอดรวมสุทธิ
$sql = "SELECT cart.*, products.name, products.price 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
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
    <title>ชำระเงิน | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* --- [ปรับสี]: ธีมม่วง-ดำ ลึกลับ (Dark Purple Neon) --- */
        body { 
            background-color: #0c001c; 
            color: #fff; 
            font-family: 'Segoe UI', sans-serif;
            background-image: radial-gradient(circle at top right, #3d1263, transparent), 
                              radial-gradient(circle at bottom left, #1e0036, transparent);
            min-height: 100vh;
        }

        .checkout-container { max-width: 1000px; margin: 50px auto; }

        .card-custom { 
            background: rgba(45, 10, 80, 0.4); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(187, 134, 252, 0.3); 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px rgba(187, 134, 252, 0.6); }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.6); }

        .form-control { 
            background: rgba(20, 0, 40, 0.6); 
            border: 1px solid rgba(187, 134, 252, 0.3); 
            color: #fff; 
            border-radius: 12px; 
            padding: 12px; 
        }
        .form-control:focus { 
            background: rgba(30, 0, 60, 0.8); 
            border-color: #f107a3; 
            color: #fff; 
            box-shadow: 0 0 15px rgba(241, 7, 163, 0.3); 
        }

        /* ปุ่มยืนยันสีชมพู-ม่วง */
        .btn-confirm { 
            background: linear-gradient(135deg, #f107a3, #bb86fc); 
            border: none; color: #fff; font-weight: bold; 
            width: 100%; padding: 15px; border-radius: 15px; 
            font-size: 1.1rem; transition: 0.3s; 
            box-shadow: 0 5px 15px rgba(241, 7, 163, 0.4);
        }
        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(241, 7, 163, 0.6); }

        /* ปุ่มยกเลิก/ย้อนกลับ */
        .btn-cancel {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.6);
            width: 100%; padding: 12px; border-radius: 15px;
            transition: 0.3s; margin-top: 10px; text-decoration: none;
            display: inline-block; text-align: center;
        }
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.05); color: #fff; border-color: #fff; }

        .payment-method-card { 
            border: 2px solid rgba(187, 134, 252, 0.2); 
            border-radius: 15px; padding: 20px; cursor: pointer; transition: 0.3s; 
            background: rgba(255, 255, 255, 0.03); 
        }
        .form-check-input:checked + .payment-method-card { 
            border-color: #f107a3; 
            background: rgba(241, 7, 163, 0.1); 
        }
    </style>
</head>
<body>

<div class="container checkout-container">
    <h2 class="mb-4 fw-bold text-neon-purple"><i class="bi bi-wallet2 me-2"></i> ชำระเงิน</h2>
    
    <form action="process_order.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card-custom h-100">
                    <h4 class="mb-4 text-neon-pink"><i class="bi bi-geo-alt me-2"></i>ที่อยู่สำหรับจัดส่ง</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" class="form-control" value="<?= $user_info['fullname'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" class="form-control" value="<?= $user_info['phone'] ?? '' ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">ที่อยู่จัดส่ง</label>
                            <textarea name="address" class="form-control" rows="3" required><?= $user_info['address'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">จังหวัด</label>
                            <input type="text" name="province" class="form-control" value="<?= $user_info['province'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">รหัสไปรษณีย์</label>
                            <input type="text" name="zipcode" class="form-control" value="<?= $user_info['zipcode'] ?? '' ?>" required>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 text-neon-pink"><i class="bi bi-credit-card me-2"></i>เลือกวิธีชำระเงิน</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="radio" class="form-check-input d-none" name="payment_method" id="bank" value="bank_transfer" checked>
                            <label class="payment-method-card d-block text-center" for="bank">
                                <i class="bi bi-bank fs-3 mb-2 text-neon-purple"></i>
                                <div>โอนเงินผ่านธนาคาร</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="form-check-input d-none" name="payment_method" id="cod" value="cod">
                            <label class="payment-method-card d-block text-center" for="cod">
                                <i class="bi bi-truck fs-3 mb-2 text-neon-purple"></i>
                                <div>เก็บเงินปลายทาง</div>
                            </label>
                        </div>
                    </div>

                    <div id="slip-section" class="mt-4 p-3 rounded-3" style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(187, 134, 252, 0.3);">
                        <p class="mb-2 small text-center text-neon-purple"><i class="bi bi-qr-code-scan me-2"></i>สแกนเพื่อโอนชำระเงิน ฿<?= number_format($grand_total) ?></p>
                        <div class="text-center mb-3">
                            <img src="https://promptpay.io/0812345678/<?= $grand_total ?>.png" class="img-fluid rounded-3" style="max-height: 200px;">
                        </div>
                        <label class="form-label small">แนบสลิปการโอนเงิน</label>
                        <input type="file" name="slip_image" class="form-control">
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card-custom">
                    <h4 class="mb-4 text-neon-pink"><i class="bi bi-receipt me-2"></i>สรุปรายการ</h4>
                    <?php foreach($items as $item): ?>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="opacity-75"><?= $item['name'] ?> x <?= $item['quantity'] ?></span>
                        <span class="fw-bold">฿<?= number_format($item['price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr class="border-secondary border-opacity-50 my-4">
                    
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
                    
                    <a href="cart.php" class="btn btn-cancel">
                        <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปหน้าตะกร้าสินค้า
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const bankRadio = document.getElementById('bank');
    const codRadio = document.getElementById('cod');
    const slipSection = document.getElementById('slip-section');

    codRadio.addEventListener('change', () => { if(codRadio.checked) slipSection.style.display = 'none'; });
    bankRadio.addEventListener('change', () => { if(bankRadio.checked) slipSection.style.display = 'block'; });
</script>
</body>
</html>