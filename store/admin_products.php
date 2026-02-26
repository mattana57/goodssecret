<?php
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description']);
    $is_v = $_POST['is_variant'];

    // 1. บันทึกรูปภาพหลัก
    $img_name = "default.png";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name);
    }

    // 2. บันทึกลงตาราง products
    $pr = ($is_v == 'no') ? $_POST['price'] : 0;
    $st = ($is_v == 'no') ? $_POST['stock'] : 0;
    $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$pr', '$st', '$cat_id', '$desc', '$img_name')");
    $main_id = $conn->insert_id;

    // 3. บันทึกรุ่นย่อยลงตาราง product_variants
    if($is_v == 'yes' && isset($_POST['v_names'])) {
        foreach($_POST['v_names'] as $i => $vname) {
            $vp = $_POST['v_prices'][$i]; $vs = $_POST['v_stocks'][$i]; $vimg = "";
            if (isset($_FILES['v_images']['name'][$i]) && $_FILES['v_images']['error'][$i] == 0) {
                $vimg = "v_" . time() . "_" . $i . "_" . basename($_FILES['v_images']['name'][$i]);
                move_uploaded_file($_FILES['v_images']['tmp_name'][$i], "images/" . $vimg);
            }
            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_image) VALUES ($main_id, '$vname', '$vp', $vs, '$vimg')");
        }
    }
    echo "<script>window.location='admin_dashboard.php?tab=products';</script>";
}

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
?>

<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4">
        <h4 class="text-white"><i class="bi bi-box-seam me-2"></i> สินค้า & สต็อก</h4>
        <button class="btn btn-neon-pink rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#pModal">+ เพิ่มสินค้าใหม่</button>
    </div>
    <table class="table table-hover datatable-js w-100">
        <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก</th><th>จัดการ</th></tr></thead>
        <tbody>
            <?php while($p = $products->fetch_assoc()): 
                $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
            ?>
            <tr class="align-middle text-white">
                <td><img src="images/<?= $p['image'] ?>" width="50" class="rounded"></td>
                <td class="fw-bold"><?= $p['name'] ?></td>
                <td class="text-neon-cyan">฿<?= number_format($p['price']) ?></td>
                <td>
                    <?php if($v_q->num_rows == 0): ?>
                        <input type="number" class="form-control form-control-sm" style="width:80px;" value="<?= $p['stock'] ?>" onchange="manualUpdateStockDirect(<?= $p['id'] ?>, this.value)">
                    <?php else: while($v = $v_q->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between small border-bottom border-secondary mb-1">
                            <span><?= $v['variant_name'] ?>:</span><span class="text-neon-cyan ms-3"><?= $v['stock'] ?></span>
                        </div>
                    <?php endwhile; endif; ?>
                </td>
                <td><button class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="pModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header border-secondary"><h5 class="text-neon-pink">จัดการสินค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label>ชื่อสินค้า</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-3"><label>ประเภท</label>
                        <select name="category_id" class="form-select">
                            <?php $cl=$conn->query("SELECT * FROM categories"); while($c=$cl->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label>มีรุ่นย่อย?</label><select name="is_variant" class="form-select" onchange="toggleVariantFields(this.value)"><option value="no">ไม่มี</option><option value="yes">มี</option></select></div>
                    <div id="no_v" class="row g-3 px-0 mx-0 mt-2"><div class="col-md-6"><label>ราคา</label><input type="number" name="price" class="form-control"></div><div class="col-md-6"><label>สต็อก</label><input type="number" name="stock" class="form-control"></div></div>
                    <div class="col-12 mt-3"><label>รายละเอียด</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="col-12 mt-3"><label>รูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
                    <div id="v_box" style="display:none;" class="col-12 mt-4 pt-3 border-top border-secondary">
                        <h6 class="text-neon-cyan mb-3">รายการรุ่นย่อยและรูปภาพ</h6>
                        <div id="v_container"></div>
                        <button type="button" class="btn btn-sm btn-outline-cyan rounded-pill" onclick="addVRow()">+ เพิ่มรุ่นย่อย</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary"><button type="submit" name="save_product" class="btn btn-neon-pink w-100 py-3 shadow">บันทึกข้อมูลเข้าคลัง</button></div>
        </form>
    </div>
</div>

<script>
    function addVRow() {
        $('#v_container').append(`<div class="variant-card mb-2 p-2 border border-secondary rounded shadow-sm">
            <div class="row g-2 align-items-end">
                <div class="col-md-3"><label class="small">รุ่น</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div>
                <div class="col-md-4"><label class="small">รูปเฉพาะรุ่น</label><input type="file" name="v_images[]" class="form-control form-control-sm"></div>
                <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-danger border-0" onclick="$(this).closest('.variant-card').remove()"><i class="bi bi-trash"></i></button></div>
            </div>
        </div>`);
    }
</script>