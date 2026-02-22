<?php
session_start();
include "connectdb.php";

// --- [ระบบความปลอดภัย]: เช็คสิทธิ์แอดมินก่อนเข้าใช้งาน ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- [Logic ส่วนที่ 1]: จัดการประเภทสินค้า (Categories) ---
if (isset($_POST['save_category'])) {
    $name = $conn->real_escape_string($_POST['cat_name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    if (!empty($_POST['cat_id'])) {
        $conn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=" . intval($_POST['cat_id']));
    } else {
        $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')");
    }
    header("Location: admin_dashboard.php?tab=categories&success=1"); exit();
}

// --- [Logic ส่วนที่ 2]: จัดการสินค้าหลัก (Products) ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description']);
    
    if (!empty($_POST['product_id'])) {
        $p_id = intval($_POST['product_id']);
        $sql = "UPDATE products SET name='$name', price='$price', category_id='$cat_id', description='$desc' WHERE id=$p_id";
        if ($_FILES['image']['error'] == 0) {
            $img = time() . "_" . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img);
            $conn->query("UPDATE products SET image='$img' WHERE id=$p_id");
        }
        $conn->query($sql);
    } else {
        $img = ($_FILES['image']['error'] == 0) ? time() . "_" . $_FILES['image']['name'] : "default.png";
        if ($_FILES['image']['error'] == 0) move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img);
        $conn->query("INSERT INTO products (name, price, category_id, description, image) VALUES ('$name', '$price', '$cat_id', '$desc', '$img')");
    }
    header("Location: admin_dashboard.php?tab=products&success=1"); exit();
}

// --- [Logic ส่วนที่ 3]: จัดการรุ่นย่อย (Variants) ---
if (isset($_POST['add_variant'])) {
    $p_id = $_POST['product_id'];
    $v_name = $conn->real_escape_string($_POST['v_name']);
    $v_stock = $_POST['v_stock'];
    $v_img = "v_" . time() . "_" . $_FILES['v_image']['name'];
    move_uploaded_file($_FILES['v_image']['tmp_name'], "images/" . $v_img);
    $conn->query("INSERT INTO product_variants (product_id, variant_name, variant_image, stock) VALUES ('$p_id', '$v_name', '$v_img', '$v_stock')");
    header("Location: admin_dashboard.php?tab=products&variant_ok=1"); exit();
}

// --- [Logic ส่วนที่ 4]: จัดการสถานะออเดอร์ ---
if (isset($_POST['update_order_status'])) {
    $oid = $_POST['order_id']; $stat = $_POST['status'];
    $conn->query("UPDATE orders SET status='$stat' WHERE id=$oid");
    header("Location: admin_dashboard.php?tab=orders&status_ok=1"); exit();
}

// --- [Logic ส่วนที่ 5]: ลบข้อมูล (Universal Delete) ---
if (isset($_GET['del_id']) && isset($_GET['type'])) {
    $id = intval($_GET['del_id']); $type = $_GET['type'];
    if ($type == 'product') $conn->query("DELETE FROM products WHERE id=$id");
    if ($type == 'category') $conn->query("DELETE FROM categories WHERE id=$id");
    if ($type == 'user') $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_dashboard.php?tab=".$_GET['tab']."&deleted=1"); exit();
}

