<?php
// --- [Logic 1]: การบันทึกข้อมูลสินค้าและรุ่นย่อย ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description'] ?? '');
    $is_variant = $_POST['is_variant'];
    
    // จัดการรูปภาพหลัก
    $img_name = "default.png";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name);
    }
    
    // ถ้าไม่มีรุ่นย่อย ให้ดึงค่าราคาและสต็อกหลัก
    $pr = ($is_variant == 'no') ? $_POST['price'] : 0;
    $st = ($is_variant == 'no') ? $_POST['stock'] : 0;
    
    $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$pr', '$st', '$cat_id', '$desc', '$img_name')");
    $target_p_id = $conn->insert_id;

    // ถ้าเลือก "มีรุ่นย่อย" ให้วนลูปบันทึกข้อมูลรุ่นย่อย
    if($is_variant == 'yes' && isset($_POST['v_names'])) {
        foreach($_POST['v_names'] as $i => $vname) {
            $vprice = $_POST['v_prices'][$i]; 
            $vstock = $_POST['v_stocks'][$i]; 
            $vimg = "";
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

<style>
    /* ปรับแต่ง UI ให้มีความสว่างและนีออน */
    .text-white-bright { color: #ffffff !important; text-shadow: 0 0 5px rgba(255,255,255,0.2); }
    .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.6); }
    .text-neon-purple { color: #bb86fc !important; text-shadow: 0 0 10px rgba(187, 134, 252, 0.6); }
    
    .table thead th { color: #00f2fe !important; font-weight: bold; border-bottom: 2px solid rgba(0, 242, 254, 0.3); }
    .table tbody td { color: #ffffff !important; border-bottom: 1px solid rgba(255,255,255,0.05); }

    .btn-edit-neon { border: 1px solid #ffc107; color: #ffc107 !important; background: transparent; transition: 0.3s; }
    .btn-edit-neon:hover { background: #ffc107; color: #000 !important; box-shadow: 0 0 10px #ffc107; }
    
    .btn-del-neon { border: 1px solid #ff4d4d; color: #ff4d4d !important; background: transparent; transition: 0.3s; }
    .btn-del-neon:hover { background: #ff4d4d; color: #fff !important; box-shadow: 0 0 10px #ff4d4d; }

    .form-control, .form-select { background: rgba(255,255,255,0.1) !important; color: #ffffff !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; }
</style>

<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h4 class="text-white-bright fw-bold mb-0"><i class="bi bi-box-seam me-2 text-neon-cyan"></i> สินค้า & สต็อก</h4>
        <button type="button" class="btn btn-neon-pink rounded-pill px-4 shadow-lg fw-bold" data-bs-toggle="modal" data-bs-target="#pModalFull">
            <i class="bi bi-plus-circle me-2"></i>เพิ่มสินค้าใหม่
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover w-100">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>สต็อก (รุ่นย่อย)</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $products->fetch_assoc()): 
                    $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
                ?>
                <tr class="align-middle">
                    <td><img src="images/<?= $p['image'] ?>" width="60" height="60" style="object-fit:cover; border-radius:12px; border: 2px solid #00f2fe;"></td>
                    <td>
                        <span class="fw-bold text-white-bright fs-5 d-block"><?= htmlspecialchars($p['name']) ?></span>
                        <small class="text-white-50">หมวดหมู่: <?= htmlspecialchars($p['cat_name']) ?></small>
                    </td>
                    <td class="text-neon-cyan fw-bold fs-5">฿<?= number_format($p['price']) ?></td>
                    <td>
                        <?php if($v_q->num_rows == 0): ?>
                            <span class="badge bg-dark border border-secondary text-white-bright px-3"><?= $p['stock'] ?> ชิ้น</span>
                        <?php else: while($v = $v_q->fetch_assoc()): ?>
                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 mb-1 pb-1">
                                <span class="text-white-bright opacity-90 small"><?= $v['variant_name'] ?>:</span>
                                <span class="text-neon-cyan fw-bold ms-3"><?= $v['stock'] ?></span>
                            </div>
                        <?php endwhile; endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group gap-2">
                            <button type="button" class="btn btn-sm btn-edit-neon rounded-3 px-3"><i class="bi bi-pencil-square"></i></button>
                            <button type="button" class="btn btn-sm btn-del-neon rounded-3 px-3" onclick="askDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="pModalFull" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" style="background: #1a0028; border: 2px solid #bb86fc; color: #ffffff;" method="POST" enctype="multipart/form-data">
            <div class="modal-header border-secondary px-4">
                <h4 class="text-neon-purple fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>รายละเอียดคลังสินค้า</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-white-bright">
                <div class="row g-3">
                    <div class="col-md-6"><label class="small fw-bold mb-2">ชื่อสินค้า</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-3"><label class="small fw-bold mb-2">ประเภท</label>
                        <select name="category_id" class="form-select">
                            <?php $cl = $conn->query("SELECT * FROM categories"); while($c=$cl->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="small fw-bold mb-2">มีรุ่นย่อย?</label>
                        <select name="is_variant" id="v_select_field" class="form-select" onchange="toggleVariantDisplay(this.value)">
                            <option value="no">ไม่มี</option><option value="yes">มี</option>
                        </select>
                    </div>
                    <div id="no_variant_row" class="row g-3 px-0 mx-0 mt-2">
                        <div class="col-md-6"><label class="small fw-bold mb-2">ราคาขาย (฿)</label><input type="number" name="price" class="form-control"></div>
                        <div class="col-md-6"><label class="small fw-bold mb-2">จำนวนสต็อก</label><input type="number" name="stock" class="form-control"></div>
                    </div>
                    <div class="col-12 mt-3"><label class="small fw-bold mb-2">อัปโหลดรูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
                    
                    <div id="variant_section_box" style="display:none;" class="col-12 mt-4 pt-3 border-top border-secondary">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-neon-cyan fw-bold mb-0">รายการรุ่นย่อยและรูปภาพ</h6>
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="addVariantRowAction()">+ เพิ่มแถว</button>
                        </div>
                        <div id="variant_list_container"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4 pb-4">
                <button type="submit" name="save_product" class="btn btn-neon-pink w-100 py-3 shadow-lg fw-bold fs-5">บันทึกข้อมูลเข้าคลัง</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center py-4 shadow-lg border-danger">
            <div class="modal-body">
                <i class="bi bi-exclamation-triangle text-danger display-1 mb-4"></i>
                <h3 class="fw-bold mb-3 text-white-bright">ยืนยันการลบสินค้า?</h3>
                <p class="text-white-bright opacity-90 mb-4 fs-5">ลบ <span id="delProdName" class="text-danger fw-bold"></span>?<br>ข้อมูลสต็อกจะหายไปถาวร</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" id="finalDeleteBtn" class="btn btn-danger px-4 rounded-pill fw-bold">ยืนยันลบ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// --- [JavaScript]: ควบคุมฟังก์ชันรุ่นย่อยและการลบ ---

function toggleVariantDisplay(val) {
    if (val === 'yes') {
        $('#variant_section_box').slideDown(); 
        $('#no_variant_row').slideUp();       
    } else {
        $('#variant_section_box').slideUp();   
        $('#no_variant_row').slideDown();     
    }
}

function addVariantRowAction() {
    const html = `
        <div class="variant-item-row bg-black bg-opacity-25 p-3 rounded-3 mb-3 border border-secondary shadow-sm">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="small text-white-bright mb-1">ชื่อลาย/รุ่น</label>
                    <input type="text" name="v_names[]" class="form-control" placeholder="เช่น ลายปลาห้อย" required>
                </div>
                <div class="col-md-3">
                    <label class="small text-white-bright mb-1">ราคา (฿)</label>
                    <input type="number" name="v_prices[]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="small text-white-bright mb-1">สต็อก</label>
                    <input type="number" name="v_stocks[]" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="small text-white-bright mb-1">รูปเฉพาะรุ่น</label>
                    <input type="file" name="v_images[]" class="form-control">
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="$(this).closest('.variant-item-row').remove()">ลบแถวนี้</button>
                </div>
            </div>
        </div>`;
    $('#variant_list_container').append(html);
}

function askDelete(id, name) {
    $('#delProdName').text(name);
    $('#finalDeleteBtn').attr('href', 'admin_dashboard.php?tab=products&del_id=' + id + '&type=product');
    new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
}
</script>