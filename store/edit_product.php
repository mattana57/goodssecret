<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

$id = intval($_GET['id']);

if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $has_variants = isset($_POST['has_variants']) ? 1 : 0;
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    // อัปเดตรูปหลักสินค้า
    $img_sql = "";
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_name = "main_" . time() . "_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $new_name)) {
            $img_sql = ", image = '$new_name'";
        }
    }

    // กรณีไม่มีรุ่นย่อย ให้เอาราคา/สต็อกหลักมาใช้
    $main_price = !$has_variants ? floatval($_POST['main_price']) : 0;
    $main_stock = !$has_variants ? intval($_POST['main_stock']) : 0;

    $conn->query("UPDATE products SET name='$name', description='$desc', price=$main_price, stock=$main_stock, is_hot=$is_hot, is_trending=$is_trending $img_sql WHERE id=$id");

    // จัดการรุ่นย่อย (ลบของเก่าแล้วเพิ่มใหม่เพื่อให้รองรับการเพิ่ม/ลดแถวได้อิสระ)
    if ($has_variants && isset($_POST['v_name'])) {
        $conn->query("DELETE FROM product_variants WHERE product_id = $id");
        foreach ($_POST['v_name'] as $key => $v_name) {
            $v_name = mysqli_real_escape_string($conn, $v_name);
            $v_price = floatval($_POST['v_price'][$key]);
            $v_stock = intval($_POST['v_stock'][$key]);
            $v_desc = mysqli_real_escape_string($conn, $_POST['v_desc'][$key]);
            
            $v_img = $_POST['v_img_old'][$key] ?? "";
            if (!empty($_FILES['v_image']['name'][$key])) {
                $ext = pathinfo($_FILES['v_image']['name'][$key], PATHINFO_EXTENSION);
                $v_new = "v_" . time() . "_" . $key . "." . $ext;
                if (move_uploaded_file($_FILES['v_image']['tmp_name'][$key], "images/" . $v_new)) {
                    $v_img = $v_new;
                }
            }

            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_description, variant_image) 
                          VALUES ($id, '$v_name', $v_price, $v_stock, '$v_desc', '$v_img')");
        }
    }
    echo "<script>alert('บันทึกสำเร็จ!'); window.location='admin_dashboard.php';</script>";
}

