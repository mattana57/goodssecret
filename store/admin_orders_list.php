<?php
// ปรับ Query ใหม่โดยใช้ FIELD() เพื่อกำหนดความสำคัญของสถานะ
// 1 = pending, 2 = processing, 3 = shipped (กลุ่มทำงาน)
// 4 = delivered, 5 = cancelled (กลุ่มงานจบแล้ว/ปัดท้าย)
$orders = $conn->query("SELECT * FROM orders 
    ORDER BY 
        CASE 
            WHEN status IN ('pending', 'processing', 'shipped') THEN 1 
            ELSE 2 
        END ASC, 
        CASE 
            WHEN status IN ('pending', 'processing', 'shipped') THEN created_at 
        END ASC,
        created_at DESC"); 
?>
<div class="glass-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-white-bright fw-bold"><i class="bi bi-receipt me-2"></i> รายการสั่งซื้อล่าสุด</h4>
        <span class="badge bg-info">เรียงตามความสำคัญ (ออเดอร์ค้างอยู่บน)</span>
    </div>
    
    <div class="table-responsive">
        <table class="table text-white w-100 datatable-js">
            <thead>
                <tr class="text-info">
                    <th>วันที่สั่งซื้อ</th>
                    <th>เลขที่บิล</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($o = $orders->fetch_assoc()): 
                    // กำหนดสี Badge ตามสถานะเพื่อให้พี่แยกแยะง่ายขึ้น
                    $badge_class = "bg-secondary";
                    if($o['status'] == 'pending') $badge_class = "bg-warning text-dark";
                    elseif($o['status'] == 'processing') $badge_class = "bg-primary";
                    elseif($o['status'] == 'shipped') $badge_class = "bg-info text-dark";
                    elseif($o['status'] == 'delivered') $badge_class = "bg-success";
                    elseif($o['status'] == 'cancelled') $badge_class = "bg-danger";
                ?>
                <tr class="align-middle <?= ($o['status'] == 'delivered' || $o['status'] == 'cancelled') ? 'opacity-50' : '' ?>">
                    <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                    <td class="text-info fw-bold">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="text-neon-cyan fw-bold">฿<?= number_format($o['total_price']) ?></td>
                    <td>
                        <span class="badge <?= $badge_class ?> px-3 py-2">
                            <?= strtoupper($o['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="admin_order_view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">
                            <i class="bi bi-search me-1"></i> รายละเอียด
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>