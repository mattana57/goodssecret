<?php
// ปรับ Query ใหม่เพื่อแบ่งกลุ่มความสำคัญของสถานะ
// กลุ่มที่ 1: PENDING, PROCESSING, SHIPPED (กลุ่มงานค้าง) -> เรียงจากเก่าไปใหม่ (ใครสั่งก่อนอยู่บน)
// กลุ่มที่ 2: DELIVERED, CANCELLED (กลุ่มงานจบแล้ว) -> ปัดไปอยู่ท้ายตาราง
$orders = $conn->query("SELECT * FROM orders 
    ORDER BY 
        -- ขั้นที่ 1: แบ่งกลุ่มสถานะ (กลุ่ม 1 = งานค้าง, กลุ่ม 2 = งานจบ)
        CASE 
            WHEN status IN ('pending', 'processing', 'shipped') THEN 1 
            ELSE 2 
        END ASC,
        -- ขั้นที่ 2: ในกลุ่มงานค้าง (กลุ่ม 1) ให้เรียงตามเวลาที่สั่งซื้อ (เก่าขึ้นก่อนเพื่อให้จัดการตามคิว)
        CASE 
            WHEN status IN ('pending', 'processing', 'shipped') THEN created_at 
        END ASC,
        -- ขั้นที่ 3: สำหรับกลุ่มงานจบแล้ว (กลุ่ม 2) ให้เรียงตามเวลาล่าสุด (ใครจบงานล่าสุดอยู่บนของกลุ่มท้าย)
        created_at DESC"); 
?>
<div class="glass-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-white-bright fw-bold"><i class="bi bi-receipt me-2"></i> รายการสั่งซื้อล่าสุด</h4>
        <span class="badge bg-info text-dark shadow-sm">
            <i class="bi bi-filter-left me-1"></i> เรียงตามความสำคัญ (งานค้างอยู่บนสุด)
        </span>
    </div>
    
    <div class="table-responsive">
        <table class="table text-white w-100 datatable-js">
            <thead>
                <tr class="text-info border-bottom border-secondary border-opacity-25">
                    <th>วันที่สั่งซื้อ</th>
                    <th>เลขที่บิล</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($o = $orders->fetch_assoc()): 
                    // กำหนดสี Badge ตามสถานะเพื่อให้แยกแยะง่ายขึ้น
                    $badge_class = "bg-secondary";
                    if($o['status'] == 'pending') $badge_class = "bg-warning text-dark";
                    elseif($o['status'] == 'processing') $badge_class = "bg-primary";
                    elseif($o['status'] == 'shipped') $badge_class = "bg-info text-dark";
                    elseif($o['status'] == 'delivered') $badge_class = "bg-success";
                    elseif($o['status'] == 'cancelled') $badge_class = "bg-danger";

                    // ปรับสไตล์แถวที่จบงานแล้วให้จางลงเล็กน้อยเพื่อเน้นงานค้าง
                    $row_opacity = ($o['status'] == 'delivered' || $o['status'] == 'cancelled') ? 'opacity: 0.6;' : '';
                ?>
                <tr class="align-middle" style="<?= $row_opacity ?>">
                    <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                    <td class="text-info fw-bold">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="text-neon-cyan fw-bold">฿<?= number_format($o['total_price']) ?></td>
                    <td>
                        <span class="badge <?= $badge_class ?> px-3 py-2 text-uppercase" style="min-width: 100px;">
                            <?= $o['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="admin_order_view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-light rounded-pill px-3 shadow-sm">
                            <i class="bi bi-search me-1"></i> รายละเอียด
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>