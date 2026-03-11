<?php
// --- [ส่วนที่ 1: Logic สำหรับลบประเภทสินค้า]: ห้ามตัดออกเด็ดขาด ---
if (isset($_GET['del_id']) && $_GET['type'] == 'category') {
    $id = intval($_GET['del_id']);
    
    // ตรวจสอบก่อนว่ามีสินค้าตัวไหนใช้ประเภทนี้อยู่ไหม เพื่อป้องกัน Error (Foreign Key)
    $check_usage = $conn->query("SELECT id FROM products WHERE category_id = $id LIMIT 1");
    
    if ($check_usage->num_rows > 0) {
        // ถ้ายังมีสินค้าใช้งานประเภทนี้อยู่ ห้ามลบเด็ดขาด
        echo "<script>alert('ไม่สามารถลบได้! เนื่องจากยังมีสินค้าที่ใช้งานประเภทนี้อยู่'); window.location='admin_dashboard.php?tab=categories';</script>";
    } else {
        // สั่งลบจากฐานข้อมูลจริง
        $conn->query("DELETE FROM categories WHERE id = $id");
        echo "<script>window.location='admin_dashboard.php?tab=categories&deleted=1';</script>";
    }
}

// --- [Logic 1]: เพิ่มประเภทสินค้าใหม่ (คงเดิม) ---
if (isset($_POST['save_cat'])) {
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("INSERT INTO categories (name, slug) VALUES ('$n', '".strtolower($n)."')");
    echo "<script>window.location='admin_dashboard.php?tab=categories&success=1';</script>";
}

// --- [Logic 2]: อัปเดตข้อมูลประเภทสินค้า (คงเดิม) ---
if (isset($_POST['update_cat'])) {
    $id = intval($_POST['cat_id']);
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("UPDATE categories SET name = '$n', slug = '".strtolower($n)."' WHERE id = $id");
    echo "<script>window.location='admin_dashboard.php?tab=categories&updated=1';</script>";
}

$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ปรับปรุง CSS เพื่อแก้ปัญหา Layout เพี้ยน */
    .admin-card-panel {
        background: rgba(255, 255, 255, 0.03) !important;
        backdrop-filter: blur(15px);
        border: 1px solid rgba(187, 134, 252, 0.2) !important;
        border-radius: 25px;
        padding: 30px;
    }

    .btn-edit-neon {
        background: transparent !important;
        border: 1px solid #ffc107 !important;
        color: #ffc107 !important;
        box-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
        border-radius: 12px !important;
        transition: 0.3s;
    }
    .btn-edit-neon:hover {
        background: #ffc107 !important;
        color: #000 !important;
        box-shadow: 0 0 15px #ffc107;
    }

    .btn-delete-neon {
        background: transparent !important;
        border: 1px solid #ff4d4d !important;
        color: #ff4d4d !important;
        border-radius: 12px !important;
        transition: 0.3s;
    }
    .btn-delete-neon:hover {
        background: #ff4d4d !important;
        color: #fff !important;
        box-shadow: 0 0 15px #ff4d4d;
    }

    .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px #00f2fe; }

    /* ปรับแต่ง SweetAlert ให้เข้าธีมร้าน (คงเดิม) */
    .swal2-popup {
        background: #1a0028 !important;
        border: 2px solid #bb86fc !important;
        border-radius: 25px !important;
        color: #fff !important;
    }
    .swal2-title { color: #00f2fe !important; }
    .swal2-confirm { background: #2582d1 !important; border-radius: 10px !important; padding: 10px 30px !important; font-weight: bold !important; }
    .swal2-cancel { background: #6e7881 !important; border-radius: 10px !important; padding: 10px 30px !important; }

    /* ปรับปรุงปุ่มเพิ่มประเภทให้สวยงามเหมือนหน้าหลัก */
    .btn-neon-pink {
        background: linear-gradient(135deg, #f107a3, #ff0080) !important;
        border: none !important;
        color: white !important;
        font-weight: bold !important;
        border-radius: 25px !important;
        transition: 0.3s;
    }
    .btn-neon-pink:hover { box-shadow: 0 0 15px #f107a3; transform: scale(1.05); }
</style>

<div class="admin-card-panel mt-2">
    <div class="d-flex justify-content-between mb-4 align-items-center flex-wrap gap-4">
        <h4 class="mb-0 text-white fw-bold"><i class="bi bi-tags-fill me-2 text-neon-cyan"></i> จัดการประเภทสินค้า</h4>
        
        <form method="POST" class="d-flex gap-2">
            <input type="text" name="cat_name" class="form-control rounded-pill px-4 bg-dark text-white border-secondary" placeholder="ชื่อประเภทใหม่" style="min-width: 250px;" required>
            <button type="submit" name="save_cat" class="btn btn-neon-pink px-4 shadow">เพิ่มประเภท</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 datatable-js">
            <thead>
                <tr class="text-neon-cyan border-bottom border-secondary">
                    <th style="width: 15%;">ID</th>
                    <th style="width: 55%;">ชื่อประเภทสินค้า</th>
                    <th style="width: 30%;" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $cats->fetch_assoc()): ?>
                <tr class="align-middle border-bottom border-white border-opacity-5">
                    <td class="text-white-50">#<?= $c['id'] ?></td>
                    <td class="fw-bold fs-5 text-white"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-edit-neon px-3 py-1" 
                                    onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                                <i class="bi bi-pencil-square"></i> แก้ไข
                            </button>
                            
                            <button class="btn btn-sm btn-delete-neon px-3 py-1" 
                                    onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
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
        <form method="POST" class="modal-content shadow-lg border-warning" style="background: #1a0028 !important; border-radius: 20px;">
            <div class="modal-header border-secondary px-4">
                <h5 class="modal-title text-warning fw-bold"><i class="bi bi-pencil-square me-2"></i> แก้ไขประเภทสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                <div class="mb-2">
                    <label class="small text-white-50 mb-2">ระบุชื่อใหม่</label>
                    <input type="text" name="cat_name" id="edit_cat_name" class="form-control bg-black text-white border-secondary" required>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_cat" class="btn btn-neon-pink rounded-pill px-4 fw-bold">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
// ฟังก์ชันลบพร้อมป๊อปอัพ SweetAlert2
function confirmDelete(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        html: `ต้องการลบประเภทสินค้า <b style="color:#00f2fe">[${name}]</b> ใช่หรือไม่?<br><small class="opacity-50">ข้อมูลในฐานข้อมูลจะถูกลบทันที</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'admin_dashboard.php?tab=categories&del_id=' + id + '&type=category';
        }
    })
}

function editCategory(id, name) {
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_cat_name').value = name;
    var editModal = new bootstrap.Modal(document.getElementById('editCatModal'));
    editModal.show();
}
</script>