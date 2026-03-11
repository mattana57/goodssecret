<?php
// ดึงข้อมูลลูกค้าเฉพาะบทบาท user มาแสดงผล
$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY id DESC");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* สไตล์ปุ่มลบสีแดงนีออนที่มึงต้องการ */
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
    .modal-content { backdrop-filter: blur(10px); }
    .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
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

<script>
    function viewHistory(uid, uname) {
        $('#h_uname').text(uname);
        $('#h_content').html('<div class="text-center py-5 opacity-50"><div class="spinner-border text-info mb-2"></div><br>กำลังดึงข้อมูล...</div>');
        $('#historyModal').modal('show');
        $('#h_content').load('admin_dashboard.php?ajax_action=get_user_history&uid=' + uid);
    }

    // ฟังก์ชันยืนยันการลบลูกค้าพร้อมป๊อปอัพสวยๆ
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
                // ส่งค่าไปประมวลผลการลบที่ไฟล์แม่
                window.location = 'admin_dashboard.php?tab=customers&del_id=' + id + '&type=customer';
            }
        })
    }
</script>