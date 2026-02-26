<?php
$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY id DESC");
?>
<div class="glass-panel">
    <h4 class="mb-4 text-white"><i class="bi bi-people me-2"></i> จัดการลูกค้า</h4>
    <table class="table table-hover text-white w-100 datatable-js">
        <thead><tr class="text-info"><th>Username</th><th>ชื่อ-นามสกุล</th><th>โทร</th><th>จัดการ</th></tr></thead>
        <tbody>
            <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td class="fw-bold"><?= $u['username'] ?></td>
                <td><?= $u['fullname'] ?: '-' ?></td>
                <td><?= $u['phone'] ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="viewHistory(<?= $u['id'] ?>, '<?= $u['username'] ?>')"><i class="bi bi-clock-history"></i> ประวัติ</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-info">
            <div class="modal-header border-secondary"><h5>ประวัติ: <span id="h_name" class="text-info"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="h_body"></div>
        </div>
    </div>
</div>

<script>
    function viewHistory(uid, uname) {
        $('#h_name').text(uname);
        $('#historyModal').modal('show');
        $('#h_body').load('admin_dashboard.php?ajax_action=get_user_history&uid=' + uid);
    }
</script>