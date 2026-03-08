<?php
// 1. ห้าม include "connectdb.php" หรือ session_start() ซ้ำ
// 2. ใช้คำสั่ง SQL ให้ตรงกับตารางในฐานข้อมูลของคุณ

$sql_p = "SELECT p.*, c.name AS cat_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.id DESC";

// 3. ใช้ $conn->query ตามแบบที่ไฟล์ admin_dashboard.php กำหนดไว้
$result_p = $conn->query($sql_p); 
?>

<div class="glass-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-neon-cyan"><i class="bi bi-box-seam me-2"></i>สินค้า & สต็อก</h4>
        <a href="add_product.php" class="btn btn-neon-pink rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i>เพิ่มสินค้าใหม่
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover datatable-js">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>สต็อก</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // ตรวจสอบว่ามีข้อมูลก่อนวนลูป เพื่อป้องกัน Error
                if ($result_p && $result_p->num_rows > 0): 
                    while($row = $result_p->fetch_assoc()): 
                ?>
                <tr>
                    <td>
                        <img src="uploads/<?= $row['image'] ?>" class="rounded" 
                             style="width: 50px; height: 50px; object-fit: cover; border: 1px solid rgba(187, 134, 252, 0.4);" 
                             onerror="this.src='https://via.placeholder.com/50'">
                    </td>
                    <td>
                        <div class="fw-bold"><?= $row['name'] ?></div>
                        <small class="text-white-50">หมวดหมู่: <?= $row['cat_name'] ?? 'ทั่วไป' ?></small>
                    </td>
                    <td class="text-neon-cyan fw-bold">฿<?= number_format($row['price']) ?></td>
                    <td>
                        <span class="badge rounded-pill bg-dark border border-secondary px-3">
                            <?= $row['stock'] ?> ชิ้น
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning border-0">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </a>
                        <a href="admin_dashboard.php?del_id=<?= $row['id'] ?>&type=product&tab=products" 
                           class="btn btn-sm btn-outline-danger border-0" 
                           onclick="return confirm('ยืนยันการลบ?')">
                            <i class="bi bi-trash fs-5"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr><td colspan="5" class="text-center py-5 opacity-50">ไม่พบข้อมูลสินค้าในระบบ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>