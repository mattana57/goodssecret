<?php
session_start();
include "connectdb.php"; // ตรวจสอบชื่อไฟล์เชื่อมต่อให้ถูก

if (!isset($_GET['id'])) { header("Location: admin_dashboard.php"); exit(); }
$id = intval($_GET['id']);

// --- ส่วนบันทึกข้อมูล ---
if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $is_hot = isset($_POST['is_hot']) ? 1 : 0; // ปรับให้ตรงกับ Database
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    $sql = "UPDATE products SET 
            name='$name', price='$price', stock='$stock', 
            description='$description', is_hot='$is_hot', is_trending='$is_trending' 
            WHERE id=$id";
    
    if ($conn->query($sql)) {
        echo "<script>alert('บันทึกสำเร็จ'); window.location='admin_dashboard.php?tab=products';</script>";
    }
}

// ดึงข้อมูลมาโชว์ในฟอร์ม
$p = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0c001c; color: white; padding: 50px; font-family: 'Tahoma'; }
        .edit-container { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 30px; border: 1px solid #bb86fc; }
        .form-control { background: rgba(255,255,255,0.1); color: white; border: 1px solid #444; }
        .form-control:focus { background: rgba(255,255,255,0.2); color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="edit-container">
        <h2 class="mb-4" style="color: #00f2fe;">🔮 แก้ไขข้อมูลสินค้า</h2>
        <form method="POST">
            <div class="row">
                <div class="col-md-4 text-center">
                    <img src="images/<?= $p['image'] ?>" class="img-fluid rounded mb-3" style="max-height: 300px; border: 2px solid #bb86fc;">
                    <p class="text-white-50">ชื่อไฟล์: <?= $p['image'] ?></p>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label>ชื่อสินค้า</label>
                        <input type="text" name="name" class="form-control" value="<?= $p['name'] ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>ราคา (฿)</label>
                            <input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label>สต็อก (ชิ้น)</label>
                            <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="4"><?= $p['description'] ?></textarea>
                    </div>
                    
                    <div class="mb-4 d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-warning">สินค้าแนะนำ (Hot)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_trending" <?= $p['is_trending'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-info">สินค้าอินเทรนด์</label>
                        </div>
                    </div>

                    <button type="submit" name="update_product" class="btn btn-primary px-5 rounded-pill">บันทึกการแก้ไข</button>
                    <a href="admin_dashboard.php" class="btn btn-outline-light px-5 rounded-pill">ยกเลิก</a>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>