// ดึงข้อมูลแสดงผล
$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$categories_list = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC"); // เอาสั่งเข้ามาก่อนไว้ด้านบน
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 25px; padding: 30px; }
        .nav-pills .nav-link { color: #fff; border-radius: 12px; margin: 0 5px; transition: 0.3s; border: 1px solid transparent; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 15px #bb86fc; }
        .text-neon-pink { color: #f107a3; text-shadow: 0 0 10px rgba(241, 7, 163, 0.6); }
        .table { --bs-table-bg: transparent; color: #fff !important; }
        .modal-content { background: #1a0028; border: 1px solid #bb86fc; border-radius: 20px; }
        .form-control, .form-select { background: rgba(255,255,255,0.05); border: 1px solid rgba(187,134,252,0.3); color: #fff; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .slip-preview { width: 45px; height: 45px; object-fit: cover; cursor: pointer; border: 1px solid #00f2fe; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-neon-pink"><i class="bi bi-shield-lock-fill me-2"></i> ADMIN DASHBOARD</h2>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-info rounded-pill px-4">ดูหน้าหน้าร้าน</a>
            <a href="logout.php" class="btn btn-danger rounded-pill px-4">ออกจากระบบ</a>
        </div>
    </div>

    <ul class="nav nav-pills mb-5 justify-content-center" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#products">1. จัดการสินค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#categories">2. จัดการประเภท</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#customers">3. จัดการลูกค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">4. จัดการออเดอร์</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="products">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4 align-items-center">
                    <h4>รายการสินค้า (ค้นหาและลบได้ในตาราง)</h4>
                    <button class="btn btn-primary rounded-pill px-4" onclick="openAddProduct()">+ เพิ่มสินค้าใหม่</button>
                </div>
                <table class="table table-hover datatable-js">
                    <thead><tr class="text-neon-pink"><th>รูป</th><th>ชื่อ</th><th>ประเภท</th><th>ราคา</th><th>สต็อกรวม</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $p['image'] ?>" class="product-img"></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="badge bg-secondary"><?= $p['cat_name'] ?></span></td>
                            <td class="text-info fw-bold">฿<?= number_format($p['price']) ?></td>
                            <td><?php $v=$conn->query("SELECT SUM(stock) as s FROM product_variants WHERE product_id=".$p['id'])->fetch_assoc(); echo $v['s']??0; ?> ชิ้น</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info" title="เพิ่มรุ่นย่อย" onclick="openVariantModal(<?= $p['id'] ?>, '<?= $p['name'] ?>')"><i class="bi bi-layers"></i></button>
                                    <button class="btn btn-sm btn-outline-light" onclick='openEditProduct(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                    <a href="?del_id=<?= $p['id'] ?>&type=product&tab=products" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบสินค้า?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="categories">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4 align-items-center">
                    <h4>ข้อมูลประเภทสินค้า</h4>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openCatModal()">+ เพิ่มประเภทสินค้า</button>
                </div>
                <table class="table table-hover datatable-js">
                    <thead><tr class="text-neon-pink"><th>ID</th><th>ชื่อประเภท</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($c = $categories_list->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td class="fw-bold"><?= $c['name'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-light" onclick='openEditCat(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                                <a href="?del_id=<?= $c['id'] ?>&type=category&tab=categories" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบประเภทสินค้า?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="customers">
            <div class="glass-panel">
                <h4 class="mb-4">ข้อมูลลูกค้าในระบบ</h4>
                <table class="table table-hover datatable-js">
                    <thead><tr class="text-neon-pink"><th>Username</th><th>ชื่อ-นามสกุล</th><th>เบอร์โทร</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><?= $u['username'] ?></td>
                            <td><?= $u['fullname'] ?: '-' ?></td>
                            <td><?= $u['phone'] ?></td>
                            <td>
                                <a href="?del_id=<?= $u['id'] ?>&type=user&tab=customers" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบข้อมูลลูกค้ารายนี้?')"><i class="bi bi-person-x"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="orders">
            <div class="glass-panel">
                <h4 class="mb-4 text-info">รายการสั่งซื้อ (ออเดอร์ใหม่ล่าสุดจะอยู่ด้านบน)</h4>
                <table class="table table-hover datatable-js">
                    <thead><tr class="text-neon-pink"><th>วันที่</th><th>บิล ID</th><th>ชื่อลูกค้า</th><th>ยอดสุทธิ</th><th>สลิป</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($o = $orders->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                            <td class="fw-bold">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= $o['fullname'] ?></td>
                            <td class="text-warning fw-bold">฿<?= number_format($o['total_price']) ?></td>
                            <td>
                                <?php if($o['slip_image']): ?>
                                    <img src="uploads/slips/<?= $o['slip_image'] ?>" class="slip-preview" onclick="window.open(this.src)">
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $o['status']=='paid'?'bg-success':'bg-warning text-dark' ?>"><?= $o['status'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick='viewOrderDetail(<?= json_encode($o) ?>)'><i class="bi bi-receipt"></i> ดูบิล/ปรับสถานะ</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="POST" enctype="multipart/form-data">
        <div class="modal-header border-secondary"><h5 class="modal-title" id="p_title">จัดการสินค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="product_id" id="p_id">
            <div class="mb-3"><label>ชื่อสินค้า</label><input type="text" name="name" id="p_name" class="form-control" required></div>
            <div class="row"><div class="col-6 mb-3"><label>ราคา</label><input type="number" name="price" id="p_price" class="form-control" required></div>
            <div class="col-6 mb-3"><label>ประเภท</label><select name="category_id" id="p_cat" class="form-select"><?php $categories_list->data_seek(0); while($c=$categories_list->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=$c['name']?></option><?php endwhile; ?></select></div></div>
            <div class="mb-3"><label>รายละเอียด</label><textarea name="description" id="p_desc" class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label>รูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_product" class="btn btn-primary px-4">บันทึกข้อมูล</button></div>
    </form></div>
</div>

<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="POST">
        <div class="modal-header border-secondary"><h5 class="modal-title" id="cat_title">เพิ่มประเภทสินค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="cat_id" id="cat_id">
            <div class="mb-3"><label>ชื่อประเภทสินค้า</label><input type="text" name="cat_name" id="cat_name" class="form-control" required></div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_category" class="btn btn-primary">บันทึก</button></div>
    </form></div>
</div>

<div class="modal fade" id="variantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="POST" enctype="multipart/form-data">
        <div class="modal-header border-secondary"><h5 class="modal-title">เพิ่มรุ่นย่อย: <span id="v_pname"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="product_id" id="v_pid">
            <div class="mb-3"><label>ชื่อรุ่น (เช่น ปลาห้อย, โจรมุมตึก)</label><input type="text" name="v_name" class="form-control" required></div>
            <div class="mb-3"><label>จำนวนสต็อก</label><input type="number" name="v_stock" class="form-control" value="10"></div>
            <div class="mb-3"><label>รูปภาพเฉพาะรุ่น</label><input type="file" name="v_image" class="form-control" required></div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="add_variant" class="btn btn-info">บันทึกรุ่นย่อย</button></div>
    </form></div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header border-secondary"><h5 class="modal-title">ข้อมูลการสั่งซื้อบิล #<span id="o_id_text"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-7">
                    <h6 class="text-info">ข้อมูลการจัดส่ง</h6>
                    <p id="o_ship_info" class="small"></p>
                    <hr class="border-secondary">
                    <h6 class="text-info">รายการสินค้าในบิลนี้</h6>
                    <div id="order_items_area"></div>
                </div>
                <div class="col-md-5 border-start border-secondary">
                    <h6 class="text-neon-pink mb-3">ปรับเปลี่ยนสถานะ (ให้ลูกค้าเห็น)</h6>
                    <form method="POST"><input type="hidden" name="order_id" id="o_id_val">
                        <select name="status" id="o_status" class="form-select mb-3">
                            <option value="pending">รอตรวจสอบ</option>
                            <option value="paid">ชำระเงินแล้ว</option>
                            <option value="shipped">จัดส่งสินค้าเรียบร้อย</option>
                            <option value="cancelled">ยกเลิกออเดอร์</option>
                        </select>
                        <button type="submit" name="update_order_status" class="btn btn-success w-100 py-2">ยืนยันการปรับสถานะ</button>
                    </form>
                </div>
            </div>
        </div>
    </div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { 
        $('.datatable-js').DataTable({ "language": { "search": "ค้นหาด่วน:", "lengthMenu": "แสดง _MENU_ รายการ" } }); 
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('tab')) { new bootstrap.Tab(document.querySelector(`button[data-bs-target="#${urlParams.get('tab')}"]`)).show(); }
    });

    function openAddProduct() { $('#p_id').val(''); $('#p_title').text('เพิ่มสินค้าใหม่'); $('#productModal').modal('show'); }
    function openEditProduct(p) { $('#p_id').val(p.id); $('#p_name').val(p.name); $('#p_price').val(p.price); $('#p_cat').val(p.category_id); $('#p_desc').val(p.description); $('#p_title').text('แก้ไขข้อมูลสินค้า'); $('#productModal').modal('show'); }
    function openVariantModal(id, name) { $('#v_pid').val(id); $('#v_pname').text(name); $('#variantModal').modal('show'); }
    function openCatModal() { $('#cat_id').val(''); $('#cat_title').text('เพิ่มประเภทสินค้า'); $('#catModal').modal('show'); }
    function openEditCat(c) { $('#cat_id').val(c.id); $('#cat_name').val(c.name); $('#cat_title').text('แก้ไขประเภทสินค้า'); $('#catModal').modal('show'); }
    
    function viewOrderDetail(o) { 
        $('#o_id_text').text(o.id); $('#o_id_val').val(o.id); $('#o_status').val(o.status);
        $('#o_ship_info').html(`<strong>ชื่อผู้รับ:</strong> ${o.fullname}<br><strong>โทร:</strong> ${o.phone}<br><strong>ที่อยู่:</strong> ${o.address} ${o.province} ${o.zipcode}`);
        $('#orderModal').modal('show');
    }
</script>
</body>
</html>