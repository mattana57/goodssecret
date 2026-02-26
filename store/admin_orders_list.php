<?php
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
?>
<div class="glass-panel">
    <h4 class="mb-4"><i class="bi bi-receipt me-2"></i> รายการสั่งซื้อล่าสุด</h4>
    <table class="table text-white w-100 datatable-js">
        <thead><tr class="text-info"><th>วันที่</th><th>เลขที่บิล</th><th>ยอดรวม</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
        <tbody>
            <?php while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                <td class="text-info fw-bold">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td>฿<?= number_format($o['total_price']) ?></td>
                <td><span class="badge bg-secondary"><?= strtoupper($o['status']) ?></span></td>
                <td><a href="admin_order_view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-light rounded-pill">ดูรายละเอียด</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>