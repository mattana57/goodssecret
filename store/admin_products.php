<?php
// --- Logic การบันทึกสินค้า (รองรับทั้งเพิ่มและแก้ไข) ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description']);
    $is_variant = $_POST['is_variant'];
    $p_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
    
    // จัดการอัปโหลดรูปภาพหลัก
    $img_sql = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name)) {
            $img_sql = ", image='$img_name'";
        }
    }
    
    if ($p_id) {
        // กรณี "แก้ไข" สินค้าเดิม
        $pr = ($is_variant == 'no') ? $_POST['price'] : 0;
        $st = ($is_variant == 'no') ? intval($_POST['stock']) : 0;
        $conn->query("UPDATE products SET name='$name', price='$pr', stock='$st', category_id='$cat_id', description='$desc' $img_sql WHERE id=$p_id");
    } else {
        // กรณี "เพิ่ม" สินค้าใหม่
        $pr = ($is_variant == 'no') ? $_POST['price'] : 0;
        $st = ($is_variant == 'no') ? intval($_POST['stock']) : 0;
        $final_img = (isset($img_name)) ? $img_name : "default.png";
        $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$pr', '$st', '$cat_id', '$desc', '$final_img')");
    }
    echo "<script>window.location='admin_dashboard.php?tab=products&success=1';</script>";
}

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
?>

