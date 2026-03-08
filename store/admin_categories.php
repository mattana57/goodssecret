<?php
// --- [Logic 1]: เพิ่มประเภทสินค้าใหม่ ---
if (isset($_POST['save_cat'])) {
    $n = $conn->real_escape_string($_POST['cat_name']);
    // สร้าง Slug อัตโนมัติจากชื่อประเภท
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
    /* --- [ส่วนที่แก้ไข CSS ใหม่]: ดีไซน์ Dark Glassmorphism ขั้นสูง --- */
    body { background: #0c001c !important; } /* พื้นหลังสีดำเข้ม */
    
    .glass-panel-heavy {
        background: rgba(255, 255, 255, 0.03); 
        backdrop-filter: blur(20px); 
        border: 1px solid rgba(187, 134, 252, 0.2); 
        border-radius: 30px; 
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    /* สไตล์สีนีออน */
    .text-neon-cyan { color: #00f2fe; text-shadow: 0 0 10px #00f2fe; }
    .text-neon-purple { color: #bb86fc; text-shadow: 0 0 10px #bb86fc; }
    
    /* ปุ่มเรืองแสงขั้นสูง */
    .btn-neon-glow {
        background: linear-gradient(135deg, #00f2fe, #bb86fc);
        border: none;
        color: #000;
        font-weight: bold;
        transition: 0.3s;
        border-radius: 50px;
        box-shadow: 0 0 15px rgba(0, 242, 254, 0.5);
    }
    .btn-neon-glow:hover {
        box-shadow: 0 0 25px rgba(187, 134, 252, 0.8);
        transform: translateY(-2px);
    }
    
    /* ปุ่มแบบ Outline เรืองแสง */
    .btn-outline-neon-cyan {
        background: transparent;
        border: 1px solid #00f2fe;
        color: #00f2fe;
        border-radius: 50px;
        transition: 0.3s;
    }
    .btn-outline-neon-cyan:hover {
        background: #00f2fe;
        color: #000;
        box-shadow: 0 0 15px #00f2fe;
    }

    .btn-outline-neon-red {
        background: transparent;
        border: 1px solid #ff4d4d;
        color: #ff4d4d;
        border-radius: 50px;
        transition: 0.3s;
    }
    .btn-outline-neon-red:hover {
        background: #ff4d4d;
        color: #fff;
        box-shadow: 0 0 15px #ff4d4d;
    }

    /* ปรับแต่งกรอบ Input ไม่ให้ขาว */
    .form-control {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(187, 134, 252, 0.3) !important;
        color: #fff !important;
        border-radius: 12px;
    }
    .form-control:focus {
        border-color: #00f2fe !important;
        box-shadow: 0 0 10px rgba(0, 242, 254, 0.5) !important;
    }

    /* สไตล์ตาราง */
    .table-dark { background: transparent !important; color: #fff !important; }
    .table-dark td, .table-dark th { border-color: rgba(255,255,255,0.05) !important; }
</style>

<div class="glass-panel-heavy mt-2 mx-auto" style="max-width: 950px;">
    <div class="d-flex justify-content-between mb-5 align-items-center flex-wrap gap-4">
        <h3 class="mb-0 text-white fw-bold"><i class="bi bi-tags-fill me-2 text-neon-cyan"></i> จัดการประเภทสินค้า</h3>
        
        <form method="POST" class="d-flex gap-2 bg-black bg-opacity-30 p-2 rounded-pill border border-white border-opacity-10">
            <input type="text" name="cat_name" class="form-control border-0 bg-transparent rounded-pill px-4 py-2" placeholder="ชื่อประเภทใหม่..." required>
            <button type="submit" name="save_cat" class="btn btn-neon-glow px-4 fw-bold">เพิ่ม</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 small datatable-js">
            <thead>
                <tr class="text-neon-purple border-bottom border-white border-opacity-10 text-uppercase">
                    <th style="width: 15%;">ID</th>
                    <th style="width: 55%;">ชื่อประเภทสินค้า</th>
                    <th style="width: 30%;" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $cats->fetch_assoc()): ?>
                <tr class="align-middle border-bottom border-white border-opacity-5">
                    <td class="text-white-50">#<?= str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td class="fw-bold fs-5 text-white"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-neon-cyan px-3 py-1" 
                                    onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                                <i class="bi bi-pencil-square me-1"></i> แก้ไข
                            </button>
                            
                            <button class="btn btn-sm btn-outline-neon-red px-3 py-1" 
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
        <form method="POST" class="modal-content shadow-lg" style="background: rgba(26,0,40,0.9); border: 2px solid #00f2fe; border-radius: 25px; backdrop-filter: blur(15px);">
            <div class="modal-header border-secondary px-4 py-3">
                <h5 class="modal-title text-neon-cyan fw-bold"><i class="bi bi-pencil-square me-2"></i> แก้ไขประเภทสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                <div class="mb-2">
                    <label class="small text-white-50 mb-2 fw-bold">ชื่อประเภทสินค้าใหม่</label>
                    <input type="text" name="cat_name" id="edit_cat_name" class="form-control py-2 px-3 fs-5 fw-bold text-white" required>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_cat" class="btn btn-neon-glow rounded-pill px-4 fw-bold">บันทึกการแก้ไข</button>
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