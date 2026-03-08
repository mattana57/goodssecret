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

// Logic การอัปเดตข้อมูล (คงเดิมและเพิ่มส่วน Variants)
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

    $sql = "UPDATE products SET name='$name', description='$desc', price=$price, stock=$stock, is_hot=$is_hot, is_trending=$is_trending $image_query WHERE id=$id";
    
    if ($conn->query($sql)) {
        // --- [ส่วนที่เพิ่ม]: อัปเดตรุ่นย่อย (Variants) ---
        if (isset($_POST['v_id'])) {
            foreach ($_POST['v_id'] as $key => $v_id) {
                $v_name = mysqli_real_escape_string($conn, $_POST['v_name'][$key]);
                $v_stock = intval($_POST['v_stock'][$key]);
                $conn->query("UPDATE product_variants SET variant_name='$v_name', stock=$v_stock WHERE id=$v_id");
            }
        }
        echo "<script>alert('บันทึกการแก้ไขเรียบร้อยแล้ว'); window.location='admin_dashboard.php?tab=products';</script>";
    }
}

// ดึงข้อมูลสินค้าเดิม
$p = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
// ดึงข้อมูลรุ่นย่อย
$variants = $conn->query("SELECT * FROM product_variants WHERE product_id=$id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* CSS เดิมของคุณทั้งหมด 100% */
        body { background: radial-gradient(circle at 20% 30%, #2a0845 0%, transparent 40%), linear-gradient(135deg,#0c001c,#1a0028); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding: 40px 0; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .form-control { background: rgba(255, 255, 255, 0.1) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #fff !important; border-radius: 15px !important; padding: 12px 20px !important; }
        .btn-save { background: linear-gradient(135deg, #00f2fe, #4facfe); border: none; color: #0c001c; font-weight: bold; border-radius: 30px; padding: 12px 40px; transition: 0.3s; }
        .btn-save:hover { transform: scale(1.05); box-shadow: 0 0 20px rgba(0, 242, 254, 0.6); }
        /* CSS ส่วนรุ่นย่อยที่เพิ่มเข้ามา */
        .variant-box { background: rgba(0, 242, 254, 0.05); border: 1px dashed rgba(0, 242, 254, 0.3); border-radius: 20px; padding: 20px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form method="POST" enctype="multipart/form-data">
                <div class="glass-card">
                    <h2 class="mb-4 d-flex align-items-center gap-3">
                        <i class="bi bi-pencil-square text-info"></i> แก้ไขสินค้า
                    </h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <img id="imgPreview" src="images/<?= $p['image'] ?>" class="img-fluid rounded-4 mb-3 border border-secondary" style="max-height: 300px; object-fit: cover;">
                                <input type="file" name="image" class="form-control" onchange="preview(this)">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label opacity-75">ชื่อสินค้า</label><input type="text" name="name" class="form-control" value="<?= $p['name'] ?>" required></div>
                                <div class="col-12"><label class="form-label opacity-75">รายละเอียด</label><textarea name="description" class="form-control" rows="4"><?= $p['description'] ?></textarea></div>
                                <div class="col-md-6"><label class="form-label opacity-75">ราคาหลัก (บาท)</label><input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" required></div>
                                <div class="col-md-6"><label class="form-label opacity-75">สต็อกรวม</label><input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required></div>
                                
                                <div class="col-12">
                                    <div class="variant-box">
                                        <h5 class="text-info mb-3"><i class="bi bi-layers"></i> จัดการรุ่นย่อย (Variants)</h5>
                                        <?php if($variants->num_rows > 0): ?>
                                            <?php while($v = $variants->fetch_assoc()): ?>
                                                <div class="row g-2 mb-2 align-items-end">
                                                    <input type="hidden" name="v_id[]" value="<?= $v['id'] ?>">
                                                    <div class="col-7">
                                                        <label class="small opacity-50">ชื่อรุ่น</label>
                                                        <input type="text" name="v_name[]" class="form-control form-control-sm" value="<?= $v['variant_name'] ?>">
                                                    </div>
                                                    <div class="col-5">
                                                        <label class="small opacity-50">สต็อกรุ่นนี้</label>
                                                        <input type="number" name="v_stock[]" class="form-control form-control-sm" value="<?= $v['stock'] ?>">
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p class="text-white-50 small">ไม่มีข้อมูลรุ่นย่อยสำหรับสินค้านี้</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-12 d-flex gap-3 my-3">
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
                                    <button type="submit" name="update_product" class="btn-save">บันทึกการแก้ไขทั้งหมด</button>
                                </div>
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