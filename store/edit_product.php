<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = ($id > 0);

if (isset($_POST['save_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $cat_id = intval($_POST['category_id']); 
    $has_variants = isset($_POST['has_variants']) ? 1 : 0;
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    $new_image_name = "";
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_image_name = "p_" . time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $new_image_name);
    }

    if ($is_edit) {
        $img_sql = $new_image_name ? ", image = '$new_image_name'" : "";
        $main_price = !$has_variants ? floatval($_POST['main_price']) : 0;
        $main_stock = !$has_variants ? intval($_POST['main_stock']) : 0;
        $conn->query("UPDATE products SET name='$name', description='$desc', category_id=$cat_id, price=$main_price, stock=$main_stock, is_hot=$is_hot, is_trending=$is_trending $img_sql WHERE id=$id");
    } else {
        $img_val = $new_image_name ? $new_image_name : "default.png";
        $main_price = !$has_variants ? floatval($_POST['main_price']) : 0;
        $main_stock = !$has_variants ? intval($_POST['main_stock']) : 0;
        $conn->query("INSERT INTO products (name, description, category_id, image, price, stock, is_hot, is_trending) VALUES ('$name', '$desc', $cat_id, '$img_val', $main_price, $main_stock, $is_hot, $is_trending)");
        $id = $conn->insert_id;
    }

    if ($has_variants && isset($_POST['v_name'])) {
        $conn->query("DELETE FROM product_variants WHERE product_id = $id");
        foreach ($_POST['v_name'] as $key => $v_name) {
            $v_p = floatval($_POST['v_price'][$key]);
            $v_s = intval($_POST['v_stock'][$key]);
            $v_d = mysqli_real_escape_string($conn, $_POST['v_desc'][$key]);
            
            $v_img = $_POST['v_img_old'][$key] ?? "";
            if (!empty($_FILES['v_image']['name'][$key])) {
                $v_ext = pathinfo($_FILES['v_image']['name'][$key], PATHINFO_EXTENSION);
                $v_new = "v_" . time() . "_" . $key . "." . $v_ext;
                if (move_uploaded_file($_FILES['v_image']['tmp_name'][$key], "images/" . $v_new)) { $v_img = $v_new; }
            }
            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_description, variant_image) VALUES ($id, '$v_name', $v_p, $v_s, '$v_d', '$v_img')");
        }
    }
    header("Location: admin_dashboard.php?tab=products"); exit();
}

$p = $is_edit ? $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc() : ['name'=>'','description'=>'','category_id'=>'','image'=>'','price'=>0,'stock'=>0,'is_hot'=>0,'is_trending'=>0];
$variants = $is_edit ? $conn->query("SELECT * FROM product_variants WHERE product_id=$id") : null;

