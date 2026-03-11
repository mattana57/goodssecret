<?php
if (isset($_POST['update_user'])) {
    $uid = intval($_POST['user_id']);
    $full = $conn->real_escape_string($_POST['fullname']);
    $phone = $conn->real_escape_string($_POST['phone']);
    
    $conn->query("UPDATE users SET fullname = '$full', phone = '$phone' WHERE id = $uid");
    echo "<script>window.location='admin_dashboard.php?tab=customers&updated=1';</script>";
}

$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY id DESC");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .btn-edit-neon {
        background: transparent !important;
        border: 1px solid #ffc107 !important;
        color: #ffc107 !important;
        transition: 0.3s;
        border-radius: 50px;
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
        transition: 0.3s;
        border-radius: 50px;
    }
    .btn-delete-neon:hover {
        background: #ff4d4d !important;
        color: #fff !important;
        box-shadow: 0 0 15px #ff4d4d;
    }
    .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 25px; padding: 30px; }
    .modal-content-custom { background: #1a0028 !important; border: 2px solid #bb86fc !important; border-radius: 25px; color: #fff; }
    .swal2-popup { background: #1a0028 !important; border: 2px solid #bb86fc !important; border-radius: 25px !important; color: #fff !important; }
</style>

<div class="glass-panel mt-2">
    <h4 class="mb-4 text-white fw-bold"><i class="bi bi-people-fill me-2 text-neon-cyan"></i> จัดการลูกค้า</h4>
    <div class="table-responsive">
        <table class="table table-hover text-white w-100 datatable-js">
            <thead>
                <tr class="text-info">
                    <th>Username</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>โทร</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = $users->fetch_assoc()): ?>
                <tr class="align-middle border-bottom border-white border-opacity-5">
                    <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['fullname']) ?: '-' ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="viewHistory(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                <i class="bi bi-clock-history"></i> ประวัติ
                            </button>
                            <button class="btn btn-sm btn-edit-neon rounded-pill px-3" 
                                    onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>', '<?= htmlspecialchars($u['phone']) ?>')">
                                <i class="bi bi-pencil-square"></i> แก้ไข
                            </button>
                            <button class="btn btn-sm btn-delete-neon rounded-pill px-3" onclick="confirmDeleteCustomer(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
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

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-secondary px-4">
                <h5 class="modal-title fw-bold text-neon-cyan"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="mb-3">
                    <label class="small text-white-50 mb-2">ชื่อ-นามสกุล</label>
                    <input type="text" name="fullname" id="edit_fullname" class="form-control bg-dark text-white border-secondary rounded-pill px-3" required>
                </div>
                <div class="mb-3">
                    <label class="small text-white-50 mb-2">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control bg-dark text-white border-secondary rounded-pill px-3" required>
                </div>
            </div>
            <div class="modal-footer border-secondary px-4">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_user" class="btn btn-info rounded-pill px-4 fw-bold shadow">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(id, fullname, phone) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_fullname').value = fullname;
        document.getElementById('edit_phone').value = phone;
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    function viewHistory(uid, uname) {
        $('#h_uname').text(uname);
        $('#h_content').html('<div class="text-center py-5 opacity-50"><div class="spinner-border text-info mb-2"></div><br>กำลังดึงข้อมูล...</div>');
        $('#historyModal').modal('show');
        $('#h_content').load('admin_dashboard.php?ajax_action=get_user_history&uid=' + uid);
    }

    function confirmDeleteCustomer(id, username) {
        Swal.fire({
            title: 'ยืนยันการลบลูกค้า?',
            html: `คุณต้องการลบผู้ใช้งาน <b>[${username}]</b> ใช่หรือไม่?<br><small class="text-danger">คำเตือน: ประวัติการสั่งซื้อและที่อยู่ทั้งหมดจะถูกลบถาวร!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true,
            confirmButtonColor: '#ff4d4d',
            cancelButtonColor: '#6e7881'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'admin_dashboard.php?tab=customers&del_id=' + id + '&type=customer';
            }
        })
    }
</script>