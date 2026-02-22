<?php
session_start();
include "connectdb.php";

// --- [ฟังก์ชันจัดการออเดอร์]: อัปเดตสถานะ ---
if (isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$status' WHERE id = $order_id");
    header("Location: admin_products.php?tab=orders&success=1");
}

// --- [ฟังก์ชันจัดการประเภทสินค้า]: เพิ่ม/ลบ ---
if (isset($_POST['add_category'])) {
    $name = $conn->real_escape_string($_POST['cat_name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')");
    header("Location: admin_products.php?tab=categories&success=1");
}

// ดึงข้อมูลสำหรับแต่ละแท็บ
$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
// ออเดอร์: เอาอันใหม่ขึ้นก่อน (OrderBy ID DESC หรือ Created_at DESC)
$orders = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบหลังร้าน | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 15px; padding: 20px; }
        .nav-tabs .nav-link { color: #aaa; border: none; }
        .nav-tabs .nav-link.active { background: transparent; color: #bb86fc; border-bottom: 3px solid #bb86fc; font-weight: bold; }
        .table { color: #fff !important; }
        .status-pending { color: #ffc107; } .status-completed { color: #28a745; }
    </style>
</head>
<body>

<div class="container-fluid py-5">
    <h2 class="text-center text-neon-purple mb-5 fw-bold"><i class="bi bi-speedometer2"></i> ADMIN DASHBOARD</h2>

    <div class="glass-card">
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-products">สินค้า</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories">ประเภทสินค้า</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders">ออเดอร์</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-users">ลูกค้า</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-products">
                <div class="d-flex justify-content-between mb-3">
                    <h4>รายการสินค้า</h4>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">+ เพิ่มสินค้า</button>
                </div>
                <table class="table table-hover datatable">
                    <thead><tr><th>ชื่อ</th><th>ประเภท</th><th>ราคา</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?= $p['name'] ?></td>
                            <td><?= $p['cat_name'] ?></td>
                            <td>฿<?= number_format($p['price']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick="location.href='edit_product.php?id=<?= $p['id'] ?>'"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $p['id'] ?>, 'product')"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-categories">
                <div class="d-flex justify-content-between mb-3">
                    <h4>หมวดหมู่สินค้า</h4>
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="cat_name" class="form-control form-control-sm" placeholder="ชื่อประเภทใหม่" required>
                        <button type="submit" name="add_category" class="btn btn-success btn-sm">เพิ่ม</button>
                    </form>
                </div>
                <table class="table table-hover datatable">
                    <thead><tr><th>ID</th><th>ชื่อประเภท</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($c = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= $c['name'] ?></td>
                            <td><button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $c['id'] ?>, 'category')"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-orders">
                <h4>รายการสั่งซื้อ</h4>
                <table class="table table-hover datatable">
                    <thead><tr><th>วันที่</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th><th>สลิป</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($o = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $o['created_at'] ?></td>
                            <td><?= $o['username'] ?></td>
                            <td>฿<?= number_format($o['total_price']) ?></td>
                            <td class="status-<?= $o['status'] ?>"><?= $o['status'] ?></td>
                            <td>
                                <?php if($o['slip_image']): ?>
                                    <a href="uploads/slips/<?= $o['slip_image'] ?>" target="_blank" class="btn btn-sm btn-light">ดูสลิป</a>
                                <?php else: ?> <span class="text-muted">ไม่มี</span> <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>รอตรวจสอบ</option>
                                        <option value="shipping" <?= $o['status']=='shipping'?'selected':'' ?>>กำลังส่ง</option>
                                        <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>สำเร็จ</option>
                                        <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>ยกเลิก</option>
                                    </select>
                                    <input type="hidden" name="update_order_status">
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-users">
                <h4>ข้อมูลสมาชิก</h4>
                <table class="table table-hover datatable">
                    <thead><tr><th>Username</th><th>ชื่อ-สกุล</th><th>เบอร์โทร</th><th>ที่อยู่</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['username'] ?></td>
                            <td><?= $u['fullname'] ?></td>
                            <td><?= $u['phone'] ?></td>
                            <td style="font-size: 12px;"><?= $u['address'] ?></td>
                            <td><button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $u['id'] ?>, 'user')"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            "language": { "search": "ค้นหา:", "lengthMenu": "แสดง _MENU_ รายการ" },
            "order": [] // ไม่บังคับเรียงอัตโนมัติเพื่อให้ยึดตาม SQL (ออเดอร์ล่าสุดอยู่บน)
        });
    });

    function confirmDelete(id, type) {
        if(confirm('ยืนยันการลบข้อมูลนี้หรือไม่?')) {
            window.location.href = 'admin_delete.php?id=' + id + '&type=' + type;
        }
    }
</script>
</body>
</html>