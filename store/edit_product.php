<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php"); exit();
}

$id = intval($_GET['id']);

// Logic การอัปเดตข้อมูล
if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    $image_query = "";
    if (!empty($_FILES['image']['name'])) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_image_name = time() . "_" . uniqid() . "." . $file_ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $new_image_name)) {
            $image_query = ", image = '$new_image_name'";
        }
    }

    $sql = "UPDATE products SET 
            name = '$name', description = '$desc', 
            price = $price, stock = $stock, is_hot = $is_hot, is_trending = $is_trending 
            $image_query 
            WHERE id = $id";

    if ($conn->query($sql)) {
        echo "<script>alert('บันทึกการแก้ไขสำเร็จ!'); window.location='admin_dashboard.php?tab=products';</script>";
        exit();
    }
}

$p = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า: <?= $p['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 50px 0; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .text-neon { color: #00f2fe; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .form-control { background: rgba(255, 255, 255, 0.1) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #fff !important; border-radius: 15px; }
        .preview-box { width: 100%; aspect-ratio: 1/1; border: 2px dashed rgba(187, 134, 252, 0.5); border-radius: 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .preview-box img { width: 100%; height: 100%; object-fit: cover; }
        .btn-save { background: linear-gradient(135deg, #00f2fe, #bb86fc); border: none; color: #120018; font-weight: bold; border-radius: 30px; padding: 12px 40px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 glass-card">
            <h2 class="text-neon mb-5"><i class="bi bi-pencil-square me-3"></i>แก้ไขสินค้า: <?= htmlspecialchars($p['name']) ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-5">
                    <div class="col-md-4 text-center">
                        <div class="preview-box mb-3">
                            <img src="images/<?= $p['image'] ?>" id="imgPreview" onerror="this.src='https://via.placeholder.com/300'">
                        </div>
                        <input type="file" name="image" class="form-control" onchange="preview(this)">
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">ชื่อสินค้า</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ราคา (บาท)</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $p['price'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">สต็อก (ชิ้น)</label>
                                <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($p['description']) ?></textarea>
                            </div>
                            <div class="col-12 d-flex gap-4 my-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_hot" id="hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-warning" for="hot">สินค้าแนะนำ (Hot)</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_trending" id="trend" <?= $p['is_trending'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-info" for="trend">สินค้าอินเทรนด์</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-3">
                                <a href="admin_dashboard.php?tab=products" class="btn btn-outline-light rounded-pill px-4">ยกเลิก</a>
                                <button type="submit" name="update_product" class="btn-save">บันทึกการแก้ไข</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function preview(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('imgPreview').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>