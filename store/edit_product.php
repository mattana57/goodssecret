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

// Logic การอัปเดตข้อมูล (คงเดิมทั้งหมด + เพิ่ม Loop อัปเดตรุ่นย่อย)
if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    // 1. อัปเดตข้อมูลสินค้าหลัก
    $sql = "UPDATE products SET name = '$name', description = '$desc', is_hot = $is_hot, is_trending = $is_trending WHERE id = $id";
    $conn->query($sql);

    // 2. [ส่วนที่ปรับเพิ่ม]: อัปเดตรุ่นย่อย (Variants) ครบทุกฟิลด์ (รูป, ราคา, สต็อก, คำอธิบาย)
    if (isset($_POST['v_id'])) {
        foreach ($_POST['v_id'] as $key => $v_id) {
            $v_name = mysqli_real_escape_string($conn, $_POST['v_name'][$key]);
            $v_price = floatval($_POST['v_price'][$key]);
            $v_stock = intval($_POST['v_stock'][$key]);
            $v_desc = mysqli_real_escape_string($conn, $_POST['v_desc'][$key]);

            // จัดการรูปภาพแยกตามรุ่นย่อย
            $v_img_query = "";
            if (!empty($_FILES['v_image']['name'][$key])) {
                $file_ext = strtolower(pathinfo($_FILES['v_image']['name'][$key], PATHINFO_EXTENSION));
                $v_new_name = "v_" . time() . "_" . $key . "." . $file_ext;
                if (move_uploaded_file($_FILES['v_image']['tmp_name'][$key], "images/" . $v_new_name)) {
                    $v_img_query = ", variant_image = '$v_new_name'";
                }
            }

            $conn->query("UPDATE product_variants SET 
                variant_name = '$v_name', 
                price = $v_price, 
                stock = $v_stock, 
                variant_description = '$v_desc' 
                $v_img_query 
                WHERE id = " . intval($v_id));
        }
    }
    echo "<script>alert('บันทึกการแก้ไขทั้งหมดสำเร็จ!'); window.location='admin_dashboard.php?tab=products';</script>";
    exit();
}

$p = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = $id")->fetch_assoc();
$variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า: <?= $p['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* CSS เดิมของมึง 100% ห้ามลบ */
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 50px 0; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 40px; }
        .text-neon { color: #00f2fe; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .form-control { background: rgba(255, 255, 255, 0.1) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #fff !important; border-radius: 15px; }
        /* ส่วนที่เพิ่มเพื่อความสวยงามของ Variants */
        .variant-item { background: rgba(0, 242, 254, 0.05); border: 1px dashed rgba(0, 242, 254, 0.3); border-radius: 20px; padding: 25px; margin-bottom: 20px; }
        .btn-save { background: linear-gradient(135deg, #00f2fe, #bb86fc); border: none; color: #120018; font-weight: bold; border-radius: 30px; padding: 12px 40px; }
    </style>
</head>
<body>
<div class="container">
    <div class="glass-card shadow-lg">
        <h2 class="text-neon mb-5"><i class="bi bi-pencil-square me-3"></i>จัดการสินค้าหลัก & รุ่นย่อย</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4 mb-5">
                <div class="col-12">
                    <label class="form-label opacity-75">ชื่อกลุ่มสินค้าหลัก</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label opacity-75">รายละเอียดสินค้าหลัก</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
                </div>
            </div>

            <h4 class="text-warning mb-4"><i class="bi bi-layers-half me-2"></i>รายละเอียดรุ่นย่อย (Variants)</h4>
            <?php if ($variants->num_rows > 0): ?>
                <?php while($v = $variants->fetch_assoc()): ?>
                    <div class="variant-item">
                        <input type="hidden" name="v_id[]" value="<?= $v['id'] ?>">
                        <div class="row g-4">
                            <div class="col-md-3 text-center">
                                <label class="small d-block mb-2 opacity-50">รูปเฉพาะรุ่น</label>
                                <img src="images/<?= $v['variant_image'] ?>" class="img-fluid rounded-3 mb-2 border border-secondary" style="height: 140px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/150'">
                                <input type="file" name="v_image[]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="small opacity-50">ชื่อรุ่นย่อย</label>
                                        <input type="text" name="v_name[]" class="form-control" value="<?= htmlspecialchars($v['variant_name']) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small opacity-50">ราคา (฿)</label>
                                        <input type="number" step="0.01" name="v_price[]" class="form-control" value="<?= $v['price'] ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small opacity-50">สต็อก (ชิ้น)</label>
                                        <input type="number" name="v_stock[]" class="form-control" value="<?= $v['stock'] ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="small opacity-50">รายละเอียดรุ่นนี้</label>
                                        <textarea name="v_desc[]" class="form-control" rows="2"><?= htmlspecialchars($v['variant_description']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4 opacity-50 border border-secondary rounded-4">ยังไม่มีรุ่นย่อยสำหรับสินค้านี้</div>
            <?php endif; ?>

            <div class="col-12 d-flex gap-4 my-5">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_hot" id="hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                    <label class="form-check-label text-warning" for="hot">สินค้าแนะนำ (Hot)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_trending" id="trend" <?= $p['is_trending'] ? 'checked' : '' ?>>
                    <label class="form-check-label text-info" for="trend">สินค้าอินเทรนด์</label>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3">
                <a href="admin_dashboard.php?tab=products" class="btn btn-outline-light rounded-pill px-4">ยกเลิก</a>
                <button type="submit" name="update_product" class="btn-save">บันทึกการแก้ไขทั้งหมด</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>