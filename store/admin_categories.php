<?php
// --- [Logic 1]: เพิ่มประเภทสินค้าใหม่ ---
if (isset($_POST['save_cat'])) {
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("INSERT INTO categories (name, slug) VALUES ('$n', '".strtolower($n)."')");
    echo "<script>window.location='admin_dashboard.php?tab=categories&success=1';</script>";
}

// --- [Logic 2]: อัปเดตข้อมูลประเภทสินค้า ---
if (isset($_POST['update_cat'])) {
    $id = intval($_POST['cat_id']);
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("UPDATE categories SET name = '$n', slug = '".strtolower($n)."' WHERE id = $id");
    echo "<script>window.location='admin_dashboard.php?tab=categories&updated=1';</script>";
}

$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<style>
    /* ปรับแต่งสไตล์นีออนเฉพาะหน้าจัดการประเภท */
    .btn-neon-cyan { background: transparent; border: 1px solid #00f2fe; color: #00f2fe; transition: 0.3s; }
    .btn-neon-cyan:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px #00f2fe; }
    
    .btn-neon-yellow { background: transparent; border: 1px solid #ffc107; color: #ffc107; transition: 0.3s; }
    .btn-neon-yellow:hover { background: #ffc107; color: #000; box-shadow: 0 0 15px #ffc107; }
    
    .btn-neon-red { background: transparent; border: 1px solid #ff4d4d; color: #ff4d4d; transition: 0.3s; }
    .btn-neon-red:hover { background: #ff4d4d; color: #fff; box-shadow: 0 0 15px #ff4d4d; }

    .glass-panel { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 25px; padding: 30px; }
    .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
</style>

<div class="glass-panel mt-2">
    <div class="d-flex justify-content-between mb-4 align-items-center flex-wrap gap-3">
        <h4 class="mb-0 text-white fw-bold"><i class="bi bi-tags-fill me-2 text-neon-cyan"></i> จัดการประเภทสินค้า</h4>
        
        <form method="POST" class="d-flex gap-2">
            <input type="text" name="cat_name" class="form-control rounded-pill px-4" placeholder="ชื่อประเภทใหม่" required>
            <button type="submit" name="save_cat" class="btn btn-neon-cyan rounded-pill px-4 fw-bold">เพิ่ม</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table text-white w-100 datatable-js">
            <thead>
                <tr class="text-neon-cyan border-bottom border-secondary border-opacity-50">
                    <th style="width: 15%;">ID</th>
                    <th style="width: 55%;">ชื่อประเภทสินค้า</th>
                    <th style="width: 30%;" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $cats->fetch_assoc()): ?>
                <tr class="align-middle border-bottom border-white border-opacity-10">
                    <td class="text-white-50">#<?= $c['id'] ?></td>
                    <td class="fw-bold fs-5"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-neon-yellow rounded-pill px-3 py-1" 
                                    onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                                <i class="bi bi-pencil-square me-1"></i> แก้ไข
                            </button>
                            
                            <button class="btn btn-sm btn-neon-red rounded-pill px-3 py-1" 
                                    onclick="if(confirm('คุณต้องการลบประเภทสินค้า [<?= htmlspecialchars($c['name']) ?>] ใช่หรือไม่?')) window.location='admin_dashboard.php?tab=categories&del_id=<?= $c['id'] ?>&type=category'">
                                <i class="bi bi-trash me-1"></i> ลบ
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content shadow-lg border-warning" style="background: #1a0028 !important; border-radius: 25px;">
            <div class="modal-header border-secondary px-4 py-3">
                <h5 class="modal-title text-warning fw-bold"><i class="bi bi-pencil-square me-2"></i> แก้ไขประเภทสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                <div class="mb-2">
                    <label class="small text-white-50 mb-2">ระบุชื่อประเภทสินค้าใหม่</label>
                    <input type="text" name="cat_name" id="edit_cat_name" class="form-control py-2 px-3" required>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_cat" class="btn btn-neon-yellow rounded-pill px-4 fw-bold text-dark" style="background: #ffc107;">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<script>
// ฟังก์ชันเรียกใช้ Modal พร้อมค่าเริ่มต้น
function editCategory(id, name) {
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_cat_name').value = name;
    var editModal = new bootstrap.Modal(document.getElementById('editCatModal'));
    editModal.show();
}
</script>