$categories_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit ? 'แก้ไข' : 'เพิ่ม' ?>สินค้า | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* CSS ของคุณคงเดิมทั้งหมด 100% */
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 40px 0; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 35px; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.1) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #fff !important; border-radius: 12px; }
        option { background: #1a0028; color: #fff; }
        .variant-card { background: rgba(0, 242, 254, 0.05); border: 1px dashed #00f2fe; border-radius: 20px; padding: 20px; margin-bottom: 15px; position: relative; }
        .btn-remove { position: absolute; top: 10px; right: 10px; color: #ff4d4d; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-info"><i class="bi <?= $is_edit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i><?= $is_edit ? 'จัดการข้อมูลสินค้า' : 'เพิ่มสินค้าใหม่' ?></h2>
        <a href="admin_dashboard.php?tab=products" class="btn btn-outline-light rounded-pill px-4">ย้อนกลับ</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="glass-card mb-4">
            <h4 class="mb-4 text-info"><i class="bi bi-box-seam me-2"></i>ข้อมูลสินค้าหลัก</h4>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <img src="images/<?= $p['image'] ?: 'default.png' ?>" id="mainPreview" class="img-fluid rounded-4 mb-3 border border-secondary" style="max-height: 250px;">
                    <input type="file" name="image" class="form-control" onchange="previewMain(this)">
                </div>
                <div class="col-md-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="mb-2">ชื่อสินค้าหลัก</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="mb-2">ประเภทสินค้า</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php while($cat = $categories_res->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <label class="mb-2">รายละเอียดรวม</label>
                    <textarea name="description" class="form-control mb-3" rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
                    
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="has_variants" id="has_variants" onchange="toggleV()" <?= ($is_edit && $variants && $variants->num_rows > 0) ? 'checked' : '' ?>>
                        <label class="form-check-label text-info fw-bold">เปิดใช้งานรุ่นย่อย (Variants)</label>
                    </div>

                    <div id="main_stats" style="<?= ($is_edit && $variants && $variants->num_rows > 0) ? 'display:none;' : '' ?>">
                        <div class="row g-3">
                            <div class="col-6"><label>ราคาหลัก (฿)</label><input type="number" step="0.01" name="main_price" class="form-control" value="<?= $p['price'] ?>"></div>
                            <div class="col-6"><label>สต็อกหลัก (ชิ้น)</label><input type="number" name="main_stock" class="form-control" value="<?= $p['stock'] ?>"></div>
                        </div>
                    </div>

                    <div class="d-flex gap-4 mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_hot" id="is_hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-warning fw-bold">แนะนำ (Hot)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" <?= $p['is_trending'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-primary fw-bold">อินเทรนด์</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="v_section" class="glass-card" style="<?= ($is_edit && $variants && $variants->num_rows > 0) ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-warning"><i class="bi bi-layers-half me-2"></i>จัดการรุ่นย่อย (Variants)</h4>
                <button type="button" class="btn btn-outline-warning rounded-pill" onclick="addV()"><i class="bi bi-plus-lg me-2"></i>เพิ่มรุ่น</button>
            </div>
            <div id="v_container">
                <?php if($is_edit && $variants && $variants->num_rows > 0): ?>
                    <?php while($v = $variants->fetch_assoc()): ?>
                        <div class="variant-card">
                            <i class="bi bi-x-circle-fill btn-remove" onclick="this.parentElement.remove()"></i>
                            <input type="hidden" name="v_img_old[]" value="<?= $v['variant_image'] ?>">
                            <div class="row g-3">
                                <div class="col-md-2 text-center">
                                    <img src="images/<?= $v['variant_image'] ?>" class="img-fluid rounded" style="height: 80px; width: 80px; object-fit: cover;">
                                    <input type="file" name="v_image[]" class="form-control form-control-sm mt-2">
                                </div>
                                <div class="col-md-10"><div class="row g-2">
                                    <div class="col-md-6"><input type="text" name="v_name[]" class="form-control" placeholder="ชื่อรุ่น" value="<?= htmlspecialchars($v['variant_name']) ?>"></div>
                                    <div class="col-md-3"><input type="number" step="0.01" name="v_price[]" class="form-control" placeholder="ราคา" value="<?= $v['price'] ?>"></div>
                                    <div class="col-md-3"><input type="number" name="v_stock[]" class="form-control" placeholder="สต็อก" value="<?= $v['stock'] ?>"></div>
                                    <div class="col-12"><textarea name="v_desc[]" class="form-control" placeholder="รายละเอียดเฉพาะรุ่น" rows="1"><?= htmlspecialchars($v['variant_description']) ?></textarea></div>
                                </div></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-end mt-4">
            <button type="submit" name="save_product" class="btn btn-info rounded-pill px-5 fw-bold py-3 text-dark shadow-lg">บันทึกข้อมูลทั้งหมด</button>
        </div>
    </form>
</div>

<script>
function toggleV() {
    let checked = document.getElementById('has_variants').checked;
    document.getElementById('v_section').style.display = checked ? 'block' : 'none';
    document.getElementById('main_stats').style.display = checked ? 'none' : 'block';
}

function addV() {
    let html = `
    <div class="variant-card">
        <i class="bi bi-x-circle-fill btn-remove" onclick="this.parentElement.remove()"></i>
        <div class="row g-3">
            <div class="col-md-2 text-center"><input type="file" name="v_image[]" class="form-control form-control-sm"></div>
            <div class="col-md-10">
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="v_name[]" class="form-control" placeholder="ชื่อรุ่นย่อย"></div>
                    <div class="col-md-3"><input type="number" step="0.01" name="v_price[]" class="form-control" placeholder="ราคา"></div>
                    <div class="col-md-3"><input type="number" name="v_stock[]" class="form-control" placeholder="สต็อก"></div>
                    <div class="col-12"><textarea name="v_desc[]" class="form-control" placeholder="รายละเอียดรุ่น" rows="1"></textarea></div>
                </div>
            </div>
        </div>
    </div>`;
    document.getElementById('v_container').insertAdjacentHTML('beforeend', html);
}

function previewMain(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = (e) => document.getElementById('mainPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>