$p = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
$variants = $conn->query("SELECT * FROM product_variants WHERE product_id=$id");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสินค้าหลัก & รุ่นย่อย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 40px 0; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 30px; padding: 30px; }
        .form-control { background: rgba(255, 255, 255, 0.1) !important; color: #fff !important; border: 1px solid #444 !important; border-radius: 12px; }
        .variant-card { background: rgba(0, 242, 254, 0.05); border: 1px dashed #00f2fe; border-radius: 20px; padding: 20px; margin-bottom: 15px; position: relative; }
        .btn-remove { position: absolute; top: 10px; right: 10px; color: #ff4d4d; cursor: pointer; font-size: 1.5rem; transition: 0.3s; }
        .btn-remove:hover { transform: scale(1.2); }
        .btn-back { border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 50px; padding: 10px 25px; transition: 0.3s; }
        .btn-back:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-info"><i class="bi bi-pencil-square me-2"></i>จัดการสินค้า</h2>
        <a href="admin_dashboard.php" class="btn btn-back"><i class="bi bi-arrow-left me-2"></i>กลับหน้าแดชบอร์ด</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="glass-card mb-4">
            <h4 class="mb-4 text-info"><i class="bi bi-box-seam me-2"></i>ข้อมูลสินค้าหลัก</h4>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <img src="images/<?= $p['image'] ?>" id="mainPreview" class="img-fluid rounded-4 mb-3 border border-secondary" style="max-height: 250px;">
                    <input type="file" name="image" class="form-control" onchange="previewMain(this)">
                </div>
                <div class="col-md-8">
                    <label class="mb-2">ชื่อสินค้า</label>
                    <input type="text" name="name" class="form-control mb-3" value="<?= htmlspecialchars($p['name']) ?>">
                    <label class="mb-2">รายละเอียด</label>
                    <textarea name="description" class="form-control mb-3" rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
                    
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="has_variants" id="has_variants" onchange="toggleVariantUI()" <?= ($variants->num_rows > 0) ? 'checked' : '' ?>>
                        <label class="form-check-label text-info fw-bold" for="has_variants">เปิดใช้งานรุ่นย่อย (Variants)</label>
                    </div>

                    <div id="main_stats" style="<?= ($variants->num_rows > 0) ? 'display:none;' : '' ?>">
                        <div class="row">
                            <div class="col-6"><label class="mb-2">ราคาหลัก (บาท)</label><input type="number" name="main_price" class="form-control" value="<?= $p['price'] ?>"></div>
                            <div class="col-6"><label class="mb-2">สต็อกหลัก (ชิ้น)</label><input type="number" name="main_stock" class="form-control" value="<?= $p['stock'] ?>"></div>
                        </div>
                    </div>

                    <div class="d-flex gap-4 mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_hot" id="is_hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-warning fw-bold" for="is_hot">สินค้าแนะนำ (Hot)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" <?= $p['is_trending'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-primary fw-bold" for="is_trending">สินค้าอินเทรนด์</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="variant_section" class="glass-card" style="<?= ($variants->num_rows > 0) ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-warning"><i class="bi bi-layers-half me-2"></i>รายละเอียดรุ่นย่อย (Variants)</h4>
                <button type="button" class="btn btn-outline-warning rounded-pill" onclick="addVariant()"><i class="bi bi-plus-lg me-2"></i>เพิ่มรุ่นย่อย</button>
            </div>

            <div id="variant_container">
                <?php while($v = $variants->fetch_assoc()): ?>
                    <div class="variant-card">
                        <i class="bi bi-x-circle-fill btn-remove" title="ลบรุ่นนี้" onclick="this.parentElement.remove()"></i>
                        <input type="hidden" name="v_img_old[]" value="<?= $v['variant_image'] ?>">
                        <div class="row g-3">
                            <div class="col-md-2 text-center">
                                <img src="images/<?= $v['variant_image'] ?>" class="img-fluid rounded mb-2" style="height: 80px; width: 80px; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                                <input type="file" name="v_image[]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-10">
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" name="v_name[]" class="form-control" placeholder="ชื่อรุ่น (เช่น รุ่นติดเกาะ)" value="<?= htmlspecialchars($v['variant_name']) ?>"></div>
                                    <div class="col-md-3"><input type="number" name="v_price[]" class="form-control" placeholder="ราคา" value="<?= $v['price'] ?>"></div>
                                    <div class="col-md-3"><input type="number" name="v_stock[]" class="form-control" placeholder="สต็อก" value="<?= $v['stock'] ?>"></div>
                                    <div class="col-12"><textarea name="v_desc[]" class="form-control" placeholder="รายละเอียดรุ่นย่อยเฉพาะ" rows="1"><?= htmlspecialchars($v['variant_description']) ?></textarea></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="text-end mt-5">
            <button type="submit" name="update_product" class="btn btn-info rounded-pill px-5 fw-bold py-3 text-dark shadow-lg">บันทึกการแก้ไขทั้งหมด</button>
        </div>
    </form>
</div>

<script>
function toggleVariantUI() {
    let checked = document.getElementById('has_variants').checked;
    document.getElementById('variant_section').style.display = checked ? 'block' : 'none';
    document.getElementById('main_stats').style.display = checked ? 'none' : 'block';
}

function addVariant() {
    let html = `
    <div class="variant-card">
        <i class="bi bi-x-circle-fill btn-remove" title="ลบรุ่นนี้" onclick="this.parentElement.remove()"></i>
        <div class="row g-3">
            <div class="col-md-2 text-center"><input type="file" name="v_image[]" class="form-control form-control-sm"></div>
            <div class="col-md-10">
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="v_name[]" class="form-control" placeholder="ชื่อรุ่นย่อย"></div>
                    <div class="col-md-3"><input type="number" name="v_price[]" class="form-control" placeholder="ราคา"></div>
                    <div class="col-md-3"><input type="number" name="v_stock[]" class="form-control" placeholder="สต็อก"></div>
                    <div class="col-12"><textarea name="v_desc[]" class="form-control" placeholder="รายละเอียดรุ่นย่อยเฉพาะ" rows="1"></textarea></div>
                </div>
            </div>
        </div>
    </div>`;
    document.getElementById('variant_container').insertAdjacentHTML('beforeend', html);
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