<style>
    .btn-edit-neon { border: 1px solid #ffc107; color: #ffc107; background: transparent; transition: 0.3s; }
    .btn-edit-neon:hover { background: #ffc107; color: #000; box-shadow: 0 0 15px #ffc107; }
    .btn-del-neon { border: 1px solid #ff4d4d; color: #ff4d4d; background: transparent; transition: 0.3s; }
    .btn-del-neon:hover { background: #ff4d4d; color: #fff; box-shadow: 0 0 15px #ff4d4d; }
    .text-white-bright { color: #ffffff !important; }
    .custom-confirm-modal .modal-content { background: rgba(26, 0, 40, 0.98); border: 2px solid #ff4d4d; border-radius: 30px; color: #fff; }
</style>

<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h4 class="text-white-bright fw-bold"><i class="bi bi-box-seam me-2"></i> สินค้า & สต็อก</h4>
        <button type="button" class="btn btn-neon-pink rounded-pill px-4 shadow-lg" onclick="openAddModal()">
            <i class="bi bi-plus-circle me-2"></i>เพิ่มสินค้าใหม่
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover w-100">
            <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก (รุ่นย่อย)</th><th class="text-center">จัดการ</th></tr></thead>
            <tbody>
                <?php while($p = $products->fetch_assoc()): 
                    $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
                ?>
                <tr class="align-middle">
                    <td><img src="images/<?= $p['image'] ?>" width="55" height="55" style="object-fit:cover; border-radius:12px; border: 2px solid rgba(0, 242, 254, 0.3);"></td>
                    <td class="fw-bold text-white-bright fs-5"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="text-neon-cyan fw-bold">฿<?= number_format($p['price']) ?></td>
                    <td>
                        <?php if($v_q->num_rows == 0): ?>
                            <span class="badge bg-secondary px-3"><?= $p['stock'] ?> ชิ้น</span>
                        <?php else: while($v = $v_q->fetch_assoc()): ?>
                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 mb-1 pb-1">
                                <span class="text-white-bright opacity-75 small"><?= $v['variant_name'] ?>:</span>
                                <span class="text-neon-cyan fw-bold ms-3"><?= $v['stock'] ?></span>
                            </div>
                        <?php endwhile; endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group gap-2">
                            <button type="button" class="btn btn-sm btn-edit-neon rounded-3 px-3" 
                                    onclick='openEditModal(<?= json_encode($p) ?>)'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-del-neon rounded-3 px-3" 
                                    onclick="askDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
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
            <div class="modal-header border-secondary">
                <h4 class="text-neon-purple fw-bold mb-0" id="modalTitle">จัดการสินค้า</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="product_id" id="p_id">
                <div class="row g-3">
                    <div class="col-md-6"><label class="small fw-bold mb-1 opacity-75">ชื่อสินค้า</label><input type="text" name="name" id="p_name" class="form-control text-white" required></div>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1 opacity-75">ประเภท</label>
                        <select name="category_id" id="p_cat" class="form-select text-white">
                            <?php $cl = $conn->query("SELECT * FROM categories"); while($c=$cl->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1 opacity-75">มีรุ่นย่อย?</label>
                        <select name="is_variant" id="v_select_field" class="form-select text-white" onchange="toggleVariantDisplay(this.value)">
                            <option value="no">ไม่มี</option><option value="yes">มี</option>
                        </select>
                    </div>
                    <div id="no_variant_row" class="row g-3 px-0 mx-0 mt-2">
                        <div class="col-md-6"><label class="small fw-bold mb-1">ราคาขาย</label><input type="number" name="price" id="p_price" class="form-control"></div>
                        <div class="col-md-6"><label class="small fw-bold mb-1">จำนวนสต็อก</label><input type="number" name="stock" id="p_stock" class="form-control"></div>
                    </div>
                    <div class="col-12 mt-3"><label class="small fw-bold mb-1 opacity-75">รายละเอียดสินค้า</label><textarea name="description" id="p_desc" class="form-control" rows="2"></textarea></div>
                    <div class="col-12 mt-3">
                        <label class="small fw-bold mb-1 opacity-75">รูปภาพหลัก</label>
                        <div id="currentImgBox" class="mb-2" style="display:none;">
                            <small class="d-block opacity-50 mb-1">รูปปัจจุบัน:</small>
                            <img id="p_img_preview" src="" width="80" class="rounded border border-secondary">
                        </div>
                        <input type="file" name="image" class="form-control text-white">
                    </div>
                    
                    <div id="variant_section_box" style="display:none;" class="col-12 mt-4 pt-3 border-top border-secondary border-opacity-50">
                        <h6 class="text-neon-cyan fw-bold mb-3">รายการรุ่นย่อยใหม่</h6>
                        <div id="variant_list_container"></div>
                        <button type="button" class="btn btn-sm btn-outline-cyan rounded-pill mt-2" onclick="addVariantRowAction()">+ เพิ่มแถวรุ่นย่อย</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" name="save_product" class="btn btn-neon-pink w-100 py-3 fw-bold fs-5 shadow-lg">บันทึกข้อมูลสินค้า</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade custom-confirm-modal" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center py-4">
            <div class="modal-body">
                <i class="bi bi-exclamation-triangle text-danger display-1 mb-4"></i>
                <h3 class="fw-bold mb-3 text-white-bright">ยืนยันการลบ?</h3>
                <p class="opacity-75 mb-4 px-3 text-white-bright">สินค้า <span id="delProdName" class="text-danger fw-bold"></span> จะหายไปถาวร</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" id="finalDeleteBtn" class="btn btn-danger px-4 rounded-pill shadow">ยืนยันลบ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const myModal = new bootstrap.Modal(document.getElementById('pModalFull'));

    function openAddModal() {
        document.getElementById('modalTitle').innerText = "เพิ่มสินค้าใหม่";
        document.getElementById('p_id').value = "";
        document.getElementById('currentImgBox').style.display = "none";
        document.querySelector('form').reset();
        toggleVariantDisplay('no');
        myModal.show();
    }

    function openEditModal(p) {
        document.getElementById('modalTitle').innerText = "แก้ไขข้อมูล: " + p.name;
        document.getElementById('p_id').value = p.id;
        document.getElementById('p_name').value = p.name;
        document.getElementById('p_cat').value = p.category_id;
        document.getElementById('p_price').value = p.price;
        document.getElementById('p_stock').value = p.stock;
        document.getElementById('p_desc').value = p.description;
        
        // แสดงรูปภาพปัจจุบัน
        document.getElementById('p_img_preview').src = "images/" + p.image;
        document.getElementById('currentImgBox').style.display = "block";
        
        // ล็อคไม่ให้แก้สวิตช์รุ่นย่อยตอนแก้ไข (ป้องกันข้อมูลพัง)
        document.getElementById('v_select_field').value = 'no'; 
        toggleVariantDisplay('no');
        
        myModal.show();
    }

    function toggleVariantDisplay(val) {
        document.getElementById('variant_section_box').style.display = (val === 'yes') ? 'block' : 'none';
        document.getElementById('no_variant_row').style.display = (val === 'yes') ? 'none' : 'flex';
    }

    function addVariantRowAction() {
        const container = document.getElementById('variant_list_container');
        const div = document.createElement('div');
        div.className = 'variant-card mb-3 p-3 border border-secondary border-opacity-25 rounded-4 shadow-sm';
        div.innerHTML = `<div class="row g-2 align-items-end">
                <div class="col-md-3"><label class="small fw-bold">ชื่อรุ่น</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small fw-bold">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small fw-bold">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div>
                <div class="col-md-4"><label class="small fw-bold">รูปภาพ</label><input type="file" name="v_images[]" class="form-control form-control-sm"></div>
                <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-danger border-0" onclick="this.closest('.variant-card').remove()"><i class="bi bi-trash-fill"></i></button></div>
            </div>`;
        container.appendChild(div);
    }

    function askDelete(pid, pname) {
        document.getElementById('delProdName').innerText = pname;
        document.getElementById('finalDeleteBtn').href = 'admin_dashboard.php?tab=products&del_id=' + pid + '&type=product';
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }
</script>