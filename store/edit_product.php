<?php
session_start();
include "connectdb.php"; // ไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบสิทธิ์การเป็น Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php"); exit();
}

$id = intval($_GET['id']);

// --- ส่วนของการบันทึกข้อมูล (Update Logic) ---
if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cat_id = intval($_POST['category_id']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    // ตรวจสอบการเปลี่ยนรูปภาพหลัก
    $image_query = "";
    if (!empty($_FILES['image']['name'])) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_image_name = time() . "_" . uniqid() . "." . $file_ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $new_image_name)) {
            $image_query = ", image = '$new_image_name'";
        }
    }

    $sql = "UPDATE products SET 
            name = '$name', 
            category_id = $cat_id, 
            description = '$desc', 
            price = $price, 
            stock = $stock, 
            is_hot = $is_hot, 
            is_trending = $is_trending 
            $image_query 
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: admin_dashboard.php?tab=products&update_success=1");
        exit();
    }
}

// ดึงข้อมูลสินค้าปัจจุบัน
$res = $conn->query("SELECT * FROM products WHERE id = $id");
$p = $res->fetch_assoc();

// ดึงหมวดหมู่ทั้งหมดสำหรับ Select Box
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า: <?= $p['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; padding-top: 50px; }
        .edit-card { 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(187, 134, 252, 0.3); 
            border-radius: 25px; 
            padding: 40px; 
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .form-control, .form-select { 
            background: rgba(255, 255, 255, 0.1) !important; 
            color: #fff !important; 
            border: 1px solid rgba(187, 134, 252, 0.4) !important; 
            border-radius: 12px;
        }
        .form-control:focus { box-shadow: 0 0 15px rgba(187, 134, 252, 0.5); }
        .btn-save { background: linear-gradient(135deg, #00f2fe, #bb86fc); border: none; color: #120018; font-weight: bold; }
        .preview-img { width: 150px; height: 150px; object-fit: cover; border-radius: 15px; border: 2px solid #bb86fc; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 edit-card">
            <h3 class="mb-4 text-neon-cyan"><i class="bi bi-pencil-square me-2"></i>แก้ไขสินค้า: <?= $p['name'] ?></h3>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <label class="form-label d-block">รูปภาพปัจจุบัน</label>
                        <img src="images/<?= $p['image'] ?>" class="preview-img" onerror="this.src='https://via.placeholder.com/150'">
                        <input type="file" name="image" class="form-control form-control-sm">
                        <small class="text-white-50 mt-2 d-block">ปล่อยว่างหากไม่ต้องการเปลี่ยนรูป</small>
                    </div>

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">ชื่อสินค้า</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">หมวดหมู่</label>
                            <select name="category_id" class="form-select" required>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= $cat['name'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">ราคา (บาท)</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $p['price'] ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">สต็อก (ชิ้น)</label>
                                <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">รายละเอียดสินค้า</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($p['description']) ?></textarea>
                </div>

                <div class="mb-4 d-flex gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_hot" id="hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="hot text-danger">สินค้าแนะนำ (Hot)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_trending" id="trend" <?= $p['is_trending'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="trend text-info">สินค้าอินเทรนด์</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="admin_dashboard.php?tab=products" class="btn btn-outline-light rounded-pill px-4">ยกเลิก</a>
                    <button type="submit" name="update_product" class="btn btn-save rounded-pill px-5">
                        <i class="bi bi-check-circle me-1"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>