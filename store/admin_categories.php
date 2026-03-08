<?php
// --- [Logic 1]: เพิ่มประเภทสินค้าใหม่ ---
if (isset($_POST['save_cat'])) {
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("INSERT INTO categories (name, slug) VALUES ('$n', '".strtolower($n)."')");
    echo "<script>window.location='admin_dashboard.php?tab=categories&success=1';</script>";
}

// --- [Logic 2]: อัปเดตข้อมูลประเภทสินค้า (เพิ่มใหม่) ---
if (isset($_POST['update_cat'])) {
    $id = intval($_POST['cat_id']);
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("UPDATE categories SET name = '$n', slug = '".strtolower($n)."' WHERE id = $id");
    echo "<script>window.location='admin_dashboard.php?tab=categories&updated=1';</script>";
}

$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h4 class="mb-0"><i class="bi bi-tags me-2 text-neon-cyan"></i> จัดการประเภทสินค้า</h4>
        <form method="POST" class="d-flex gap-2">
            <input type="text" name="cat_name" class="form-control" placeholder="ชื่อประเภทใหม่" required>
            <button type="submit" name="save_cat" class="btn btn-primary rounded-pill px-4 shadow">เพิ่ม</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table text-white w-100 datatable-js">
            <thead>
                <tr class="text-neon-cyan border-bottom border-secondary">
                    <th>ID</th>
                    <th>ชื่อประเภท</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $cats->fetch_assoc()): ?>
                <tr class="align-middle">
                    <td class="text-white-50">#<?= $c['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="text-center">
                        <div class="btn-group gap-2">
                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3" 
                                    onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                                <i class="bi bi-pencil-square"></i> แก้ไข
                            </button>
                            
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                    onclick="if(confirm('ยืนยันการลบประเภทนี้?')) window.location='admin_dashboard.php?tab=categories&del_id=<?= $c['id'] ?>&type=category'">
                                <i class="bi bi-trash"></i> ลบ
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
        <form method="POST" class="modal-content shadow-lg border-warning">
            <div class="modal-header border-secondary px-4">
                <h5 class="modal-title text-warning fw-bold"><i class="bi bi-pencil-square me-2"></i> แก้ไขประเภทสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                <div class="mb-3">
                    <label class="small text-white-50 mb-2">ชื่อประเภทสินค้า</label>
                    <input type="text" name="cat_name" id="edit_cat_name" class="form-control py-2" required>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_cat" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<script>
// ฟังก์ชันสำหรับส่งข้อมูลไปที่ Modal
function editCategory(id, name) {
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_cat_name').value = name;
    var editModal = new bootstrap.Modal(document.getElementById('editCatModal'));
    editModal.show();
}
</script>