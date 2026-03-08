<?php
// ห้าม include "admin_dashboard.php" หรือ "connectdb.php" ซ้ำในนี้เด็ดขาด
// เพราะไฟล์ admin_dashboard.php (ไฟล์แม่) ทำไว้ให้แล้ว

// [ปรับเพิ่ม]: SQL เพื่อดึงราคาต่ำสุด (min_v_price) และสต็อกรวม (total_v_stock) จากตารางรุ่นย่อย
$sql_p = "SELECT p.*, c.name AS cat_name,
          (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_v_price,
          (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) as total_v_stock
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.id DESC";

$result_p = $conn->query($sql_p); // ใช้ตัวแปร $conn จากไฟล์แม่ได้เลย
?>

<div class="glass-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-neon-cyan"><i class="bi bi-box-seam me-2"></i>สินค้า & สต็อก</h4>
        <a href="edit_product.php" class="btn btn-neon-pink rounded-pill px-4">
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
                <?php if ($result_p && $result_p->num_rows > 0): ?>
                    <?php while($row = $result_p->fetch_assoc()): 
                        // ตรรกะการแสดงผล: ถ้ามีรุ่นย่อยให้ใช้ค่าจากรุ่นย่อย ถ้าไม่มีให้ใช้จากตารางหลัก
                        $display_price = ($row['min_v_price'] > 0) ? $row['min_v_price'] : $row['price'];
                        $display_stock = ($row['total_v_stock'] !== null) ? $row['total_v_stock'] : $row['stock'];
                    ?>
                    <tr>
                        <td>
                            <img src="images/<?= $row['image'] ?>" class="rounded" 
                                 style="width: 50px; height: 50px; object-fit: cover; border: 1px solid rgba(187, 134, 252, 0.4);" 
                                 onerror="this.src='https://via.placeholder.com/50'">
                        </td>
                        <td>
                            <div class="fw-bold"><?= $row['name'] ?></div>
                            <small class="text-white-50">หมวดหมู่: <?= $row['cat_name'] ?? 'ทั่วไป' ?></small>
                        </td>
                        <td class="text-neon-cyan fw-bold">
                            <?= ($row['min_v_price'] > 0) ? "เริ่มต้น " : "" ?>฿<?= number_format($display_price) ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-dark border border-secondary px-3">
                                <?= $display_stock ?> ชิ้น
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning border-0">
                                <i class="bi bi-pencil-square fs-5"></i>
                            </a>
                            <a href="admin_dashboard.php?del_id=<?= $row['id'] ?>&type=product&tab=products" 
                               class="btn btn-sm btn-outline-danger border-0" 
                               onclick="return confirm('ยืนยันการลบสินค้า?')">
                                <i class="bi bi-trash fs-5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 opacity-50">ยังไม่มีข้อมูลสินค้า</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>