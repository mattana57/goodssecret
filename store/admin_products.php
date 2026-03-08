<?php
// ห้าม include "admin_dashboard.php" หรือ "connectdb.php" ซ้ำ
// ใช้ SQL เดิมที่มึงทำไว้ดีอยู่แล้ว
$sql_p = "SELECT p.*, c.name AS cat_name,
          (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_v_price,
          (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) as total_v_stock
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.id DESC";
$result_p = $conn->query($sql_p);
?>

<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">

<style>
    /* [ปรับเพิ่ม]: สไตล์ SweetAlert ให้เนียนกริบกับธีมม่วงนีออน */
    .swal2-popup {
        background: #1a0028 !important; /* พื้นหลังม่วงเข้มมืด */
        border: 2px solid #f107a3 !important; /* ขอบนีออนชมพู */
        border-radius: 30px !important;
        box-shadow: 0 0 30px rgba(241, 7, 163, 0.4) !important; /* เงาเรืองแสง */
    }
    .swal2-title { color: #00f2fe !important; font-weight: 700 !important; } /* หัวข้อนีออนฟ้า */
    .swal2-html-container { color: rgba(255,255,255,0.8) !important; }
    
    /* สไตล์ปุ่มในป๊อปอัพ */
    .custom-swal-confirm {
        background: linear-gradient(135deg, #f107a3, #ff0080) !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 10px 25px !important;
        font-weight: bold !important;
        box-shadow: 0 4px 15px rgba(241, 7, 163, 0.4) !important;
    }
    .custom-swal-cancel {
        background: rgba(255,255,255,0.1) !important;
        color: #fff !important;
        border: 1px solid rgba(255,255,255,0.3) !important;
        border-radius: 50px !important;
        padding: 10px 25px !important;
    }
</style>

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
                        $display_price = ($row['min_v_price'] > 0) ? $row['min_v_price'] : $row['price'];
                        $display_stock = ($row['total_v_stock'] !== null) ? $row['total_v_stock'] : $row['stock'];
                    ?>
                    <tr>
                        <td><img src="images/<?= $row['image'] ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid rgba(187, 134, 252, 0.4);"></td>
                        <td><div class="fw-bold text-white"><?= $row['name'] ?></div><small class="text-white-50"><?= $row['cat_name'] ?? 'ทั่วไป' ?></small></td>
                        <td class="text-neon-cyan fw-bold"><?= ($row['min_v_price'] > 0) ? "เริ่มต้น " : "" ?>฿<?= number_format($display_price) ?></td>
                        <td><span class="badge rounded-pill bg-dark border border-secondary px-3"><?= $display_stock ?> ชิ้น</span></td>
                        <td class="text-center">
                            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning border-0"><i class="bi bi-pencil-square fs-5"></i></a>
                            <a href="javascript:void(0)" onclick="confirmDeleteProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash fs-5"></i></a>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeleteProduct(productId, productName) {
    Swal.fire({
        title: 'ยืนยันการลบสินค้า?',
        html: `คุณกำลังจะลบ <b style="color:#f107a3">[${productName}]</b><br><small style="color:#aaa">ข้อมูลนี้จะหายไปจากระบบถาวร!</small>`,
        icon: 'warning',
        iconColor: '#f107a3',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการลบ',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            confirmButton: 'custom-swal-confirm',
            cancelButton: 'custom-swal-cancel'
        },
        buttonsStyling: false // ปิดสไตล์พื้นฐานเพื่อใช้ class ที่เราสร้างเอง
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "admin_dashboard.php?del_id=" + productId + "&type=product&tab=products";
        }
    });
}
</script>