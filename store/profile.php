<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$update_success = false;
$order_complete = isset($_GET['order_complete']);

// --- ระบบบันทึกการแก้ไขโปรไฟล์ (คงเดิมไว้ทั้งหมด) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $province = $conn->real_escape_string($_POST['province']);
    $zipcode = $conn->real_escape_string($_POST['zipcode']);

    $sql = "UPDATE users SET fullname = '$fullname', email = '$email', phone = '$phone', 
            address = '$address', province = '$province', zipcode = '$zipcode' WHERE id = $user_id";
    if($conn->query($sql)) { $update_success = true; }
}

$user_q = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_data = $user_q->fetch_assoc();
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: radial-gradient(circle at 20% 30%, #4b2c63 0%, transparent 40%), 
                        radial-gradient(circle at 80% 70%, #6a1b9a 0%, transparent 40%), 
                        linear-gradient(135deg,#120018,#2a0845,#3d1e6d);
            color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh;
        }
        .card-custom { background: rgba(26, 0, 40, 0.65); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 24px; }
        .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px rgba(187, 134, 252, 0.5); }
        .info-label { font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-bottom: 2px; }
        .info-value { font-size: 1.1rem; color: #fff; margin-bottom: 15px; border-bottom: 1px solid rgba(187, 134, 252, 0.2); padding-bottom: 5px; }
        .custom-input { background: rgba(20, 0, 40, 0.6) !important; border: 1px solid rgba(187, 134, 252, 0.3) !important; color: #fff !important; border-radius: 12px !important; }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); color: #fff; border: none; font-weight: bold; border-radius: 12px; padding: 12px; transition: 0.3s; }
        
        /* --- [ปรับ]: แก้ไขตารางให้โปร่งแสงและลบสีขาวออก --- */
        .table { color: #fff !important; background: transparent !important; }
        .table thead th { background: rgba(187, 134, 252, 0.1) !important; color: #bb86fc !important; border: none !important; padding: 15px; }
        .table tbody td { background: transparent !important; border-bottom: 1px solid rgba(187, 134, 252, 0.1) !important; color: #fff !important; padding: 15px; }
        .badge-status { border: 1px solid #bb86fc; color: #bb86fc; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .modal-content.custom-popup { background: rgba(26, 0, 40, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(187, 134, 252, 0.4); border-radius: 25px; color: #fff; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-neon-cyan mb-0"><i class="bi bi-person-badge me-2"></i> โปรไฟล์ของฉัน</h4>
                    <button class="btn btn-outline-info btn-sm rounded-pill" id="toggleEditBtn" onclick="toggleEdit()">แก้ไข</button>
                </div>

                <div id="displayMode">
                    <div class="info-label">อีเมล</div>
                    <div class="info-value"><?= htmlspecialchars($user_data['email']) ?></div>
                    <div class="info-label">ชื่อ-นามสกุล</div>
                    <div class="info-value text-neon-purple"><?= htmlspecialchars($user_data['fullname']) ?: 'ยังไม่ได้ระบุ' ?></div>
                    <div class="info-label">เบอร์โทรศัพท์</div>
                    <div class="info-value"><?= htmlspecialchars($user_data['phone']) ?: 'ยังไม่ได้ระบุ' ?></div>
                    <div class="info-label">ที่อยู่จัดส่ง</div>
                    <div class="info-value small"><?= htmlspecialchars($user_data['address']) ?: 'ยังไม่ได้ระบุ' ?></div>
                    <div class="row">
                        <div class="col-6"><div class="info-label">จังหวัด</div><div class="info-value"><?= htmlspecialchars($user_data['province']) ?: '-' ?></div></div>
                        <div class="col-6"><div class="info-label">รหัสไปรษณีย์</div><div class="info-value"><?= htmlspecialchars($user_data['zipcode']) ?: '-' ?></div></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="index.php" class="btn btn-outline-info w-50" style="border-radius:12px;">หน้าหลัก</a>
                        <a href="logout.php" class="btn btn-outline-danger w-50" style="border-radius:12px;">ออกจากระบบ</a>
                    </div>
                </div>

                <div id="editMode" style="display: none;">
                    <form action="" method="POST">
                        <div class="mb-3"><label class="small opacity-50">อีเมล</label><input type="email" name="email" class="form-control custom-input" value="<?= htmlspecialchars($user_data['email']) ?>" required></div>
                        <div class="mb-3"><label class="small opacity-50">ชื่อ-นามสกุล</label><input type="text" name="fullname" class="form-control custom-input" value="<?= htmlspecialchars($user_data['fullname'] ?? '') ?>"></div>
                        <div class="mb-3"><label class="small opacity-50">เบอร์โทรศัพท์</label><input type="tel" name="phone" class="form-control custom-input" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"></div>
                        <div class="mb-3"><label class="small opacity-50">ที่อยู่</label><textarea name="address" class="form-control custom-input" rows="2"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><input type="text" name="province" class="form-control custom-input" placeholder="จังหวัด" value="<?= htmlspecialchars($user_data['province'] ?? '') ?>"></div>
                            <div class="col-6"><input type="text" name="zipcode" class="form-control custom-input" placeholder="รหัสไปรษณีย์" value="<?= htmlspecialchars($user_data['zipcode'] ?? '') ?>"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-light w-50" style="border-radius:12px;" onclick="toggleEdit()">ยกเลิก</button>
                            <button type="submit" name="update_profile" class="btn btn-neon-pink w-50">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-custom p-4 h-100">
                <h4 class="text-neon-cyan mb-4"><i class="bi bi-clock-history me-2"></i> ประวัติการสั่งซื้อ</h4>
                <div class="table-responsive">
                    <table class="table border-0">
                        <thead>
                            <tr class="small"><th>เลขออเดอร์ (กดเพื่อดูบิล)</th><th>ยอดรวม</th><th class="text-end">สถานะ</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $orders->fetch_assoc()): ?>
                            <tr class="align-middle">
                                <td>
                                    <a href="order_detail.php?id=<?= $row['id'] ?>" class="fw-bold text-decoration-none" style="color:#bb86fc;">
                                        #<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?> <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </td>
                                <td class="text-neon-cyan">฿<?= number_format($row['total_price']) ?></td>
                                <td class="text-end">
                                    <span class="badge-status">
                                        <?= $row['status'] == 'pending' ? 'รอตรวจสอบ' : ($row['status'] == 'cancelled' ? 'ยกเลิกแล้ว' : 'สำเร็จ') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-popup text-center py-5">
            <div class="modal-body">
                <i class="bi bi-check-circle text-neon-pink display-1 mb-4"></i>
                <h3 class="text-neon-purple fw-bold" id="modalTitle">บันทึกสำเร็จ!</h3>
                <p class="opacity-75" id="modalBody">ข้อมูลส่วนตัวของคุณได้รับการอัปเดตแล้ว ✨</p>
                <button type="button" class="btn btn-neon-pink px-5 mt-3" data-bs-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleEdit() {
        const d = document.getElementById('displayMode'), e = document.getElementById('editMode'), b = document.getElementById('toggleEditBtn');
        const isEdit = d.style.display === 'none';
        d.style.display = isEdit ? 'block' : 'none'; e.style.display = isEdit ? 'none' : 'block';
        b.innerHTML = isEdit ? 'แก้ไข' : 'ดูข้อมูล';
    }
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($update_success || $order_complete): ?>
            if (<?= $order_complete ? 'true' : 'false' ?>) {
                document.getElementById('modalTitle').innerText = 'สั่งซื้อสำเร็จ!';
                document.getElementById('modalBody').innerText = 'เราได้รับคำสั่งซื้อความลับของคุณแล้ว ✨';
            }
            new bootstrap.Modal(document.getElementById('successModal')).show();
        <?php endif; ?>
    });
</script>
</body>
</html>