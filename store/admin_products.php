<?php
// Logic การบันทึกสินค้า (ห้ามยุ่งส่วนอื่นเด็ดขาด)
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description']);
    $is_variant = $_POST['is_variant'];
    
    $img_name = "default.png";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name);
    }
    
    $pr = ($is_variant == 'no') ? $_POST['price'] : 0;
    $st = ($is_variant == 'no') ? $_POST['stock'] : 0;
    
    $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$pr', '$st', '$cat_id', '$desc', '$img_name')");
    $target_p_id = $conn->insert_id;

    if($is_variant == 'yes' && isset($_POST['v_names'])) {
        foreach($_POST['v_names'] as $i => $vname) {
            $vprice = $_POST['v_prices'][$i]; $vstock = $_POST['v_stocks'][$i]; $vimg = "";
            if (isset($_FILES['v_images']['name'][$i]) && $_FILES['v_images']['error'][$i] == 0) {
                $vimg = "v_" . time() . "_" . $i . "_" . basename($_FILES['v_images']['name'][$i]);
                move_uploaded_file($_FILES['v_images']['tmp_name'][$i], "images/" . $vimg);
            }
            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_image) VALUES ($target_p_id, '$vname', '$vprice', $vstock, '$vimg')");
        }
    }
    echo "<script>window.location='admin_dashboard.php?tab=products&success=1';</script>";
}

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
?>

<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h4 class="text-white fw-bold"><i class="bi bi-box-seam me-2"></i> สินค้า & สต็อก</h4>
        <button class="btn btn-neon-pink rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#pModal">+ เพิ่มสินค้าใหม่</button>
    </div>
    
    <table class="table table-hover datatable-js w-100">
        <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก (รุ่นย่อย)</th><th>จัดการ</th></tr></thead>
        <tbody>
            <?php while($p = $products->fetch_assoc()): 
                $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
            ?>
            <tr class="align-middle">
                <td><img src="images/<?= $p['image'] ?>" width="55" height="55" style="object-fit:cover; border-radius:12px; border: 2px solid rgba(0, 242, 254, 0.3);"></td>
                <td class="fw-bold text-white fs-5"><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-neon-cyan fw-bold fs-5">฿<?= number_format($p['price']) ?></td>
                <td>
                    <?php if($v_q->num_rows == 0): ?>
                        <span class="badge bg-secondary px-3 fs-6"><?= $p['stock'] ?> ชิ้น</span>
                    <?php else: while($v = $v_q->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 mb-1 pb-1">
                            <span class="text-white"><?= $v['variant_name'] ?>:</span>
                            <span class="text-neon-cyan fw-bold fs-6 ms-4"><?= $v['stock'] ?></span>
                        </div>
                    <?php endwhile; endif; ?>
                </td>
                <td>
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="pModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" style="background: #1a0028; border: 2px solid #bb86fc; color: #ffffff;" method="POST" enctype="multipart/form-data">
            <div class="modal-header border-secondary">
                <h4 class="text-neon-purple fw-bold mb-0">จัดการสินค้า</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold mb-1">ชื่อสินค้า</label>
                        <input type="text" name="name" class="form-control text-white" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">ประเภท</label>
                        <select name="category_id" class="form-select text-white">
                            <?php $cl = $conn->query("SELECT * FROM categories"); while($c=$cl->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">มีรุ่นย่อย?</label>
                        <select name="is_variant" class="form-select text-white" onchange="$('#v_box').toggle(this.value==='yes'); $('#no_v').toggle(this.value==='no')">
                            <option value="no">ไม่มี</option><option value="yes">มี</option>
                        </select>
                    </div>
                    
                    <div id="no_v" class="row g-3 px-0 mx-0 mt-2">
                        <div class="col-md-6"><label class="small fw-bold mb-1">ราคา</label><input type="number" name="price" class="form-control"></div>
                        <div class="col-md-6"><label class="small fw-bold mb-1">สต็อกรวม</label><input type="number" name="stock" class="form-control"></div>
                    </div>
                    
                    <div class="col-12 mt-3"><label class="small fw-bold mb-1">รายละเอียด</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="col-12 mt-3"><label class="small fw-bold mb-1">รูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
                    
                    <div id="v_box" style="display:none;" class="col-12 mt-4 pt-3 border-top border-secondary">
                        <h6 class="text-neon-cyan fw-bold mb-3"><i class="bi bi-layers"></i> รายการรุ่นย่อยและรูปภาพ</h6>
                        <div id="v_container"></div>
                        <button type="button" class="btn btn-sm btn-outline-cyan rounded-pill mt-2" onclick="addVRow()">+ เพิ่มแถวรุ่นย่อย</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" name="save_product" class="btn btn-neon-pink w-100 py-3 fw-bold fs-5 shadow">บันทึกข้อมูลเข้าคลัง</button>
            </div>
        </form>
    </div>
</div>

<script>
    function addVRow() {
        $('#v_container').append(`<div class="variant-card mb-3 p-3 border border-secondary border-opacity-50 rounded shadow-sm" style="background: rgba(255,255,255,0.02);">
            <div class="row g-2 align-items-end">
                <div class="col-md-3"><label class="small fw-bold">ชื่อรุ่น</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small fw-bold">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small fw-bold">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div>
                <div class="col-md-4"><label class="small fw-bold">รูปประจำรุ่น</label><input type="file" name="v_images[]" class="form-control form-control-sm"></div>
                <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-danger border-0" onclick="$(this).closest('.variant-card').remove()"><i class="bi bi-trash"></i></button></div>
            </div>
        </div>`);
    }
</script>